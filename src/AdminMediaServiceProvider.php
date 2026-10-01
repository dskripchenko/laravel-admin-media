<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia;

use Dskripchenko\LaravelAdmin\Plugin\Concerns\RegistersAdminPlugin;
use Dskripchenko\LaravelAdminMedia\Services\ImageProcessor;
use Dskripchenko\LaravelAdminMedia\Services\MediaService;
use Illuminate\Support\ServiceProvider;

final class AdminMediaServiceProvider extends ServiceProvider
{
    use RegistersAdminPlugin;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-media.php', 'admin-media');

        $this->app->singleton(ImageProcessor::class);
        $this->app->singleton(MediaService::class);

        // Strings are keyed by their Russian source text; a host may override
        // them with its own lang/{locale}.json. Registered in register() so the
        // path is known before the core boots plugins (which already translate
        // permission labels).
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        $this->registerAdminPlugin(AdminMediaPlugin::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/admin-media.php' => config_path('admin-media.php'),
        ], 'admin-media-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/admin-media.php');
    }
}
