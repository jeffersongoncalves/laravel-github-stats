<?xml version="1.0" encoding="UTF-8"?>
<svg width="495" height="195" viewBox="0 0 495 195" fill="none" xmlns="http://www.w3.org/2000/svg">
  <style>
    .header { font: 600 18px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['title'] }}; }
    .stat-label { font: 400 14px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; }
    .stat-value { font: 700 14px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; }
    .icon { fill: #{{ $theme['icon'] }}; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateX(-5px); }
      to { opacity: 1; transform: translateX(0); }
    }
    .stat-row { animation: fadeIn 0.3s ease-in-out forwards; opacity: 0; }
  </style>

  @unless($hide_border)
  <rect x="0.5" y="0.5" rx="4.5" width="494" height="194" fill="#{{ $theme['bg'] }}" stroke="#{{ $theme['border'] }}" stroke-opacity="1"/>
  @else
  <rect x="0.5" y="0.5" rx="4.5" width="494" height="194" fill="#{{ $theme['bg'] }}" stroke="none"/>
  @endunless

  <g transform="translate(25, 35)">
    <text class="header">{{ $title }}'s GitHub Stats</text>
  </g>

  @foreach($stats as $i => $stat)
  <g class="stat-row" style="animation-delay: {{ $i * 150 }}ms" transform="translate(25, {{ 60 + $i * 25 }})">
    <svg class="icon" viewBox="0 0 16 16" width="16" height="16" x="0" y="-12">
      @switch($stat['icon'])
        @case('star')
          <path fill-rule="evenodd" d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"/>
          @break
        @case('commit')
          <path fill-rule="evenodd" d="M11.93 8.5a4.002 4.002 0 01-7.86 0H.75a.75.75 0 010-1.5h3.32a4.002 4.002 0 017.86 0h3.32a.75.75 0 010 1.5h-3.32zm-1.43-.75a2.5 2.5 0 10-5 0 2.5 2.5 0 005 0z"/>
          @break
        @case('pr')
          <path fill-rule="evenodd" d="M7.177 3.073L9.573.677A.25.25 0 0110 .854v4.792a.25.25 0 01-.427.177L7.177 3.427a.25.25 0 010-.354zM3.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122v5.256a2.251 2.251 0 11-1.5 0V5.372A2.25 2.25 0 011.5 3.25zM11 2.5h-1V4h1a1 1 0 011 1v5.628a2.251 2.251 0 101.5 0V5A2.5 2.5 0 0011 2.5zm1 10.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM3.75 12a.75.75 0 100 1.5.75.75 0 000-1.5z"/>
          @break
        @case('issue')
          <path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
          <path fill-rule="evenodd" d="M8 0a8 8 0 100 16A8 8 0 008 0zM1.5 8a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0z"/>
          @break
        @case('followers')
          <path fill-rule="evenodd" d="M5.5 3.5a2 2 0 100 4 2 2 0 000-4zM2 5.5a3.5 3.5 0 115.898 2.549 5.507 5.507 0 013.034 4.084.75.75 0 11-1.482.235 4.001 4.001 0 00-7.9 0 .75.75 0 01-1.482-.236A5.507 5.507 0 013.102 8.05 3.49 3.49 0 012 5.5zM11 4a.75.75 0 100 1.5 1.5 1.5 0 01.666 2.844.75.75 0 00-.416.672v.352a.75.75 0 00.574.73c1.2.289 2.162 1.2 2.522 2.372a.75.75 0 101.434-.44 5.01 5.01 0 00-2.56-3.012A3 3 0 0011 4z"/>
          @break
      @endswitch
    </svg>
    <text class="stat-label" x="25" y="0">{{ $stat['label'] }}:</text>
    <text class="stat-value" x="220" y="0">{{ number_format($stat['value']) }}</text>
  </g>
  @endforeach
</svg>
