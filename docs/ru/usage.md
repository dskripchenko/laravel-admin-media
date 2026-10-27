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
(dskripchenko/laravel-admin 1.36 и новее):

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


## Проверка загрузок

Каждую загрузку можно проверить до сохранения. В пакете есть клиент демона
ClamAV (`clamd`) — без зависимостей, протокол INSTREAM через TCP или
unix-сокет:

```dotenv
ADMIN_MEDIA_SCANNER=clamd
ADMIN_MEDIA_CLAMD=tcp://127.0.0.1:3310      # или unix:///run/clamav/clamd.ctl
ADMIN_MEDIA_CLAMD_TIMEOUT=30
ADMIN_MEDIA_SCAN_ON_ERROR=reject            # или allow
```

- Файл с угрозой не сохраняется: `MediaService::upload()` бросает
  `InfectedUpload` (с `$threat`), эндпоинт загрузки отвечает 422
  (`errorKey: infected`).
- Если демон не может ответить, `reject` (по умолчанию) отклоняет файл —
  `ScannerUnavailable`, 503 (`scanner_unavailable`); `allow` сохраняет его и
  пишет предупреждение в журнал.
- Оба отказа порождают событие `UploadRefused` (имя файла, угроза, кто
  загружал, причина) — для журнала аудита или уведомления.
- `StreamMaxLength` у clamd держите не меньше `max_size_mb`, иначе большие
  файлы отклоняются как непроверяемые.

Свой сканер реализует `Contracts\UploadScanner` и регистрируется в
контейнере вместо настроенного.
