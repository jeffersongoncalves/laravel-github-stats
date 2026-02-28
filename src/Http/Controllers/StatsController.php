<?php

namespace JeffersonGoncalves\GitHubStats\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\GitHubStats\Services\GitHubService;
use JeffersonGoncalves\GitHubStats\Services\ThemeService;

class StatsController extends Controller
{
    public function __construct(
        protected GitHubService $github,
        protected ThemeService $themeService,
    ) {}

    public function stats(Request $request): Response
    {
        $theme = $this->resolveTheme($request);
        $hideBorder = $this->resolveHideBorder($request);
        $cacheKey = $this->buildSvgCacheKey('stats', $request);

        $svgTtl = config('github-stats.cache.svg_ttl', 3600);

        $svg = Cache::remember($cacheKey, $svgTtl, function () use ($theme, $hideBorder) {
            $stats = $this->github->getStats();

            /** @var view-string $view */
            $view = 'github-stats::svg.stats';

            return view($view, [
                'theme' => $theme,
                'hide_border' => $hideBorder,
                'title' => $stats['name'],
                'stats' => [
                    ['label' => 'Total Stars', 'value' => $stats['total_stars'], 'icon' => 'star'],
                    ['label' => 'Total Commits', 'value' => $stats['total_commits'], 'icon' => 'commit'],
                    ['label' => 'Total PRs', 'value' => $stats['total_prs'], 'icon' => 'pr'],
                    ['label' => 'Total Issues', 'value' => $stats['total_issues'], 'icon' => 'issue'],
                    ['label' => 'Followers', 'value' => $stats['followers'], 'icon' => 'followers'],
                ],
            ])->render();
        });

        return $this->svgResponse($svg);
    }

    public function topLangs(Request $request): Response
    {
        $theme = $this->resolveTheme($request);
        $hideBorder = $this->resolveHideBorder($request);
        $cacheKey = $this->buildSvgCacheKey('langs', $request);

        $svgTtl = config('github-stats.cache.svg_ttl', 3600);

        $svg = Cache::remember($cacheKey, $svgTtl, function () use ($theme, $hideBorder) {
            $languages = $this->github->getLanguages();

            /** @var view-string $view */
            $view = 'github-stats::svg.top-langs';

            return view($view, [
                'theme' => $theme,
                'hide_border' => $hideBorder,
                'languages' => $languages,
                'title' => 'Most Used Languages',
            ])->render();
        });

        return $this->svgResponse($svg);
    }

    public function streak(Request $request): Response
    {
        $theme = $this->resolveTheme($request);
        $hideBorder = $this->resolveHideBorder($request);
        $cacheKey = $this->buildSvgCacheKey('streak', $request);

        $svgTtl = config('github-stats.cache.svg_ttl', 3600);

        $svg = Cache::remember($cacheKey, $svgTtl, function () use ($theme, $hideBorder) {
            $streak = $this->github->getStreak();

            /** @var view-string $view */
            $view = 'github-stats::svg.streak';

            return view($view, [
                'theme' => $theme,
                'hide_border' => $hideBorder,
                'streak' => $streak,
            ])->render();
        });

        return $this->svgResponse($svg);
    }

    public function trophies(Request $request): Response
    {
        $theme = $this->resolveTheme($request);
        $hideBorder = $this->resolveHideBorder($request);
        $columns = $this->resolveTrophyColumns($request);
        $noFrame = $this->resolveBooleanParam($request, 'no_frame');
        $noBg = $this->resolveBooleanParam($request, 'no_bg');
        $cacheKey = $this->buildSvgCacheKey('trophies', $request);

        $svgTtl = config('github-stats.cache.svg_ttl', 3600);

        $svg = Cache::remember($cacheKey, $svgTtl, function () use ($theme, $hideBorder, $columns, $noFrame, $noBg) {
            $trophies = $this->github->getTrophies();

            /** @var view-string $view */
            $view = 'github-stats::svg.trophies';

            return view($view, [
                'theme' => $theme,
                'hide_border' => $hideBorder,
                'trophies' => $trophies,
                'columns' => $columns,
                'no_frame' => $noFrame,
                'no_bg' => $noBg,
            ])->render();
        });

        return $this->svgResponse($svg);
    }

    public function health(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'cache' => Cache::has('github:user_stats') ? 'warm' : 'cold',
            'last_update' => Cache::get('github:last_refresh', 'never'),
            'username' => config('github-stats.username'),
        ]);
    }

    /**
     * @return array{bg: string, title: string, text: string, icon: string, border: string}
     */
    protected function resolveTheme(Request $request): array
    {
        $themeName = $request->get('theme', config('github-stats.defaults.theme', 'tokyonight'));

        return $this->themeService->resolve($themeName, [
            'bg_color' => $request->get('bg_color'),
            'title_color' => $request->get('title_color'),
            'text_color' => $request->get('text_color'),
            'icon_color' => $request->get('icon_color'),
            'border_color' => $request->get('border_color'),
        ]);
    }

    protected function resolveHideBorder(Request $request): bool
    {
        return filter_var(
            $request->get('hide_border', config('github-stats.defaults.hide_border', false)),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    protected function resolveTrophyColumns(Request $request): ?int
    {
        $column = $request->get('column');
        if ($column === null) {
            return null;
        }

        return max(1, min(10, (int) $column));
    }

    protected function resolveBooleanParam(Request $request, string $param): bool
    {
        return filter_var($request->get($param, false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function buildSvgCacheKey(string $card, Request $request): string
    {
        $params = $request->only([
            'theme', 'bg_color', 'title_color', 'text_color', 'icon_color', 'border_color',
            'hide_border', 'column', 'no_frame', 'no_bg',
        ]);
        $hash = md5(serialize($params));

        return "github:svg:{$card}:{$hash}";
    }

    protected function svgResponse(string $svg): Response
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            'ETag' => '"'.md5($svg).'"',
            'Expires' => now()->addHour()->toRfc7231String(),
        ]);
    }
}
