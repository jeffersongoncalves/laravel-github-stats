## Laravel GitHub Stats

### Overview

A self-hosted Laravel package that serves dynamic SVG cards with GitHub statistics.
It fetches data via GitHub GraphQL/REST APIs, caches results, and renders SVG views
for stats, top languages, contribution streaks, and trophies.

### Key Concepts

- **GitHubService**: Core service that fetches user data, stats, languages, streaks, and trophies
- **ThemeService**: Resolves color themes (tokyonight, dark, radical, merko, gruvbox, onedark) with custom overrides
- **StreakCalculator**: Computes current/longest contribution streaks from calendar data
- **StatsController**: Renders cached SVG responses for each card type
- **LockUsername middleware**: Prevents querying other users' stats (private by design)

### SVG Card Endpoints

@verbatim
<code-snippet name="routes" lang="php">
// All routes use prefix from config('github-stats.route_prefix', 'api')
// Middleware: throttle:github-stats, github-stats.lock-username
GET /{prefix}/stats       // User stats card (stars, commits, PRs, issues, followers)
GET /{prefix}/top-langs   // Most used languages card
GET /{prefix}/streak      // Contribution streak card
GET /{prefix}/trophies    // Achievement trophies card
GET /{prefix}/health      // JSON health check endpoint
</code-snippet>
@endverbatim

### Query Parameters

@verbatim
<code-snippet name="theme-params" lang="php">
// Theme selection
?theme=tokyonight  // Available: tokyonight, dark, radical, merko, gruvbox, onedark

// Custom color overrides (hex without #)
?bg_color=0d1117&title_color=58a6ff&text_color=c9d1d9&icon_color=3fb950&border_color=30363d

// Border control
?hide_border=true

// Trophies-specific
?column=4&no_frame=true&no_bg=true
</code-snippet>
@endverbatim

### Caching Strategy

@verbatim
<code-snippet name="caching" lang="php">
// Cache keys used by GitHubService
'github:user_stats'  // Raw stats data (data_ttl: 4 hours)
'github:languages'   // Language breakdown (data_ttl: 4 hours)
'github:streak'      // Streak calculations (data_ttl: 4 hours)
'github:trophies'    // Trophy levels (data_ttl: 4 hours)
'github:svg:{card}:{hash}' // Rendered SVGs (svg_ttl: 1 hour)

// Refresh all caches
php artisan github:refresh
</code-snippet>
@endverbatim

### Configuration

@verbatim
<code-snippet name="config-keys" lang="php">
// config/github-stats.php
'username'     => env('GITHUB_USERNAME', 'jeffersongoncalves'),
'token'        => env('GITHUB_TOKEN'),
'cache.data_ttl' => env('GITHUB_STATS_DATA_TTL', 14400),
'cache.svg_ttl'  => env('GITHUB_STATS_SVG_TTL', 3600),
'defaults.theme' => env('GITHUB_STATS_THEME', 'tokyonight'),
'defaults.hide_border' => false,
'defaults.langs_count' => 8,
'route_prefix' => env('GITHUB_STATS_ROUTE_PREFIX', 'api'),
'middleware'   => [],
</code-snippet>
@endverbatim

### Conventions

- Services are registered as singletons: `ThemeService`, `StreakCalculator`, `GitHubService`
- SVG responses include proper `Content-Type`, `Cache-Control`, `ETag`, and `Expires` headers
- Rate limiting: 60 requests per minute per IP
- Trophy levels: S > A > B > C > D > none (based on configurable thresholds)
- All-time contributions fetched year by year from account creation date
