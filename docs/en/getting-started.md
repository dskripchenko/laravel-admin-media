---
title: Getting Started
locale: en
status: stable
---

# Getting Started

`dskripchenko/laravel-admin-media` is a sister-pack of `dskripchenko/laravel-admin`.
Install once — it auto-registers and surfaces in your admin.

## Install

```bash
composer require dskripchenko/laravel-admin-media
php artisan migrate
```

## Configure

```bash
php artisan vendor:publish --tag=media-config
```

Edit `config/media.php`.


## What it adds

`/admin/r/media` — global library across all collections.
`MediaUploadField` — replace the default `FileUpload` in any
Resource:

```php
MediaUploadField::make('cover')
    ->collection('articles')
    ->responsive([320, 640, 1280])
    ->focalPoint()
    ->stripExif(),
```

The pack provides its own `media` table — no `spatie/medialibrary`
needed.

## See also

- [Usage](usage.md)
- [Glossary](https://github.com/dskripchenko/laravel-admin/blob/main/docs/en/glossary.md)
