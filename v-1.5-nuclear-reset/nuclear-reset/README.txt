=== Nuclear Reset ===
Contributors:       yourname
Tags:               reset, database, development, staging, tools
Requires at least:  6.0
Tested up to:       6.7
Requires PHP:       8.0
Stable tag:         1.0.2
License:            GPL-2.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Restore WordPress to its original out-of-the-box state with a single click.

== Description ==

**Nuclear Reset** gives administrators a safe, confirmed, UI-driven way to wipe a WordPress site back to factory defaults. It is designed for **development and staging environments** where you need to iterate quickly.

**What it does:**

* 🗑️ **Deletes all content** — posts, pages, custom post types, media records, comments, terms, and non-admin users
* 🗄️ **Resets the database** — drops all plugin/custom tables, truncates core tables, and re-seeds WordPress defaults (Hello World, Sample Page, default categories)
* 🔌 **Deactivates all plugins** — every plugin except Nuclear Reset itself is deactivated (files are not deleted)
* 🎨 **Activates the default theme** — switches to the newest bundled WordPress default theme (Twenty Twenty-Five and below)
* ⚙️ **Resets site options** — clears widgets, menus, transients, and rewrite rules; the site title and tagline go back to the WordPress defaults; site URL, admin email, and admin credentials are preserved
* 🧹 **Removes broken themes** — theme folders missing their stylesheet are deleted, so they no longer show up under Appearance → Themes → "Broken Themes"

**Safety features:**

* Requires typing `RESET` in a confirmation field before the button is enabled
* Two-step confirmation (typed phrase + browser `confirm()` dialog)
* Current administrator account and credentials are always preserved
* Site URL and admin email survive every reset; site title and tagline are reset to the WordPress factory defaults
* Missing core options are backfilled with WordPress defaults after a DB wipe
* Deleting theme files is refused if the active theme is not a bundled default
* `wp-config.php` and uploaded/plugin files are never touched
* Granular checkboxes — choose exactly which steps to run
* Full colour-coded log output after every reset

== Installation ==

1. Upload the `nuclear-reset` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen
3. Navigate to **Tools → Nuclear Reset**

⚠️ **Only install on development or staging sites. Never on production.**

== Frequently Asked Questions ==

= Will this delete my theme or plugin files? =
No. Only database records are modified. Your uploaded files, theme folders, and plugin folders are untouched.

= Can I undo a reset? =
No. There is no undo. Take a full backup before running this tool.

= What happens to my admin account? =
Your current administrator account is always preserved, including its username, password, and email.

= Is this safe for production? =
**No.** This plugin is intended exclusively for local, development, and staging environments.

== Changelog ==

= 1.0.2 =
* **Fix:** the site title (`blogname`) and tagline (`blogdescription`) are now reset to the WordPress defaults instead of being restored from the pre-reset snapshot, so Settings → General really goes back to its out-of-the-box state.
* **Fix:** broken theme folders (directories without a `style.css`, e.g. "Stylesheet is missing") are deleted by a dedicated sweep that runs independently of "Delete Custom Theme Files" — they no longer survive the reset and keep appearing under Appearance → Themes → "Broken Themes". Every registered theme root is scanned and the cached theme-directory scan is invalidated afterwards.

= 1.0.1 =
* **Fix:** `html_type` is now snapshotted. Wiping the options table used to drop it, which made WordPress send `Content-Type: ; charset=UTF-8`. Combined with the `X-Content-Type-Options: nosniff` header, browsers then rendered the page as raw HTML source instead of the site.
* **Fix:** after a DB wipe, any other missing core option is backfilled from the WordPress defaults via `populate_options()`, so future core options can no longer be silently lost. Option caches are flushed afterwards so the rest of the request sees the restored values.
* **Fix:** the active-theme pointers (`template`, `stylesheet`, `current_theme`) are snapshotted when the theme is neither switched nor deleted, so a database-only reset no longer silently changes the active theme.
* **Fix:** "Delete Custom Theme Files" now refuses to run when the active theme is not a bundled default theme, preventing the "The theme directory does not exist" fatal screen.
* **Fix:** "Delete Custom Plugin Files" deactivates plugins first when the "Deactivate Plugins" step was not selected, so `active_plugins` never keeps dangling entries.
* Polished: `html_type` is also included in the explicit option-defaults reset.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Resets the site title and tagline to the WordPress defaults and removes broken theme folders that used to survive the reset.

= 1.0.1 =
Fixes a bug where a database reset removed the `html_type` option, causing the front end to display raw HTML source instead of the rendered page.

= 1.0.0 =
Initial release.
