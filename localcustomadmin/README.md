# Local Custom Admin Plugin

A custom administrative panel plugin for Moodle 4.4+ that provides enhanced administrative functionality and dashboard features.

## Features

- Custom administrative dashboard with system statistics
- User management capabilities
- Course and category overview
- Administrative reports
- Role-based access control
- PSR-12 compliant code

## Requirements

- Moodle 4.4 or higher
- PHP 8.1 or higher

## Installation

1. Copy the plugin files to `/local/localcustomadmin/` in your Moodle installation
2. Log in as an administrator
3. Go to Site Administration → Notifications
4. Follow the installation prompts

## Usage

After installation, users with appropriate permissions can access the plugin through:

- Site Administration → Local Custom Admin
- Navigation menu → Local Custom Admin

### Capabilities

The plugin defines two capabilities:

- `local/localcustomadmin:view` - Allows viewing the administrative panel
- `local/localcustomadmin:manage` - Allows managing administrative settings

## File Structure

```
localcustomadmin/
├── classes/
│   └── admin.php          # Main admin utility class
├── db/
│   └── access.php         # Capabilities definition
├── lang/
│   └── en/
│       └── local_localcustomadmin.php  # English language strings
├── dashboard.php          # Administrative dashboard
├── index.php             # Main plugin page
├── lib.php               # Plugin library functions
├── version.php           # Plugin version information
└── README.md             # This file
```

## Development

This plugin follows PSR-12 coding standards and Moodle coding guidelines.

### Key Classes

- `\local_localcustomadmin\Admin` - Main utility class for administrative operations

### Key Functions

- `local_localcustomadmin_extend_navigation()` - Extends Moodle navigation
- `local_localcustomadmin_extend_settings_navigation()` - Adds admin settings

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Support

For support and bug reports, please contact the plugin maintainer.

## Changelog

### Version 1.0 (2025-10-13)
- Initial release
- Basic administrative dashboard
- User and course statistics
- Role-based access control
- English language support