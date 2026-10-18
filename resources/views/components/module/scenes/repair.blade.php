{{-- Seconde vie : un vêtement sur cintre posé sur son colis, entouré d'une boucle de circularité. --}}
<svg viewBox="0 0 280 230" class="w-full drop-shadow-[0_30px_30px_rgba(40,15,0,.45)]">
    {!! \App\Support\Isometric::ground(8.6) !!}
    <ellipse cx="140" cy="{{ explode(',', \App\Support\Isometric::point(0, 0, 0))[1] }}" rx="128" ry="64" fill="none" stroke="#fde68a" stroke-width="2" stroke-dasharray="70 18" opacity=".8"/>
    {!! \App\Support\Isometric::box(-2.4, -2.0, 0, 4.6, 4.0, 2.2, ['#fde68a', '#f59e0b', '#b45309']) !!}
    {!! \App\Support\Isometric::box(-0.3, -2.0, 2.2, 0.5, 4.0, 0.02, ['#fffbeb', '#fef3c7', '#fde68a']) !!}
    <g transform="translate({{ \App\Support\Isometric::point(-0.1, 0, 2.2) }})">
        <path d="M0 -76 a 6 6 0 1 1 6 6 l 0 5 L 34 -48 L -34 -48 L 0 -65" fill="none" stroke="#fff" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" transform="translate(-6 0)"/>
        <path d="M-40 -46 L -20 -54 L 0 -46 L 20 -54 L 40 -46 L 34 -26 L 24 -28 L 24 -4 L -24 -4 L -24 -28 L -34 -26 Z" fill="#fff7ed" stroke="#fdba74" stroke-width="1.5" stroke-linejoin="round"/>
        <path d="M-6 -50 L 0 -44 L 6 -50" fill="none" stroke="#f97316" stroke-width="1.5"/>
    </g>
</svg>
