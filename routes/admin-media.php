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
        // Four segments after `api/`: laravel-api's generic
        // `api/{version}/{controller}/{action}` route is registered first and
        // swallows any three-segment path — `api/admin/media/upload` answered
        // 404 "The requested method was not found" in a real host.
        Route::post('media/library/upload', [UploadController::class, 'upload'])
            ->middleware(AdminAccess::class.':admin.media.upload')
            ->name('admin.media.upload');
        // The original path, kept for hosts where it is reachable.
        Route::post('media/upload', [UploadController::class, 'upload'])
            ->middleware(AdminAccess::class.':admin.media.upload')
            ->name('admin.media.upload.legacy');
    });
