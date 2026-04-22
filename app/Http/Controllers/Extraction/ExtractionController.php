<?php

namespace App\Http\Controllers\Extraction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extraction\ExtractionUploadRequest;
use App\Jobs\ProcessExtractionJob;
use App\Models\ExtractionJob;
use App\Models\ExtractionTemplate;
use App\Services\Ai\AiProviderFactory;
use App\Services\Guest\GuestService;
use App\Services\Output\OutputFormatterFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Handles the full lifecycle of a PDF extraction job:
 *   page()     — renders the upload form (Inertia)
 *   process()  — validates upload → stores temp file → dispatches job → redirects
 *   status()   — renders the status/result page (Inertia)
 *   poll()     — JSON polling endpoint (called every 2s by the frontend)
 *   download() — streams the output file to the browser
 */
class ExtractionController extends Controller
{
    public function __construct(private readonly GuestService $guests) {}

    // ─── Page ─────────────────────────────────────────────────────────────

    public function page(Request $request): InertiaResponse
    {
        $userId = $request->user()?->id;

        $templates = ExtractionTemplate::active()
            ->visibleTo($userId)
            ->orderByRaw('user_id IS NULL DESC')  // system templates first
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'description', 'is_system'])
            ->toArray();

        $providers = array_map(
            fn (string $slug) => [
                'slug'    => $slug,
                'name'    => ucfirst($slug),
                'enabled' => (bool) config("ai.providers.{$slug}.enabled"),
            ],
            AiProviderFactory::availableProviders(),
        );

        $formats = array_map(
            fn (string $fmt) => [
                'value' => $fmt,
                'label' => strtoupper($fmt),
                'mime'  => OutputFormatterFactory::make($fmt)->getMimeType(),
            ],
            OutputFormatterFactory::availableFormats(),
        );

        return Inertia::render('tools/extract-pdf/page', [
            'templates'     => $templates,
            'providers'     => $providers,
            'formats'       => $formats,
            'defaultFormat' => OutputFormatterFactory::defaultFormat(),
        ]);
    }

    // ─── Process ──────────────────────────────────────────────────────────

    public function process(ExtractionUploadRequest $request)
    {
        // ExtractionUploadRequest has already run its base validation rules before
        // we reach here. We add the plan-specific file size check manually.
        $ctx    = $this->guests->context($request);
        $maxMb  = $ctx->limits['max_file_size_mb'];
        $maxBytes = $maxMb * 1024 * 1024;

        if ($request->file('file')?->getSize() > $maxBytes) {
            return back()->withErrors([
                'file' => "The PDF exceeds the {$maxMb}MB limit for your plan.",
            ]);
        }

        if ($ctx->hasReachedLimit()) {
            return back()->withErrors([
                'file' => 'You have reached your daily extraction limit. Upgrade to Pro for unlimited extractions.',
            ]);
        }

        $uploaded = $request->file('file');
        $slug     = $request->input('template');
        $format   = $request->outputFormat();
        $provider = $request->providerOverride();

        // Verify template exists before creating any DB records.
        $template = ExtractionTemplate::findBySlug($slug, $request->user()?->id);
        abort_if($template === null, 422, "Template \"{$slug}\" not found.");

        // Store the uploaded PDF in a temp location under storage/app/temp/.
        $batchId  = (string) Str::uuid();
        $tempPath = $ctx->storagePath('extraction', $batchId);
        $uploaded->storeAs($tempPath, $uploaded->getClientOriginalName(), 'local');
        $tempAbsPath = Storage::disk('local')->path($tempPath . '/' . $uploaded->getClientOriginalName());

        // Record usage before dispatch so it's committed even if the job fails.
        $ctx->recordUsage($uploaded->getSize(), 1);

        // Create the tracking record.
        $extractionJob = ExtractionJob::create([
            'uuid'              => $batchId,
            'user_id'           => $ctx->userId,
            'guest_id'          => $ctx->guestId,
            'template_id'       => $template->id,
            'original_filename' => $uploaded->getClientOriginalName(),
            'file_size_bytes'   => $uploaded->getSize(),
            'status'            => 'pending',
            'output_format'     => $format,
        ]);

        ProcessExtractionJob::dispatch(
            $extractionJob,
            $tempAbsPath,
            $slug,
            $format,
            $provider,
        );

        return redirect()->route('extraction.status', $extractionJob->uuid);
    }

    // ─── Status page ──────────────────────────────────────────────────────

    public function status(Request $request, string $uuid): InertiaResponse
    {
        $job = $this->findOwned($request, $uuid);

        return Inertia::render('tools/extraction-status/page', [
            'jobUuid'       => $uuid,
            'initialStatus' => $this->jobToArray($job),
        ]);
    }

    // ─── Poll (JSON) ──────────────────────────────────────────────────────

    public function poll(Request $request, string $uuid): JsonResponse
    {
        $job = $this->findOwned($request, $uuid);

        return response()->json($this->jobToArray($job));
    }

    // ─── Download ─────────────────────────────────────────────────────────

    public function download(Request $request, string $uuid)
    {
        $job = $this->findOwned($request, $uuid);

        abort_if($job->isExpired(), 410, 'This file has expired and is no longer available.');
        abort_if(! $job->isCompleted() || ! $job->output_path, 400, 'File is not ready for download.');
        abort_if(! file_exists($job->output_path), 404, 'Output file not found on storage.');

        return response()->download(
            $job->output_path,
            basename($job->output_path),
            ['Content-Type' => OutputFormatterFactory::make($job->output_format)->getMimeType()],
        );
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function findOwned(Request $request, string $uuid): ExtractionJob
    {
        $query = ExtractionJob::where('uuid', $uuid);

        if ($user = $request->user()) {
            $query->where('user_id', $user->id);
        } else {
            $guestId = $this->guests->getGuestId($request);
            abort_if(! $guestId, 403, 'Access denied.');
            $query->where('guest_id', $guestId);
        }

        return $query->firstOrFail();
    }

    private function jobToArray(ExtractionJob $job): array
    {
        return [
            'uuid'               => $job->uuid,
            'status'             => $job->status,
            'original_filename'  => $job->original_filename,
            'output_format'      => $job->output_format,
            'page_count'         => $job->page_count,
            'tokens_used'        => $job->tokens_used,
            'processing_time_ms' => $job->processing_time_ms,
            'error_message'      => $job->error_message,
            'extracted_data'     => $job->extracted_data,
            'is_expired'         => $job->isExpired(),
            'can_download'       => $job->isCompleted() && ! $job->isExpired() && $job->output_path,
            'expires_at'         => $job->expires_at?->toIso8601String(),
            'processed_at'       => $job->processed_at?->toIso8601String(),
        ];
    }
}
