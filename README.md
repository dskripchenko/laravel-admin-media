# dskripchenko/laravel-admin-media

> 🌐 **English** · [Русский](docs/ru/README.md) · [Deutsch](docs/de/README.md) · [中文](docs/zh/README.md)

Extended media library: collections, tags, focal-point, responsive variants, EXIF stripping. No spatie/medialibrary dependency.

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
php artisan vendor:publish --tag=media-config
```

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko
