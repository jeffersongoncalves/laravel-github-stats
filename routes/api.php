<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\GitHubStats\Http\Controllers\StatsController;

Route::group([
    'prefix' => config('github-stats.route_prefix', 'api'),
    'middleware' => array_merge(
        ['throttle:github-stats', 'github-stats.lock-username'],
        config('github-stats.middleware', []),
    ),
], function () {
    Route::get('/stats', [StatsController::class, 'stats'])->name('github-stats.stats');
    Route::get('/top-langs', [StatsController::class, 'topLangs'])->name('github-stats.top-langs');
    Route::get('/streak', [StatsController::class, 'streak'])->name('github-stats.streak');
    Route::get('/trophies', [StatsController::class, 'trophies'])->name('github-stats.trophies');
    Route::get('/health', [StatsController::class, 'health'])->name('github-stats.health');
});
