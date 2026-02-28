# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-02-27

### Added

- GitHub Stats SVG card (`/api/stats`)
- Top Languages SVG card (`/api/top-langs`)
- Contribution Streak SVG card (`/api/streak`)
- Achievement Trophies SVG card (`/api/trophies`)
- Health check endpoint (`/api/health`)
- GitHub GraphQL and REST API integration
- Two-layer caching (data + rendered SVG)
- 6 built-in themes (tokyonight, dark, radical, merko, gruvbox, onedark)
- Custom color overrides via query parameters
- Username lock middleware for security
- Scheduled cache refresh command (`github:refresh`)

[Unreleased]: https://github.com/jeffersongoncalves/laravel-github-stats/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jeffersongoncalves/laravel-github-stats/releases/tag/v1.0.0
