<?php

use JeffersonGoncalves\GitHubStats\Services\ThemeService;

beforeEach(function () {
    $this->service = new ThemeService;
});

it('returns tokyonight theme by default', function () {
    $theme = $this->service->resolve('tokyonight');

    expect($theme)
        ->bg->toBe('1a1b27')
        ->title->toBe('70a5fd')
        ->text->toBe('38bdae')
        ->icon->toBe('bf91f3')
        ->border->toBe('38bdae');
});

it('returns all available themes', function () {
    $themes = $this->service->availableThemes();

    expect($themes)->toContain('tokyonight', 'dark', 'radical', 'merko', 'gruvbox', 'onedark');
});

it('falls back to default theme for unknown name', function () {
    $theme = $this->service->resolve('nonexistent');

    expect($theme['bg'])->toBe('1a1b27');
});

it('applies color overrides', function () {
    $theme = $this->service->resolve('dark', [
        'bg_color' => 'ff0000',
        'title_color' => '00ff00',
    ]);

    expect($theme)
        ->bg->toBe('ff0000')
        ->title->toBe('00ff00')
        ->text->toBe('c9d1d9'); // unchanged
});

it('rejects invalid hex colors in overrides', function () {
    $theme = $this->service->resolve('dark', [
        'bg_color' => 'not-a-color',
        'title_color' => 'xyz',
    ]);

    expect($theme)
        ->bg->toBe('0d1117') // unchanged
        ->title->toBe('58a6ff'); // unchanged
});
