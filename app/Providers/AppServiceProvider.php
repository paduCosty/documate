<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Queue::failing(function (JobFailed $event) {
            $adminEmail = env('ADMIN_EMAIL');
            if (! $adminEmail) return;

            $jobName  = class_basename($event->job->resolveName());
            $error    = $event->exception->getMessage();
            $queue    = $event->job->getQueue();

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
