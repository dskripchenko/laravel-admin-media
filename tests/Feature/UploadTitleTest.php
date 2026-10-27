<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Feature;

use Dskripchenko\LaravelAdminMedia\Services\MediaService;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UploadTitleTest extends TestCase
{
    public function test_an_upload_without_a_title_keeps_the_original_file_name(): void
    {
        Storage::fake(config('admin-media.disk'));

        $media = app(MediaService::class)->upload(UploadedFile::fake()->create('Quarterly report.pdf', 12, 'application/pdf'));

        $this->assertSame('Quarterly report.pdf', $media->title);
    }

    public function test_a_given_title_wins(): void
    {
        Storage::fake(config('admin-media.disk'));

        $media = app(MediaService::class)->upload(UploadedFile::fake()->create('scan.pdf', 12, 'application/pdf'), extraAttributes: ['title' => 'Contract']);

        $this->assertSame('Contract', $media->title);
    }
}
