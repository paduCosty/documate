<?php

namespace App\Services\Dashboard;

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
        
        // Calculate daily operations used today
        $dailyOperationsUsed = $user->files()
            ->whereDate('created_at', today())
            ->count();
        
        $dailyOperationsLimit = $hasSubscription ? null : 3; // 3 for free users, unlimited for pro
        
        $totalStorageBytes = $user->files()->sum('input_size_bytes');
        $storageLimitBytes = $hasSubscription ? 1073741824 : 104857600; // 1GB for Pro, 100MB for Free
        $storageUsedMB = round($totalStorageBytes / 1048576, 1);
        $storageLimitMB = $storageLimitBytes / 1048576;

        return [
            [
                'icon' => 'Zap',
                'label' => "Operations today",
                'value' => $hasSubscription ? "Unlimited" : "{$dailyOperationsUsed} / {$dailyOperationsLimit}",
                'subtext' => $hasSubscription ? "No limits" : ($dailyOperationsLimit - $dailyOperationsUsed) . " remaining",
                'color' => "text-zinc-600",
            ],
            [
                'icon' => 'Files',
                'label' => "Files processed",
                'value' => $user->files()->count(),
                'subtext' => "this month",
                'color' => "text-zinc-600",
            ],
            [
                'icon' => 'HardDrive',
                'label' => "Storage used",
                'value' => $storageUsedMB . " MB",
                'subtext' => "of {$storageLimitMB} MB",
                'color' => "text-zinc-600",
            ],
            [
                'icon' => 'CreditCard',
                'label' => "Current plan",
                'value' => $hasSubscription ? 'Pro' : 'Free',
                'subtext' => $hasSubscription ? "Active" : "Upgrade →",
                'isLink' => !$hasSubscription,
                'color' => "text-zinc-600",
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
                'name'       => is_array($file->original_filenames) ? implode(', ', $file->original_filenames) : $file->original_filenames,
                'tool'       => $file->operation_type ?? 'unknown',
                'size'       => $this->formatFileSize($file->input_size_bytes),
                'date'       => $file->created_at->diffForHumans(),
                'expires'    => $this->getExpirationText($file),
                'isExpired'  => $file->expires_at?->isPast() ?? false,
                'canDownload' => $file->status === 'completed' && !($file->expires_at?->isPast() ?? false) && $file->output_path,
            ]);
    }

    private function getUsage(User $user): array
    {
        // calculezi daily operations, storage etc.
        return [
            'daily_used' => 3,
            'daily_limit' => 10,
            'percentage' => 30,
            'resets_at'  => 'midnight UTC',
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