<?php

namespace JeffersonGoncalves\GitHubStats\Services;

use Carbon\Carbon;

class StreakCalculator
{
    /**
     * Calculate streak data from contribution calendar days.
     *
     * @param  array<int, array{date: string, contributionCount: int}>  $days
     * @return array{current_streak: int, longest_streak: int, total_contributions: int, current_streak_start: string|null, current_streak_end: string|null, longest_streak_start: string|null, longest_streak_end: string|null}
     */
    public function calculate(array $days): array
    {
        if (empty($days)) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'total_contributions' => 0,
                'current_streak_start' => null,
                'current_streak_end' => null,
                'longest_streak_start' => null,
                'longest_streak_end' => null,
            ];
        }

        // Sort by date ascending
        usort($days, fn ($a, $b) => strcmp($a['date'], $b['date']));

        $totalContributions = array_sum(array_column($days, 'contributionCount'));

        // Calculate longest streak
        $longestStreak = 0;
        $longestStart = null;
        $longestEnd = null;
        $currentRun = 0;
        $runStart = null;

        foreach ($days as $day) {
            if ($day['contributionCount'] > 0) {
                if ($currentRun === 0) {
                    $runStart = $day['date'];
                }
                $currentRun++;
                if ($currentRun > $longestStreak) {
                    $longestStreak = $currentRun;
                    $longestStart = $runStart;
                    $longestEnd = $day['date'];
                }
            } else {
                $currentRun = 0;
            }
        }

        // Calculate current streak (from today backwards)
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $daysReversed = array_reverse($days);
        $currentStreak = 0;
        $currentStreakStart = null;
        $currentStreakEnd = null;
        $started = false;

        foreach ($daysReversed as $day) {
            // Skip future dates
            if ($day['date'] > $today) {
                continue;
            }

            // Allow starting from today or yesterday
            if (! $started) {
                if ($day['contributionCount'] > 0) {
                    $started = true;
                    $currentStreakEnd = $day['date'];
                    $currentStreakStart = $day['date'];
                    $currentStreak = 1;
                } elseif ($day['date'] === $today) {
                    // Today has 0, check yesterday
                    continue;
                } else {
                    // Yesterday also has 0 — no current streak
                    break;
                }
            } else {
                if ($day['contributionCount'] > 0) {
                    $currentStreak++;
                    $currentStreakStart = $day['date'];
                } else {
                    break;
                }
            }
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'total_contributions' => $totalContributions,
            'current_streak_start' => $currentStreakStart,
            'current_streak_end' => $currentStreakEnd,
            'longest_streak_start' => $longestStart,
            'longest_streak_end' => $longestEnd,
        ];
    }
}
