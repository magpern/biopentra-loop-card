# Biopentra Loop Card 1.2.6

**Canonical standalone release** from [magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card).

## What changed

- GitHub Actions CI and Release on `v*` tags (production ZIP: `biopentra-loop-card-1.2.6.zip`).
- GitHub Release updater points at **this repository**.
- Dev-only `includes/setup-shop-page-cli.php` excluded from production ZIP.

No intentional shop loop behavior changes vs the previous monorepo-only copy at **1.2.6**.

## Install / upgrade

1. Download **`biopentra-loop-card-1.2.6.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.

## Dev sites

Disable the updater with `BIOPENTRA_LOOP_CARD_DISABLE_GITHUB_UPDATER` or deploy via rsync from this repo (not ZIP).

## Rollback

Restore the previous plugin folder from backup.

Changelog: [CHANGELOG.md](../CHANGELOG.md)
