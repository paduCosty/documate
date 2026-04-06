<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Tools\MergePdfController;
use App\Http\Controllers\Tools\CompressPdfController;
use App\Http\Controllers\Tools\ToolStatusController;
use App\Http\Controllers\UserFileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/contact', function () {
    return Inertia::render('contact/page');
});

Route::get('/faq', function () {
    return Inertia::render('faq/page');
});

Route::get('/', function () {
    return Inertia::render('page');
});

Route::middleware('auth')->group(function () {
    Route::post('/tools/compress-pdf', [CompressPdfController::class, 'process'])->name('tools.compress-pdf.process');
    Route::post('/tools/merge-pdf', [MergePdfController::class, 'process'])
        ->name('tools.merge-pdf.process');
});


Route::middleware('auth')->group(function () {
   // Status page - Inertia render (IMPORTANT)
    Route::get('/status/{uuid}', [ToolStatusController::class, 'show'])
         ->name('tools.status');

    // Polling endpoint - JSON allowed
    Route::get('/status/{uuid}/poll', [ToolStatusController::class, 'status'])
         ->name('tools.status.poll');

    // Download
    Route::get('/tools/download/{uuid}', [ToolStatusController::class, 'download'])
         ->name('tools.download');

    // Merge PDF route
    Route::post('/merge-pdf', [MergePdfController::class, 'process'])
         ->name('tools.merge-pdf.process');
});


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

    Route::get('excel-to-pdf', function () {
        return Inertia::render('tools/excel-to-pdf/page');
    })->name('tools.excel-to-pdf');

    Route::get('ppt-to-pdf', function () {
        return Inertia::render('tools/ppt-to-pdf/page');
    })->name('tools.ppt-to-pdf');

    Route::get('pdf-to-jpg', function () {
        return Inertia::render('tools/pdf-to-jpg/page');
    })->name('tools.pdf-to-jpg');

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
    Route::get('/', function () {
        return Inertia::render('dashboard/page', [
            'user' => auth()->user()
        ]);
    })->name('index');
    Route::get('/', [DashboardController::class, 'index'])->name('index');


    Route::get('/analytics', function () {
        return Inertia::render('dashboard/analytics/page');
    })->name('analytics');

    Route::get('/settings', function () {
        return Inertia::render('dashboard/settings/page');
    })->name('settings');

    Route::get('/reports', function () {
        return Inertia::render('dashboard/reports/page');
    })->name('reports');

    Route::get('/users', function () {
        return Inertia::render('dashboard/users/page');
    })->name('users');

    Route::get('/usage', function () {
        return Inertia::render('dashboard/usage/page');
    })->name('usage');

    Route::get('/files', function () {
        return Inertia::render('dashboard/files/page');
    })->name('files');

    Route::get('/billing', function () {
        return Inertia::render('dashboard/billing/page');
    })->name('billing');
});

Route::get('/pricing', [SubscriptionController::class, 'index'])->name('pricing');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/billing', [SubscriptionController::class, 'billing'])->name('billing');

    Route::post('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])
        ->name('subscription.checkout');

    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel');

    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('billing.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('billing.cancel');

    Route::post('/files/upload', [UserFileController::class, 'upload'])->name('files.upload');
    Route::get('/files/{fileId}/download', [UserFileController::class, 'download'])->name('files.download');
});




Route::get('/checkout/{plan}', [SubscriptionController::class, 'showCheckout'])->name('checkout.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
