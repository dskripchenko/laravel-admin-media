<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Resources;

use Dskripchenko\LaravelAdmin\Field\Input;
use Dskripchenko\LaravelAdmin\Field\TagsInput;
use Dskripchenko\LaravelAdmin\Field\Textarea;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Filter\OptionsFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminMedia\Models\Media;
use Illuminate\Database\Eloquent\Builder;

/**
 * Browse-страница media-библиотеки.
 *
 * Permissions: admin.media.{view,update,delete}.
 *
 * Upload отдельным endpoint'ом (см. UploadController) — Resource на изменения
 * только редактирование metadata (alt/title/tags/focal-point).
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
        return 'Медиа-библиотека';
    }

    public function fields(): array
    {
        return [
            Input::make('alt')->title('Alt-текст'),
            Input::make('title')->title('Заголовок'),
            Textarea::make('description')->title('Описание'),
            TagsInput::make('tags')->title('Теги'),
            Input::make('collection')->title('Коллекция'),
        ];
    }

    public function columns(): array
    {
        return [
            TableColumn::make('id')->sort()->width('60px'),
            TableColumn::make('path')->copyable()->search(),
            TableColumn::make('mime')->sort()->asBadge([]),
            TableColumn::make('collection')->sort()->asBadge([]),
            TableColumn::make('size')->sort()->align('right')->asBytes(),
            TableColumn::make('width')->align('right'),
            TableColumn::make('height')->align('right'),
            TableColumn::make('alt')->search(),
            TableColumn::make('created_at')->sort()->asDateTime(),
        ];
    }

    public function filters(): array
    {
        return [
            InputFilter::for('collection')->label('Коллекция'),
            InputFilter::for('mime')->label('MIME (substring)'),
            OptionsFilter::for('mime_kind')->label('Тип')->options([
                'image/' => 'Изображения',
                'video/' => 'Видео',
                'audio/' => 'Аудио',
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
        return $this->modelQuery()->orderByDesc('created_at');
    }
}
