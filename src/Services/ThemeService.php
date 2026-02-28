<?php

namespace JeffersonGoncalves\GitHubStats\Services;

class ThemeService
{
    /**
     * @var array<string, array{bg: string, title: string, text: string, icon: string, border: string}>
     */
    protected array $themes = [
        'tokyonight' => [
            'bg' => '1a1b27',
            'title' => '70a5fd',
            'text' => '38bdae',
            'icon' => 'bf91f3',
            'border' => '38bdae',
        ],
        'dark' => [
            'bg' => '0d1117',
            'title' => '58a6ff',
            'text' => 'c9d1d9',
            'icon' => '3fb950',
            'border' => '30363d',
        ],
        'radical' => [
            'bg' => '141321',
            'title' => 'fe428e',
            'text' => 'a9fef7',
            'icon' => 'f8d847',
            'border' => 'fe428e',
        ],
        'merko' => [
            'bg' => '0a0f0b',
            'title' => 'abd200',
            'text' => '68b587',
            'icon' => 'b7d364',
            'border' => 'abd200',
        ],
        'gruvbox' => [
            'bg' => '282828',
            'title' => 'fabd2f',
            'text' => 'ebdbb2',
            'icon' => 'fe8019',
            'border' => 'fabd2f',
        ],
        'onedark' => [
            'bg' => '282c34',
            'title' => 'e4bf7a',
            'text' => 'abb2bf',
            'icon' => '98c379',
            'border' => 'e4bf7a',
        ],
    ];

    /**
     * @param  array<string, string|null>  $overrides
     * @return array{bg: string, title: string, text: string, icon: string, border: string}
     */
    public function resolve(string $themeName, array $overrides = []): array
    {
        $theme = $this->themes[$themeName]
            ?? $this->themes[config('github-stats.defaults.theme', 'tokyonight')];

        foreach (['bg_color' => 'bg', 'title_color' => 'title', 'text_color' => 'text', 'icon_color' => 'icon', 'border_color' => 'border'] as $param => $key) {
            if (! empty($overrides[$param]) && preg_match('/^[0-9a-fA-F]{3,8}$/', $overrides[$param])) {
                $theme[$key] = $overrides[$param];
            }
        }

        return $theme;
    }

    /**
     * @return array<string>
     */
    public function availableThemes(): array
    {
        return array_keys($this->themes);
    }
}
