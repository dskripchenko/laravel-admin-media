<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Exceptions;

use RuntimeException;

/** The scanner could not tell whether a file is clean. */
final class ScannerUnavailable extends RuntimeException {}
