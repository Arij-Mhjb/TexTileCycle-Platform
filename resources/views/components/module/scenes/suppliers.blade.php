{{-- Fournisseurs : balles de tissu sur une palette, reliées par un trajet tracé jusqu'au point d'origine. --}}
<svg viewBox="0 0 280 230" class="w-full drop-shadow-[0_30px_30px_rgba(20,0,30,.45)]">
    {!! \App\Support\Isometric::ground(8.6) !!}
    <path d="M {{ \App\Support\Isometric::point(3.6, -3.2, 0) }} Q {{ \App\Support\Isometric::point(4.6, 0, 0) }} {{ \App\Support\Isometric::point(2.4, 3.2, 0.05) }}" fill="none" stroke="#f9a8d4" stroke-width="2" stroke-dasharray="2 5" stroke-linecap="round"/>
    {!! \App\Support\Isometric::box(-3.4, -2.4, 0, 4.6, 3.6, 0.45, ['#d6b48a', '#a4825a', '#8a6a45']) !!}
    {!! \App\Support\Isometric::box(-3.2, -2.2, 0.45, 2.1, 3.2, 1.6, ['#f0abfc', '#a855f7', '#7e22ce']) !!}
    {!! \App\Support\Isometric::box(-1.0, -2.2, 0.45, 2.1, 3.2, 1.6, ['#fbcfe8', '#ec4899', '#be185d']) !!}
    {!! \App\Support\Isometric::box(-2.2, -1.6, 2.05, 2.2, 2.4, 1.4, ['#e9d5ff', '#c084fc', '#9333ea']) !!}
    {!! \App\Support\Isometric::box(-1.6, -0.2, 3.45, 0.9, 0.08, 0.9, ['#fff', '#fdf2f8', '#fce7f3']) !!}
    <g transform="translate({{ \App\Support\Isometric::point(3.6, -3.2, 0) }})">
        <ellipse cx="0" cy="0" rx="9" ry="4.5" fill="rgba(0,0,0,.25)"/>
        <path d="M0 0 C -9 -14 -11 -20 -11 -25 a 11 11 0 0 1 22 0 c 0 5 -2 11 -11 25 Z" fill="#fde047" stroke="#fff" stroke-width="1.5"/>
        <circle cx="0" cy="-25" r="4.2" fill="#7e22ce"/>
    </g>
</svg>
