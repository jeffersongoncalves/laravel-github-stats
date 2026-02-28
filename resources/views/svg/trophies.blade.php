@php
  // Detect dark theme by calculating bg luminance
  $bgHex = $theme['bg'];
  $r = hexdec(substr($bgHex, 0, 2));
  $g = hexdec(substr($bgHex, 2, 2));
  $b = hexdec(substr($bgHex, 4, 2));
  $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
  $isDark = $luminance < 0.5;

  $trophyColors = $isDark ? [
    'S'    => ['bg' => 'ffd700', 'border' => 'ffc107', 'text' => 'ffd700'],
    'A'    => ['bg' => 'c0c0c0', 'border' => 'a0a0a0', 'text' => 'c0c0c0'],
    'B'    => ['bg' => 'cd7f32', 'border' => 'b87333', 'text' => 'cd7f32'],
    'C'    => ['bg' => 'e8e8e8', 'border' => 'cccccc', 'text' => 'b0b0b0'],
    'D'    => ['bg' => 'f0f0f0', 'border' => 'dddddd', 'text' => '999999'],
    'none' => ['bg' => 'f5f5f5', 'border' => 'eeeeee', 'text' => '777777'],
  ] : [
    'S'    => ['bg' => 'ffd700', 'border' => 'ffc107', 'text' => '6d5300'],
    'A'    => ['bg' => 'c0c0c0', 'border' => 'a0a0a0', 'text' => '404040'],
    'B'    => ['bg' => 'cd7f32', 'border' => 'b87333', 'text' => '4a2f0e'],
    'C'    => ['bg' => 'e8e8e8', 'border' => 'cccccc', 'text' => '555555'],
    'D'    => ['bg' => 'f0f0f0', 'border' => 'dddddd', 'text' => '777777'],
    'none' => ['bg' => 'f5f5f5', 'border' => 'eeeeee', 'text' => 'aaaaaa'],
  ];

  $cols = $columns ?? min(count($trophies), 4);
  $rows = (int) ceil(count($trophies) / $cols);
  $trophyWidth = 120;
  $trophyHeight = 120;
  $gap = 10;
  $totalWidth = $cols * $trophyWidth + ($cols - 1) * $gap + 20;
  $totalHeight = $rows * $trophyHeight + ($rows - 1) * $gap + 20;

  $noFrame = $no_frame ?? false;
  $noBg = $no_bg ?? false;

  $iconOpacity = $isDark ? '0.6' : '0.3';
  $iconFillColor = $isDark ? $theme['icon'] : null;
@endphp
<?xml version="1.0" encoding="UTF-8"?>
<svg width="{{ $totalWidth }}" height="{{ $totalHeight }}" viewBox="0 0 {{ $totalWidth }} {{ $totalHeight }}" fill="none" xmlns="http://www.w3.org/2000/svg">
  <style>
    .trophy-title { font: 600 12px 'Segoe UI', Ubuntu, Sans-Serif; }
    .trophy-level { font: 700 26px 'Segoe UI', Ubuntu, Sans-Serif; }
    .trophy-value { font: 400 11px 'Segoe UI', Ubuntu, Sans-Serif; }
    .trophy-icon { opacity: {{ $iconOpacity }}; }
  </style>

  @unless($hide_border)
  <rect x="0.5" y="0.5" rx="4.5" width="{{ $totalWidth - 1 }}" height="{{ $totalHeight - 1 }}" fill="{{ $noBg ? 'transparent' : '#' . $theme['bg'] }}" fill-opacity="{{ $noBg ? '0' : '1' }}" stroke="#{{ $theme['border'] }}" stroke-opacity="1"/>
  @else
  <rect x="0" y="0" rx="4.5" width="{{ $totalWidth }}" height="{{ $totalHeight }}" fill="{{ $noBg ? 'transparent' : '#' . $theme['bg'] }}" fill-opacity="{{ $noBg ? '0' : '1' }}" stroke="none"/>
  @endunless

  @foreach($trophies as $i => $trophy)
  @php
    $col = $i % $cols;
    $row = intdiv($i, $cols);
    $x = 10 + $col * ($trophyWidth + $gap);
    $y = 10 + $row * ($trophyHeight + $gap);
    $tc = $trophyColors[$trophy['level']] ?? $trophyColors['none'];
    $cardStroke = $noFrame ? 'none' : '#' . $tc['border'];
    $cardFill = $noBg ? 'transparent' : '#' . $tc['bg'];
    $cardFillOpacity = $noBg ? '0' : '0.15';
    $trophyIconColor = $iconFillColor ?? $tc['text'];
  @endphp
  <g transform="translate({{ $x }}, {{ $y }})">
    {{-- Trophy card background --}}
    <rect x="0" y="0" rx="4" width="{{ $trophyWidth }}" height="{{ $trophyHeight }}" fill="{{ $cardFill }}" fill-opacity="{{ $cardFillOpacity }}" stroke="{{ $cardStroke }}" stroke-opacity="0.5"/>

    {{-- Trophy icon --}}
    <g class="trophy-icon" transform="translate({{ $trophyWidth / 2 - 15 }}, 15)">
      <svg width="30" height="30" viewBox="0 0 16 16" fill="#{{ $trophyIconColor }}">
        @switch($trophy['icon'])
          @case('star')
            <path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"/>
            @break
          @case('commit')
            <path d="M11.93 8.5a4.002 4.002 0 01-7.86 0H.75a.75.75 0 010-1.5h3.32a4.002 4.002 0 017.86 0h3.32a.75.75 0 010 1.5h-3.32zm-1.43-.75a2.5 2.5 0 10-5 0 2.5 2.5 0 005 0z"/>
            @break
          @case('pr')
            <path d="M7.177 3.073L9.573.677A.25.25 0 0110 .854v4.792a.25.25 0 01-.427.177L7.177 3.427a.25.25 0 010-.354zM3.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122v5.256a2.251 2.251 0 11-1.5 0V5.372A2.25 2.25 0 011.5 3.25zM11 2.5h-1V4h1a1 1 0 011 1v5.628a2.251 2.251 0 101.5 0V5A2.5 2.5 0 0011 2.5zm1 10.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM3.75 12a.75.75 0 100 1.5.75.75 0 000-1.5z"/>
            @break
          @case('issue')
            <path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
            <path d="M8 0a8 8 0 100 16A8 8 0 008 0zM1.5 8a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0z"/>
            @break
          @case('repo')
            <path d="M2 2.5A2.5 2.5 0 014.5 0h8.75a.75.75 0 01.75.75v12.5a.75.75 0 01-.75.75h-2.5a.75.75 0 110-1.5h1.75v-2h-8a1 1 0 00-.714 1.7.75.75 0 01-1.072 1.05A2.495 2.495 0 012 11.5v-9zm10.5-1h-8a1 1 0 00-1 1v6.708A2.486 2.486 0 014.5 9h8V1.5zm-8 11h1.5v-2H4.5a1 1 0 100 2z"/>
            @break
          @case('followers')
            <path d="M5.5 3.5a2 2 0 100 4 2 2 0 000-4zM2 5.5a3.5 3.5 0 115.898 2.549 5.507 5.507 0 013.034 4.084.75.75 0 11-1.482.235 4.001 4.001 0 00-7.9 0 .75.75 0 01-1.482-.236A5.507 5.507 0 013.102 8.05 3.49 3.49 0 012 5.5zM11 4a.75.75 0 100 1.5 1.5 1.5 0 01.666 2.844.75.75 0 00-.416.672v.352a.75.75 0 00.574.73c1.2.289 2.162 1.2 2.522 2.372a.75.75 0 101.434-.44 5.01 5.01 0 00-2.56-3.012A3 3 0 0011 4z"/>
            @break
          @case('review')
            <path d="M1.5 2.75a.25.25 0 01.25-.25h12.5a.25.25 0 01.25.25v8.5a.25.25 0 01-.25.25h-6.5a.75.75 0 00-.53.22L4.5 14.44v-2.19a.75.75 0 00-.75-.75H1.75a.25.25 0 01-.25-.25v-8.5zM1.75 1A1.75 1.75 0 000 2.75v8.5C0 12.216.784 13 1.75 13H3v1.543a1.457 1.457 0 002.487 1.03L8.061 13h6.189A1.75 1.75 0 0016 11.25v-8.5A1.75 1.75 0 0014.25 1H1.75z"/>
            @break
        @endswitch
      </svg>
    </g>

    {{-- Level badge --}}
    <text class="trophy-level" text-anchor="middle" x="{{ $trophyWidth / 2 }}" y="70" fill="#{{ $tc['text'] }}">{{ $trophy['level'] === 'none' ? '?' : $trophy['level'] }}</text>

    {{-- Trophy name --}}
    <text class="trophy-title" text-anchor="middle" x="{{ $trophyWidth / 2 }}" y="90" fill="#{{ $theme['text'] }}">{{ $trophy['name'] }}</text>

    {{-- Value --}}
    <text class="trophy-value" text-anchor="middle" x="{{ $trophyWidth / 2 }}" y="108" fill="#{{ $theme['text'] }}" opacity="0.7">{{ number_format($trophy['value']) }}</text>
  </g>
  @endforeach
</svg>
