# Changelog

All notable changes to `tftp-client` will be documented in this file

## 2.0.0 - 2026-03-29

### Added
- Support for Laravel 10, 11 and 12
- Support for PHP 8.2, 8.3 and 8.4
- Full test suite with Pest (73 tests)
- Socket receive timeout (5 seconds) to prevent infinite blocking
- Socket cleanup in destructor to prevent resource leaks
- Config validation with clear error on missing/invalid connection
- File readability check before upload
- Empty file upload support per RFC 1350

### Fixed
- RFC 1350 compliance: files are now split into 512-byte blocks with individual ACKs
- Block number encoding overflow for files larger than 128KB (using `pack('n')` for 16-bit values)
- `getServerResponse()` returning boolean instead of string causing TypeError on write operations
- `get()` logging last packet size instead of total file size
- Opcode encoding now uses proper 16-bit big-endian format in all packets

### Changed
- Unified `sendReadPacket()`/`sendWritePacket()` into `sendRequestPacket()`
- Simplified `Loggable` trait from 8 match arms to single `Log::log()` call
- Separated `getServerResponse()` into `receiveData()` and `receiveAck()` for type safety
- Updated CI workflow with proper matrix for all supported PHP/Laravel combinations
- Minimum PHP version bumped to 8.2 (Pest 2 dependency requires it)

### Breaking
- Dropped Laravel 8 and 9 support
- Requires PHP ^8.2 (was ^8.1)
- Requires `illuminate/support` ^10.0|^11.0|^12.0

## 1.0.0 - 2023-11-25

- initial release
