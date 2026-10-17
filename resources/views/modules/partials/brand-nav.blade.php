{{-- Liens marque des modules : Fournisseurs (indigo), Impact ESG (sky), Seconde vie (amber). --}}
<a href="{{ route('brand.suppliers.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.suppliers.*', 'brand.traceability.*') ? 'bg-indigo-50 text-indigo-700' : 'text-indigo-700 hover:bg-indigo-50' }}">Fournisseurs</a>
<a href="{{ route('brand.impact.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.impact.*') ? 'bg-sky-50 text-sky-700' : 'text-sky-700 hover:bg-sky-50' }}">Impact ESG</a>
<a href="{{ route('brand.marketplace.index') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('brand.marketplace.*') ? 'bg-amber-50 text-amber-800' : 'text-amber-700 hover:bg-amber-50' }}">Seconde vie</a>
