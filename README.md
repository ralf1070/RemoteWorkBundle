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

## Requirements

- Kimai >= 2.32.0
- Kimai core with DayAddon attributes support (see below)

### Core Changes Required

This plugin requires changes to Kimai core that add attribute support to `DayAddon`:

**Repository:** https://github.com/ralf1070/kimai
**Branch:** `feature/day-addon-attributes`

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
