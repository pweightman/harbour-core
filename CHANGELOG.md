# Changelog

All notable changes to this plugin are documented here. Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-08-24
### Added
- Initial plugin skeleton: header, guards, version constants.
- Self-updating from GitHub Releases via Plugin Update Checker v5.7.
- `uninstall.php` that preserves customer records by default (deletion is explicit opt-in only).
- Release CI: tag/header version guard, `php -l` lint, distribution zip with the correct wrapping folder.
