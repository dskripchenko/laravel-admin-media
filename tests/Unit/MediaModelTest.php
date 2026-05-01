<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Unit;

use Dskripchenko\LaravelAdminMedia\Models\Media;
use Dskripchenko\LaravelAdminMedia\Models\MediaVariant;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;

final class MediaModelTest extends TestCase
{
    public function test_kind_classifies_by_mime_prefix(): void
    {
        $img = new Media(['mime' => 'image/jpeg']);
        $this->assertSame('image', $img->kind);

        $video = new Media(['mime' => 'video/mp4']);
        $this->assertSame('video', $video->kind);

        $audio = new Media(['mime' => 'audio/mpeg']);
        $this->assertSame('audio', $audio->kind);

        $pdf = new Media(['mime' => 'application/pdf']);
        $this->assertSame('document', $pdf->kind);

        $other = new Media(['mime' => 'application/zip']);
        $this->assertSame('other', $other->kind);
    }

    public function test_url_uses_disk(): void
    {
        // Сохраняем чтобы Storage::disk нормально работал
        $media = Media::create([
            'disk' => 'media-test',
            'path' => 'media/2026/01/test.jpg',
            'mime' => 'image/jpeg',
            'size' => 1000,
        ]);

        $this->assertStringContainsString('media/2026/01/test.jpg', $media->url);
    }

    public function test_variant_returns_named_variant(): void
    {
        $media = Media::create([
            'disk' => 'media-test',
            'path' => 'media/x.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
        ]);
        MediaVariant::create([
            'media_id' => $media->id,
            'name' => 'thumb',
            'path' => 'media/x.thumb.webp',
            'mime' => 'image/webp',
            'size' => 50,
        ]);

        $thumb = $media->variant('thumb');
        $this->assertNotNull($thumb);
        $this->assertSame('thumb', $thumb->name);

        $this->assertNull($media->variant('nonexistent'));
    }
}
