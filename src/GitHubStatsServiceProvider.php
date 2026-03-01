<?php

namespace JeffersonGoncalves\GitHubStats;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use JeffersonGoncalves\GitHubStats\Commands\RefreshGitHubStatsCommand;
use JeffersonGoncalves\GitHubStats\Http\Middleware\LockUsername;
use JeffersonGoncalves\GitHubStats\Services\GitHubService;
use JeffersonGoncalves\GitHubStats\Services\StreakCalculator;
use JeffersonGoncalves\GitHubStats\Services\ThemeService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitHubStatsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('github-stats')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('api')
            ->hasCommand(RefreshGitHubStatsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ThemeService::class);

        $this->app->singleton(StreakCalculator::class);

        $this->app->singleton(GitHubService::class, function ($app) {
            return new GitHubService(
                $app->make(StreakCalculator::class),
            );
        });
    }

    public function packageBooted(): void
    {
        $this->configureRateLimiting();

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('github-stats.lock-username', LockUsername::class);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('github-stats', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
