<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Tests\Feature;

use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdminMedia\AdminMediaPlugin;
use Dskripchenko\LaravelAdminMedia\Resources\MediaResource;
use Dskripchenko\LaravelAdminMedia\Tests\TestCase;

final class PluginRegistrationTest extends TestCase
{
    public function test_plugin_in_admin_plugins_config(): void
    {
        $this->assertContains(AdminMediaPlugin::class, (array) config('admin.plugins', []));
    }

    public function test_resource_registered(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $this->assertContains(MediaResource::class, $admin->getResources());
    }

    public function test_permissions_registered(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $r = $admin->getPermissionRegistry();

        foreach (
            [
                'admin.media.view',
                'admin.media.upload',
                'admin.media.update',
                'admin.media.delete',
                'admin.media.collections.manage',
            ] as $key
        ) {
            $this->assertTrue($r->knows($key));
        }
    }

    public function test_upload_endpoint_registered(): void
    {
        $r = $this->postJson('/api/admin/media/upload');
        $this->assertNotSame(404, $r->status());
    }

    public function test_version_is_resolved_from_composer(): void
    {
        $this->assertNotSame('', (new AdminMediaPlugin)->version());
        $this->assertNotSame('0.1.0', (new AdminMediaPlugin)->version());
    }

    public function test_english_translations_loaded(): void
    {
        app()->setLocale('en');
        $this->assertSame('Media library', __('Медиа-библиотека'));
        $this->assertSame('Media library', MediaResource::label());
    }
}
