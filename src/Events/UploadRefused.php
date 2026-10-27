<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Events;

/**
 * An upload was not stored: a threat was found (`$threat`), or the scanner
 * could not answer and the configuration refuses unchecked files.
 */
final class UploadRefused
{
    public function __construct(
        public readonly string $fileName,
        public readonly ?string $threat,
        public readonly string|int|null $uploaderId,
        public readonly string $reason,
    ) {}
}
