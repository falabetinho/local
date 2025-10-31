# Local Custom Admin Plugin

A custom administrative panel plugin for Moodle 4.4+ that provides enhanced administrative functionality, user management, and password reset capabilities.

## ✨ Features

- 📊 Custom administrative dashboard with system statistics
- 👥 User management with advanced filtering
- 🔑 **Password reset feature** with secure modal dialog
- 📚 Course and category overview
- 📋 Administrative reports
- 🔐 Role-based access control with capabilities
- 🌐 Internationalization support (English, Portuguese-BR)
- ⚡ Modern AMD module architecture
- 🔒 Secure webservice with POST method

## 📋 Requirements

- Moodle 4.4 or higher
- PHP 7.4 or higher
- Node.js (for development)
- npm/yarn (for dependency management)

## 🚀 Installation

1. Copy the plugin files to `/local/localcustomadmin/` in your Moodle installation
2. Log in as an administrator
3. Go to **Site Administration → Notifications**
4. Follow the installation prompts
5. Run: `php admin/cli/purge_caches.php`

## 📖 Usage

After installation, users with appropriate permissions can access the plugin through:

- **Site Administration → Local Custom Admin**
- **Navigation menu → Local Custom Admin**

### Password Reset

1. Navigate to **Administration → Users Management**
2. Click the **Reset Password** button (🔑 icon) for any user
3. Enter the new password in both fields
4. Click **Save**
5. Success notification will appear

### Capabilities

The plugin defines two capabilities:

- `local/localcustomadmin:view` - Allows viewing the administrative panel
- `local/localcustomadmin:manage` - Allows managing users and resetting passwords

## 📁 File Structure

```
localcustomadmin/
├── amd/
│   ├── src/
│   │   ├── reset_password.js    # Modal logic for password reset
│   │   └── usuarios.js          # Table integration and initialization
│   └── build/                   # Minified output (auto-generated)
├── classes/
│   ├── admin.php
│   └── webservice/
│       └── user_handler.php     # Password reset webservice
├── db/
│   ├── access.php               # Capabilities definition
│   └── services.php             # External webservice registration
├── lang/
│   ├── en/
│   │   └── local_localcustomadmin.php
│   └── pt_br/
│       └── local_localcustomadmin.php
├── styles/
│   └── styles.css               # Custom styling
├── templates/
│   ├── categorias.mustache
│   ├── cursos.mustache
│   ├── index.mustache
│   └── usuarios.mustache        # Users table with reset button
├── version.php
├── Gruntfile.js                 # Build configuration
├── package.json                 # Dependencies
└── README.md
```

## 🔧 Development

### Building JavaScript

```bash
cd local/localcustomadmin

# Install dependencies (first time only)
npm install

# Build AMD modules
grunt amd

# Watch for changes during development
npm run watch
```

### Project Architecture

**Frontend (JavaScript/AMD)**
- `reset_password.js` - Password reset modal with form validation
- `usuarios.js` - Integrates reset functionality with user table

**Backend (PHP)**
- `user_handler.php` - Webservice implementation for password reset
- `services.php` - Registers the external webservice

## 🔒 Security Features

✅ Passwords sent via **POST** (never in URL)
✅ Context and capability validation
✅ Password policy enforcement
✅ Secure password hashing (`hash_internal_user_password()`)
✅ Audit trail with `user_password_updated` event
✅ Only administrators with `manage` capability can reset passwords

## 🌍 Languages

Currently supported:
- 🇺🇸 English (en)
- 🇧🇷 Portuguese Brazil (pt_br)

To add a new language:
1. Create `lang/{locale}/local_localcustomadmin.php`
2. Copy string keys from English version
3. Translate strings

## 🐛 Troubleshooting

### Modal doesn't open
```
- Check browser console (F12) for JavaScript errors
- Verify AMD modules are compiled: grunt amd
- Clear Moodle and browser caches
- Ensure usuarios.js module is loaded
```

### Password reset fails
```
- Check webservice registration in admin panel
- Verify user has local/localcustomadmin:manage capability
- Check PHP error logs
- Run: php admin/cli/purge_caches.php
```

### Webservice not found (404)
```
- Clear Moodle caches: php admin/cli/purge_caches.php
- Verify db/services.php is properly formatted
- Check version.php is updated
```

## 📚 Dependencies

**AMD Modules Used:**
- `core/modal_factory` - Modal dialog creation
- `core/str` - Language string loading
- `core/ajax` - AJAX webservice calls
- `core/notification` - User notifications
- `jquery` - DOM manipulation

**Build Tools:**
- `grunt` - Task runner
- `grunt-contrib-uglify` - JavaScript minification
- `grunt-contrib-watch` - File watching

## ✍️ Contributing

When contributing to this project:

1. Follow Moodle Coding Standards
2. Update language files for new strings
3. Test in both English and Portuguese
4. Run `grunt amd` before committing
5. Use meaningful commit messages
6. Update README for new features

## 📜 License

GNU General Public License v3 or later

See LICENSE file for details.

## 👤 Author

2025

## 📞 Support

For bugs and feature requests, please contact your administrator or check the project repository.

## 🎯 Roadmap

Planned features:
- [ ] Email notification when password is reset
- [ ] Bulk password reset
- [ ] Password reset history log
- [ ] Two-factor authentication support
- [ ] API documentation
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