@props(['source' => 'local'])
@php
[$label, $class] = match ($source) {
    'gemini', 'gemini-vision' => ['✦ IA Gemini'.($source === 'gemini-vision' ? ' Vision' : ''), 'bg-gradient-to-r from-fuchsia-600 to-violet-600 text-white'],
    'huggingface' => ['🤗 Hugging Face', 'bg-gradient-to-r from-amber-400 to-yellow-300 text-slate-900'],
    'ocr' => ['OCR local (Tesseract)', 'bg-slate-800 text-white'],
    'none' => ['IA indisponible', 'bg-slate-200 text-slate-600'],
    default => ['Analyse locale (repli IA)', 'bg-slate-800 text-white'],
};
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold '.$class]) }}>{{ $label }}</span>
