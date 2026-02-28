<div class="filament-hidden">

![Laravel GitHub Stats](https://raw.githubusercontent.com/jeffersongoncalves/laravel-github-stats/main/art/jeffersongoncalves-laravel-github-stats.png)

</div>

# Laravel GitHub Stats

Self-hosted Laravel package that serves dynamic SVG cards with your GitHub statistics. Replace unreliable third-party services (github-readme-stats, streak-stats, github-profile-trophy) with your own private service.

## Features

- **Stats Card** — Total Stars, Commits, PRs, Issues, Followers
- **Top Languages Card** — Horizontal bar chart with top 8 languages
- **Streak Card** — Current Streak, Longest Streak, Total Contributions
- **Trophies Card** — Achievement badges with rank levels (S/A/B/C/D)
- **6 Built-in Themes** — tokyonight, dark, radical, merko, gruvbox, onedark
- **Custom Colors** — Override any color via query parameters
- **Aggressive Caching** — Two-layer cache (4h data + 1h SVG)
- **Username Locked** — Service locked to a single username via `.env`
- **Background Refresh** — Scheduled command to keep cache warm

## Installation

```bash
composer require jeffersongoncalves/laravel-github-stats
```

Publish the config file:

```bash
php artisan vendor:publish --tag="github-stats-config"
```

## Configuration

Add to your `.env`:

```env
GITHUB_USERNAME=jeffersongoncalves
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
```

Create a [Personal Access Token](https://github.com/settings/tokens/new) with `read:user` scope. Add `repo` scope if you want private repository stats.

### Config Options

```php
// config/github-stats.php
return [
    'username' => env('GITHUB_USERNAME', 'jeffersongoncalves'),
    'token'    => env('GITHUB_TOKEN'),

    'cache' => [
        'data_ttl' => 14400, // 4 hours
        'svg_ttl'  => 3600,  // 1 hour
    ],

    'defaults' => [
        'theme'       => 'tokyonight',
        'hide_border' => false,
        'show_icons'  => true,
        'langs_count' => 8,
    ],

    'route_prefix' => 'api',
    'middleware'    => [],
];
```

## Endpoints

| Method | Route | Description | Response |
|:------:|:------|:------------|:--------:|
| GET | `/api/stats` | GitHub stats card | SVG |
| GET | `/api/top-langs` | Top languages card | SVG |
| GET | `/api/streak` | Contribution streak card | SVG |
| GET | `/api/trophies` | Achievement trophies card | SVG |
| GET | `/api/health` | Service health check | JSON |

### Query Parameters

| Param | Default | Options |
|:------|:--------|:--------|
| `theme` | `tokyonight` | `dark`, `radical`, `merko`, `gruvbox`, `onedark`, `tokyonight` |
| `hide_border` | `false` | `true`, `false` |
| `bg_color` | per theme | Any hex (without `#`) |
| `title_color` | per theme | Any hex |
| `text_color` | per theme | Any hex |
| `icon_color` | per theme | Any hex |

## Usage in GitHub README

```markdown
<div align="center">
  <img height="180em"
    src="https://stats.yourdomain.com/api/stats?theme=tokyonight&hide_border=true" />
  <img height="180em"
    src="https://stats.yourdomain.com/api/top-langs?theme=tokyonight&hide_border=true" />
</div>

<div align="center">
  <img src="https://stats.yourdomain.com/api/streak?theme=tokyonight&hide_border=true" />
</div>

<div align="center">
  <img src="https://stats.yourdomain.com/api/trophies?theme=tokyonight" />
</div>
```

## Cache Refresh

The package includes a scheduled command to keep the cache warm:

```bash
php artisan github:refresh
```

Add to your scheduler (in `routes/console.php` or `app/Console/Kernel.php`):

```php
Schedule::command('github:refresh')->everyFourHours();
```

## Deployment

### Option A — VPS with Forge/Ploi

1. Provision a small VPS (1 vCPU, 1GB RAM)
2. Deploy your Laravel app with this package
3. Set `.env` variables
4. Setup Redis for caching
5. Configure scheduler: `* * * * * php artisan schedule:run`
6. Point subdomain: `stats.yourdomain.com`
7. SSL via Let's Encrypt

### Option B — Docker

```yaml
services:
  app:
    build: .
    ports: ["8080:80"]
    environment:
      GITHUB_USERNAME: jeffersongoncalves
      GITHUB_TOKEN: ${GITHUB_TOKEN}
    depends_on: [redis]
  redis:
    image: redis:alpine
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
