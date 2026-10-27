<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Scanning;

use Dskripchenko\LaravelAdminMedia\Contracts\UploadScanner;
use Dskripchenko\LaravelAdminMedia\Exceptions\ScannerUnavailable;

/**
 * Asks a ClamAV daemon over TCP or a unix socket with its INSTREAM command:
 * the file goes in chunks, each prefixed by its length, the daemon answers
 * `stream: OK` or `stream: <threat> FOUND`. No dependencies — a socket and
 * the protocol (clamd(8)).
 */
final class ClamdScanner implements UploadScanner
{
    private const CHUNK = 65536;

    /**
     * @param  string  $address  `tcp://host:port` or `unix:///path/to/clamd.sock`
     */
    public function __construct(
        private readonly string $address,
        private readonly float $timeout = 30.0,
    ) {}

    public function scan(string $path, string $name): ScanResult
    {
        $file = @fopen($path, 'rb');
        if ($file === false) {
            throw new ScannerUnavailable("The upload {$name} cannot be read for scanning.");
        }

        $socket = @stream_socket_client($this->address, $code, $message, $this->timeout);
        if ($socket === false) {
            fclose($file);
            throw new ScannerUnavailable("ClamAV at {$this->address} is unreachable: {$message}");
        }
        stream_set_timeout($socket, (int) ceil($this->timeout));

        try {
            $this->write($socket, "zINSTREAM\0");
            while (! feof($file)) {
                $chunk = fread($file, self::CHUNK);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $this->write($socket, pack('N', strlen($chunk)).$chunk);
            }
            $this->write($socket, pack('N', 0));

            $reply = stream_get_contents($socket);
            if ($reply === false || $reply === '' || stream_get_meta_data($socket)['timed_out']) {
                throw new ScannerUnavailable("ClamAV at {$this->address} gave no answer for {$name}.");
            }

            return self::parse($reply);
        } finally {
            fclose($file);
            fclose($socket);
        }
    }

    /**
     * The daemon's answer to INSTREAM: `stream: OK`, `stream: Eicar-Test-Signature FOUND`,
     * or an error such as `INSTREAM size limit exceeded. ERROR`.
     */
    public static function parse(string $reply): ScanResult
    {
        $reply = trim($reply, "\0\r\n ");
        if (preg_match('/^stream: OK$/', $reply) === 1) {
            return ScanResult::clean();
        }
        if (preg_match('/^stream: (.+) FOUND$/', $reply, $m) === 1) {
            return new ScanResult($m[1]);
        }

        throw new ScannerUnavailable('ClamAV answered: '.$reply);
    }

    /** @param resource $socket */
    private function write($socket, string $data): void
    {
        for ($written = 0; $written < strlen($data); $written += $n) {
            $n = @fwrite($socket, substr($data, $written));
            if ($n === false || $n === 0) {
                throw new ScannerUnavailable("ClamAV at {$this->address} closed the connection (a size limit, perhaps).");
            }
        }
    }
}
