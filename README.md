# Roundcube Catch-All Sender Plugin

A powerful Roundcube webmail plugin that allows users to define catch-all wildcard rules (`*@domain.com`, `*alias@domain.com`, etc.) and automatically creates sender identities when incoming emails match these patterns.

---

## Features

- Wildcard & Catch-All Rules: Create flexible email masks (e.g., `*@domain.com`, `sales-*@domain.com`) to manage inbound addressing dynamically.
- Automatic Identity Creation: Automatically detects incoming messages addressed to wildcard patterns and registers them as valid sender identities in Roundcube.
- Dedicated Settings Interface: A clean, integrated management panel in Roundcube settings to add, edit, delete, and reorder rules.
- Full Localization Support: Includes localization files for over 87 languages, seamlessly integrated with Roundcube's language switcher.
- Database Persistence: Directly synchronizes and saves user rules securely into the Roundcube database backend.

---

## Requirements

- Roundcube Webmail: Version 1.4 or higher.
- PHP: Version 7.3 or higher.
- Mail Server: Compatible with any IMAP server (e.g., Stalwart, Postfix/Dovecot) supporting alias or catch-all routing.

---

## Installation

### Method 1: Manual Installation
1. Download or clone this repository into your Roundcube plugins directory:
   cd path/to/roundcube/plugins
   git clone https://github.com/coyote-studio/roundcube-catch-all-sender.git catch_all_sender
2. Ensure the plugin folder has the correct permissions so your web server can read it.

### Method 2: Docker Integration
If you manage Roundcube via Docker, you can include it automatically using a custom Dockerfile or a startup script that clones the repository into `/var/www/html/plugins/catch_all_sender`.

---

## Configuration

1. Open your Roundcube configuration file (`config/config.inc.php`) or use environment variables.
2. Add `catch_all_sender` to your active plugins list:
   $config['plugins'] = array(
       'archive',
       'zipdownload',
       'catch_all_sender',
   );
   *(Or if using environment variables in Docker: `ROUNDCUBEMAIL_PLUGINS=archive,zipdownload,catch_all_sender`)*

---

## Usage

1. Log in to your Roundcube webmail.
2. Navigate to Settings > Catch-All Sender (or the corresponding localized label).
3. Add your custom rules using wildcards (e.g., `*@coyote.studio`).
4. Configure whether the rule is allowed to automatically generate sender identities, set display names, signatures, and custom reply-to addresses.
5. Save your changes. Incoming emails matching your rules will now automatically provision usable identities for replying!

---

## Localization

This plugin supports multi-language environments out of the box. Translation files are located in the `localization/` directory. If you want to contribute or update translations, ensure your locale `.inc` file matches Roundcube's standard nomenclature.

---

## License

This project is licensed under the GNU AGPLv3 license. See the LICENSE file for details.

---

## Author

Developed with care by Coyote Studio — https://coyote.studio
