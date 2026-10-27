<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Feature;

use Dskripchenko\LaravelAdminMedia\Contracts\UploadScanner;
use Dskripchenko\LaravelAdminMedia\Events\UploadRefused;
use Dskripchenko\LaravelAdminMedia\Exceptions\InfectedUpload;
use Dskripchenko\LaravelAdminMedia\Exceptions\ScannerUnavailable;
use Dskripchenko\LaravelAdminMedia\Models\Media;
use Dskripchenko\LaravelAdminMedia\Scanning\ClamdScanner;
use Dskripchenko\LaravelAdminMedia\Scanning\ScanResult;
use Dskripchenko\LaravelAdminMedia\Services\MediaService;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

final class UploadScanningTest extends TestCase
{
    /** The EICAR test file: every antivirus reports it, it does nothing. */
    private const EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    private function scanner(?string $threat, bool $down = false): void
    {
        $this->app->forgetInstance(MediaService::class);
        $this->app->instance(UploadScanner::class, new class($threat, $down) implements UploadScanner
        {
            public function __construct(private readonly ?string $threat, private readonly bool $down) {}

            public function scan(string $path, string $name): ScanResult
            {
                if ($this->down) {
                    throw new ScannerUnavailable('down');
                }

                return new ScanResult($this->threat);
            }
        });
    }

    public function test_a_clean_file_is_stored(): void
    {
        Storage::fake(config('admin-media.disk'));
        $this->scanner(null);

        $media = app(MediaService::class)->upload(UploadedFile::fake()->create('notes.pdf', 3, 'application/pdf'));

        $this->assertTrue(Storage::disk($media->disk)->exists($media->path));
    }

    public function test_an_infected_file_is_refused_and_nothing_is_stored(): void
    {
        Storage::fake(config('admin-media.disk'));
        Event::fake([UploadRefused::class]);
        $this->scanner('Eicar-Test-Signature');

        try {
            app(MediaService::class)->upload(UploadedFile::fake()->create('evil.pdf', 1, 'application/pdf'));
            $this->fail('Stored an infected file');
        } catch (InfectedUpload $e) {
            $this->assertSame('Eicar-Test-Signature', $e->threat);
        }

        $this->assertSame(0, Media::query()->count());
        $this->assertSame([], Storage::disk(config('admin-media.disk'))->allFiles());
        Event::assertDispatched(UploadRefused::class, fn (UploadRefused $e) => $e->threat === 'Eicar-Test-Signature' && $e->fileName === 'evil.pdf');
    }

    public function test_an_unreachable_scanner_refuses_by_default_and_lets_through_when_allowed(): void
    {
        Storage::fake(config('admin-media.disk'));
        $this->scanner(null, down: true);

        try {
            app(MediaService::class)->upload(UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'));
            $this->fail('Stored an unscanned file');
        } catch (ScannerUnavailable) {
            $this->assertSame(0, Media::query()->count());
        }

        config()->set('admin-media.scanning.on_error', 'allow');
        app(MediaService::class)->upload(UploadedFile::fake()->create('b.pdf', 1, 'application/pdf'));
        $this->assertSame(1, Media::query()->count());
    }

    public function test_clamd_replies_are_read(): void
    {
        $this->assertTrue(ClamdScanner::parse("stream: OK\0")->isClean());
        $this->assertSame('Win.Test.EICAR_HDB-1', ClamdScanner::parse("stream: Win.Test.EICAR_HDB-1 FOUND\0")->threat);
        $this->expectException(ScannerUnavailable::class);
        ClamdScanner::parse("INSTREAM size limit exceeded. ERROR\0");
    }

    public function test_an_unreachable_daemon_is_reported(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'scan');
        file_put_contents((string) $file, 'hello');
        $this->expectException(ScannerUnavailable::class);
        try {
            (new ClamdScanner('tcp://127.0.0.1:1', 1.0))->scan((string) $file, 'hello.txt');
        } finally {
            @unlink((string) $file);
        }
    }

    /** Against a real daemon: CLAMD_TEST_ADDRESS=tcp://127.0.0.1:3310. */
    public function test_a_real_clamd_finds_eicar_and_passes_a_clean_file(): void
    {
        $address = getenv('CLAMD_TEST_ADDRESS');
        if (! is_string($address) || $address === '') {
            $this->markTestSkipped('Set CLAMD_TEST_ADDRESS to a ClamAV daemon.');
        }
        $scanner = new ClamdScanner($address, 10.0);
        $bad = tempnam(sys_get_temp_dir(), 'eicar');
        $good = tempnam(sys_get_temp_dir(), 'clean');
        file_put_contents((string) $bad, self::EICAR);
        file_put_contents((string) $good, str_repeat('plain text ', 20000));

        try {
            $this->assertStringContainsStringIgnoringCase('eicar', (string) $scanner->scan((string) $bad, 'eicar.com')->threat);
            $this->assertTrue($scanner->scan((string) $good, 'clean.txt')->isClean());
        } finally {
            @unlink((string) $bad);
            @unlink((string) $good);
        }
    }
}
