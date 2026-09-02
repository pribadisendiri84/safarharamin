@php
  $paths = [
    'home' => '<path d="M4 11l8-7 8 7"/><path d="M6 10v10h12V10"/>',
    'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-8 8"/>',
    'quote' => '<path d="M7 11a4 4 0 014 4v4H5v-4a6 6 0 016-6"/><path d="M17 11a4 4 0 014 4v4h-6v-4a6 6 0 016-6"/>',
    'inbox' => '<path d="M12 3v10"/><path d="M8 9l4 4 4-4"/><path d="M4 15v4h16v-4"/>',
    'history' => '<path d="M4 12a8 8 0 101.6-4.8"/><path d="M4 5v4h4"/><path d="M12 8v5l3 2"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2 2 2 0 11-4 0 1.7 1.7 0 00-2.9-1.2l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 004.6 15a2 2 0 010-4 1.7 1.7 0 001.2-2.9l-.1-.1a2 2 0 112.8-2.8l.1.1A1.7 1.7 0 0011.5 4a2 2 0 014 0 1.7 1.7 0 002.9 1.2l.1-.1a2 2 0 112.8 2.8l-.1.1A1.7 1.7 0 0019.4 11a2 2 0 010 4z"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 19c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.2"/><path d="M21 19c0-2.2-1.6-3.8-3.8-4.2"/>',
    'external' => '<path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M19 14v5H5V5h5"/>',
    'plane' => '<path d="M21 4L3 11.5l7.5 1.8L14 20l2-6.5L21 4z"/><path d="M10.5 13.3L21 4"/>',
    'alert' => '<path d="M12 3l9 16H3l9-16z"/><path d="M12 9v5"/><circle cx="12" cy="17" r="1"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3 10h18"/>',
    'star' => '<path d="M12 3l2.6 5.4L21 9.2l-4.5 4.2L17.7 20 12 16.9 6.3 20l1.2-6.6L3 9.2l6.4-.8L12 3z"/>',
    'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
    'phone' => '<path d="M7 3h4l1 5-3 2a12 12 0 006 6l2-3 5 1v4c0 1-1 3-8 3C7 21 3 12 3 8c0-7 2-5 4-5z"/>',
    'check' => '<path d="M20 6L9 17l-5-5"/>',
    'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-3.5-3.5"/>',
    'upload' => '<path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 20h16"/>',
    'pin' => '<path d="M12 21s7-7.2 7-12a7 7 0 10-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.2"/>',
    'chart' => '<path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19v-7"/><path d="M21 19H3"/>',
  ];
@endphp
<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">{!! $paths[$name] ?? $paths['box'] !!}</svg>
