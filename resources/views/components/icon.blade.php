@props(['name'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    @switch($name)
        @case('home')<path stroke-linecap="round" stroke-linejoin="round" d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9Z"/>@break
        @case('search')<path stroke-linecap="round" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/>@break
        @case('scan')<path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4m8 0h4v4m0 8v4h-4m-8 0H4v-4m4-8h8v8H8V8Z"/>@break
        @case('map-pin')<path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
        @case('refresh')<path stroke-linecap="round" stroke-linejoin="round" d="M20 7h-5V2m4 5a8 8 0 0 0-13-2M4 17h5v5m-4-5a8 8 0 0 0 13 2"/>@break
        @case('shirt')<path stroke-linecap="round" stroke-linejoin="round" d="m8 4 4 2 4-2 5 3-3 5-2-1v10H8V11l-2 1-3-5 5-3Z"/>@break
        @case('repair')<path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 0 0-5-5l2.1 2.1-2.8 2.8-2.1-2.1a4 4 0 0 0 5 5L20 17.2a2 2 0 1 1-2.8 2.8l-8.1-8.1"/>@break
        @case('heart')<path stroke-linecap="round" stroke-linejoin="round" d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>@break
        @case('recycle')<path stroke-linecap="round" stroke-linejoin="round" d="m9 4 3-2 3 2m-6 0 3 5 3-5M5 9l-3 2v4m3-6 3 5-6 1m17-6 3 2v4m-3-6-3 5 6 1M8 20l4 2 4-2m-8 0 3-5h2l3 5"/>@break
        @case('star')<path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.8 5.8 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.7l6.2-.9L12 3Z"/>@break
        @case('scale')<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M5 6h14M5 6l-3 7h6L5 6Zm14 0-3 7h6l-3-7ZM8 21h8"/>@break
        @case('layers')<path stroke-linecap="round" stroke-linejoin="round" d="m12 3 9 5-9 5-9-5 9-5Zm9 10-9 5-9-5m18 5-9 5-9-5"/>@break
        @case('chart')<path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>@break
        @case('check')<path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>@break
        @case('document')<path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8m-8 4h6"/>@break
        @case('upload')<path stroke-linecap="round" stroke-linejoin="round" d="M12 16V3m0 0L7 8m5-5 5 5M5 14v6h14v-6"/>@break
        @case('image')<path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4Zm0 12 5-5 4 4 2-2 5 5M15 8h.01"/>@break
        @case('arrow-up')<path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7m0 0H8m9 0v9"/>@break
        @case('chevron-right')<path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>@break
        @default<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
    @endswitch
</svg>
