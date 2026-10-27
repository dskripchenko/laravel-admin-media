<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Feature;

use Dskripchenko\LaravelAdminMedia\Services\MediaService;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class CropVariantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('admin-media.responsive_sets.square', [
            ['name' => 'thumb', 'width' => 100, 'height' => 100, 'crop' => true],
        ]);
    }

    public function test_upload_crops_a_variant_without_a_focal_point(): void
    {
        $media = app(MediaService::class)->upload(
            UploadedFile::fake()->image('photo.jpg', 400, 300),
            null,
            'square',
        );

        $variant = $media->variants()->where('name', 'thumb')->first();
        $this->assertNotNull($variant);
        $this->assertSame(100, $variant->width);
        $this->assertSame(100, $variant->height);
    }

    public function test_regenerating_variants_of_a_media_with_a_null_focal_point(): void
    {
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('photo.jpg', 300, 400));
        $media->forceFill(['focal_x' => null, 'focal_y' => null]);

        $this->assertSame(1, app(MediaService::class)->generateVariants($media, 'square'));
    }

    public function test_an_out_of_range_focal_point_is_clamped(): void
    {
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('photo.jpg', 400, 300));
        $media->forceFill(['focal_x' => 1.7, 'focal_y' => -0.3]);

        $this->assertSame(1, app(MediaService::class)->generateVariants($media, 'square'));
        $variant = $media->variants()->where('name', 'thumb')->first();
        $this->assertSame([100, 100], [$variant?->width, $variant?->height]);
    }

    public function test_variants_work_on_a_disk_without_local_paths(): void
    {
        // A local disk that, like S3, has no path on this machine.
        Storage::extend('remote-like', static function ($app, array $config): FilesystemAdapter {
            $adapter = new LocalFilesystemAdapter($config['root']);

            return new class(new Filesystem($adapter), $adapter, $config) extends FilesystemAdapter
            {
                public function path($path): string
                {
                    throw new \LogicException('No local path on this disk');
                }
            };
        });
        config()->set('filesystems.disks.remote', ['driver' => 'remote-like', 'root' => sys_get_temp_dir().'/media-remote-'.bin2hex(random_bytes(4))]);
        config()->set('admin-media.disk', 'remote');

        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('photo.jpg', 400, 300), null, 'square');

        $variant = $media->variants()->where('name', 'thumb')->firstOrFail();
        $this->assertSame([100, 100], [$variant->width, $variant->height]);
        $this->assertTrue(Storage::disk('remote')->exists($variant->path));
        $this->assertGreaterThan(0, $variant->size);
    }
}
