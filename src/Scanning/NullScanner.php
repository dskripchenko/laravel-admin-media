<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Scanning;

use Dskripchenko\LaravelAdminMedia\Contracts\UploadScanner;

/** No scanning: every file passes. The default. */
final class NullScanner implements UploadScanner
{
    public function scan(string $path, string $name): ScanResult
    {
        return ScanResult::clean();
    }
}
