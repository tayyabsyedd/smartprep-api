<?php

use App\Http\Controllers\Admin\SlipController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/slips/{purchase}', [SlipController::class, 'show'])
    ->middleware(['auth', EnsureUserIsAdmin::class])
    ->name('admin.slips.show');
