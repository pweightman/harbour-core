# Changelog

All notable changes to this plugin are documented here. Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/).

## [Unreleased]
### Added
- Hero-heading + repeatable FAQ meta for service/area posts, with editor metaboxes (`admin/content-meta.php`).
- Service and FAQPage JSON-LD on singular service/area pages (areaServed set to the town on area pages).
- Content template tags: `harbour_hero_heading()`, `harbour_get_faq()`, `harbour_render_faq()`.

### Added
- Post types: `service`, `area` (public) and `enquiry` (private, editor-only, never public/searchable), plus shared `service_type` / `service_area` taxonomies.
- Harbour settings screen (Business + Enquiries tabs), one option array with a single sanitise pass; every NAP fact the templates print now lives here.
- JSON-LD schema from settings: `LocalBusiness` + `HomeAndConstructionBusiness`, `WebSite`, `BreadcrumbList`. No fabricated ratings. `/thank-you/` set to noindex.
- Quote enquiries (Module 1): self-posting form with accessible inline errors, nonce, honeypot + submission-timing, per-IP rate limit, UK-postcode validation, EXIF/GPS-stripped photo upload (MIME verified by content), record-first storage that survives a mail failure, yard + customer emails, and redirect to /thank-you/.
- Enquiry admin: list columns (name/phone/service/postcode/status), triage metabox (status, notes, inline photos, consent record), and CSV export — all capability-checked and nonce-protected.
- PHPUnit tests for the pure logic (postcode normalise/validate, honeypot/timing) and a Tests CI workflow.


## [0.1.1] - 2026-08-24
### Changed
- Proved the self-update pipeline end to end: release detection from GitHub Releases.

## [0.1.0] - 2026-08-24
### Added
- Initial plugin skeleton: header, guards, version constants.
- Self-updating from GitHub Releases via Plugin Update Checker v5.7.
- `uninstall.php` that preserves customer records by default (deletion is explicit opt-in only).
- Release CI: tag/header version guard, `php -l` lint, distribution zip with the correct wrapping folder.
