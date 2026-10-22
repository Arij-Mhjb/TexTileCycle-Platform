<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label class="field-label">Catégorie</label>
        <select name="chatbot_category_id" class="field" required>
            <option value="">Choisir une catégorie</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('chatbot_category_id', $item->chatbot_category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-2">
        <label class="field-label">Ordre d'affichage</label>
        <input type="number" min="0" name="position" value="{{ old('position', $item->position ?? 0) }}" class="field">
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Question</label>
        <input name="question" value="{{ old('question', $item->question ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Mots-clés (séparés par des virgules)</label>
        <input name="keywords" value="{{ old('keywords', $item->keywords ?? '') }}" class="field" placeholder="retour, don, vêtement usagé">
        <p class="text-xs text-slate-400">Utilisés pour reconnaître la question posée par un visiteur, en plus du texte de la question.</p>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Réponse</label>
        <textarea name="answer" rows="6" class="field" required>{{ old('answer', $item->answer ?? '') }}</textarea>
    </div>
    <div class="flex items-center gap-2 md:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <label for="is_active" class="text-sm font-semibold text-slate-700">Réponse active (visible par le chatbot)</label>
    </div>
</div>
