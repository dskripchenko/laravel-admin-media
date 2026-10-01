<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TranslationCoverageTest extends TestCase
{
    public function test_every_cyrillic_string_in_src_has_an_english_translation(): void
    {
        $root = dirname(__DIR__, 2);
        /** @var array<string, string> $en */
        $en = json_decode((string) file_get_contents($root.'/resources/lang/en.json'), true, flags: JSON_THROW_ON_ERROR);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));
        $missing = [];
        $found = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }
                $value = stripslashes(substr($token[1], 1, -1));
                if (! preg_match('/\p{Cyrillic}/u', $value)) {
                    continue;
                }
                $found++;
                if (! array_key_exists($value, $en) || $en[$value] === '') {
                    $missing[] = $value;
                }
            }
        }

        $this->assertGreaterThan(0, $found);
        $this->assertSame([], array_values(array_unique($missing)), 'Missing keys in resources/lang/en.json');
    }
}
