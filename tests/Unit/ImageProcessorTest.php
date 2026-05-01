<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Unit;

use Dskripchenko\LaravelAdminMedia\Services\ImageProcessor;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;

final class ImageProcessorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir().'/img-processor-test-'.uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    private function createTestJpeg(int $w = 400, int $h = 300): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 200, 100, 50));
        $path = $this->tmpDir.'/source.jpg';
        imagejpeg($img, $path, 90);
        imagedestroy($img);

        return $path;
    }

    public function test_info_returns_dimensions_and_mime(): void
    {
        $path = $this->createTestJpeg(640, 480);
        $proc = new ImageProcessor;
        $info = $proc->info($path);

        $this->assertNotNull($info);
        $this->assertSame(640, $info['width']);
        $this->assertSame(480, $info['height']);
        $this->assertSame('image/jpeg', $info['mime']);
    }

    public function test_info_returns_null_for_missing_file(): void
    {
        $proc = new ImageProcessor;
        $this->assertNull($proc->info('/no/such/file'));
    }

    public function test_resize_writes_target_with_specified_width(): void
    {
        $src = $this->createTestJpeg(400, 300);
        $dst = $this->tmpDir.'/resized.jpg';
        $proc = new ImageProcessor;

        $ok = $proc->resize($src, $dst, 200, null);
        $this->assertTrue($ok);
        $this->assertFileExists($dst);

        $info = $proc->info($dst);
        $this->assertSame(200, $info['width']);
        $this->assertSame(150, $info['height']); // aspect 4:3 saved
    }

    public function test_resize_preserves_aspect_when_height_specified(): void
    {
        $src = $this->createTestJpeg(400, 300);
        $dst = $this->tmpDir.'/resized.jpg';
        $proc = new ImageProcessor;

        $proc->resize($src, $dst, null, 150);
        $info = $proc->info($dst);
        $this->assertSame(200, $info['width']);
        $this->assertSame(150, $info['height']);
    }

    public function test_crop_to_box_produces_exact_dimensions(): void
    {
        $src = $this->createTestJpeg(800, 400); // wide
        $dst = $this->tmpDir.'/cropped.jpg';
        $proc = new ImageProcessor;

        $ok = $proc->cropToBox($src, $dst, 200, 200);
        $this->assertTrue($ok);

        $info = $proc->info($dst);
        $this->assertSame(200, $info['width']);
        $this->assertSame(200, $info['height']);
    }
}
