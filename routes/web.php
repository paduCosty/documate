<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/contact', function () {
    return Inertia::render('contact/page');
});
Route::get('/pricing', function () {
    return Inertia::render('pricing/page');
});

Route::get('/faq', function () {
    return Inertia::render('faq/page');
});

Route::get('/', function () {
    return Inertia::render('page');
});

Route::get('tools/compress-pdf', function () {
    return Inertia::render('tools/compress-pdf/page');
});
Route::get('tools/merge-pdf', function () {
    return Inertia::render('tools/merge-pdf/page');
});
Route::get('tools/word-to-pdf', function () {
    return Inertia::render('tools/word-to-pdf/page');
});
Route::get('tools/excel-to-pdf', function () {
    return Inertia::render('tools/excel-to-pdf/page');
});
Route::get('tools/ppt-to-pdf', function () {
    return Inertia::render('tools/ppt-to-pdf/page');
});
Route::get('tools/compress-pdf', function () {
    return Inertia::render('tools/compress-pdf/page');
});
Route::get('tools/pdf-to-jpg', function () {
    return Inertia::render('tools/pdf-to-jpg/page');
});
Route::get('tools/split-pdf', function () {
    return Inertia::render('tools/split-pdf/page');
});


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {



    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
