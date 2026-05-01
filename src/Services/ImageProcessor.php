<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Services;

use RuntimeException;

/**
 * Минимальный image-processor поверх GD.
 *
 * Поддерживает: read JPEG/PNG/GIF/WebP, resize-to-width-or-height,
 * crop с focal-point, save в JPEG/PNG/WebP. EXIF-strip автоматически
 * (GD не пишет EXIF при save'е).
 *
 * Для расширенного feature-set'а (AVIF / watermarks / advanced filters)
 * — установить intervention/image и подменить bindings в SP.
 */
final class ImageProcessor
{
    private const SUPPORTED_INPUT = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Прочитать image-info: width / height / mime.
     *
     * @return array{width: int, height: int, mime: string}|null
     */
    public function info(string $sourcePath): ?array
    {
        if (! is_file($sourcePath)) {
            return null;
        }
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        return [
            'width' => (int) $info[0],
            'height' => (int) $info[1],
            'mime' => (string) $info['mime'],
        ];
    }

    /**
     * Resize изображения по width (если задан) или height (если задан).
     * Не оба сразу — для box-fit (с crop'ом) используйте `crop()`.
     *
     * @param  int  $quality  0..100 для JPEG/WebP
     */
    public function resize(string $sourcePath, string $targetPath, ?int $width, ?int $height, ?string $format = null, int $quality = 85): bool
    {
        $info = $this->info($sourcePath);
        if ($info === null || ! in_array($info['mime'], self::SUPPORTED_INPUT, true)) {
            return false;
        }

        $src = $this->readSource($sourcePath, $info['mime']);
        if ($src === null) {
            return false;
        }

        $aspect = $info['width'] / $info['height'];
        if ($width !== null && $height === null) {
            $height = (int) round($width / $aspect);
        } elseif ($height !== null && $width === null) {
            $width = (int) round($height * $aspect);
        } elseif ($width === null && $height === null) {
            imagedestroy($src);

            return false;
        }

        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);

            return false;
        }

        // Сохраняем прозрачность для PNG/WebP.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $info['width'], $info['height']);
        $ok = $this->save($dst, $targetPath, $format ?? $info['mime'], $quality);

        imagedestroy($src);
        imagedestroy($dst);

        return $ok;
    }

    /**
     * Crop с focal-point ($fx, $fy ∈ [0, 1]).
     *
     * Окно определяется так, чтобы focal-point остался внутри после
     * обрезки до размеров $width × $height. Если focal слишком близко к
     * краю — окно прижимается к нему.
     */
    public function cropToBox(
        string $sourcePath,
        string $targetPath,
        int $width,
        int $height,
        float $fx = 0.5,
        float $fy = 0.5,
        ?string $format = null,
        int $quality = 85,
    ): bool {
        $info = $this->info($sourcePath);
        if ($info === null || ! in_array($info['mime'], self::SUPPORTED_INPUT, true)) {
            return false;
        }

        $src = $this->readSource($sourcePath, $info['mime']);
        if ($src === null) {
            return false;
        }

        $srcAspect = $info['width'] / $info['height'];
        $dstAspect = $width / $height;

        if ($srcAspect > $dstAspect) {
            // Source шире — crop по горизонтали
            $cropH = $info['height'];
            $cropW = (int) round($cropH * $dstAspect);
            $cropY = 0;
            $cropX = (int) round(($info['width'] - $cropW) * $fx);
        } else {
            // Source выше — crop по вертикали
            $cropW = $info['width'];
            $cropH = (int) round($cropW / $dstAspect);
            $cropX = 0;
            $cropY = (int) round(($info['height'] - $cropH) * $fy);
        }

        $cropX = max(0, min($cropX, $info['width'] - $cropW));
        $cropY = max(0, min($cropY, $info['height'] - $cropH));

        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);

            return false;
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);
        $ok = $this->save($dst, $targetPath, $format ?? $info['mime'], $quality);

        imagedestroy($src);
        imagedestroy($dst);

        return $ok;
    }

    private function readSource(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        return $image === false ? null : $image;
    }

    private function save(\GdImage $img, string $targetPath, string $format, int $quality): bool
    {
        // Format может быть mime ('image/jpeg') либо short ('jpg'/'webp').
        $shortFormat = match (true) {
            str_contains($format, 'jpeg') || str_contains($format, 'jpg') => 'jpeg',
            str_contains($format, 'png') => 'png',
            str_contains($format, 'gif') => 'gif',
            str_contains($format, 'webp') => 'webp',
            default => throw new RuntimeException("Unsupported output format: $format"),
        };

        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return match ($shortFormat) {
            'jpeg' => imagejpeg($img, $targetPath, $quality),
            'png' => imagepng($img, $targetPath, (int) round((100 - $quality) / 11)), // 0..9
            'gif' => imagegif($img, $targetPath),
            'webp' => imagewebp($img, $targetPath, $quality),
        };
    }
}
