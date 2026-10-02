# dskripchenko/laravel-admin-media

> 🌐 **English** · [Русский](docs/ru/README.md) · [Deutsch](docs/de/README.md) · [中文](docs/zh/README.md)

Extended media library: collections, tags, focal-point, responsive variants, EXIF stripping, and a `MediaPicker` form field. No spatie/medialibrary dependency.

A sister-pack for [`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin).

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-admin-media)](https://packagist.org/packages/dskripchenko/laravel-admin-media)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-admin-media)](LICENSE)

## Install

```bash
composer require dskripchenko/laravel-admin-media
php artisan migrate
```

The plugin auto-registers via Laravel package discovery. To publish the
config:

```bash
php artisan vendor:publish --tag=admin-media-config
```

## Picking media in forms

```php
use Dskripchenko\LaravelAdminMedia\Fields\MediaPicker;

MediaPicker::make('cover_id')->images()->collection('articles');
MediaPicker::make('gallery')->images()->multiple()->maxItems(12);
```

A dialog with thumbnails, search, filters, pagination and upload, built on the
core's `ResourcePicker`. See [Usage](docs/en/usage.md#picking-media-in-resource-forms).

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko
