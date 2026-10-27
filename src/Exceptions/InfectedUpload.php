<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Exceptions;

use RuntimeException;

/** The scanner found a threat in an upload; nothing was stored. */
final class InfectedUpload extends RuntimeException
{
    public function __construct(public readonly string $threat, public readonly string $fileName)
    {
        parent::__construct("The file {$fileName} was refused: {$threat}.");
    }
}
