# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries for releases published before this file existed were reconstructed from
the tagged commit history.

## [Unreleased]

### Fixed
- The media library table had column headers made from the column names
  ("Path", "Mime", "Created at"…), English in every panel language. Every
  column now carries a label, a source string translated per request.

## [1.5.1] — 2026-10-02

### Fixed
- A cropped variant (`'crop' => true` in a responsive set) threw a `TypeError`
  when the media had no focal point: right after `MediaService::upload()` the
  model does not carry the column defaults yet, and a host may store `null`.
  `ImageProcessor::cropToBox()` now takes `?float` focal coordinates, treats
  `null` as the centre and clamps values outside `[0, 1]`.

## [1.5.0] — 2026-10-02

### Added
- `Fields\MediaPicker` — a form field that picks files from the library in a
  dialog with thumbnails, search, filters, pagination and upload; a preset of
  the core's `ResourcePicker` with `images()`, `collection()`, `mime()`,
  `responsiveSet()`, `withoutUpload()`, `multiple()` and `maxItems()`.
- `MediaResource` describes its records for pickers and global search: the
  title (title, alt or file name), the MIME type with the image dimensions, and
  a thumbnail preview (the `thumb` variant, the smallest variant or the
  original image).

### Changed
- Requires `dskripchenko/laravel-admin` ^1.36 (the release with `ResourcePicker`).
- The collection filter matches the collection name exactly (`articles` no
  longer also lists `articles-archive`).
- The media list loads the variants with the records.

### Fixed
- The upload endpoint is now `POST /api/admin/media/library/upload`. The old
  `media/upload` path has the same shape as laravel-api's generic
  `api/{version}/{controller}/{action}` route, which is registered first, so in
  a real host it answered 404 "The requested method was not found". The old
  path is still registered.
- The "Type" filter (`mime_kind`) queried a column that does not exist; it now
  matches the start of the MIME type.

## [1.4.0] — 2026-10-01

### Changed
- `AdminMediaPlugin::version()` now reports the installed package version
  (via `Composer\InstalledVersions`) instead of a hardcoded `0.1.0`; falls back
  to `dev` when the version cannot be resolved.
- Minimum supported `dskripchenko/laravel-admin` raised to `^1.30`.
- Composer `suggest` descriptions translated to English.

### Added
- English translations of the admin UI strings (`resources/lang/en.json`),
  loaded through Laravel JSON translations; hosts can override them with their
  own `lang/{locale}.json`.
- Weekly scheduled CI run to catch upstream dependency breakage.

### Fixed
- Documentation referred to a non-existent `media-config` publish tag; the
  correct tag is `admin-media-config`.
- Getting-started guide pointed to `config/media.php` and `/admin/r/media`;
  corrected to `config/admin-media.php` and `/admin/r/media-library`.

## [v1.3.0] - 2026-07-20

### Changed
- Supported versions moved to the canonical matrix: PHP 8.2-8.5 with Laravel 11, 12 and 13.

### Added
- GitHub Actions pipeline covering the whole support matrix.
- Documentation in German, Russian and Chinese alongside the English default.

## [v1.2.0] - 2026-05-01

### Changed
- Version aligned with the admin core release line. No functional changes.

## [v1.0.0] - 2026-05-01

### Added
- First standalone release, extracted from the laravel-admin monorepo.
- Packagist metadata: description, keywords, authors and support links.
