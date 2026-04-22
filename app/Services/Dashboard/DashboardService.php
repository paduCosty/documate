<?php

namespace App\Services\Dashboard;

use App\Models\ExtractionJob;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get all data needed for the dashboard.
     */
    public function getDashboardData(User $user): array
    {
        return [
            'stats' => $this->getStats($user),
            'recentFiles' => $this->getRecentFiles($user),
            'usage' => $this->getUsage($user),
        ];
    }

    private function getStats(User $user): array
    {
        $hasSubscription = $user->subscribed('default') && !$user->subscription('default')?->canceled();

        $totalStorageBytes = $user->files()->sum('input_size_bytes');
        $storageLimitBytes = $hasSubscription ? 1073741824 : 104857600; // 1 GB Pro, 100 MB Free
        $storageUsedMB    = round($totalStorageBytes / 1048576, 1);
        $storageLimitMB   = $storageLimitBytes / 1048576;

        if ($hasSubscription) {
            $opsValue   = 'Unlimited';
            $opsSubtext = 'No limits';
            $planValue  = 'Pro';
            $planSub    = 'Active';
            $planLink   = false;
        } else {
            // Free ops are tracked in DailyUsage, NOT by counting all files.
            // Credit-paid ops do NOT increment DailyUsage, so this stays ≤ 3.
            $freeOpsUsed  = $user->todayUsage()->operations_count ?? 0;
            $freeOpsLimit = 3;
            $freeRemain   = max(0, $freeOpsLimit - $freeOpsUsed);
            $opsValue     = "{$freeOpsUsed} / {$freeOpsLimit}";
            $opsSubtext   = $freeRemain > 0 ? "{$freeRemain} free remaining" : 'Using credits';

            $hasCredits = $user->hasCredits();
            $planValue  = $hasCredits ? 'Credits' : 'Free';
            $planSub    = $hasCredits ? $user->creditBalance() . ' credits left' : 'Upgrade →';
            $planLink   = !$hasCredits;
        }

        return [
            [
                'icon'    => 'Zap',
                'label'   => 'Operations today',
                'value'   => $opsValue,
                'subtext' => $opsSubtext,
                'color'   => 'text-zinc-600',
            ],
            [
                'icon'    => 'Files',
                'label'   => 'Files processed',
                'value'   => $user->files()->count(),
                'subtext' => 'this month',
                'color'   => 'text-zinc-600',
            ],
            [
                'icon'    => 'Zap',
                'label'   => 'AI Extractions',
                'value'   => ExtractionJob::where('user_id', $user->id)
                                 ->where('status', 'completed')
                                 ->where('created_at', '>=', now()->startOfMonth())
                                 ->count(),
                'subtext' => 'this month',
                'color'   => 'text-zinc-600',
            ],
            [
                'icon'    => 'HardDrive',
                'label'   => 'Storage used',
                'value'   => $storageUsedMB . ' MB',
                'subtext' => "of {$storageLimitMB} MB",
                'color'   => 'text-zinc-600',
            ],
            [
                'icon'    => 'CreditCard',
                'label'   => 'Current plan',
                'value'   => $planValue,
                'subtext' => $planSub,
                'isLink'  => $planLink,
                'color'   => 'text-zinc-600',
            ],
        ];
    }

    private function getRecentFiles(User $user): Collection|array
    {
        return $user->files()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($file) => [
                'id'         => $file->id,
                'uuid'       => $file->uuid,
                'name'       => $file->output_path ? basename($file->output_path) : (is_array($file->original_filenames) ? implode(', ', $file->original_filenames) : $file->original_filenames),
                'tool'       => $file->operation_type ?? 'unknown',
                'size'       => $this->formatFileSize($file->input_size_bytes),
                'date'       => $file->created_at->diffForHumans(),
                'expires'    => $this->getExpirationText($file),
                'isExpired'  => $file->expires_at?->isPast() ?? false,
                'canDownload'     => $file->status === 'completed' && !($file->expires_at?->isPast() ?? false) && $file->output_path,
                'awaitingPayment' => $file->status === 'awaiting_payment',
            ]);
    }

    private function getUsage(User $user): array
    {
        $todayUsage  = $user->todayUsage();
        $limits      = $user->currentPlanLimits();
        $opsLimit    = $limits["operations_per_day"];
        $dailyUsed   = $todayUsage->operations_count ?? 0;
        $percentage  = $opsLimit > 0 ? min(round($dailyUsed / $opsLimit * 100), 100) : 0;

        return [
            "daily_used"  => $dailyUsed,
            "daily_limit" => $opsLimit >= 999999 ? "∞" : $opsLimit,
            "percentage"  => $percentage,
            "resets_at"   => "midnight UTC",
        ];
    }

    private function getToolColor(string $toolName = null): string
    {
        return match ($toolName) {
            'merge-pdf' => 'bg-blue-500/20 text-blue-400',
            'compress-pdf' => 'bg-green-500/20 text-green-400',
            'split-pdf' => 'bg-orange-500/20 text-orange-400',
            'word-to-pdf' => 'bg-red-500/20 text-red-400',
            'excel-to-pdf' => 'bg-yellow-500/20 text-yellow-400',
            'ppt-to-pdf' => 'bg-orange-500/20 text-orange-400',
            'pdf-to-jpg' => 'bg-pink-500/20 text-pink-400',
            default => 'text-zinc-500',
        };
    }

    private function formatFileSize(int $bytes = 0): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    private function getExpirationText($file): string
    {
        if (!$file->expires_at) return 'Never';

        if ($file->expires_at->isPast()) return 'Expired';

        return $file->expires_at->diffForHumans();
    }
}
