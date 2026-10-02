---
title: Использование
locale: ru
status: stub
translated_from: ../en/usage.md
---

# Использование

> ⚠️ **Эта страница ещё не полностью переведена.** Пока используйте [английскую версию](../en/usage.md).

## Выбор медиа в формах ресурсов

`MediaPicker` — поле для выбора файлов библиотеки в диалоге с превью, поиском,
фильтрами и пагинацией. Это пресет `ResourcePicker` из ядра
(dskripchenko/laravel-admin 1.34 и новее):

```php
use Dskripchenko\LaravelAdminMedia\Fields\MediaPicker;

MediaPicker::make('cover_id')->images()->collection('articles'),
MediaPicker::make('gallery')->images()->multiple()->maxItems(12),
MediaPicker::make('attachment_id')->mime('application/pdf')->withoutUpload(),
```

- `images()` — только изображения; загрузка предлагает только картинки;
- `collection('articles')` — только эта коллекция, загрузки попадают в неё;
- `mime('video/')` — только файлы, чей MIME содержит строку;
- `responsiveSet('content')` — варианты для загруженных изображений;
- `withoutUpload()` — без кнопки загрузки;
- `multiple()`, `maxItems(n)` — упорядоченный список id, не больше `n`.

Значение — id файла, а с `multiple()` — список id в порядке, заданном
пользователем (колонку приводите к `array`). При сохранении каждый id
проверяется по библиотеке. Диалог требует `admin.media.view`, кнопка загрузки —
`admin.media.upload`.
