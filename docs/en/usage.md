---
title: Usage
locale: en
status: stable
---

# Usage

```php
$media = \Dskripchenko\LaravelAdminMedia\Models\Media::query()
    ->where('collection', 'articles')
    ->latest()
    ->paginate(20);

foreach ($media as $m) {
    echo $m->url();              // primary URL
    echo $m->url('thumb');       // responsive variant
    echo $m->focal_point;        // [x, y] for object-position
}
```

To disable EXIF stripping (for documents):

```php
'strip_exif' => false,
```


## Picking media in resource forms

`MediaPicker` lets a form pick files from the library in a dialog with
thumbnails, search, filters and pagination. It is a preset of the core's
`ResourcePicker` (dskripchenko/laravel-admin 1.36 or later):

```php
use Dskripchenko\LaravelAdminMedia\Fields\MediaPicker;

public function fields(): array
{
    return [
        MediaPicker::make('cover_id')->images()->collection('articles'),
        MediaPicker::make('gallery')->images()->multiple()->maxItems(12),
        MediaPicker::make('attachment_id')->mime('application/pdf')->withoutUpload(),
    ];
}
```

| Method | Effect |
|---|---|
| `images()` | only images; the upload dialog offers image files |
| `collection('articles')` | only that collection; uploads land in it |
| `mime('video/')` | only files whose MIME type contains the text |
| `responsiveSet('content')` | uploaded images get the variants of that set |
| `withoutUpload()` | no upload button, picking only |
| `multiple()`, `maxItems(n)` | an ordered list of ids, at most `n` |

The value is the media id, or with `multiple()` a list of ids in the order the
user arranged them — cast that column to `array`. On save every id is checked
against the library. The dialog needs `admin.media.view`; the upload button is
shown to users with `admin.media.upload`. The view page shows the picked files
with their thumbnails.

The thumbnail is the `thumb` variant, or the smallest one, or the original
image; other files show a placeholder. The library itself can be the target of
a plain `ResourcePicker` too: `ResourcePicker::make('logo_id')->resource('media-library')`.


## Scanning uploads

Every upload can be scanned before it is stored. The package ships a client
for a ClamAV daemon (`clamd`) — no dependencies, its INSTREAM protocol over a
TCP or unix socket:

```dotenv
ADMIN_MEDIA_SCANNER=clamd
ADMIN_MEDIA_CLAMD=tcp://127.0.0.1:3310      # or unix:///run/clamav/clamd.ctl
ADMIN_MEDIA_CLAMD_TIMEOUT=30
ADMIN_MEDIA_SCAN_ON_ERROR=reject            # or allow
```

- A file with a threat is not stored: `MediaService::upload()` throws
  `InfectedUpload` (with `$threat`), the upload endpoint answers 422
  (`errorKey: infected`).
- When the daemon cannot answer, `reject` (the default) refuses the file —
  `ScannerUnavailable`, 503 (`scanner_unavailable`); `allow` stores it and
  logs a warning.
- Both refusals fire `UploadRefused` (file name, threat, uploader, reason) for
  an audit log or a notification.
- Keep clamd's `StreamMaxLength` at least `max_size_mb`, or larger files are
  refused as unscannable.

Your own scanner implements `Contracts\UploadScanner` and is bound in the
container in place of the configured one.
