@props(['name' => 'circle'])

<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('grid')
            <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>
            @break
        @case('document')
            <path d="M7 3h7l5 5v13H7z"></path><path d="M14 3v5h5"></path><path d="M9 13h6"></path><path d="M9 17h6"></path>
            @break
        @case('upload')
            <path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M5 20h14"></path>
            @break
        @case('shield')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            @break
        @case('archive')
            <path d="M4 7h16"></path><path d="M6 7v12h12V7"></path><path d="M9 11h6"></path><path d="M5 3h14v4H5z"></path>
            @break
        @case('chart')
            <path d="M4 19V5"></path><path d="M4 19h16"></path><path d="M8 16V9"></path><path d="M13 16V6"></path><path d="M18 16v-4"></path>
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path>
            @break
        @case('building')
            <path d="M4 21h16"></path><path d="M6 21V5h12v16"></path><path d="M9 8h1"></path><path d="M14 8h1"></path><path d="M9 12h1"></path><path d="M14 12h1"></path><path d="M10 21v-5h4v5"></path>
            @break
        @case('settings')
            <path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3.4-.1-.1a1.7 1.7 0 0 0-1.9-.3 7.8 7.8 0 0 1-1.6.7 1.7 1.7 0 0 0-1.3 1.6V22H9.1v-.2a1.7 1.7 0 0 0-1.3-1.6 7.8 7.8 0 0 1-1.6-.7 1.7 1.7 0 0 0-1.9.3l-.1.1-2-3.4.1-.1a1.7 1.7 0 0 0 .3-1.9A8.8 8.8 0 0 1 2 12c0-.6.1-1.2.3-1.8A1.7 1.7 0 0 0 2 8.3l-.1-.1 2-3.4.1.1a1.7 1.7 0 0 0 1.9.3c.5-.3 1-.5 1.6-.7A1.7 1.7 0 0 0 8.8 3V2h3.8v1a1.7 1.7 0 0 0 1.3 1.6c.6.2 1.1.4 1.6.7a1.7 1.7 0 0 0 1.9-.3l.1-.1 2 3.4-.1.1a1.7 1.7 0 0 0-.3 1.9c.2.6.3 1.2.3 1.8s-.1 1.2-.3 1.8z"></path>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M21 3v18"></path>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"></path>
            @break
        @case('chevrons-left')
            <path d="m11 17-5-5 5-5"></path><path d="m18 17-5-5 5-5"></path>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"></path>
            @break
        @case('edit')
            <path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"></path>
            @break
        @case('inbox')
            <path d="M4 4h16l-2 10H6z"></path><path d="M4 14h5l2 3h2l2-3h5"></path><path d="M4 14v6h16v-6"></path>
            @break
        @case('file')
            <path d="M6 3h9l3 3v15H6z"></path><path d="M15 3v4h4"></path>
            @break
        @case('sparkle')
            <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"></path><path d="M19 17l.8 2.2L22 20l-2.2.8L19 23l-.8-2.2L16 20l2.2-.8z"></path>
            @break
        @case('eye')
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>
            @break
        @case('trash')
            <path d="M4 7h16"></path><path d="M9 7V4h6v3"></path><path d="M7 7l1 14h8l1-14"></path>
            @break
        @default
            <circle cx="12" cy="12" r="8"></circle>
    @endswitch
</svg>
