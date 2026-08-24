# Harbour Core

Companion WordPress plugin for Harbour Tree Care. Holds everything the site *does* — as opposed to how it looks — so it survives a theme change: post types, quote enquiries with photo upload, firewood ordering (pay on delivery, postcode radius check), reviews, job gallery, JSON-LD schema, and business settings.

Installed as `wp-content/plugins/harbour-core`. Requires WordPress 6.5+ and PHP 8.1+. Text domain `harbour-core`, British English throughout.

## Why a plugin, not the theme

If the design is ever rebuilt, the log orders, enquiry history, reviews and job gallery are untouched. The rule: if it would need to survive a redesign, it lives here. The theme may *render* a log-order form; it must not *define* one. Templates for plugin-owned content live in `templates/` with a theme-override lookup, the way WooCommerce does it.

## Data safety

`uninstall.php` **does not** delete enquiries, orders or reviews by default — removing a plugin should not destroy a business's customer records. Deletion is an explicit opt-in in settings.

The plugin stores names, phone numbers, addresses and photographs of people's property: personal data under UK GDPR. A personal-data exporter and eraser, consent logging, and configurable retention are part of the spec (`privacy.php`).

## Self-updating

Updates appear in **Plugins** whenever a release is tagged here, via a vendored copy of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) v5.7 (`vendor/`, committed).

## Releasing

The `Version:` header in `harbour-core.php` is the single source of truth and **must** equal the release tag minus the `v`. CI enforces this.

```bash
# bump Version: in harbour-core.php, update CHANGELOG.md, commit
git commit -am "chore(release): v0.1.1"
git tag v0.1.1
git push origin main --tags
```
