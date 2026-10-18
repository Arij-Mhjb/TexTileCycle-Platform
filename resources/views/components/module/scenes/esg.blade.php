{{-- Impact ESG : colonnes d'impact qui décroissent, avec une feuille au sommet. --}}
<svg viewBox="0 0 280 230" class="w-full drop-shadow-[0_30px_30px_rgba(0,20,40,.45)]">
    {!! \App\Support\Isometric::ground(8.6) !!}
    {!! \App\Support\Isometric::box(-3.6, -1, 0, 1.4, 1.4, 5.2, ['#e0f2fe', '#38bdf8', '#0369a1']) !!}
    {!! \App\Support\Isometric::box(-1.6, -1, 0, 1.4, 1.4, 3.8, ['#cffafe', '#22d3ee', '#0e7490']) !!}
    {!! \App\Support\Isometric::box(0.4, -1, 0, 1.4, 1.4, 2.4, ['#ccfbf1', '#2dd4bf', '#0f766e']) !!}
    {!! \App\Support\Isometric::box(2.4, -1, 0, 1.4, 1.4, 1.2, ['#dcfce7', '#4ade80', '#15803d']) !!}
    <g transform="translate({{ \App\Support\Isometric::point(3.1, -0.3, 1.2) }})">
        <path d="M0 -4 C -18 -10 -20 -34 0 -44 C 20 -34 18 -10 0 -4 Z" fill="#86efac" stroke="#fff" stroke-width="1.5"/>
        <path d="M0 -6 L0 -40" stroke="#15803d" stroke-width="1.6" stroke-linecap="round"/>
    </g>
</svg>
