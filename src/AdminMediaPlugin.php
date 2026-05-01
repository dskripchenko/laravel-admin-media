<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia;

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
        return '0.1.0';
    }

    public function register(): void {}

    public function boot(Admin $admin): void
    {
        $admin->resources([MediaResource::class]);

        $admin->permissions(
            ItemPermission::group('Медиа')
                ->addPermission('admin.media.view', 'Просмотр библиотеки')
                ->addPermission('admin.media.upload', 'Загрузка')
                ->addPermission('admin.media.update', 'Редактирование (alt, title, focal)')
                ->addPermission('admin.media.delete', 'Удаление')
                ->addPermission('admin.media.collections.manage', 'Управление коллекциями'),
        );
    }
}
