<?php

namespace App\Providers;

use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiProviderOrchestrator;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Extraction\ExtractionService;
use App\Services\Extraction\JsonExtractor;
use App\Services\Extraction\JsonValidator;
use App\Services\Extraction\PromptBuilder;
use App\Services\Extraction\ResultNormalizer;
use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\PdfProcessorFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PdfProcessorInterface::class,
            fn () => PdfProcessorFactory::fromConfig()
        );

        $this->app->bind(
            AiProviderInterface::class,
            fn () => new AiProviderOrchestrator(
                primary:     AiProviderFactory::fromConfig(),
                fallback:    AiProviderFactory::fallback(),
                maxAttempts: config('ai.extraction.json_retries', 3),
            )
        );

        // ExtractionService is bound so its constructor dependencies are
        // resolved automatically from the container.
        $this->app->bind(ExtractionService::class, function ($app) {
            return new ExtractionService(
                pdfProcessor:  $app->make(PdfProcessorInterface::class),
                aiProvider:    $app->make(AiProviderInterface::class),
                promptBuilder: new PromptBuilder(),
                jsonValidator: new JsonValidator(new JsonExtractor()),
                normalizer:    new ResultNormalizer(),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Queue::failing(function (JobFailed $event) {
            $adminEmail = env('ADMIN_EMAIL');
            if (! $adminEmail) return;

            $jobName = class_basename($event->job->resolveName());
            $error   = $event->exception->getMessage();
            $queue   = $event->job->getQueue();

            Mail::raw(
                "Job failed on queue [{$queue}]\n\nJob: {$jobName}\n\nError:\n{$error}",
                function ($message) use ($adminEmail, $jobName) {
                    $message->to($adminEmail)
                            ->subject("[Documate] Job failed: {$jobName}");
                }
            );
        });
    }
}
