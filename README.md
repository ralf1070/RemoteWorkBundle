# RemoteWorkBundle for Kimai

A Kimai plugin for tracking remote work days (homeoffice and business trips).

## Features

- **Day-based tracking** - Record full or half days of remote work
- **Date range support** - Create multiple entries at once with automatic working day detection
- **Two remote work types**:
  - Homeoffice (working from home)
  - Business trips (working while traveling)
- **Overlap validation** - Warns about conflicts with existing remote work, absences, and holidays
- **Optional approval workflow** - Entries can require manager approval
- **Batch actions** - Approve or reject multiple entries at once
- **Working time integration** - Remote work days are displayed in the working time overview with colored icons
- **Statistics dashboard** - Overview of remote work days per type
- **Excel export** - Export remote work entries per year and user
- **iCal export** - Import remote work days into calendar apps (Outlook, Google Calendar, etc.)
- **CalDAV sync** - Automatic synchronization with user calendars (Kopano, Nextcloud, etc.)

## Requirements

- Kimai >= 2.48.0 (includes DayAddon type support)

For the optional `comment` attribute in working time tooltips, the `feature/day-addon-attributes` branch is required:
- **Repository:** https://github.com/ralf1070/kimai
- **Branch:** `feature/day-addon-attributes`

## Installation

1. Clone the repository into the `var/plugins/` directory:
   ```bash
   cd var/plugins/
   git clone https://github.com/YOUR_USERNAME/RemoteWorkBundle.git
   ```

2. Clear the cache:
   ```bash
   bin/console cache:clear
   ```

3. Run database migrations:
   ```bash
   bin/console doctrine:migrations:migrate
   ```

## Configuration

Navigate to **System > Settings > Remote Work** to configure:

- **Approval required** - If enabled, remote work entries must be approved by a supervisor before they appear in the working time overview

### CalDAV Sync (optional)

Enable automatic synchronization of remote work entries to user calendars:

- **CalDAV enabled** - Enable/disable calendar synchronization
- **CalDAV URL** - URL template with `{username}` placeholder (e.g., `https://server.com:8443/caldav/{username}/Calendar/`)
- **CalDAV username** - Service account username with access to all user calendars
- **CalDAV password** - Service account password

When enabled:
- Approved entries are automatically synced to the user's calendar
- A manual sync button appears per month for re-syncing existing entries
- Rejected or deleted entries are removed from the calendar

## Permissions

| Permission | Description |
|------------|-------------|
| `view_own_remote_work` | View own remote work entries |
| `create_own_remote_work` | Create own remote work entries |
| `edit_own_remote_work` | Edit own remote work entries |
| `delete_own_remote_work` | Delete own remote work entries |
| `view_other_remote_work` | View remote work entries of other users |
| `edit_other_remote_work` | Edit remote work entries of other users |
| `delete_other_remote_work` | Delete remote work entries of other users |
| `approve_remote_work` | Approve or reject remote work entries |

## Optional Integration

### WorkContractBundle

If the WorkContractBundle is installed, this plugin will:
- Check for overlaps with absences (vacation, sickness, etc.)
- Check for overlaps with public holidays
- Use working day configuration to skip weekends when creating date ranges

## License

MIT License - see [LICENSE](LICENSE) file for details.
