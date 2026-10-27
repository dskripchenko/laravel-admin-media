<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Services;

use Dskripchenko\LaravelAdminMedia\Contracts\UploadScanner;
use Dskripchenko\LaravelAdminMedia\Events\UploadRefused;
use Dskripchenko\LaravelAdminMedia\Exceptions\InfectedUpload;
use Dskripchenko\LaravelAdminMedia\Exceptions\ScannerUnavailable;
use Dskripchenko\LaravelAdminMedia\Models\Media;
use Dskripchenko\LaravelAdminMedia\Models\MediaVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The high-level service for uploads and variant generation.
 *
 * The upload algorithm:
 *   1. Validate the mime and the size (from the config)
 *   1a. Scan the file (an UploadScanner — ClamAV, say): a threat refuses it,
 *       an unreachable scanner refuses it or lets it pass by `scanning.on_error`
 *   2. Store the original on the disk (the config's disk plus a path prefix)
 *   3. Create the Media record (with the width and height taken from the image
 *      info)
 *   4. Generate the variants of the responsive set (when one is given) —
 *      synchronously; a production host swaps this for a queued job
 */
final class MediaService
{
    public function __construct(
        private readonly ImageProcessor $images,
        private readonly ?UploadScanner $scanner = null,
    ) {}

    /**
     * Upload a file into the media library.
     *
     * @param  array<string, mixed>  $extraAttributes  The extra Media fields
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
        $this->scan($file, $extraAttributes['uploader_id'] ?? null);

        $disk = (string) config('admin-media.disk', 'public');
        $pathPrefix = (string) config('admin-media.path_prefix', 'media');

        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $path = $pathPrefix.'/'.date('Y/m').'/'.$filename;

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        $info = $this->images->info($file->getRealPath());

        // Without a title the original file name is the only human name the
        // file ever had; downloads and lists need it.
        $extraAttributes += ['title' => $file->getClientOriginalName()];

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
     * @throws InfectedUpload
     * @throws ScannerUnavailable when the scanner cannot answer and `scanning.on_error` is `reject`
     */
    private function scan(UploadedFile $file, mixed $uploaderId): void
    {
        if ($this->scanner === null) {
            return;
        }
        $name = $file->getClientOriginalName();
        $uploader = is_string($uploaderId) || is_int($uploaderId) ? $uploaderId : null;

        try {
            $result = $this->scanner->scan((string) $file->getRealPath(), $name);
        } catch (ScannerUnavailable $e) {
            if (config('admin-media.scanning.on_error', 'reject') === 'allow') {
                Log::warning('Upload stored unscanned: '.$e->getMessage(), ['file' => $name]);

                return;
            }
            event(new UploadRefused($name, null, $uploader, $e->getMessage()));

            throw $e;
        }

        if (! $result->isClean()) {
            Log::warning('Upload refused by the scanner', ['file' => $name, 'threat' => $result->threat, 'uploader' => $uploader]);
            event(new UploadRefused($name, $result->threat, $uploader, 'threat'));

            throw new InfectedUpload((string) $result->threat, $name);
        }
    }

    /**
     * Generate the variants from the `admin-media.responsive_sets.{set}` config.
     */
    public function generateVariants(Media $media, string $setName): int
    {
        /** @var list<array<string, mixed>> $set */
        $set = (array) config("admin-media.responsive_sets.$setName", []);
        if ($set === []) {
            return 0;
        }

        // Through temporary files, so that any disk works — S3 has no local path.
        $disk = Storage::disk($media->disk);
        $source = $disk->readStream($media->path);
        if (! is_resource($source)) {
            return 0;
        }
        $sourcePath = (string) tempnam(sys_get_temp_dir(), 'media');
        $copy = fopen($sourcePath, 'wb');
        if ($copy === false) {
            return 0;
        }
        stream_copy_to_stream($source, $copy);
        fclose($copy);
        fclose($source);

        try {
            return $this->variantsFrom($media, $sourcePath, $set);
        } finally {
            @unlink($sourcePath);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $set
     */
    private function variantsFrom(Media $media, string $sourcePath, array $set): int
    {
        $disk = Storage::disk($media->disk);
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
            $variantFsPath = (string) tempnam(sys_get_temp_dir(), 'variant');

            $ok = $crop && $width !== null && $height !== null
                ? $this->images->cropToBox(
                    $sourcePath, $variantFsPath, $width, $height,
                    $media->focal_x, $media->focal_y, $format, $quality,
                )
                : $this->images->resize($sourcePath, $variantFsPath, $width, $height, $format, $quality);

            $info = $ok ? $this->images->info($variantFsPath) : null;
            $size = $ok ? (@filesize($variantFsPath) ?: 0) : 0;
            $stream = $ok ? fopen($variantFsPath, 'rb') : false;
            $stored = $stream !== false && $disk->writeStream($variantPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($variantFsPath);
            if (! $stored) {
                continue;
            }

            MediaVariant::query()->updateOrCreate(
                ['media_id' => $media->id, 'name' => $name],
                [
                    'path' => $variantPath,
                    'width' => $info['width'] ?? null,
                    'height' => $info['height'] ?? null,
                    'mime' => $info['mime'] ?? 'application/octet-stream',
                    'size' => $size,
                    'format' => $format,
                ],
            );
            $created++;
        }

        return $created;
    }
}
