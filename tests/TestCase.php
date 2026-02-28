<?php

namespace JeffersonGoncalves\GitHubStats\Tests;

use JeffersonGoncalves\GitHubStats\GitHubStatsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GitHubStatsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('github-stats.username', 'testuser');
        $app['config']->set('github-stats.token', 'fake-token');
        $app['config']->set('github-stats.cache.data_ttl', 60);
        $app['config']->set('github-stats.cache.svg_ttl', 30);
    }
}
