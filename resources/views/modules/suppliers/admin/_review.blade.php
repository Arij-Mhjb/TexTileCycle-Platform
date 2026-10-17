{{-- Formulaire de revue réutilisable : $action (route PATCH) --}}
<form method="POST" action="{{ $action }}" class="mt-3 flex flex-wrap items-center gap-2">
    @csrf @method('PATCH')
    <input name="note" placeholder="Commentaire (facultatif)" class="field !mt-0 min-w-0 flex-1 !py-1.5 text-xs">
    <button name="status" value="verified" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500">Valider</button>
    <button name="status" value="rejected" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Refuser</button>
</form>
