<div class="grid gap-5">
    <div class="space-y-2">
        <label class="field-label">Nom de la catégorie</label>
        <input name="name" value="{{ old('name', $item->name ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2">
        <label class="field-label">Description</label>
        <textarea name="description" rows="4" class="field">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
</div>
