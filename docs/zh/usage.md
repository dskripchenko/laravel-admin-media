---
title: 使用
locale: zh
status: stub
translated_from: ../en/usage.md
---

# 使用

> ⚠️ **此页面尚未完全翻译。** 在此期间请阅读[英文版本](../en/usage.md)。


## 上传扫描

每个上传文件都可以在保存前进行扫描。本包自带 ClamAV 守护进程（`clamd`）客户端——无依赖，通过 TCP 或 Unix 套接字使用其 INSTREAM 协议：

```dotenv
ADMIN_MEDIA_SCANNER=clamd
ADMIN_MEDIA_CLAMD=tcp://127.0.0.1:3310      # 或 unix:///run/clamav/clamd.ctl
ADMIN_MEDIA_CLAMD_TIMEOUT=30
ADMIN_MEDIA_SCAN_ON_ERROR=reject            # 或 allow
```

- 含有威胁的文件不会被保存：`MediaService::upload()` 抛出 `InfectedUpload`（带 `$threat`），上传接口返回 422（`errorKey: infected`）。
- 守护进程无法应答时，`reject`（默认）拒绝该文件——`ScannerUnavailable`，503（`scanner_unavailable`）；`allow` 则保存文件并在日志中记录警告。
- 两种拒绝都会触发 `UploadRefused` 事件（文件名、威胁、上传者、原因），可用于审计日志或通知。
- 请将 clamd 的 `StreamMaxLength` 设置为不小于 `max_size_mb`，否则较大的文件会因无法扫描而被拒绝。

自定义扫描器需实现 `Contracts\UploadScanner`，并在容器中替换已配置的扫描器。
