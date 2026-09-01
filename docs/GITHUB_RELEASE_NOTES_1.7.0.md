# Biopentra Loop Card 1.7.0 — release notes

**Update-delivery migration** — the plugin now self-updates from a private
update server.

## Changed

- Bundled [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
  v5 (`vendor/plugin-update-checker/`). The main plugin file registers it only
  when the `BIOPENTRA_UPDATE_SERVER` constant is defined (set it in
  `wp-config.php`); otherwise no update check runs.
- Removed the bespoke GitHub-release updater (`includes/class-github-updater.php`).
- Added a CI workflow that uploads the release ZIP to the update server on every
  release tag.

## Install

Deploy `biopentra-loop-card` **1.7.0** / tag **`v1.7.0`**. Ensure
`BIOPENTRA_UPDATE_SERVER` is defined in `wp-config.php` on the target site.

Rollback: **1.6.4** / `v1.6.4`.
