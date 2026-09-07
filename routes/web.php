<?php

use Illuminate\Support\Facades\Route;
use SalsabilEnnaiem\PvModule\Http\Controllers\PvController;
use SalsabilEnnaiem\PvModule\Http\Controllers\SignatureController;
use SalsabilEnnaiem\PvModule\Http\Controllers\TemplateController;

Route::get('/', [PvController::class, 'index'])->name('index');

Route::prefix('notifications')->name('notifications.')->controller(PvController::class)->group(function () {
    Route::get('/', 'notifications')->name('index');
    Route::post('/read-all', 'readAllNotifications')->name('read-all');
    Route::post('/{notification}', 'readNotification')->name('read');
});

Route::get('/create', [PvController::class, 'create'])->name('create');
Route::post('/', [PvController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('store');
Route::post('/store-and-send', [PvController::class, 'storeAndSend'])
    ->middleware('throttle:30,1')
    ->name('store-and-send');

Route::prefix('signature')->name('signature.')->controller(SignatureController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/image', 'image')->name('image');
    Route::post('/upload', 'upload')->name('upload');
    Route::post('/save', 'save')->name('save');
    Route::delete('/', 'delete')->name('delete');
    Route::get('/info', 'info')->name('info');
});

Route::prefix('templates')->name('templates.')->controller(TemplateController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'create')->name('create');
    Route::post('/reset', 'reset')->name('reset');
    Route::get('/{template}/edit', 'edit')->name('edit');
    Route::put('/{template}', 'update')->name('update');
    Route::delete('/{template}', 'destroy')->name('destroy');
});

Route::post('/{pv}/send', [PvController::class, 'send'])->name('send');
Route::post('/{pv}/validate', [PvController::class, 'validate'])->name('validate');
Route::post('/{pv}/sign', [PvController::class, 'sign'])->name('sign');
Route::post('/{pv}/reject', [PvController::class, 'reject'])->name('reject');

Route::get('/{pv}', [PvController::class, 'show'])->name('show');
Route::get('/{pv}/edit', [PvController::class, 'edit'])->name('edit');
Route::get('/{pv}/preview', [PvController::class, 'preview'])->name('preview');
Route::get('/{pv}/pdf', [PvController::class, 'pdf'])->name('pdf');
Route::get('/{pv}/versions', [PvController::class, 'versions'])->name('versions');
Route::put('/{pv}', [PvController::class, 'update'])->name('update');
Route::delete('/{pv}', [PvController::class, 'destroy'])->name('destroy');