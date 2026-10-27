<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Contracts;

use Dskripchenko\LaravelAdminMedia\Exceptions\ScannerUnavailable;
use Dskripchenko\LaravelAdminMedia\Scanning\ScanResult;

/**
 * Looks into an uploaded file before it is stored: an antivirus, a content
 * policy. The default does nothing; ClamdScanner asks a ClamAV daemon.
 */
interface UploadScanner
{
    /**
     * @param  string  $path  a readable local file
     * @param  string  $name  the name the uploader gave it, for the log
     *
     * @throws ScannerUnavailable when the scanner cannot give an answer
     */
    public function scan(string $path, string $name): ScanResult;
}
