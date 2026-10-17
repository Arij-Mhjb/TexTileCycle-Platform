@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false])
<label class="block">
    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</span>
    {{-- old() : la saisie est conservée après une erreur de validation --}}
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes->merge(['class' => 'field']) }} @error($name) aria-invalid="true" style="border-color:#f43f5e" @enderror>
    @error($name)
        <p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>
    @enderror
</label>
