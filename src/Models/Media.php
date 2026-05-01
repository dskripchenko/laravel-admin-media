<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property array<string, mixed>|null $exif
 * @property float $focal_x
 * @property float $focal_y
 * @property string|null $alt
 * @property string|null $title
 * @property string|null $description
 * @property string $collection
 * @property array<int, string>|null $tags
 * @property int|null $uploader_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read string $url
 * @property-read string $kind                  'image' | 'video' | 'audio' | 'document' | 'other'
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MediaVariant> $variants
 */
final class Media extends Model
{
    protected $table = 'admin_media';

    protected $guarded = ['id'];

    protected $casts = [
        'exif' => 'array',
        'tags' => 'array',
        'focal_x' => 'float',
        'focal_y' => 'float',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Все variants (thumb / w-768 / etc).
     *
     * @return HasMany<MediaVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    /**
     * Public URL оригинала через Storage.
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => (string) Storage::disk($this->disk)->url($this->path));
    }

    /**
     * Тип media по MIME.
     */
    protected function kind(): Attribute
    {
        return Attribute::get(function (): string {
            $mime = $this->mime;
            if (str_starts_with($mime, 'image/')) {
                return 'image';
            }
            if (str_starts_with($mime, 'video/')) {
                return 'video';
            }
            if (str_starts_with($mime, 'audio/')) {
                return 'audio';
            }
            if (in_array($mime, ['application/pdf'], true)) {
                return 'document';
            }

            return 'other';
        });
    }

    /**
     * Получить variant по имени либо null.
     */
    public function variant(string $name): ?MediaVariant
    {
        return $this->variants->firstWhere('name', $name);
    }
}
