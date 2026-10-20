<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Nom du partenaire</label>
        <input name="name" value="{{ old('name', $item->name ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2">
        <label class="field-label">Type</label>
        <select name="type" class="field" required>
            @foreach(['atelier' => 'Atelier', 'partenaire' => 'Partenaire', 'collectif' => 'Collectif'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $item->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-2">
        <label class="field-label">Ville</label>
        <input name="city" value="{{ old('city', $item->city ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Adresse</label>
        <input name="address" value="{{ old('address', $item->address ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Description</label>
        <textarea name="description" rows="4" class="field">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
    <div class="space-y-2">
        <label class="field-label">Contact</label>
        <input name="contact_name" value="{{ old('contact_name', $item->contact_name ?? '') }}" class="field">
    </div>
    <div class="space-y-2">
        <label class="field-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $item->email ?? '') }}" class="field">
    </div>
    <div class="space-y-2">
        <label class="field-label">Téléphone</label>
        <input name="phone" value="{{ old('phone', $item->phone ?? '') }}" class="field">
    </div>
    <div class="space-y-2">
        <label class="field-label">Site web</label>
        <input type="url" name="website" value="{{ old('website', $item->website ?? '') }}" class="field">
    </div>
    <label class="mt-2 flex items-center gap-3 text-sm text-slate-700 md:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
        Partenaire actif
    </label>
</div>
