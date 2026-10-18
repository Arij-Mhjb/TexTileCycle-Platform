{{-- Carte d'un certificat lu par l'IA. Variables : $certificate, $documentRoute (nom de route), $review (route PATCH admin, facultatif) --}}
@php $ai = $certificate->ai_extraction; @endphp
<div class="rounded-2xl border p-4 text-sm {{ $certificate->isValid() ? 'border-emerald-200 bg-emerald-50/60' : ($certificate->status === 'rejected' || $certificate->isExpired() ? 'border-rose-200 bg-rose-50/60' : 'border-pink-200 bg-pink-50/40') }} dark:border-slate-700 dark:bg-slate-800/40">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <p class="font-black">{{ $certificate->type ? (\App\Models\MaterialOriginCertificate::TYPES[$certificate->type] ?? $certificate->type) : 'Certificat (type non lu)' }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                N° <strong>{{ $certificate->certificate_number ?? '—' }}</strong>
                · {{ $certificate->issuer ?? 'organisme non lu' }}
                @if($certificate->issued_at) · émis le {{ $certificate->issued_at->format('d/m/Y') }}@endif
                @if($certificate->expires_at) · expire le {{ $certificate->expires_at->format('d/m/Y') }}@endif
            </p>
        </div>
        <x-module.badge :tone="$certificate->isValid() ? 'good' : ($certificate->status === 'pending' ? 'warn' : 'bad')">{{ $certificate->statusLabel() }}</x-module.badge>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        @if($ai)<x-module.ai-badge :source="$ai['source'] ?? 'local'"/>@if(! empty($ai['confidence']))<span class="text-xs font-bold text-pink-800 dark:text-pink-300">Confiance {{ $ai['confidence'] }} %</span>@endif @endif
        @if($certificate->document_path)<a href="{{ route($documentRoute, $certificate) }}" target="_blank" class="text-xs font-black text-purple-700 underline">Voir le document</a>@elseif($certificate->document_url)<a href="{{ $certificate->document_url }}" target="_blank" rel="noopener" class="text-xs font-black text-purple-700 underline">Voir le document</a>@endif
    </div>
    @foreach($ai['warnings'] ?? [] as $warning)<p class="mt-2 flex gap-2 text-xs text-amber-800"><span>⚠</span>{{ $warning }}</p>@endforeach
    @isset($review)@include('modules.suppliers.admin._review', ['action' => $review])@endisset
</div>
