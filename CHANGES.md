# Changelog

All notable changes to this project will be documented in this file.

## [1.0.3] - 2026-01-30

### Added
- CalDAV synchronization for automatic calendar integration
- Remote work entries are automatically synced to user calendars when created/modified/deleted
- Configurable via System Settings (URL, service account credentials)
- Supports any CalDAV-compatible server (Kopano, Nextcloud, etc.)
- Manual resync button per month (icon button with tooltip)
- Sync respects approval workflow: only approved entries are synced
- Rejected entries are removed from calendar

### Changed
- Refactored iCal generation into `IcalHelper` service (shared by export and CalDAV)
- iCal SUMMARY now includes comment (e.g., "Homeoffice: Project work")
- All iCal texts are now translated (DE/EN)
- English translation: "Home Office" → "Working from home"

## [1.0.2] - 2026-01-30

### Added
- iCal export for remote work entries (per year/user)
- Entries can be imported into calendar apps (Outlook, Google Calendar, Apple Calendar, etc.)
- Uses RFC 5545 compliant format with UID for duplicate prevention

## [1.0.1] - 2026-01-29

### Added
- Excel export for remote work entries (per year/user)
- Export button in page toolbar (top right)
- `RemoteWorkPageActionSubscriber` for page-level actions

## [1.0.0] - 2026-01-29

### Added
- Initial release
- Day-based tracking for homeoffice and business trips
- Full and half day support
- Date range selection with automatic working day detection
- Overlap validation with existing remote work entries
- Overlap validation with absences and holidays (requires WorkContractBundle)
- Optional approval workflow
- Batch approval and rejection
- Integration with working time overview
- Colored icons for homeoffice (blue) and business trips (orange)
- Comment support with tooltip display
- Statistics dashboard
- German and English translations
