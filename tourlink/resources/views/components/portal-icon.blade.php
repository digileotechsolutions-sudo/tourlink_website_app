<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('dashboard')<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="11" width="7" height="10" rx="1.5"/><rect x="3" y="14" width="8" height="7" rx="1.5"/>@break
        @case('route')<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-13 4h3m-3 3h7"/>@break
        @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7a4 4 0 0 1 0 8m2 3h2a3 3 0 0 1 3 3v1"/>@break
        @case('vehicle')<path d="m5 11 1.5-5h11l1.5 5m-15 0h18v8H4v-8Zm2 8v2m12-2v2M7 15h.01M17 15h.01"/>@break
        @case('wallet')<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 9h18m-5 5h2"/>@break
        @case('receipt')<path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3Zm4 5h6m-6 4h6m-6 4h3"/>@break
        @case('star')<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>@break
        @case('message')<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>@break
        @case('gift')<path d="M20 12v9H4v-9m-2-5h20v5H2V7Zm10 14V7m0 0H7.5a2.5 2.5 0 1 1 2.4-3.2L12 7Zm0 0h4.5a2.5 2.5 0 1 0-2.4-3.2L12 7Z"/>@break
        @case('chart')<path d="M4 19V5m0 14h17M8 15l3-4 3 2 5-7"/>@break
        @case('shield')<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>@break
        @case('settings')<circle cx="12" cy="12" r="3"/><path d="M19 13.5a2 2 0 0 0 .4 2.2l.1.1-2.1 2.1-.1-.1a2 2 0 0 0-2.2-.4 2 2 0 0 0-1.2 1.8v.2h-3v-.2a2 2 0 0 0-1.2-1.8 2 2 0 0 0-2.2.4l-.1.1-2.1-2.1.1-.1a2 2 0 0 0 .4-2.2A2 2 0 0 0 4 12.3h-.2v-3H4a2 2 0 0 0 1.8-1.2 2 2 0 0 0-.4-2.2l-.1-.1 2.1-2.1.1.1a2 2 0 0 0 2.2.4 2 2 0 0 0 1.2-1.8v-.2h3v.2a2 2 0 0 0 1.2 1.8 2 2 0 0 0 2.2-.4l.1-.1 2.1 2.1-.1.1a2 2 0 0 0-.4 2.2 2 2 0 0 0 1.8 1.2h.2v3H21a2 2 0 0 0-2 1.2Z"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
        @case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>@break
        @case('history')<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8m0-5v5h5m4-1v5l3 2"/>@break
        @case('document')<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6m-11 4h8m-8 4h8"/>@break
        @case('database')<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5m-18 7c0 1.7 4 3 9 3s9-1.3 9-3"/>@break
        @case('clipboard')<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1m-6 6h6m-6 4h6m-6 4h3"/>@break
    @endswitch
</svg>