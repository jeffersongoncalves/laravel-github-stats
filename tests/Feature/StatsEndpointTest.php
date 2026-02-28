<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.github.com/graphql' => Http::response([
            'data' => [
                'user' => [
                    'name' => 'Test User',
                    'login' => 'testuser',
                    'avatarUrl' => 'https://github.com/testuser.png',
                    'followers' => ['totalCount' => 42],
                    'following' => ['totalCount' => 10],
                    'repositories' => [
                        'totalCount' => 15,
                        'nodes' => [
                            [
                                'name' => 'repo-1',
                                'stargazerCount' => 100,
                                'forkCount' => 20,
                                'primaryLanguage' => ['name' => 'PHP', 'color' => '#4F5D95'],
                                'languages' => [
                                    'edges' => [
                                        ['size' => 50000, 'node' => ['name' => 'PHP', 'color' => '#4F5D95']],
                                        ['size' => 10000, 'node' => ['name' => 'Blade', 'color' => '#f7523f']],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'repo-2',
                                'stargazerCount' => 50,
                                'forkCount' => 5,
                                'primaryLanguage' => ['name' => 'JavaScript', 'color' => '#f1e05a'],
                                'languages' => [
                                    'edges' => [
                                        ['size' => 30000, 'node' => ['name' => 'JavaScript', 'color' => '#f1e05a']],
                                    ],
                                ],
                            ],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                    'contributionsCollection' => [
                        'totalCommitContributions' => 500,
                        'totalIssueContributions' => 30,
                        'totalPullRequestContributions' => 80,
                        'totalPullRequestReviewContributions' => 25,
                        'restrictedContributionsCount' => 0,
                        'contributionCalendar' => [
                            'totalContributions' => 635,
                            'weeks' => [
                                [
                                    'contributionDays' => [
                                        ['date' => '2026-02-25', 'contributionCount' => 5],
                                        ['date' => '2026-02-26', 'contributionCount' => 3],
                                        ['date' => '2026-02-27', 'contributionCount' => 7],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
        'api.github.com/search/commits*' => Http::response([
            'total_count' => 1234,
        ]),
    ]);
});

it('returns stats as SVG', function () {
    $response = $this->get('/api/stats');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=3600');

    expect($response->getContent())
        ->toContain('<svg')
        ->toContain('Test User');
});

it('returns top languages as SVG', function () {
    $response = $this->get('/api/top-langs');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');

    expect($response->getContent())
        ->toContain('<svg')
        ->toContain('PHP');
});

it('returns streak as SVG', function () {
    $response = $this->get('/api/streak');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');

    expect($response->getContent())
        ->toContain('<svg')
        ->toContain('Current Streak');
});

it('returns trophies as SVG', function () {
    $response = $this->get('/api/trophies');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');

    expect($response->getContent())
        ->toContain('<svg')
        ->toContain('Stars');
});

it('accepts theme parameter', function () {
    $response = $this->get('/api/stats?theme=dark');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('0d1117'); // dark theme bg color
});

it('accepts hide_border parameter', function () {
    $response = $this->get('/api/stats?hide_border=true');

    $response->assertOk();

    expect($response->getContent())
        ->not->toContain('stroke="#');
});

it('includes ETag header', function () {
    $response = $this->get('/api/stats');

    $response->assertOk();

    expect($response->headers->get('ETag'))->not->toBeNull();
});
