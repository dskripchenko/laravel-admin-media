<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $media_id
 * @property string $name
 * @property string $path
 * @property int|null $width
 * @property int|null $height
 * @property string $mime
 * @property int $size
 * @property string|null $format
 * @property-read string $url
 * @property-read Media $media
 */
final class MediaVariant extends Model
{
    protected $table = 'admin_media_variants';

    protected $guarded = ['id'];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Public URL variant'а. Использует disk родительской media.
     */
    protected function url(): Attribute
    {
        return Attribute::get(function (): string {
            /** @var Media|null $media */
            $media = $this->media;
            $disk = $media instanceof Media && $media->disk !== '' ? $media->disk : 'public';

            return (string) Storage::disk($disk)->url($this->path);
        });
    }
}
