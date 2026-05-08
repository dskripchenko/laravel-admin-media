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

