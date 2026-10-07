<x-guest-layout>
<form method="POST" action="{{ route('register') }}" novalidate
    x-data="brandRegistration(@js(old('role', 'consumer')), @js(old('answers', [])), @js($questions))"
    @submit="submit($event)">
    @csrf
    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700" x-text="role === 'brand' ? 'Parcours marque · étape ' + (step + 1) + ' sur 6' : 'Bienvenue chez TexTileCycle'"></p>
    <h1 class="mt-2 text-2xl font-black" x-text="titles[step]">Créer un compte</h1>
    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100" x-show="role === 'brand'"><div class="h-full bg-emerald-600 transition-all" :style="'width:' + ((step + 1) / 6 * 100) + '%'"></div></div>
    @if($errors->any())
    <div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800"><p class="font-bold">Veuillez vérifier les réponses suivantes :</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div x-show="step === 0" class="mt-6 space-y-4">
        <div><x-input-label for="name" value="Nom du responsable"/><x-text-input id="name" class="mt-1 block w-full" name="name" :value="old('name')" required autofocus maxlength="255"/></div>
        <div><x-input-label for="email" value="E-mail"/><x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required maxlength="255"/></div>
        <div><x-input-label for="role" value="Type de compte"/><select id="role" name="role" x-model="role" class="mt-1 block w-full rounded-md border-gray-300"><option value="consumer">Consommateur</option><option value="brand">Marque</option></select></div>
        <div x-show="role === 'brand'" x-cloak><x-input-label for="brand_name" value="Nom de la marque"/><x-text-input id="brand_name" class="mt-1 block w-full" name="brand_name" :value="old('brand_name')" x-bind:required="role === 'brand'" x-bind:disabled="role !== 'brand'" maxlength="255"/><p class="mt-2 text-xs leading-5 text-slate-500">Un questionnaire précis nous permettra de vous orienter. Votre compte sera accessible après validation administrateur.</p></div>
        <div><x-input-label for="password" value="Mot de passe"/><x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required minlength="8" autocomplete="new-password"/></div>
        <div><x-input-label for="password_confirmation" value="Confirmer le mot de passe"/><x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"/></div>
    </div>
    @foreach($questions as $key => $question)
    <div class="mt-5" x-show="role === 'brand' && step === {{ $question['section'] }} && visible('{{ $key }}')" x-cloak>
        <label for="answer_{{ $key }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $question['label'] }}</label>
        @if(isset($question['options']))
        <select id="answer_{{ $key }}" name="answers[{{ $key }}]" x-model="answers.{{ $key }}" :required="role === 'brand' && visible('{{ $key }}')" :disabled="role !== 'brand' || !visible('{{ $key }}')" class="mt-2 block w-full rounded-lg border-gray-300 text-sm dark:bg-slate-800 dark:text-white">
            <option value="">Choisir une réponse</option>@foreach($question['options'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
        </select>
        @elseif(($question['type'] ?? '') === 'textarea')
        <textarea id="answer_{{ $key }}" name="answers[{{ $key }}]" x-model="answers.{{ $key }}" :required="role === 'brand' && visible('{{ $key }}')" :disabled="role !== 'brand' || !visible('{{ $key }}')" rows="3" maxlength="3000" class="mt-2 block w-full rounded-lg border-gray-300 text-sm dark:bg-slate-800 dark:text-white" placeholder="Décrivez vos pratiques ou indiquez ce qui n’est pas encore documenté."></textarea>
        @else
        <input id="answer_{{ $key }}" name="answers[{{ $key }}]" x-model="answers.{{ $key }}" :required="role === 'brand' && visible('{{ $key }}')" :disabled="role !== 'brand' || !visible('{{ $key }}')" type="{{ $question['type'] }}" @if($question['type'] === 'number') min="0" max="100" step="0.01" @else maxlength="3000" @endif class="mt-2 block w-full rounded-lg border-gray-300 text-sm dark:bg-slate-800 dark:text-white">
        @endif
    </div>
    @endforeach
    <div x-show="role === 'brand' && step === 5" x-cloak class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-950">
        <p>Ces réponses servent à proposer des démarches, pas à attribuer une certification. L’administrateur peut compléter ou retirer les recommandations.</p>
        <label class="mt-4 flex items-start gap-3"><input type="checkbox" name="analysis_consent" value="1" @checked(old('analysis_consent')) :required="role === 'brand'" :disabled="role !== 'brand'" class="mt-1 rounded border-emerald-300"><span>J’accepte que les réponses soient transmises à OpenAI pour l’analyse, examinées par l’administrateur et conservées avec sa décision pour la traçabilité. Je confirme leur exactitude et n’inclus aucun secret ni donnée personnelle inutile.</span></label>
    </div>
    <div class="mt-7 flex items-center justify-between gap-3">
        <a x-show="step === 0" class="text-sm underline" href="{{ route('login') }}">Déjà inscrit ?</a>
        <button type="button" x-show="step > 0" x-cloak @click="step--" class="text-sm font-semibold text-slate-600">Précédent</button>
        <x-primary-button><span x-text="role === 'brand' ? (step < 5 ? 'Continuer' : 'Envoyer mon dossier') : 'S’inscrire'">S’inscrire</span></x-primary-button>
    </div>
    <noscript><p class="mt-4 text-sm">Activez JavaScript pour compléter le questionnaire marque.</p></noscript>
</form>
</x-guest-layout>
