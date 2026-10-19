{{-- Ajout d'un certificat sur un lot : n'importe quelle image, l'IA lit le certificat ou dit ce qu'elle voit. Variable : $action --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" x-data="{ name: '' }"
      class="flex flex-col justify-center gap-3 rounded-2xl border-2 border-dashed border-pink-300 bg-pink-50/40 p-4 text-center">
    @csrf
    <p class="text-sm font-black text-pink-900">Ajouter un certificat</p>
    <p class="text-xs text-slate-500">Envoyez la photo ou le scan : l’IA lit le type, le numéro, l’organisme et les dates.</p>
    <label class="mx-auto cursor-pointer rounded-lg bg-white px-4 py-2 text-xs font-semibold text-pink-800 ring-1 ring-pink-200 hover:bg-pink-50">
        <span x-text="name || 'Choisir la photo du certificat'"></span>
        <input type="file" name="document" accept="image/*,.heic,.heif" required class="sr-only" @change="name = $event.target.files[0]?.name || ''">
    </label>
    <button class="mx-auto rounded-lg bg-pink-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-800" :disabled="!name" :class="!name && 'opacity-50 cursor-not-allowed'">Envoyer à l’IA</button>
</form>
