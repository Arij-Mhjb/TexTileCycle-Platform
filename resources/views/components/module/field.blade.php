@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false])
<label class="block">
    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</span>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes->merge(['class' => 'field']) }}>
    <x-input-error :messages="$errors->get($name)" class="mt-1"/>
</label>
