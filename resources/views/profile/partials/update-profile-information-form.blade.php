<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Informations du profil
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Modifiez vos informations personnelles, votre adresse e-mail et votre photo.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ preview: @js($user->profile_photo_url) }">
        @csrf
        @method('patch')

        <div class="flex flex-wrap items-center gap-5">
            <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-3xl bg-emerald-100 text-2xl font-black text-emerald-700">
                <img x-show="preview" x-bind:src="preview" alt="Photo de profil" class="h-full w-full object-cover">
                <span x-show="!preview">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            </div>
            <label class="block flex-1">
                <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Photo de profil</span>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" data-max-bytes="3145728" data-validation-message="La photo doit être au format JPG, PNG ou WebP et ne pas dépasser 3 Mo." class="mt-2 block w-full rounded-xl border border-gray-300 p-3 text-sm dark:border-gray-700" @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : @js($user->profile_photo_url)">
                <span class="mt-1 block text-xs text-gray-500">JPG, PNG ou WebP · 3 Mo maximum.</span>
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </label>
        </div>

        <div>
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </form>
</section>
