# Changelog

All notable changes to this plugin are documented here. Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/).

## [Unreleased]

## [0.5.0] - 2026-08-25
### Added
- `/llms.txt` — a plain-text guide for AI assistants (per llmstxt.org), generated from business settings and published services/areas/pages so it stays in sync; held/draft pages are excluded automatically. Rewrite rules auto-flush after a plugin update.

### Note
- The XML sitemap is provided by WordPress core at `/wp-sitemap.xml` (already linked from robots.txt); no change needed.

## [0.4.0] - 2026-08-25
### Added
- Enquiry notification email now embeds the uploaded photos inline (CID) and lists every detail with clickable phone/email, so whoever quotes has the complete enquiry in the email and never needs to log in. Uses the resized "large" image to keep the message small; Reply-To is the customer.
### Changed
- Customer enquiry photos are hidden from the Media Library (grid + list) — kept with the enquiry record and the email, but out of the way since they're not reused elsewhere.

## [0.3.1] - 2026-08-25
### Changed
- Hold Hedge cutting (service kept as draft) and Prices (page kept as draft) until confirmed — both removed from the navigation. Emergency stays live. The refresh action now honours a per-item status so held pages stay held.

## [0.3.0] - 2026-08-25
### Added
- New service: Hedge cutting. Bundled default content for all services and areas (`includes/content/content-data.json`).
- Setup tool "Load / refresh site content" — upserts services/areas and the standalone pages by slug from the bundled copy (no duplicates), for wp-admin-only hosts.
- Per-page SEO title + meta description fields (`_harbour_seo_*`), output in the head; archive SEO for the service/area hubs.
### Changed
- Refreshed service and area copy to the revised SEO-optimised prototype; internal "confirm before publishing" notes stripped from shipped copy.
- Navigation updated: Hedge cutting + Prices under Services, Emergency top-level, Nuneaton & Bedworth.

## [0.2.1] - 2026-08-25
### Added
- "Harbour → Setup" one-click tool (for hosts without wp-cli): build the navigation menus, load starter settings, and set the front page. Idempotent, nonce- and capability-protected.

## [0.2.0] - 2026-08-24
### Changed
- Coding standards: the whole plugin is now clean against WordPress-Extra (PHPCS), enforced in CI. Nonce checks moved ahead of any `$_POST` read; output escaping, translator comments and short-ternary/style fixes throughout.

### Added
- Firewood log ordering (Module 2): products/radius/slots/VAT settings, order form (pay on delivery), postcode delivery-radius check via postcodes.io + haversine (inside/outer/outside/unknown bands, 30-day coord cache), order storage, admin (columns, status new→confirmed→delivered→cancelled, delivery date, distance, CSV). Never loses an order if the geocoder is down.
- Reviews (Module 3): testimonial post type, review-fields metabox with a standing "reviews must be genuine" notice, `[harbour_reviews]` shortcode + render function, Review schema (real, named reviews only) and AggregateRating (only from a real admin-entered figure — no fabrication).
- Job gallery (Module 4): job post type, before/after image-picker metabox (WP media modal), filterable grid + `[harbour_gallery]` shortcode, ImageObject pair schema, archive-job/single-job rendering.
- PHPUnit: haversine, radius-band classification, price parsing and order-total/minimum-order rules (36 tests total).

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
