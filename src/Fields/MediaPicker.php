<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Fields;

use Dskripchenko\LaravelAdmin\Field\ResourcePicker;
use Dskripchenko\LaravelAdminMedia\Resources\MediaResource;

/**
 * Picks files from the media library in a resource form.
 *
 * A preset of the core's ResourcePicker: the target is the media library, the
 * dialog shows thumbnails, and users holding `admin.media.upload` can upload a
 * new file straight from it. The value is the media id — or, with multiple(),
 * an ordered list of ids:
 *
 *     MediaPicker::make('cover_id')->images()->collection('articles');
 *     MediaPicker::make('gallery')->images()->multiple()->maxItems(12);
 *     MediaPicker::make('attachment_id')->mime('application/pdf')->withoutUpload();
 */
class MediaPicker extends ResourcePicker
{
    public static function make(string $name): static
    {
        $field = parent::make($name);
        $field->resource(MediaResource::slug());
        $field->layout('grid');
        $field->uploadTo('/media/library/upload', 'admin.media.upload', 'file', 'media');

        return $field;
    }

    /**
     * Only the files of one collection. Uploads from the dialog land in it.
     */
    public function collection(string $collection): static
    {
        $this->filters(['collection' => $collection]);

        return $this->withUploadData(['collection' => $collection]);
    }

    /**
     * Only images. The upload dialog offers image files only.
     */
    public function images(): static
    {
        $this->filters(['mime_kind' => 'image/']);

        return $this->withUploadAccept('image/*');
    }

    /**
     * Only the files whose MIME type contains the given text: 'image/svg',
     * 'application/pdf', 'video/'.
     */
    public function mime(string $mime): static
    {
        $this->filters(['mime' => $mime]);

        return $this->withUploadAccept($mime);
    }

    /**
     * The responsive set the uploaded images get their variants from; see
     * `admin-media.responsive_sets`.
     */
    public function responsiveSet(string $set): static
    {
        return $this->withUploadData(['responsive_set' => $set]);
    }

    /**
     * No upload button in the dialog: picking from the library only.
     */
    public function withoutUpload(): static
    {
        unset($this->attributes['upload']);

        return $this;
    }

    /**
     * @param  array<string, scalar>  $data
     */
    private function withUploadData(array $data): static
    {
        if (is_array($this->attributes['upload'] ?? null)) {
            /** @var array<string, scalar> $current */
            $current = $this->attributes['upload']['data'] ?? [];
            $this->attributes['upload']['data'] = array_merge($current, $data);
        }

        return $this;
    }

    private function withUploadAccept(string $accept): static
    {
        if (is_array($this->attributes['upload'] ?? null)) {
            // A MIME fragment like 'video/' is not a valid accept value.
            $this->attributes['upload']['accept'] = str_ends_with($accept, '/') ? $accept.'*' : $accept;
        }

        return $this;
    }
}
