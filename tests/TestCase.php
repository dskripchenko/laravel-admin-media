<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests;

use Dskripchenko\LaravelAdmin\Testing\PackageTestCase;
use Dskripchenko\LaravelAdminMedia\AdminMediaServiceProvider;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends PackageTestCase
{
    protected function additionalProviders(): array
    {
        return [AdminMediaServiceProvider::class];
    }

    protected function defineAdditionalEnvironment($app): void
    {
        $app['config']->set('filesystems.disks.media-test', [
            'driver' => 'local',
            'root' => sys_get_temp_dir().'/admin-media-test',
        ]);
        $app['config']->set('admin-media.disk', 'media-test');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media-test');
    }
}
