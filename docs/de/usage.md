---
title: Verwendung
locale: de
status: stub
translated_from: ../en/usage.md
---

# Verwendung

> ⚠️ **Diese Seite ist noch nicht vollständig übersetzt.** Bitte nutzen Sie vorerst die [englische Version](../en/usage.md).


## Uploads prüfen

Jeder Upload kann vor dem Speichern geprüft werden. Das Paket bringt einen
Client für den ClamAV-Daemon (`clamd`) mit — ohne Abhängigkeiten, dessen
INSTREAM-Protokoll über einen TCP- oder Unix-Socket:

```dotenv
ADMIN_MEDIA_SCANNER=clamd
ADMIN_MEDIA_CLAMD=tcp://127.0.0.1:3310      # oder unix:///run/clamav/clamd.ctl
ADMIN_MEDIA_CLAMD_TIMEOUT=30
ADMIN_MEDIA_SCAN_ON_ERROR=reject            # oder allow
```

- Eine Datei mit Bedrohung wird nicht gespeichert: `MediaService::upload()`
  wirft `InfectedUpload` (mit `$threat`), der Upload-Endpunkt antwortet 422
  (`errorKey: infected`).
- Kann der Daemon nicht antworten, lehnt `reject` (Standard) die Datei ab —
  `ScannerUnavailable`, 503 (`scanner_unavailable`); `allow` speichert sie und
  schreibt eine Warnung ins Log.
- Beide Ablehnungen lösen `UploadRefused` aus (Dateiname, Bedrohung,
  Hochladender, Grund) — für ein Audit-Log oder eine Benachrichtigung.
- Halten Sie `StreamMaxLength` von clamd mindestens bei `max_size_mb`, sonst
  werden größere Dateien als nicht prüfbar abgelehnt.

Ein eigener Scanner implementiert `Contracts\UploadScanner` und wird im
Container anstelle des konfigurierten gebunden.
