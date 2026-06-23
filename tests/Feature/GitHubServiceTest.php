<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\GitHubStats\Services\GitHubService;
use Symfony\Component\Console\Command\Command;

function ghRepoPage(array $nodes, bool $hasNextPage, ?string $endCursor): array
{
    return [
        'data' => [
            'user' => [
                'name' => 'Test User',
                'login' => 'testuser',
                'avatarUrl' => 'https://github.com/testuser.png',
                'createdAt' => '2026-01-01T00:00:00Z',
                'followers' => ['totalCount' => 42],
                'following' => ['totalCount' => 10],
                'repositories' => [
                    'totalCount' => 15,
                    'nodes' => $nodes,
                    'pageInfo' => ['hasNextPage' => $hasNextPage, 'endCursor' => $endCursor],
                ],
                'contributionsCollection' => [
                    'totalCommitContributions' => 500,
                    'totalIssueContributions' => 30,
                    'totalPullRequestContributions' => 80,
                    'totalPullRequestReviewContributions' => 25,
                    'restrictedContributionsCount' => 0,
                    'contributionCalendar' => [
                        'totalContributions' => 635,
                        'weeks' => [],
                    ],
                ],
            ],
        ],
    ];
}

function ghRepoNode(string $name, int $stars): array
{
    return [
        'name' => $name,
        'stargazerCount' => $stars,
        'forkCount' => 1,
        'primaryLanguage' => ['name' => 'PHP', 'color' => '#4F5D95'],
        'languages' => ['edges' => [['size' => 1000, 'node' => ['name' => 'PHP', 'color' => '#4F5D95']]]],
    ];
}

function ghYearResponse(int $commits, int $contributions): array
{
    return [
        'data' => [
            'user' => [
                'contributionsCollection' => [
                    'totalCommitContributions' => $commits,
                    'restrictedContributionsCount' => 0,
                    'contributionCalendar' => [
                        'totalContributions' => $contributions,
                        'weeks' => [],
                    ],
                ],
            ],
        ],
    ];
}

it('throws a RuntimeException when the GitHub API request fails', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(['message' => 'Bad credentials'], 401),
    ]);

    app(GitHubService::class)->fetchUserData();
})->throws(RuntimeException::class);

it('throws a RuntimeException when the user payload is null', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(['data' => ['user' => null], 'errors' => [['message' => 'Not found']]]),
    ]);

    app(GitHubService::class)->fetchUserData();
})->throws(RuntimeException::class, 'GitHub user not found or API error');

it('merges repositories across paginated responses', function () {
    Http::fake([
        'api.github.com/graphql' => function ($request) {
            $cursor = $request->data()['variables']['cursor'] ?? null;

            return $cursor === null
                ? Http::response(ghRepoPage([ghRepoNode('repo-1', 100)], true, 'CURSOR1'))
                : Http::response(ghRepoPage([ghRepoNode('repo-2', 50)], false, null));
        },
    ]);

    $data = app(GitHubService::class)->fetchUserData();

    expect($data['repos'])->toHaveCount(2)
        ->and(array_column($data['repos'], 'name'))->toBe(['repo-1', 'repo-2']);
});

it('aggregates commit contributions across multiple years', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(ghYearResponse(100, 100)),
    ]);

    // 2026-01-01 created -> current date 2026, so single year here would be 100.
    // Pass an older creation date to force multiple yearly windows.
    $total = app(GitHubService::class)->fetchAllTimeCommits('2024-01-01T00:00:00Z');

    // 2024, 2025, 2026 => three windows of 100 each.
    expect($total)->toBe(300);
});

it('memoizes the user payload so it is only fetched once on a cold cache', function () {
    Http::fake([
        'api.github.com/graphql' => function ($request) {
            $query = $request->data()['query'] ?? '';

            return str_contains($query, 'repositories(')
                ? Http::response(ghRepoPage([ghRepoNode('repo-1', 100)], false, null))
                : Http::response(ghYearResponse(100, 100));
        },
    ]);

    $service = app(GitHubService::class);
    $service->getStats();
    $service->getLanguages();
    $service->getStreak();

    $repoCalls = 0;
    Http::assertSent(function ($request) use (&$repoCalls) {
        if (str_contains($request->data()['query'] ?? '', 'repositories(')) {
            $repoCalls++;
        }

        return true;
    });

    expect($repoCalls)->toBe(1);
});

it('invalidates tracked SVG cache entries on refresh', function () {
    Http::fake([
        'api.github.com/graphql' => function ($request) {
            $query = $request->data()['query'] ?? '';

            return str_contains($query, 'repositories(')
                ? Http::response(ghRepoPage([ghRepoNode('repo-1', 100)], false, null))
                : Http::response(ghYearResponse(100, 100));
        },
    ]);

    $service = app(GitHubService::class);

    $key = 'github:svg:stats:'.md5('example');
    $service->rememberSvg($key, fn () => '<svg>cached</svg>');

    expect(Cache::has($key))->toBeTrue()
        ->and(Cache::get(GitHubService::SVG_INDEX_KEY))->toContain($key);

    $service->refreshCache();

    expect(Cache::has($key))->toBeFalse()
        ->and(Cache::has(GitHubService::SVG_INDEX_KEY))->toBeFalse();
});

it('returns FAILURE when the refresh command cannot reach the API', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(['message' => 'Server error'], 500),
    ]);

    $this->artisan('github:refresh')
        ->assertExitCode(Command::FAILURE);
});

it('returns SUCCESS when the refresh command succeeds', function () {
    Http::fake([
        'api.github.com/graphql' => function ($request) {
            $query = $request->data()['query'] ?? '';

            return str_contains($query, 'repositories(')
                ? Http::response(ghRepoPage([ghRepoNode('repo-1', 100)], false, null))
                : Http::response(ghYearResponse(100, 100));
        },
    ]);

    $this->artisan('github:refresh')
        ->assertExitCode(Command::SUCCESS);
});
