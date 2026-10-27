<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Scanning;

/** What a scanner found: nothing, or a named threat. */
final readonly class ScanResult
{
    public function __construct(public ?string $threat = null) {}

    public static function clean(): self
    {
        return new self;
    }

    public function isClean(): bool
    {
        return $this->threat === null;
    }
}
