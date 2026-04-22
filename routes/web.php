<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Tools\MergePdfController;
use App\Http\Controllers\Tools\CompressPdfController;
use App\Http\Controllers\Tools\SplitPdfController;
use App\Http\Controllers\Tools\OfficeToPdfController;
use App\Http\Controllers\Tools\PdfToJpgController;
use App\Http\Controllers\Tools\ToolStatusController;
use App\Http\Controllers\UserFileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Credits\CreditController;
use App\Http\Controllers\Extraction\ExtractionController;
use App\Http\Controllers\Extraction\ExtractionTemplateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;



Route::get('/sitemap.xml', function () {
    $base = rtrim(config('app.url'), '/');
    $now  = now()->toAtomString();

    $urls = [
        ['loc' => $base,                        'priority' => '1.00', 'freq' => 'weekly'],
        ['loc' => "$base/tools",                'priority' => '0.90', 'freq' => 'weekly'],
        ['loc' => "$base/tools/merge-pdf",      'priority' => '0.90', 'freq' => 'monthly'],
        ['loc' => "$base/tools/compress-pdf",   'priority' => '0.90', 'freq' => 'monthly'],
        ['loc' => "$base/tools/split-pdf",      'priority' => '0.90', 'freq' => 'monthly'],
        ['loc' => "$base/tools/word-to-pdf",    'priority' => '0.85', 'freq' => 'monthly'],
        ['loc' => "$base/tools/excel-to-pdf",   'priority' => '0.85', 'freq' => 'monthly'],
        ['loc' => "$base/tools/ppt-to-pdf",     'priority' => '0.85', 'freq' => 'monthly'],
        ['loc' => "$base/tools/pdf-to-jpg",     'priority' => '0.85', 'freq' => 'monthly'],
        ['loc' => "$base/tools/extract-pdf",    'priority' => '0.85', 'freq' => 'monthly'],
        ['loc' => "$base/pricing",              'priority' => '0.70', 'freq' => 'monthly'],
        ['loc' => "$base/about",                'priority' => '0.50', 'freq' => 'yearly'],
        ['loc' => "$base/faq",                  'priority' => '0.60', 'freq' => 'monthly'],
        ['loc' => "$base/contact",              'priority' => '0.40', 'freq' => 'yearly'],
        ['loc' => "$base/legal/terms",          'priority' => '0.20', 'freq' => 'yearly'],
        ['loc' => "$base/legal/privacy",        'priority' => '0.20', 'freq' => 'yearly'],
        ['loc' => "$base/legal/cookies",        'priority' => '0.20', 'freq' => 'yearly'],
        ['loc' => "$base/legal/refund",         'priority' => '0.20', 'freq' => 'yearly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url['loc']}</loc>\n";
        $xml .= "    <lastmod>{$now}</lastmod>\n";
        $xml .= "    <changefreq>{$url['freq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';

    return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/contact', function () {
    return Inertia::render('contact/page');
})->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

Route::get('/faq', function () {
    return Inertia::render('faq/page');
});

Route::get('/', function () {
    return Inertia::render('page');
});

Route::get('/about', function () {
    return Inertia::render('about/page');
})->name('about');

// Tool process routes — open to guests and authenticated users
Route::post('/tools/split-pdf', [SplitPdfController::class, 'process'])->name('tools.split-pdf.process');
Route::post('/tools/compress-pdf', [CompressPdfController::class, 'process'])->name('tools.compress-pdf.process');
Route::post('/tools/merge-pdf', [MergePdfController::class, 'process'])->name('tools.merge-pdf.process');


// Status + download — open to guests and authenticated users (controller checks ownership)
Route::get('/status/{uuid}', [ToolStatusController::class, 'show'])->name('tools.status');
Route::get('/status/{uuid}/poll', [ToolStatusController::class, 'status'])->name('tools.status.poll');
Route::get('/tools/download/{uuid}', [ToolStatusController::class, 'download'])->name('tools.download');


Route::prefix('tools')->group(function () {
    Route::get('/', function () {
        return Inertia::render('tools/page');
    })->name('tools.index');

    Route::get('compress-pdf', function () {
        return Inertia::render('tools/compress-pdf/page');
    })->name('tools.compress-pdf');

    Route::get('merge-pdf', function () {
        return Inertia::render('tools/merge-pdf/page');
    })->name('tools.merge-pdf');

    Route::get('word-to-pdf', function () {
        return Inertia::render('tools/word-to-pdf/page');
    })->name('tools.word-to-pdf');
    Route::post('word-to-pdf', [OfficeToPdfController::class, 'process'])->defaults('conversionType', 'word-to-pdf')->name('tools.word-to-pdf.process');
    Route::post('excel-to-pdf', [OfficeToPdfController::class, 'process'])->defaults('conversionType', 'excel-to-pdf')->name('tools.excel-to-pdf.process');
    Route::post('ppt-to-pdf', [OfficeToPdfController::class, 'process'])->defaults('conversionType', 'ppt-to-pdf')->name('tools.ppt-to-pdf.process');

    Route::get('excel-to-pdf', function () {
        return Inertia::render('tools/excel-to-pdf/page');
    })->name('tools.excel-to-pdf');

    Route::get('ppt-to-pdf', function () {
        return Inertia::render('tools/ppt-to-pdf/page');
    })->name('tools.ppt-to-pdf');

    Route::get('pdf-to-jpg', function () {
        return Inertia::render('tools/pdf-to-jpg/page');
    })->name('tools.pdf-to-jpg');
    Route::post('pdf-to-jpg', [PdfToJpgController::class, 'process'])->name('tools.pdf-to-jpg.process');

    Route::get('split-pdf', function () {
        return Inertia::render('tools/split-pdf/page');
    })->name('tools.split-pdf');
});

Route::prefix('legal')->group(function () {
    Route::get('terms', function () {
        return Inertia::render('legal/terms/page');
    });

    Route::get('privacy', function () {
        return Inertia::render('legal/privacy/page');
    });

    Route::get('cookies', function () {
        return Inertia::render('legal/cookies/page');
    });

    Route::get('refund', function () {
        return Inertia::render('legal/refund/page');
    });
});


Route::prefix('dashboard')->middleware(['auth', 'verified'])->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');


    Route::get('/settings', function (\Illuminate\Http\Request $req) {
        $user = $req->user();
        return Inertia::render('dashboard/settings/page', [
            'status'       => session('status'),
            'isSocialUser' => (bool) $user->social_provider,
            'socialProvider' => $user->social_provider,
            'notificationSettings' => $user->notification_settings ?? [
                'email'    => true,
                'weekly'   => true,
                'product'  => false,
                'security' => true,
            ],
        ]);
    })->name('settings');

    Route::get('/usage', function (\Illuminate\Http\Request $req) {
        $user   = $req->user();
        $limits = $user->currentPlanLimits();

        $allTools = [
            'merge_pdf'    => ['label' => 'Merge PDF',    'color' => 'bg-blue-500'],
            'compress_pdf' => ['label' => 'Compress PDF', 'color' => 'bg-green-500'],
            'split_pdf'    => ['label' => 'Split PDF',    'color' => 'bg-orange-500'],
            'word-to-pdf'  => ['label' => 'Word to PDF',  'color' => 'bg-red-500'],
            'excel-to-pdf' => ['label' => 'Excel to PDF', 'color' => 'bg-yellow-500'],
            'ppt-to-pdf'   => ['label' => 'PPT to PDF',   'color' => 'bg-purple-500'],
            'pdf-to-jpg'   => ['label' => 'PDF to JPG',   'color' => 'bg-pink-500'],
        ];

        $ranges = [
            'this_month'    => [now()->startOfMonth(),           now()],
            'last_month'    => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_3_months' => [now()->subMonths(3)->startOfMonth(), now()],
        ];

        $periods = [];
        foreach ($ranges as $key => [$from, $to]) {
            $q          = $user->files()->whereBetween('created_at', [$from, $to]);
            $toolCounts = (clone $q)->selectRaw('operation_type, COUNT(*) as cnt')
                ->groupBy('operation_type')->pluck('cnt', 'operation_type')->toArray();

            $tools = [];
            foreach ($allTools as $type => $info) {
                $tools[] = ['type' => $type, 'label' => $info['label'], 'color' => $info['color'], 'count' => $toolCounts[$type] ?? 0];
            }

            $periods[$key] = [
                'totalFiles'  => (clone $q)->count(),
                'totalBytes'  => (clone $q)->sum('input_size_bytes'),
                'tools'       => $tools,
            ];
        }

        $todayUsage  = $user->todayUsage();
        $isPro       = $user->subscribed('default') && !$user->subscription('default')?->canceled();

        return Inertia::render('dashboard/usage/page', [
            'periods'      => $periods,
            'limits'       => $limits,
            'todayOps'     => $todayUsage->operations_count ?? 0,
            'storageBytes' => $user->files()->sum('input_size_bytes'),
            'isPro'        => $isPro,
        ]);
    })->name('usage');

    Route::get('/files', function (\Illuminate\Http\Request $request) {
        $user  = $request->user();
        $files = $user->files()
            ->latest()
            ->paginate(20)
            ->through(fn ($file) => [
                'id'         => $file->id,
                'uuid'       => $file->uuid,
                'name'       => $file->output_path
                                    ? basename($file->output_path)
                                    : (is_array($file->original_filenames) ? implode(', ', $file->original_filenames) : ($file->original_filenames ?? 'Unknown')),
                'tool'       => $file->operation_type,
                'status'     => $file->status,
                'inputSize'  => $file->input_size_bytes,
                'outputSize' => $file->output_size_bytes,
                'date'       => $file->created_at->format('M d, Y'),
                'expires'    => $file->expires_at
                                    ? ($file->expires_at->isPast() ? 'Expired' : $file->expires_at->diffForHumans())
                                    : 'Never',
                'isExpired'  => $file->expires_at?->isPast() ?? false,
                'canDownload' => $file->status === 'completed'
                                    && !($file->expires_at?->isPast() ?? false)
                                    && $file->output_path,
            ]);
        return Inertia::render('dashboard/files/page', ['files' => $files]);
    })->name('files');

});

Route::get('/pricing', [SubscriptionController::class, 'index'])->name('pricing');

// Checkout + success open to guests (controller handles both paths)
Route::post('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('billing.success');
Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('billing.cancel');

// Auth-only billing routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/billing', [SubscriptionController::class, 'billing'])->name('billing');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel');
    Route::post('/files/upload', [UserFileController::class, 'upload'])->name('files.upload');
    Route::get('/files/{fileId}/download', [UserFileController::class, 'download'])->name('files.download');
});




Route::get('/checkout/{plan}', [SubscriptionController::class, 'showCheckout'])->name('checkout.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Credit packs (one-time purchase) ─────────────────────────────────────
Route::post('/credits/checkout/{pack}', [CreditController::class, 'checkout'])->name('credits.checkout');
Route::get('/credits/success', [CreditController::class, 'success'])->name('credits.success');

// ── Extract PDF ───────────────────────────────────────────────────────────────
// Page + upload: open to guests and authenticated users (same as other tools).
// Template CRUD: auth-only (templates belong to users).
// Status/poll/download: open — controller checks ownership via user_id or guest_id.
Route::get('/tools/extract-pdf', [ExtractionController::class, 'page'])
    ->name('tools.extract-pdf');

Route::post('/tools/extract-pdf', [ExtractionController::class, 'process'])
    ->name('tools.extract-pdf.process');

Route::prefix('extraction')->name('extraction.')->group(function () {

    // Custom template management — auth required
    Route::middleware('auth')->group(function () {
        Route::get('templates',               [ExtractionTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates',              [ExtractionTemplateController::class, 'store'])->name('templates.store');
        Route::put('templates/{template}',    [ExtractionTemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{template}', [ExtractionTemplateController::class, 'destroy'])->name('templates.destroy');
    });

    // Job lifecycle — open to guests and authenticated users
    Route::get('{uuid}',          [ExtractionController::class, 'status'])->name('status');
    Route::get('{uuid}/poll',     [ExtractionController::class, 'poll'])->name('poll');
    Route::get('{uuid}/download', [ExtractionController::class, 'download'])->name('download');
});

require __DIR__ . '/auth.php';
