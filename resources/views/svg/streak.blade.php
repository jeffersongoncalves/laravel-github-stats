<?xml version="1.0" encoding="UTF-8"?>
<svg width="495" height="195" viewBox="0 0 495 195" fill="none" xmlns="http://www.w3.org/2000/svg">
  <style>
    .stat-title { font: 400 12px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; }
    .stat-value { font: 700 28px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['title'] }}; }
    .stat-value-current { font: 700 28px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['icon'] }}; }
    .stat-date { font: 400 10px 'Segoe UI', Ubuntu, Sans-Serif; fill: #{{ $theme['text'] }}; opacity: 0.7; }
    .divider { stroke: #{{ $theme['border'] }}; stroke-width: 1; }
    .fire { fill: #{{ $theme['icon'] }}; }
    .streak-ring { fill: none; stroke: #{{ $theme['icon'] }}; stroke-width: 3; stroke-opacity: 0.4; }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .section-1 { animation: fadeIn 0.4s ease forwards; }
    .section-2 { animation: fadeIn 0.4s ease 0.15s forwards; opacity: 0; }
    .section-3 { animation: fadeIn 0.4s ease 0.3s forwards; opacity: 0; }
  </style>

  @unless($hide_border)
  <rect x="0.5" y="0.5" rx="4.5" width="494" height="194" fill="#{{ $theme['bg'] }}" stroke="#{{ $theme['border'] }}" stroke-opacity="1"/>
  @else
  <rect x="0.5" y="0.5" rx="4.5" width="494" height="194" fill="#{{ $theme['bg'] }}" stroke="none"/>
  @endunless

  {{-- Total Contributions --}}
  <g transform="translate(82.5, 48)" class="section-1">
    <text class="stat-title" text-anchor="middle" x="0" y="0">Total Contributions</text>
    <text class="stat-value" text-anchor="middle" x="0" y="38">{{ number_format($streak['total_contributions']) }}</text>
    <text class="stat-date" text-anchor="middle" x="0" y="56">
      @if($streak['created_at'])
        {{ \Carbon\Carbon::parse($streak['created_at'])->format('M j, Y') }} - Present
      @endif
    </text>
  </g>

  {{-- Divider 1 --}}
  <line class="divider" x1="165" y1="20" x2="165" y2="175"/>

  {{-- Current Streak --}}
  <g transform="translate(247.5, 48)" class="section-2">
    <text class="stat-title" text-anchor="middle" x="0" y="0">Current Streak</text>

    {{-- Fire icon (small, above ring) --}}
    <svg class="fire" x="-6" y="8" width="12" height="12" viewBox="0 0 16 16">
      <path d="M7.998 14.5c2.832 0 5-1.98 5-4.5 0-1.463-.68-2.19-1.879-3.383l-.036-.037C9.865 5.343 8.66 4.088 8.198 1.5 6.698 4 6.698 5 5.998 6c-.7 1-2 1.5-2 3.5 0 2.52 1.637 5 4 5z"/>
    </svg>

    {{-- Ring around current streak number --}}
    <circle class="streak-ring" cx="0" cy="32" r="16"/>
    <text class="stat-value-current" text-anchor="middle" x="0" y="40">{{ number_format($streak['current_streak']) }}</text>

    <text class="stat-date" text-anchor="middle" x="0" y="58">
      @if($streak['current_streak_start'] && $streak['current_streak_end'])
        {{ \Carbon\Carbon::parse($streak['current_streak_start'])->format('M j, Y') }} - {{ \Carbon\Carbon::parse($streak['current_streak_end'])->format('M j, Y') }}
      @else
        No active streak
      @endif
    </text>
  </g>

  {{-- Divider 2 --}}
  <line class="divider" x1="330" y1="20" x2="330" y2="175"/>

  {{-- Longest Streak --}}
  <g transform="translate(412.5, 48)" class="section-3">
    <text class="stat-title" text-anchor="middle" x="0" y="0">Longest Streak</text>
    <text class="stat-value" text-anchor="middle" x="0" y="38">{{ number_format($streak['longest_streak']) }}</text>
    <text class="stat-date" text-anchor="middle" x="0" y="56">
      @if($streak['longest_streak_start'] && $streak['longest_streak_end'])
        {{ \Carbon\Carbon::parse($streak['longest_streak_start'])->format('M j, Y') }} - {{ \Carbon\Carbon::parse($streak['longest_streak_end'])->format('M j, Y') }}
      @else
        N/A
      @endif
    </text>
  </g>
</svg>
