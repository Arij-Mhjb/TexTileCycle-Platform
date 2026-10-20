<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Partenaire</label>
        <select name="repair_partner_id" class="field">
            <option value="">Aucun partenaire associé</option>
            @foreach($partners as $partner)
                <option value="{{ $partner->id }}" @selected(old('repair_partner_id', $item->repair_partner_id ?? '') == $partner->id)>{{ $partner->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Nom de l’atelier</label>
        <input name="name" value="{{ old('name', $item->name ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2">
        <label class="field-label">Ville</label>
        <input name="city" value="{{ old('city', $item->city ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2">
        <label class="field-label">Capacité / semaine</label>
        <input type="number" min="1" name="capacity_per_week" value="{{ old('capacity_per_week', $item->capacity_per_week ?? '') }}" class="field">
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Adresse</label>
        <input name="address" value="{{ old('address', $item->address ?? '') }}" class="field" required>
    </div>
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Spécialités</label>
        <input name="specialties" value="{{ old('specialties', $item->specialties ?? '') }}" class="field">
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
    <div class="space-y-2 md:col-span-2">
        <label class="field-label">Téléphone</label>
        <input name="phone" value="{{ old('phone', $item->phone ?? '') }}" class="field">
    </div>
    <label class="mt-2 flex items-center gap-3 text-sm text-slate-700 md:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
        Atelier actif
    </label>
</div>
