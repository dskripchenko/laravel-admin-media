<?php

declare(strict_types=1);

use Dskripchenko\LaravelAdmin\Permission\Middleware\AdminAccess;
use Dskripchenko\LaravelAdminMedia\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

$apiPrefix = (string) config('admin.api.prefix', 'api/admin');
$apiMiddleware = (array) config('admin.middleware.api', ['web']);

Route::prefix($apiPrefix)
    ->middleware($apiMiddleware)
    ->group(function () {
        Route::post('media/upload', [UploadController::class, 'upload'])
            ->middleware(AdminAccess::class.':admin.media.upload')
            ->name('admin.media.upload');
    });
