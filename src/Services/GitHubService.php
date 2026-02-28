<?php

namespace JeffersonGoncalves\GitHubStats\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    protected string $username;

    protected string $token;

    protected string $graphqlUrl;

    protected string $restUrl;

    protected int $dataTtl;

    protected int $svgTtl;

    public function __construct(
        protected StreakCalculator $streakCalculator,
    ) {
        $this->username = config('github-stats.username', 'jeffersongoncalves');
        $this->token = config('github-stats.token', '');
        $this->graphqlUrl = config('github-stats.graphql_url', 'https://api.github.com/graphql');
        $this->restUrl = config('github-stats.rest_url', 'https://api.github.com');
        $this->dataTtl = config('github-stats.cache.data_ttl', 14400);
        $this->svgTtl = config('github-stats.cache.svg_ttl', 3600);
    }

    /**
     * @return array{name: string, login: string, avatar_url: string, followers: int, following: int, total_repos: int, repos: array<int, array<string, mixed>>, contributions: array<string, mixed>}
     */
    public function fetchUserData(): array
    {
        $allRepos = [];
        $cursor = null;
        $userData = null;

        do {
            $afterClause = $cursor ? ', after: "'.$cursor.'"' : '';

            $query = <<<GRAPHQL
            query {
              user(login: "{$this->username}") {
                name
                login
                avatarUrl
                followers { totalCount }
                following { totalCount }
                repositories(
                  first: 100
                  ownerAffiliations: OWNER
                  orderBy: { field: STARGAZERS, direction: DESC }
                  {$afterClause}
                ) {
                  totalCount
                  nodes {
                    name
                    stargazerCount
                    forkCount
                    primaryLanguage { name color }
                    languages(first: 10, orderBy: { field: SIZE, direction: DESC }) {
                      edges {
                        size
                        node { name color }
                      }
                    }
                  }
                  pageInfo { hasNextPage endCursor }
                }
                contributionsCollection {
                  totalCommitContributions
                  totalIssueContributions
                  totalPullRequestContributions
                  totalPullRequestReviewContributions
                  restrictedContributionsCount
                  contributionCalendar {
                    totalContributions
                    weeks {
                      contributionDays {
                        contributionCount
                        date
                      }
                    }
                  }
                }
              }
            }
            GRAPHQL;

            $response = Http::withToken($this->token)
                ->post($this->graphqlUrl, ['query' => $query]);

            if ($response->failed()) {
                Log::error('GitHub GraphQL API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException('GitHub API request failed: '.$response->status());
            }

            $data = $response->json('data.user');

            if ($data === null) {
                $errors = $response->json('errors', []);
                Log::error('GitHub GraphQL API returned null user', ['errors' => $errors]);

                throw new \RuntimeException('GitHub user not found or API error');
            }

            if ($userData === null) {
                $userData = $data;
            }

            $repos = $data['repositories']['nodes'] ?? [];
            $allRepos = array_merge($allRepos, $repos);

            $pageInfo = $data['repositories']['pageInfo'] ?? [];
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $cursor = $pageInfo['endCursor'] ?? null;
        } while ($hasNextPage && $cursor);

        return [
            'name' => $userData['name'] ?? $userData['login'],
            'login' => $userData['login'],
            'avatar_url' => $userData['avatarUrl'],
            'followers' => $userData['followers']['totalCount'] ?? 0,
            'following' => $userData['following']['totalCount'] ?? 0,
            'total_repos' => $userData['repositories']['totalCount'] ?? 0,
            'repos' => $allRepos,
            'contributions' => $userData['contributionsCollection'] ?? [],
        ];
    }

    public function fetchAllTimeCommits(): int
    {
        $response = Http::withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/vnd.github.cloak-preview+json',
            ])
            ->get("{$this->restUrl}/search/commits", [
                'q' => "author:{$this->username}",
                'per_page' => 1,
            ]);

        if ($response->failed()) {
            Log::warning('GitHub REST API (all-time commits) failed', [
                'status' => $response->status(),
            ]);

            return 0;
        }

        return $response->json('total_count', 0);
    }

    /**
     * @return array{total_stars: int, total_commits: int, total_prs: int, total_issues: int, total_reviews: int, followers: int, total_repos: int, name: string}
     */
    public function getStats(): array
    {
        return Cache::remember('github:user_stats', $this->dataTtl, function () {
            $userData = $this->fetchUserData();
            $allTimeCommits = $this->fetchAllTimeCommits();

            $totalStars = 0;
            foreach ($userData['repos'] as $repo) {
                $totalStars += $repo['stargazerCount'] ?? 0;
            }

            $contributions = $userData['contributions'];

            return [
                'name' => $userData['name'],
                'total_stars' => $totalStars,
                'total_commits' => $allTimeCommits ?: ($contributions['totalCommitContributions'] ?? 0),
                'total_prs' => $contributions['totalPullRequestContributions'] ?? 0,
                'total_issues' => $contributions['totalIssueContributions'] ?? 0,
                'total_reviews' => $contributions['totalPullRequestReviewContributions'] ?? 0,
                'followers' => $userData['followers'],
                'total_repos' => $userData['total_repos'],
            ];
        });
    }

    /**
     * @return array<int, array{name: string, color: string, size: int, percentage: float}>
     */
    public function getLanguages(): array
    {
        return Cache::remember('github:languages', $this->dataTtl, function () {
            $userData = $this->fetchUserData();

            $languageMap = [];
            foreach ($userData['repos'] as $repo) {
                foreach ($repo['languages']['edges'] ?? [] as $edge) {
                    $name = $edge['node']['name'];
                    $color = $edge['node']['color'] ?? '8b8b8b';
                    $size = $edge['size'] ?? 0;

                    if (! isset($languageMap[$name])) {
                        $languageMap[$name] = ['name' => $name, 'color' => ltrim($color, '#'), 'size' => 0];
                    }
                    $languageMap[$name]['size'] += $size;
                }
            }

            // Sort by size descending
            usort($languageMap, fn ($a, $b) => $b['size'] <=> $a['size']);

            // Take top N languages
            $langsCount = config('github-stats.defaults.langs_count', 8);
            $topLangs = array_slice($languageMap, 0, $langsCount);

            // Calculate percentages
            $totalSize = array_sum(array_column($topLangs, 'size'));
            if ($totalSize > 0) {
                foreach ($topLangs as &$lang) {
                    $lang['percentage'] = round(($lang['size'] / $totalSize) * 100, 1);
                }
                unset($lang);
            }

            return $topLangs;
        });
    }

    /**
     * @return array{current_streak: int, longest_streak: int, total_contributions: int, current_streak_start: string|null, current_streak_end: string|null, longest_streak_start: string|null, longest_streak_end: string|null}
     */
    public function getStreak(): array
    {
        return Cache::remember('github:streak', $this->dataTtl, function () {
            $userData = $this->fetchUserData();

            $weeks = $userData['contributions']['contributionCalendar']['weeks'] ?? [];
            $days = [];

            foreach ($weeks as $week) {
                foreach ($week['contributionDays'] ?? [] as $day) {
                    $days[] = [
                        'date' => $day['date'],
                        'contributionCount' => $day['contributionCount'],
                    ];
                }
            }

            return $this->streakCalculator->calculate($days);
        });
    }

    /**
     * @return array<int, array{name: string, icon: string, level: string, value: int, thresholds: array<string, int>}>
     */
    public function getTrophies(): array
    {
        return Cache::remember('github:trophies', $this->dataTtl, function () {
            $stats = $this->getStats();

            $trophyDefs = [
                [
                    'name' => 'Stars',
                    'icon' => 'star',
                    'value' => $stats['total_stars'],
                    'thresholds' => ['S' => 1000, 'A' => 500, 'B' => 100, 'C' => 10, 'D' => 1],
                ],
                [
                    'name' => 'Commits',
                    'icon' => 'commit',
                    'value' => $stats['total_commits'],
                    'thresholds' => ['S' => 5000, 'A' => 2000, 'B' => 500, 'C' => 100, 'D' => 1],
                ],
                [
                    'name' => 'Pull Requests',
                    'icon' => 'pr',
                    'value' => $stats['total_prs'],
                    'thresholds' => ['S' => 500, 'A' => 200, 'B' => 50, 'C' => 10, 'D' => 1],
                ],
                [
                    'name' => 'Issues',
                    'icon' => 'issue',
                    'value' => $stats['total_issues'],
                    'thresholds' => ['S' => 500, 'A' => 200, 'B' => 50, 'C' => 10, 'D' => 1],
                ],
                [
                    'name' => 'Repos',
                    'icon' => 'repo',
                    'value' => $stats['total_repos'],
                    'thresholds' => ['S' => 100, 'A' => 50, 'B' => 20, 'C' => 5, 'D' => 1],
                ],
                [
                    'name' => 'Followers',
                    'icon' => 'followers',
                    'value' => $stats['followers'],
                    'thresholds' => ['S' => 1000, 'A' => 500, 'B' => 100, 'C' => 10, 'D' => 1],
                ],
                [
                    'name' => 'Reviews',
                    'icon' => 'review',
                    'value' => $stats['total_reviews'],
                    'thresholds' => ['S' => 500, 'A' => 200, 'B' => 50, 'C' => 10, 'D' => 1],
                ],
            ];

            $trophies = [];
            foreach ($trophyDefs as $def) {
                $level = 'none';
                foreach ($def['thresholds'] as $rank => $min) {
                    if ($def['value'] >= $min) {
                        $level = $rank;
                        break;
                    }
                }

                $trophies[] = [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'level' => $level,
                    'value' => $def['value'],
                    'thresholds' => $def['thresholds'],
                ];
            }

            return $trophies;
        });
    }

    public function refreshCache(): void
    {
        Cache::forget('github:user_stats');
        Cache::forget('github:languages');
        Cache::forget('github:streak');
        Cache::forget('github:trophies');

        // Flush all SVG caches
        $themes = app(ThemeService::class)->availableThemes();
        $cards = ['stats', 'langs', 'streak', 'trophies'];

        foreach ($cards as $card) {
            foreach ($themes as $theme) {
                Cache::forget("github:svg:{$card}:{$theme}");
            }
            Cache::forget("github:svg:{$card}:custom");
        }

        // Re-warm the data cache
        $this->getStats();
        $this->getLanguages();
        $this->getStreak();
        $this->getTrophies();

        Cache::put('github:last_refresh', now()->toIso8601String(), $this->dataTtl);
    }
}
