@props(['title' => 'Gestion'])
@if(auth()->user()->isAdmin())
    <x-app-layout :title="$title">{{ $slot }}</x-app-layout>
@else
    <x-consumer-layout :title="$title">{{ $slot }}</x-consumer-layout>
@endif
