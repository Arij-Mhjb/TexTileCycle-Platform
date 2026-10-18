{{-- Liens marque des modules : Fournisseurs (violet-rose), Impact ESG (sky), Seconde vie (amber). --}}
<a href="{{ route('brand.suppliers.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.suppliers.*', 'brand.traceability.*') ? 'bg-purple-50 text-purple-700' : 'text-purple-700 hover:bg-purple-50' }}">Fournisseurs</a>
<a href="{{ route('brand.impact.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.impact.*') ? 'bg-sky-50 text-sky-700' : 'text-sky-700 hover:bg-sky-50' }}">Impact ESG</a>
<a href="{{ route('brand.marketplace.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.marketplace.*') ? 'bg-amber-50 text-amber-800' : 'text-amber-700 hover:bg-amber-50' }}">Seconde vie</a>
