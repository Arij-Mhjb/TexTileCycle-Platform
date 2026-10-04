<div class="grid gap-7 lg:grid-cols-[280px_1fr]">
    <aside x-data="{ preview: '{{ $item->exists ? $item->image_url : '' }}', fileName: '' }">
        <label class="text-sm font-bold text-slate-700 dark:text-slate-200">Photo du produit</label>
        <div class="group relative mt-2 aspect-[4/5] overflow-hidden rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-emerald-400 dark:border-slate-700 dark:bg-slate-900">
            <template x-if="preview">
                <img :src="preview" alt="Aperçu du produit" class="h-full w-full object-cover">
            </template>
            <div x-show="!preview" class="absolute inset-0 grid place-items-center p-6 text-center">
                <div><span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"><x-icon name="image" class="h-7 w-7" /></span><p class="mt-4 text-sm font-black">Ajoutez une photo nette</p><p class="mt-1 text-xs leading-5 text-slate-400">JPG, PNG ou WebP · 4 Mo maximum</p></div>
            </div>
            <div x-show="preview" class="absolute inset-x-3 bottom-3 rounded-2xl bg-slate-950/75 px-4 py-3 text-xs font-bold text-white opacity-0 backdrop-blur transition group-hover:opacity-100">Cliquez ci-dessous pour remplacer la photo</div>
        </div>
        <label class="mt-3 flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <x-icon name="upload" class="h-5 w-5" />
            <span x-text="fileName || '{{ $item->image_path ? 'Remplacer la photo' : 'Choisir une photo' }}'"></span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="const file = $event.target.files[0]; if (file) { fileName = file.name; preview = URL.createObjectURL(file); }">
        </label>
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
        @if($item->image_path)
            <label class="mt-3 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"><input type="checkbox" name="remove_image" value="1" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500"> Supprimer la photo actuelle</label>
        @endif
    </aside>

    <div class="grid gap-5 md:grid-cols-2">
        <x-ui.input name="name" label="Nom du produit" :value="$item->name" required />
        <x-ui.input name="brand" label="Marque" :value="$item->brand" required />
        <x-ui.input name="sku" label="Référence / SKU" :value="$item->sku" required />
        <x-ui.input name="category" label="Catégorie" :value="$item->category" required />
        <x-ui.input name="year" label="Année" type="number" :value="$item->year" min="1900" :max="date('Y') + 1" />
        <x-ui.select name="status" label="Statut" :value="$item->status ?: 'draft'" :options="['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé']" required />
        <div class="md:col-span-2"><x-ui.textarea name="description" label="Description" :value="$item->description" /></div>
        <div class="md:col-span-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200">Matières associées</label>
            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($materials as $material)
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-700"><input type="checkbox" name="material_ids[]" value="{{ $material->id }}" @checked(in_array($material->id, old('material_ids', $item->materials->pluck('id')->all()))) class="rounded text-emerald-600 focus:ring-emerald-500">{{ $material->name }}</label>
                @endforeach
            </div>
        </div>
        <div class="grid gap-3 md:col-span-2 sm:grid-cols-3"><x-ui.checkbox name="repairable" label="Réparable" :checked="$item->repairable" /><x-ui.checkbox name="reusable" label="Réutilisable" :checked="$item->reusable" /><x-ui.checkbox name="recyclable" label="Recyclable" :checked="$item->recyclable" /></div>
        @foreach(['environmental_score' => 'Score environnemental', 'circularity_score' => 'Score de circularité', 'animal_free_score' => 'Score sans matière animale', 'traceability_score' => 'Score de traçabilité'] as $name => $label)
            <x-ui.input :name="$name" :label="$label" type="number" :value="$item->$name" min="0" max="100" />
        @endforeach
    </div>
</div>
