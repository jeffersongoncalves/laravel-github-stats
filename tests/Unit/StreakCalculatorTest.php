<?php

use Carbon\Carbon;
use JeffersonGoncalves\GitHubStats\Services\StreakCalculator;

beforeEach(function () {
    $this->calculator = new StreakCalculator;
});

it('returns zeros for empty days', function () {
    $result = $this->calculator->calculate([]);

    expect($result)
        ->current_streak->toBe(0)
        ->longest_streak->toBe(0)
        ->total_contributions->toBe(0);
});

it('calculates total contributions', function () {
    $days = [
        ['date' => '2026-02-25', 'contributionCount' => 5],
        ['date' => '2026-02-26', 'contributionCount' => 3],
        ['date' => '2026-02-27', 'contributionCount' => 7],
    ];

    $result = $this->calculator->calculate($days);

    expect($result['total_contributions'])->toBe(15);
});

it('calculates longest streak', function () {
    $days = [
        ['date' => '2026-02-20', 'contributionCount' => 1],
        ['date' => '2026-02-21', 'contributionCount' => 2],
        ['date' => '2026-02-22', 'contributionCount' => 3],
        ['date' => '2026-02-23', 'contributionCount' => 0],
        ['date' => '2026-02-24', 'contributionCount' => 1],
        ['date' => '2026-02-25', 'contributionCount' => 1],
    ];

    $result = $this->calculator->calculate($days);

    expect($result['longest_streak'])->toBe(3)
        ->and($result['longest_streak_start'])->toBe('2026-02-20')
        ->and($result['longest_streak_end'])->toBe('2026-02-22');
});

it('calculates current streak from today', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-27'));

    $days = [
        ['date' => '2026-02-24', 'contributionCount' => 0],
        ['date' => '2026-02-25', 'contributionCount' => 3],
        ['date' => '2026-02-26', 'contributionCount' => 5],
        ['date' => '2026-02-27', 'contributionCount' => 2],
    ];

    $result = $this->calculator->calculate($days);

    expect($result['current_streak'])->toBe(3);

    Carbon::setTestNow();
});

it('allows starting streak from yesterday if today is zero', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-27'));

    $days = [
        ['date' => '2026-02-25', 'contributionCount' => 3],
        ['date' => '2026-02-26', 'contributionCount' => 5],
        ['date' => '2026-02-27', 'contributionCount' => 0],
    ];

    $result = $this->calculator->calculate($days);

    expect($result['current_streak'])->toBe(2);

    Carbon::setTestNow();
});
