@props(['title' => 'Gestion'])
@if(auth()->user()->isAdmin())
    <x-app-layout :title="$title">{{ $slot }}</x-app-layout>
@elseif(auth()->user()->isCollector())
    <x-collector-layout :title="$title">{{ $slot }}</x-collector-layout>
@else
    <x-consumer-layout :title="$title">{{ $slot }}</x-consumer-layout>
@endif
