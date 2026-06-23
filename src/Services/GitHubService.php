<?php

namespace JeffersonGoncalves\GitHubStats\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    /**
     * Cache key holding the index of every generated SVG cache key, so the
     * whole SVG cache can be invalidated regardless of the request parameters
     * (and their md5 hash) that produced each entry.
     */
    public const SVG_INDEX_KEY = 'github:svg:keys';

    protected string $username;

    protected string $token;

    protected string $graphqlUrl;

    protected string $restUrl;

    protected int $dataTtl;

    protected int $svgTtl;

    /**
     * Request-scoped memoization of the user payload, which is otherwise fetched
     * once per stats/languages/streak builder on a cold cache.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $userData = null;

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
     * @return array{name: string, login: string, avatar_url: string, followers: int, following: int, total_repos: int, repos: array<int, array<string, mixed>>, contributions: array<string, mixed>, created_at: string|null}
     */
    public function fetchUserData(): array
    {
        if ($this->userData !== null) {
            return $this->userData;
        }

        $allRepos = [];
        $cursor = null;
        $userData = null;

        $query = <<<'GRAPHQL'
        query ($login: String!, $cursor: String) {
          user(login: $login) {
            name
            login
            avatarUrl
            createdAt
            followers { totalCount }
            following { totalCount }
            repositories(
              first: 100
              ownerAffiliations: OWNER
              orderBy: { field: STARGAZERS, direction: DESC }
              after: $cursor
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

        do {
            $response = Http::withToken($this->token)
                ->post($this->graphqlUrl, [
                    'query' => $query,
                    'variables' => [
                        'login' => $this->username,
                        'cursor' => $cursor,
                    ],
                ]);

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

        return $this->userData = [
            'name' => $userData['name'] ?? $userData['login'],
            'login' => $userData['login'],
            'avatar_url' => $userData['avatarUrl'],
            'followers' => $userData['followers']['totalCount'] ?? 0,
            'following' => $userData['following']['totalCount'] ?? 0,
            'total_repos' => $userData['repositories']['totalCount'] ?? 0,
            'repos' => $allRepos,
            'contributions' => $userData['contributionsCollection'] ?? [],
            'created_at' => $userData['createdAt'] ?? null,
        ];
    }

    /**
     * Count all-time commit contributions, including private (restricted) ones.
     *
     * GitHub's GraphQL contributionsCollection is limited to a one-year window,
     * so we sum every year since the account was created. Public commits come
     * from totalCommitContributions and private ones from restrictedContributionsCount.
     */
    public function fetchAllTimeCommits(?string $createdAt = null): int
    {
        $createdAt ??= $this->fetchUserData()['created_at'];

        $startYear = $createdAt ? Carbon::parse($createdAt)->year : Carbon::now()->year;
        $currentYear = Carbon::now()->year;

        $totalCommits = 0;

        $query = <<<'GRAPHQL'
        query ($login: String!, $from: DateTime!, $to: DateTime!) {
          user(login: $login) {
            contributionsCollection(from: $from, to: $to) {
              totalCommitContributions
              restrictedContributionsCount
            }
          }
        }
        GRAPHQL;

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $response = Http::withToken($this->token)
                ->post($this->graphqlUrl, [
                    'query' => $query,
                    'variables' => [
                        'login' => $this->username,
                        'from' => "{$year}-01-01T00:00:00Z",
                        'to' => "{$year}-12-31T23:59:59Z",
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('GitHub GraphQL API (all-time commits) failed', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $collection = $response->json('data.user.contributionsCollection');
            if ($collection === null) {
                continue;
            }

            $totalCommits += $collection['totalCommitContributions'] ?? 0;
            $totalCommits += $collection['restrictedContributionsCount'] ?? 0;
        }

        return $totalCommits;
    }

    /**
     * @return array{total_stars: int, total_commits: int, total_prs: int, total_issues: int, total_reviews: int, followers: int, total_repos: int, name: string}
     */
    public function getStats(): array
    {
        return Cache::remember('github:user_stats', $this->dataTtl, function () {
            $userData = $this->fetchUserData();
            $allTimeCommits = $this->fetchAllTimeCommits($userData['created_at']);

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
     * Fetch contribution data for all years since user creation.
     *
     * @return array{days: array<int, array{date: string, contributionCount: int}>, total_contributions: int, created_at: string|null}
     */
    public function fetchAllTimeContributions(): array
    {
        $userData = $this->fetchUserData();
        $createdAt = $userData['created_at'];

        $startYear = $createdAt ? Carbon::parse($createdAt)->year : Carbon::now()->year;
        $currentYear = Carbon::now()->year;

        $allDays = [];
        $totalContributions = 0;

        $query = <<<'GRAPHQL'
        query ($login: String!, $from: DateTime!, $to: DateTime!) {
          user(login: $login) {
            contributionsCollection(from: $from, to: $to) {
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

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $response = Http::withToken($this->token)
                ->post($this->graphqlUrl, [
                    'query' => $query,
                    'variables' => [
                        'login' => $this->username,
                        'from' => "{$year}-01-01T00:00:00Z",
                        'to' => "{$year}-12-31T23:59:59Z",
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('GitHub GraphQL API (year contributions) failed', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $collection = $response->json('data.user.contributionsCollection');
            if ($collection === null) {
                continue;
            }

            $totalContributions += $collection['contributionCalendar']['totalContributions'] ?? 0;
            $totalContributions += $collection['restrictedContributionsCount'] ?? 0;

            foreach ($collection['contributionCalendar']['weeks'] ?? [] as $week) {
                foreach ($week['contributionDays'] ?? [] as $day) {
                    $allDays[$day['date']] = [
                        'date' => $day['date'],
                        'contributionCount' => $day['contributionCount'],
                    ];
                }
            }
        }

        // Sort by date
        ksort($allDays);

        return [
            'days' => array_values($allDays),
            'total_contributions' => $totalContributions,
            'created_at' => $createdAt,
        ];
    }

    /**
     * @return array{current_streak: int, longest_streak: int, total_contributions: int, current_streak_start: string|null, current_streak_end: string|null, longest_streak_start: string|null, longest_streak_end: string|null, created_at: string|null}
     */
    public function getStreak(): array
    {
        return Cache::remember('github:streak', $this->dataTtl, function () {
            $allTime = $this->fetchAllTimeContributions();

            $result = $this->streakCalculator->calculate($allTime['days']);
            $result['total_contributions'] = $allTime['total_contributions'];
            $result['created_at'] = $allTime['created_at'];

            return $result;
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

    /**
     * Cache a rendered SVG card while tracking its (parameter dependent) cache
     * key so it can later be invalidated by refreshCache().
     */
    public function rememberSvg(string $key, callable $callback): string
    {
        $this->registerSvgKey($key);

        return Cache::remember($key, $this->svgTtl, $callback);
    }

    public function refreshCache(): void
    {
        // Drop the request-scoped memo so the re-warm below fetches fresh data.
        $this->userData = null;

        Cache::forget('github:user_stats');
        Cache::forget('github:languages');
        Cache::forget('github:streak');
        Cache::forget('github:trophies');

        $this->forgetSvgCache();

        // Re-warm the data cache
        $this->getStats();
        $this->getLanguages();
        $this->getStreak();
        $this->getTrophies();

        Cache::put('github:last_refresh', now()->toIso8601String(), $this->dataTtl);
    }

    /**
     * Track an SVG cache key in the shared index.
     */
    protected function registerSvgKey(string $key): void
    {
        /** @var array<int, string> $keys */
        $keys = Cache::get(self::SVG_INDEX_KEY, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            // Keep the index alive at least as long as any SVG entry.
            Cache::put(self::SVG_INDEX_KEY, $keys, max($this->dataTtl, $this->svgTtl));
        }
    }

    /**
     * Forget every tracked SVG cache entry and clear the index.
     */
    protected function forgetSvgCache(): void
    {
        /** @var array<int, string> $keys */
        $keys = Cache::get(self::SVG_INDEX_KEY, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::SVG_INDEX_KEY);
    }
}
