# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP TFTP Client library for Laravel, compliant with RFC 1350. Uses PHP's `ext-sockets` extension for UDP communication. Published as a Composer package (`voipforall/tftp-client`).

## Requirements

- PHP ^8.1 with `ext-sockets`
- Laravel 10.x

## Commands

- **Install dependencies:** `composer install`
- **Run tests:** `composer test` (runs Pest via `vendor/bin/pest`)
- **Run tests with coverage:** `composer test-coverage`

## Architecture

The package lives entirely in `src/` under the `VoIPforAll\TFTPClient` namespace (PSR-4 autoloaded).

**Core class:** `TFTPClient` — implements the TFTP protocol over UDP sockets. Two public methods: `get(filename)` for downloads (returns file content or false) and `put(filename)` for uploads (returns bool). The TFTP protocol uses a handshake of opcodes (RRQ/WRQ -> DATA/ACK) with 512-byte data blocks and 516-byte packets.

**Laravel integration:** `TFTPClientServiceProvider` registers the config (`tftp-client`) and a singleton binding. Config is published to `config/tftp-client.php`. Supports multiple named connections (host/port/transfer_mode), selected via `TFTP_CLIENT_CONNECTION` env var.

**Enums** (backed enums in `src/Enums/`):
- `OpcodeEnum` — TFTP opcodes (READ=1, WRITE=2, DATA=3, ACK=4, ERROR=5)
- `ByteLimitEnum` — DATA (512) and PACKET (516) size limits
- `TransferModeEnum` — netascii, octet, mail
- `LogLevelEnum` — standard PSR log levels

**Logging:** The `Loggable` trait adds optional logging controlled by `TFTP_LOGGING` and `TFTP_LOGGER_CHANNEL` env vars. When enabled, all operations are logged through Laravel's Log facade.

**Exceptions:** `ServerException` (TFTP server errors), `UnknowOpcodeException`, `UnknowLogLevelException`.

## CI

GitHub Actions workflow (`.github/workflows/main.yml`) runs tests on push/PR to main. Note: the CI matrix references older PHP/Laravel versions (7.4/8.0, Laravel 8) which is out of sync with composer.json (PHP 8.1+, Laravel 10).
