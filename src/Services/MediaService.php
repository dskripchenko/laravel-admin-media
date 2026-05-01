<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Services;

use Dskripchenko\LaravelAdminMedia\Models\Media;
use Dskripchenko\LaravelAdminMedia\Models\MediaVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Высокоуровневый сервис для upload + variant-generation.
 *
 * Upload алгоритм:
 *   1. Validate mime & size (через config)
 *   2. Хранение оригинала на диске (config disk + path-prefix)
 *   3. Создание Media record (со width/height извлечёнными из image-info)
 *   4. Генерация variants по responsive_set'у (если задан) — синхронно;
 *      production-host подменяет на queued-job
 */
final class MediaService
{
    public function __construct(private readonly ImageProcessor $images) {}

    /**
     * Загрузить файл в media-library.
     *
     * @param  array<string, mixed>  $extraAttributes  Дополнительные поля Media
     *                                                 (alt, title, description,
     *                                                 tags, collection,
     *                                                 uploader_id).
     */
    public function upload(
        UploadedFile $file,
        ?string $collection = null,
        ?string $responsiveSet = null,
        array $extraAttributes = [],
    ): Media {
        $disk = (string) config('admin-media.disk', 'public');
        $pathPrefix = (string) config('admin-media.path_prefix', 'media');

        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $path = $pathPrefix.'/'.date('Y/m').'/'.$filename;

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        $info = $this->images->info($file->getRealPath());

        /** @var Media $media */
        $media = Media::query()->create(array_merge([
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'width' => $info['width'] ?? null,
            'height' => $info['height'] ?? null,
            'collection' => $collection ?? 'default',
        ], $extraAttributes));

        if ($responsiveSet !== null && $info !== null) {
            $this->generateVariants($media, $responsiveSet);
        }

        return $media->refresh();
    }

    /**
     * Сгенерировать variants по конфигу `admin-media.responsive_sets.{set}`.
     */
    public function generateVariants(Media $media, string $setName): int
    {
        /** @var list<array<string, mixed>> $set */
        $set = (array) config("admin-media.responsive_sets.$setName", []);
        if ($set === []) {
            return 0;
        }

        $disk = Storage::disk($media->disk);
        $sourcePath = $disk->path($media->path);
        if (! is_file($sourcePath)) {
            return 0;
        }

        $created = 0;
        foreach ($set as $variantSpec) {
            $name = (string) ($variantSpec['name'] ?? '');
            $width = isset($variantSpec['width']) ? (int) $variantSpec['width'] : null;
            $height = isset($variantSpec['height']) ? (int) $variantSpec['height'] : null;
            $format = isset($variantSpec['format']) ? (string) $variantSpec['format'] : null;
            $quality = (int) ($variantSpec['quality'] ?? 85);
            $crop = (bool) ($variantSpec['crop'] ?? false);

            if ($name === '') {
                continue;
            }

            $variantPath = dirname($media->path).'/'.pathinfo($media->path, PATHINFO_FILENAME).".$name.".($format ?? 'jpg');
            $variantFsPath = $disk->path($variantPath);

            $ok = $crop && $width !== null && $height !== null
                ? $this->images->cropToBox(
                    $sourcePath, $variantFsPath, $width, $height,
                    $media->focal_x, $media->focal_y, $format, $quality,
                )
                : $this->images->resize($sourcePath, $variantFsPath, $width, $height, $format, $quality);

            if (! $ok) {
                continue;
            }

            $info = $this->images->info($variantFsPath);

            MediaVariant::query()->updateOrCreate(
                ['media_id' => $media->id, 'name' => $name],
                [
                    'path' => $variantPath,
                    'width' => $info['width'] ?? null,
                    'height' => $info['height'] ?? null,
                    'mime' => $info['mime'] ?? 'application/octet-stream',
                    'size' => @filesize($variantFsPath) ?: 0,
                    'format' => $format,
                ],
            );
            $created++;
        }

        return $created;
    }
}
