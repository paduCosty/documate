<?php

namespace App\Http\Controllers\Extraction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extraction\StoreExtractionTemplateRequest;
use App\Models\ExtractionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CRUD for user-owned custom extraction templates.
 *
 * System templates (is_system = true, user_id = null) are read-only — they
 * can be listed but not modified or deleted through this controller.
 */
class ExtractionTemplateController extends Controller
{
    // ─── List ──────────────────────────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $userId = $request->user()->id;

        $templates = ExtractionTemplate::active()
            ->visibleTo($userId)
            ->orderByRaw('user_id IS NULL DESC')
            ->orderBy('name')
            ->get([
                'id', 'slug', 'name', 'description',
                'is_system', 'user_id', 'created_at',
            ]);

        return Inertia::render('extraction/templates/page', [
            'templates' => $templates,
        ]);
    }

    // ─── Create ────────────────────────────────────────────────────────────

    public function store(StoreExtractionTemplateRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $slug   = $this->uniqueSlug($request->input('name'), $userId);

        $template = ExtractionTemplate::create([
            'user_id'         => $userId,
            'slug'            => $slug,
            'name'            => $request->input('name'),
            'description'     => $request->input('description'),
            'prompt_template' => $request->input('prompt_template'),
            'output_schema'   => $request->input('output_schema'),
            'is_system'       => false,
            'active'          => true,
        ]);

        return response()->json($template, 201);
    }

    // ─── Update ────────────────────────────────────────────────────────────

    public function update(StoreExtractionTemplateRequest $request, ExtractionTemplate $template): JsonResponse
    {
        $this->authorizeOwnership($request, $template);

        $template->update([
            'name'            => $request->input('name'),
            'description'     => $request->input('description'),
            'prompt_template' => $request->input('prompt_template'),
            'output_schema'   => $request->input('output_schema'),
        ]);

        return response()->json($template->fresh());
    }

    // ─── Delete ────────────────────────────────────────────────────────────

    public function destroy(Request $request, ExtractionTemplate $template): JsonResponse
    {
        $this->authorizeOwnership($request, $template);

        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * Ensures the authenticated user owns this template.
     * System templates (user_id = null) cannot be modified.
     */
    private function authorizeOwnership(Request $request, ExtractionTemplate $template): void
    {
        if ($template->is_system || $template->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this template.');
        }
    }

    /**
     * Generates a unique slug for a user-scoped template.
     * Scope is per-user so different users can have templates with the same slug.
     */
    private function uniqueSlug(string $name, int $userId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (
            ExtractionTemplate::where('slug', $slug)
                ->where('user_id', $userId)
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
