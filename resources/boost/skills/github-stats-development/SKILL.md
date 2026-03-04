---
name: github-stats-development
description: Development guide for the Laravel GitHub Stats package - self-hosted SVG cards for GitHub statistics, streaks, languages, and trophies
---

## When to use this skill

- Adding new SVG card types (e.g., contribution graph, repo cards)
- Adding new themes to ThemeService
- Modifying trophy thresholds or adding new trophy categories
- Customizing cache strategies or TTLs
- Writing tests for GitHub API interactions
- Understanding the GraphQL queries and data flow

## Setup

### Requirements

- PHP 8.2+
- Laravel 11 or 12
- GitHub Personal Access Token (scopes: `read:user`, optionally `repo`)
- spatie/laravel-package-tools ^1.14.0

### Installation

```bash
composer require jeffersongoncalves/laravel-github-stats
```

### Environment Variables

```env
GITHUB_USERNAME=your-username
GITHUB_TOKEN=ghp_your_personal_access_token
GITHUB_STATS_DATA_TTL=14400
GITHUB_STATS_SVG_TTL=3600
GITHUB_STATS_THEME=tokyonight
GITHUB_STATS_ROUTE_PREFIX=api
```

### Publish Config

```bash
php artisan vendor:publish --tag=github-stats-config
```

## Architecture

### Namespace Structure

```
JeffersonGoncalves\GitHubStats\
    GitHubStatsServiceProvider          # Registers services, middleware, rate limiter
    Commands\
        RefreshGitHubStatsCommand       # php artisan github:refresh
    Http\
        Controllers\
            StatsController             # Renders SVG cards (stats, langs, streak, trophies, health)
        Middleware\
            LockUsername                # Blocks requests for other usernames
    Services\
        GitHubService                   # Fetches data from GitHub API, manages cache
        ThemeService                    # Resolves color themes with overrides
        StreakCalculator                # Computes current/longest streaks from contribution days
```

### Service Provider Registration

```php
// Services are singletons
$this->app->singleton(ThemeService::class);
$this->app->singleton(StreakCalculator::class);
$this->app->singleton(GitHubService::class, function ($app) {
    return new GitHubService($app->make(StreakCalculator::class));
});

// Rate limiter: 60/min per IP
RateLimiter::for('github-stats', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

// Middleware alias
$router->aliasMiddleware('github-stats.lock-username', LockUsername::class);
```

## Features

### SVG Card Endpoints

All routes are grouped under a configurable prefix with throttle and lock-username middleware:

```php
// routes/api.php
Route::group([
    'prefix' => config('github-stats.route_prefix', 'api'),
    'middleware' => ['throttle:github-stats', 'github-stats.lock-username', ...],
], function () {
    Route::get('/stats', [StatsController::class, 'stats']);
    Route::get('/top-langs', [StatsController::class, 'topLangs']);
    Route::get('/streak', [StatsController::class, 'streak']);
    Route::get('/trophies', [StatsController::class, 'trophies']);
    Route::get('/health', [StatsController::class, 'health']);
});
```

### GitHub Data Fetching

The `GitHubService` uses GitHub's GraphQL API with pagination to fetch all repositories:

```php
// Fetches: name, login, avatarUrl, followers, following, repositories (paginated),
// contributionsCollection (commits, issues, PRs, reviews, calendar)
$userData = $this->fetchUserData();

// REST API for all-time commit count
$allTimeCommits = $this->fetchAllTimeCommits();

// Year-by-year contributions from account creation to now
$allTime = $this->fetchAllTimeContributions();
```

### Stats Card Data

```php
$stats = $github->getStats();
// Returns: name, total_stars, total_commits, total_prs, total_issues,
//          total_reviews, followers, total_repos
```

### Languages Card Data

```php
$languages = $github->getLanguages();
// Returns top N languages (default 8) with: name, color, size, percentage
// Aggregated from all repositories' language edges
```

### Streak Calculation

The `StreakCalculator` processes contribution calendar days:

```php
$result = $streakCalculator->calculate($days);
// Returns: current_streak, longest_streak, total_contributions,
//          current_streak_start, current_streak_end,
//          longest_streak_start, longest_streak_end
```

Current streak allows today to have zero contributions (checks yesterday).

### Trophy System

Trophies are awarded based on thresholds with levels S, A, B, C, D, or none:

```php
// Trophy categories and S-tier thresholds:
// Stars: 1000, Commits: 5000, Pull Requests: 500, Issues: 500,
// Repos: 100, Followers: 1000, Reviews: 500
```

### Theme System

Six built-in themes with five color slots each (bg, title, text, icon, border):

```php
$theme = app(ThemeService::class)->resolve('tokyonight', [
    'bg_color' => '0d1117',     // Override specific colors
    'title_color' => 'ff0000',  // Hex values without #
]);

// Available themes: tokyonight, dark, radical, merko, gruvbox, onedark
$themes = app(ThemeService::class)->availableThemes();
```

### Cache Management

```php
// Refresh all caches (clears data + SVG caches, re-warms data)
$github->refreshCache();

// Artisan command
php artisan github:refresh

// Cache keys:
// github:user_stats, github:languages, github:streak, github:trophies
// github:svg:{card}:{hash} (hash based on theme/color query params)
// github:last_refresh (ISO 8601 timestamp)
```

### SVG Response Headers

```php
// All SVG responses include:
'Content-Type' => 'image/svg+xml; charset=utf-8',
'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
'ETag' => '"' . md5($svg) . '"',
'Expires' => now()->addHour()->toRfc7231String(),
```

## Configuration

```php
// config/github-stats.php
return [
    'username' => env('GITHUB_USERNAME', 'jeffersongoncalves'),
    'token' => env('GITHUB_TOKEN'),
    'cache' => [
        'data_ttl' => (int) env('GITHUB_STATS_DATA_TTL', 14400), // 4 hours
        'svg_ttl' => (int) env('GITHUB_STATS_SVG_TTL', 3600),    // 1 hour
    ],
    'graphql_url' => 'https://api.github.com/graphql',
    'rest_url' => 'https://api.github.com',
    'defaults' => [
        'theme' => env('GITHUB_STATS_THEME', 'tokyonight'),
        'hide_border' => false,
        'show_icons' => true,
        'langs_count' => 8,
    ],
    'route_prefix' => env('GITHUB_STATS_ROUTE_PREFIX', 'api'),
    'middleware' => [],
];
```

## Testing Patterns

### Mocking GitHub API Responses

```php
use Illuminate\Support\Facades\Http;

it('fetches user stats', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response([
            'data' => ['user' => [
                'name' => 'Test User',
                'login' => 'testuser',
                'avatarUrl' => 'https://example.com/avatar.png',
                'createdAt' => '2020-01-01T00:00:00Z',
                'followers' => ['totalCount' => 100],
                'following' => ['totalCount' => 50],
                'repositories' => [
                    'totalCount' => 30,
                    'nodes' => [],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ],
                'contributionsCollection' => [
                    'totalCommitContributions' => 500,
                    'totalIssueContributions' => 20,
                    'totalPullRequestContributions' => 50,
                    'totalPullRequestReviewContributions' => 10,
                    'restrictedContributionsCount' => 0,
                    'contributionCalendar' => ['totalContributions' => 580, 'weeks' => []],
                ],
            ]],
        ]),
        'api.github.com/search/commits*' => Http::response(['total_count' => 1200]),
    ]);

    $stats = app(GitHubService::class)->getStats();

    expect($stats)->toHaveKeys(['total_stars', 'total_commits', 'total_prs']);
});
```

### Testing Theme Resolution

```php
it('resolves theme with overrides', function () {
    $theme = app(ThemeService::class)->resolve('dark', [
        'bg_color' => 'ffffff',
    ]);

    expect($theme['bg'])->toBe('ffffff');
    expect($theme['title'])->toBe('58a6ff'); // Default dark theme title
});
```

### Testing Streak Calculator

```php
it('calculates current streak', function () {
    $days = [
        ['date' => now()->subDays(2)->format('Y-m-d'), 'contributionCount' => 3],
        ['date' => now()->subDay()->format('Y-m-d'), 'contributionCount' => 5],
        ['date' => now()->format('Y-m-d'), 'contributionCount' => 1],
    ];

    $result = app(StreakCalculator::class)->calculate($days);

    expect($result['current_streak'])->toBe(3);
    expect($result['longest_streak'])->toBe(3);
});
```

### Dev Commands

```bash
# Run tests
vendor/bin/pest

# Run static analysis
vendor/bin/phpstan analyse

# Format code
vendor/bin/pint

# Refresh cache
php artisan github:refresh
```
