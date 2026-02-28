<?xml version="1.0" encoding="UTF-8"?>
<svg width="350" height="{{ 45 + count($languages) * 25 + 40 }}" viewBox="0 0 350 {{ 45 + count($languages) * 25 + 40 }}" fill="none" xmlns="http://www.w3.org/2000/svg">
  <style>
    .header { font: 600 18px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['title'] }}; }
    .lang-name { font: 400 12px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; }
    .lang-pct { font: 600 12px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; }
  </style>

  @unless($hide_border)
  <rect x="0.5" y="0.5" rx="4.5" width="349" height="{{ 45 + count($languages) * 25 + 39 }}" fill="#{{ $theme['bg'] }}" stroke="#{{ $theme['border'] }}" stroke-opacity="1"/>
  @else
  <rect x="0.5" y="0.5" rx="4.5" width="349" height="{{ 45 + count($languages) * 25 + 39 }}" fill="#{{ $theme['bg'] }}" stroke="none"/>
  @endunless

  <g transform="translate(25, 35)">
    <text class="header">{{ $title }}</text>
  </g>

  {{-- Progress bar --}}
  <g transform="translate(25, 50)">
    <mask id="lang-bar-mask">
      <rect x="0" y="0" width="300" height="8" rx="4" fill="white"/>
    </mask>
    <g mask="url(#lang-bar-mask)">
      @php $offset = 0; @endphp
      @foreach($languages as $lang)
        <rect x="{{ $offset }}" y="0" width="{{ $lang['percentage'] * 3 }}" height="8" fill="#{{ $lang['color'] }}"/>
        @php $offset += $lang['percentage'] * 3; @endphp
      @endforeach
    </g>
  </g>

  {{-- Language list --}}
  @foreach($languages as $i => $lang)
  @php
    $col = $i % 2;
    $row = intdiv($i, 2);
    $x = $col === 0 ? 25 : 175;
    $y = 75 + $row * 25;
  @endphp
  <g transform="translate({{ $x }}, {{ $y }})">
    <circle cx="5" cy="-4" r="5" fill="#{{ $lang['color'] }}"/>
    <text class="lang-name" x="15" y="0">{{ $lang['name'] }}</text>
    <text class="lang-pct" x="110" y="0">{{ $lang['percentage'] }}%</text>
  </g>
  @endforeach
</svg>
