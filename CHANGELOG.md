# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/jeffersongoncalves/laravel-github-stats/compare/v1.1.2...HEAD)

## [1.0.0](https://github.com/jeffersongoncalves/laravel-github-stats/releases/tag/v1.0.0) - 2026-02-27

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

## [v1.1.2](https://github.com/jeffersongoncalves/laravel-github-stats/compare/v1.1.1...v1.1.2) - 2026-02-28

### Bug Fix

- **Streak card**: Fixed all sections rendering in the upper-left corner. CSS `transform: translateY()` in the fade-in animation was overriding the SVG `transform` attribute, resetting all group positions to (0,0). Animation now only animates `opacity`.

## [v1.1.1](https://github.com/jeffersongoncalves/laravel-github-stats/compare/v1.1.0...v1.1.1) - 2026-02-28

### Bug Fixes

- **Streak card**: Fixed positioning of ring and fire icon overlapping with other sections. Reduced ring size and aligned all three sections to the same vertical rhythm.
- **Trophies card**: Fixed SVG parse error caused by Blade escaping quotes in `fill-opacity` attribute. The `no_bg` parameter now works correctly.

## [v1.1.0](https://github.com/jeffersongoncalves/laravel-github-stats/compare/v1.0.0...v1.1.0) - 2026-02-28

### What's Changed

#### Streak Card - All-Time Data

- Streak card now fetches contribution data for **all years** since account creation (not just the last ~1 year)
- Added `createdAt` to GraphQL query and new `fetchAllTimeContributions()` method
- Total Contributions date range now shows full account lifetime (e.g. "Sep 22, 2010 - Present")
- Visual improvements: ring around current streak number, fade-in animations, fire icon repositioned, icon-colored current streak value

#### Trophies Card - New Parameters & Dark Theme Support

- New `column` parameter (1-10) to control trophy grid layout
- New `no_frame` parameter to remove trophy card borders
- New `no_bg` parameter for transparent trophy card backgrounds
- Dark-theme-aware trophy colors: gold/silver/bronze text colors now adapt based on background luminance
- Improved icon opacity (0.6) and font sizes in dark themes

#### Tests

- Updated test fakes with `createdAt` and `Http::sequence()` for multi-call GraphQL
- Added tests for `column`, `no_frame`, `no_bg` trophies parameters
- Added test for streak `created_at` date display
