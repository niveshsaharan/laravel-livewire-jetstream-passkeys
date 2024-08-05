@php use LaravelWebauthn\Actions\PrepareAssertionData;use LaravelWebauthn\Actions\PrepareCreationData; @endphp
<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form x-data="loginForm" x-init="login" method="POST" action="{{ route('login') }}">

            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input @blur="can_login_with_passkey" autocomplete="email" id="email" class="block mt-1 w-full" type="email" name="email"
                         x-model="email" required
                         autofocus/>
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-4">
                <x-button ::class="publicKey ? '' : 'invisible'" type="button" @click="login" title="Passkey">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </x-button>

                <div class="flex items-center justify-end">
                @if (Route::has('password.request'))
                        <a class="underline text-sm text-gray-600 hover:text-gray-900"
                           href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif


                    <x-button class="ml-4">
                    {{ __('Log in') }}
                </x-button>
            </div>
            </div>
        </form>
    </x-authentication-card>

    <script>
        document.addEventListener('alpine:init', () => {

            const webauthn = new WebAuthn();

            Alpine.data('loginForm', () => ({
                email: '{{old('email')}}',
                supports_webauthn: webauthn.webAuthnSupport(),
                invalids: [],
                userless: parseInt("{{in_array(config('webauthn.userless'), ['required','preferred']) ? "1": "0"}}"),
                publicKey: @if(in_array(config('webauthn.userless'), ['required','preferred'])) @json(\LaravelWebauthn\Facades\Webauthn::prepareAssertion(null))@else @json(null)@endif,

                async can_login_with_passkey() {

                    if(this.userless){
                        return true;
                    }

                    const emailInput = document.getElementById("email");

                    const shouldShow = this.supports_webauthn && this.email && emailInput && emailInput.checkValidity() && !this.invalids.includes(this.email);

                    if (shouldShow) {
                        const response = await axios.post("{{ route('webauthn.auth.options') }}", {email: this.email || ''}).catch(e => {
                        });

                        if (!response) {
                            const invalids = this.invalids;
                            invalids.push(this.email);
                            this.invalids = invalids
                        } else {
                            this.publicKey = response.data.publicKey
                            return true
                        }
                    }

                    this.publicKey = null
                    return false;
                },

                login() {
                    if (this.publicKey) {
                        webauthn.sign(
                            this.publicKey,
                            function (data) {
                                axios.post("{{ route('webauthn.auth') }}", {
                                    ...data,
                                })
                                    .then(function (response) {
                                        if (response.data.callback) {
                                            window.location.href = response.data.callback;
                                        }
                                    })
                                    .catch(function (error) {
                                        if(error?.response.status === 422){
                                            alert("Passkey is not valid.")
                                        }else{
                                            alert(error?.response?.data?.message || error);
                                        }
                                    });
                            }
                        );
                    }
                }
            }));
        });
    </script>
</x-guest-layout>
