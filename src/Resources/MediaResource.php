<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Resources;

use Dskripchenko\LaravelAdmin\Field\Input;
use Dskripchenko\LaravelAdmin\Field\TagsInput;
use Dskripchenko\LaravelAdmin\Field\Textarea;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminMedia\Filters\CollectionFilter;
use Dskripchenko\LaravelAdminMedia\Filters\MimeKindFilter;
use Dskripchenko\LaravelAdminMedia\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * The browse page of the media library.
 *
 * Permissions: admin.media.{view,update,delete}.
 *
 * Uploading goes through a separate endpoint (see UploadController) — as far as
 * changes go, the resource only edits the metadata (alt/title/tags/focal point).
 */
final class MediaResource extends Resource
{
    public static string $model = Media::class;

    public static string $icon = 'image';

    public static ?string $group = 'Медиа';

    public static function slug(): string
    {
        return 'media-library';
    }

    public static function permission(): string
    {
        return 'admin.media';
    }

    public static function label(): string
    {
        return (string) __('Медиа-библиотека');
    }

    public function fields(): array
    {
        return [
            Input::make('alt')->title((string) __('Alt-текст')),
            Input::make('title')->title((string) __('Заголовок')),
            Textarea::make('description')->title((string) __('Описание')),
            TagsInput::make('tags')->title((string) __('Теги')),
            Input::make('collection')->title((string) __('Коллекция')),
        ];
    }

    public function columns(): array
    {
        return [
            TableColumn::make('id')->label((string) __('ID'))->sort()->width('60px'),
            TableColumn::make('path')->label((string) __('Путь'))->copyable()->search(),
            TableColumn::make('mime')->label((string) __('Тип'))->sort()->asBadge([]),
            TableColumn::make('collection')->label((string) __('Коллекция'))->sort()->asBadge([]),
            TableColumn::make('size')->label((string) __('Размер'))->sort()->align('right')->asBytes(),
            TableColumn::make('width')->label((string) __('Ширина'))->align('right'),
            TableColumn::make('height')->label((string) __('Высота'))->align('right'),
            TableColumn::make('alt')->label((string) __('Alt-текст'))->search(),
            TableColumn::make('created_at')->label((string) __('Создано'))->sort()->asDateTime(),
        ];
    }

    public function filters(): array
    {
        return [
            CollectionFilter::for('collection')->label((string) __('Коллекция')),
            InputFilter::for('mime')->label((string) __('MIME (подстрока)')),
            MimeKindFilter::for('mime_kind')->label((string) __('Тип'))->options([
                'image/' => (string) __('Изображения'),
                'video/' => (string) __('Видео'),
                'audio/' => (string) __('Аудио'),
                'application/pdf' => 'PDF',
            ]),
        ];
    }

    public function with(): array
    {
        return ['variants'];
    }

    public function indexQuery(): Builder
    {
        // The variants come along for the picker's thumbnails.
        return $this->modelQuery()->with('variants')->orderByDesc('created_at');
    }

    /**
     * The title, the alt text, or the file name.
     */
    public function recordTitle(Model $row): string
    {
        if (! $row instanceof Media) {
            return parent::recordTitle($row);
        }
        foreach ([$row->title, $row->alt] as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return basename($row->path);
    }

    /**
     * The MIME type, with the dimensions of an image.
     */
    public function recordSubtitle(Model $row): ?string
    {
        if (! $row instanceof Media) {
            return parent::recordSubtitle($row);
        }
        $parts = [$row->mime];
        if ($row->width !== null && $row->height !== null) {
            $parts[] = $row->width.'×'.$row->height;
        }

        return implode(' · ', $parts);
    }

    /**
     * Images preview through their smallest variant, falling back to the
     * original; other files have no preview.
     */
    public function pickerPreview(Model $row): ?string
    {
        if (! $row instanceof Media || $row->kind !== 'image') {
            return null;
        }
        $thumb = $row->variant('thumb') ?? $row->variants->sortBy('width')->first();

        // The variant lives on the original's disk; resolving it here spares
        // MediaVariant::$url a query for its parent per row.
        return $thumb !== null
            ? (string) Storage::disk($row->disk)->url($thumb->path)
            : $row->url;
    }
}
