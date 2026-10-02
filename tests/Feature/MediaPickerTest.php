<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Feature;

use Dskripchenko\LaravelAdmin\Field\ResourcePicker;
use Dskripchenko\LaravelAdmin\Testing\Concerns\ActsAsAdmin;
use Dskripchenko\LaravelAdminMedia\Fields\MediaPicker;
use Dskripchenko\LaravelAdminMedia\Models\Media;
use Dskripchenko\LaravelAdminMedia\Models\MediaVariant;
use Dskripchenko\LaravelAdminMedia\Resources\MediaResource;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;

final class MediaPickerTest extends TestCase
{
    use ActsAsAdmin;

    private function media(string $path, string $mime, string $collection = 'default', array $extra = []): Media
    {
        return Media::create([
            'disk' => 'media-test',
            'path' => $path,
            'mime' => $mime,
            'size' => 100,
            'collection' => $collection,
            ...$extra,
        ]);
    }

    public function test_it_is_a_resource_picker_over_the_media_library(): void
    {
        $field = MediaPicker::make('cover_id');
        $out = $field->toArray();

        $this->assertInstanceOf(ResourcePicker::class, $field);
        $this->assertSame('resource_picker', $out['type']);
        $this->assertSame('media-library', $out['attributes']['resource']);
        $this->assertSame('grid', $out['attributes']['layout']);
        $this->assertSame('admin.media.view', $out['attributes']['viewPermission']);
        $this->assertSame([
            'url' => '/media/library/upload',
            'permission' => 'admin.media.upload',
            'fileField' => 'file',
            'responseKey' => 'media',
            'data' => [],
            'accept' => null,
        ], $out['attributes']['upload']);
    }

    public function test_presets_become_fixed_filters_and_upload_data(): void
    {
        $out = MediaPicker::make('gallery')
            ->images()
            ->collection('articles')
            ->responsiveSet('content')
            ->multiple()
            ->maxItems(6)
            ->toArray();

        $this->assertSame(['mime_kind' => 'image/', 'collection' => 'articles'], $out['attributes']['filters']);
        $this->assertSame(['collection' => 'articles', 'responsive_set' => 'content'], $out['attributes']['upload']['data']);
        $this->assertSame('image/*', $out['attributes']['upload']['accept']);
        $this->assertTrue($out['attributes']['multiple']);
        $this->assertSame(6, $out['attributes']['maxItems']);
    }

    public function test_mime_preset_and_upload_switch(): void
    {
        $out = MediaPicker::make('file_id')->mime('application/pdf')->withoutUpload()->toArray();

        $this->assertSame(['mime' => 'application/pdf'], $out['attributes']['filters']);
        $this->assertArrayNotHasKey('upload', $out['attributes']);
    }

    public function test_picker_item_of_an_image_previews_its_thumbnail(): void
    {
        $image = $this->media('media/sea.jpg', 'image/jpeg', extra: ['width' => 1200, 'height' => 800, 'alt' => 'The sea']);
        MediaVariant::create([
            'media_id' => $image->id,
            'name' => 'thumb',
            'path' => 'media/sea.thumb.webp',
            'mime' => 'image/webp',
            'size' => 10,
        ]);

        $item = (new MediaResource)->pickerItem($image->fresh(['variants']) ?? $image);

        $this->assertSame($image->id, $item['id']);
        $this->assertSame('The sea', $item['title']);
        $this->assertSame('image/jpeg · 1200×800', $item['subtitle']);
        $this->assertStringEndsWith('media/sea.thumb.webp', (string) $item['preview']);
    }

    public function test_picker_item_falls_back_to_the_original_and_the_file_name(): void
    {
        $image = $this->media('media/plain.png', 'image/png');
        $pdf = $this->media('media/report.pdf', 'application/pdf');
        $resource = new MediaResource;

        $this->assertStringEndsWith('media/plain.png', (string) $resource->pickerItem($image)['preview']);
        $this->assertSame('report.pdf', $resource->pickerItem($pdf)['title']);
        $this->assertNull($resource->pickerItem($pdf)['preview']);
    }

    public function test_the_picker_search_honours_the_fixed_filters(): void
    {
        $this->actingAsAdmin(permissions: ['admin.media.view']);
        $cover = $this->media('media/a.jpg', 'image/jpeg', 'articles');
        $this->media('media/b.jpg', 'image/jpeg', 'articles-archive');
        $this->media('media/c.pdf', 'application/pdf', 'articles');

        $response = $this->postJson('/api/admin/media-library/search', [
            'picker' => true,
            'filters' => ['mime_kind' => 'image/', 'collection' => 'articles'],
        ]);

        $response->assertOk();
        $this->assertSame([$cover->id], array_column((array) $response->json('payload.data'), 'id'));
        $this->assertSame('a.jpg', $response->json('payload.data.0._picker.title'));
    }

    public function test_the_picker_search_needs_the_view_permission(): void
    {
        $this->actingAsAdmin(permissions: ['admin.media.upload']);

        $this->postJson('/api/admin/media-library/search', ['picker' => true])->assertStatus(403);
    }

    public function test_the_upload_endpoint_of_the_picker_is_reachable(): void
    {
        $this->actingAsAdmin(permissions: ['admin.media.upload']);

        // Validation answers, not laravel-api's generic route.
        $this->postJson('/api/admin/media/library/upload')->assertStatus(422);
    }
}
