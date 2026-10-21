<x-consumer-layout :title="$member->name">
    <section class="consumer-card mb-7 flex flex-wrap items-center gap-5 p-6">
        <span class="grid h-24 w-24 place-items-center overflow-hidden rounded-3xl bg-emerald-100 text-3xl font-black text-emerald-700">@if($member->profile_photo_url)<img src="{{ $member->profile_photo_url }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr($member->name,0,1)) }}@endif</span>
        <div><p class="text-xs font-black uppercase tracking-widest text-emerald-600">Membre de la communauté</p><h1 class="mt-1 text-3xl font-black">{{ $member->name }}</h1><p class="mt-2 text-sm text-slate-500">{{ $posts->total() }} publication(s)</p></div>
        <a href="{{ route('consumer.community.index') }}" class="btn-secondary ml-auto">Retour au fil</a>
    </section>
    <div class="mx-auto max-w-3xl space-y-5">@forelse($posts as $post)@include('community._post',['post'=>$post])@empty<x-ui.empty message="Ce membre n’a encore rien publié."/>@endforelse</div>
    <div class="mx-auto mt-7 max-w-3xl">{{ $posts->links() }}</div>
</x-consumer-layout>
