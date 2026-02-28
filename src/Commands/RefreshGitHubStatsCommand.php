<?php

namespace JeffersonGoncalves\GitHubStats\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\GitHubStats\Services\GitHubService;

class RefreshGitHubStatsCommand extends Command
{
    protected $signature = 'github:refresh';

    protected $description = 'Refresh the GitHub stats cache by fetching fresh data from the GitHub API';

    public function handle(GitHubService $github): int
    {
        $this->info('Refreshing GitHub stats cache...');

        try {
            $github->refreshCache();
            $this->info('GitHub stats cache refreshed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to refresh GitHub stats: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
