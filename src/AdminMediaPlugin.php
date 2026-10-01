<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia;

use Composer\InstalledVersions;
use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdmin\Permission\ItemPermission;
use Dskripchenko\LaravelAdmin\Plugin\AdminPlugin;
use Dskripchenko\LaravelAdminMedia\Resources\MediaResource;

final class AdminMediaPlugin implements AdminPlugin
{
    public function name(): string
    {
        return 'media';
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('dskripchenko/laravel-admin-media') ?? 'dev';
    }

    public function register(): void {}

    public function boot(Admin $admin): void
    {
        $admin->resources([MediaResource::class]);

        $admin->permissions(
            ItemPermission::group((string) __('Медиа'))
                ->addPermission('admin.media.view', (string) __('Просмотр библиотеки'))
                ->addPermission('admin.media.upload', (string) __('Загрузка'))
                ->addPermission('admin.media.update', (string) __('Редактирование (alt, title, focal)'))
                ->addPermission('admin.media.delete', (string) __('Удаление'))
                ->addPermission('admin.media.collections.manage', (string) __('Управление коллекциями')),
        );
    }
}
