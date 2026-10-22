<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Catégorie</label>
        <select name="category_id" class="field" required>
            <option value="">Choisir une catégorie</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Titre</label>
        <input name="title" value="{{ old('title', $item->title ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Résumé (affiché dans la liste)</label>
        <input name="excerpt" value="{{ old('excerpt', $item->excerpt ?? '') }}" class="field" maxlength="255">
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Contenu</label>
        <textarea name="content" rows="10" class="field" required>{{ old('content', $item->content ?? '') }}</textarea>
    </div>
    <div class="space-y-2">
        <label class="field-label">Date de publication</label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($item->published_at ?? null)->format('Y-m-d\TH:i')) }}" class="field">
        <p class="text-xs text-slate-400">Laissez vide pour garder l’article en brouillon.</p>
    </div>
</div>
