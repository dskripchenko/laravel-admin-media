<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Services;

use RuntimeException;

/**
 * A minimal image processor on top of GD.
 *
 * It supports: reading JPEG/PNG/GIF/WebP, resizing to a width or a height,
 * cropping with a focal point, saving as JPEG/PNG/WebP. The EXIF is stripped
 * automatically (GD does not write EXIF on save).
 *
 * For a wider feature set (AVIF / watermarks / advanced filters) install
 * intervention/image and swap the bindings in the service provider.
 */
final class ImageProcessor
{
    private const SUPPORTED_INPUT = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Read the image info: width / height / mime.
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
     * Resize the image by width (when given) or by height (when given). Not both
     * at once — for a box fit (with a crop) use `crop()`.
     *
     * @param  int  $quality  0..100, for JPEG/WebP
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
        } elseif ($width === null) {
            imagedestroy($src);

            return false;
        }

        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);

            return false;
        }

        // Preserve the transparency for PNG/WebP.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $info['width'], $info['height']);
        $ok = $this->save($dst, $targetPath, $format ?? $info['mime'], $quality);

        imagedestroy($src);
        imagedestroy($dst);

        return $ok;
    }

    /**
     * A crop with a focal point ($fx, $fy ∈ [0, 1]).
     *
     * The window is chosen so that the focal point stays inside after the crop
     * to $width × $height. When the focal point is too close to an edge, the
     * window is pressed against it.
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
            // The source is wider — crop horizontally
            $cropH = $info['height'];
            $cropW = (int) round($cropH * $dstAspect);
            $cropY = 0;
            $cropX = (int) round(($info['width'] - $cropW) * $fx);
        } else {
            // The source is taller — crop vertically
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
        // The format may be a mime ('image/jpeg') or a short one ('jpg'/'webp').
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
