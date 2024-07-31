@php use Carbon\Carbon;use LaravelWebauthn\Actions\PrepareCreationData; @endphp

<x-action-section>
    <x-slot name="title">
        {{ __('Passkeys') }}
    </x-slot>

    <x-slot name="description">
        {{__('Log in with your fingerprint, face recognition or a PIN instead of a password. Passkeys can be synced
        across devices logged into the same platform (like Apple ID or a Google account)')}}
    </x-slot>

    <!-- Passkey List -->
    <x-slot name="content">
        <div class="space-y-3">
            @foreach ($passkeys as $i => $passkey)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="ml-4">
                            #{{$i +1}} {{ $passkey->name }}
                            <p class="mt-1 text-xs text-gray-600">
                                Created: {{ Carbon::parse($passkey->created_at)->format('M d, Y \a\t H:i a ') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <!-- Delete Passkey -->
                        <x-confirms-passkey-or-password wire:then="removePasskey({{$passkey->id}})">
                            <button type="button" wire:loading.attr="disabled" class="cursor-pointer ml-6 text-sm text-red-500">
                                {{ __('Remove') }}
                            </button>
                        </x-confirms-passkey-or-password>
                    </div>
                </div>
                <hr/>
            @endforeach

            <x-confirms-passkey-or-password wire:then="confirmCreateNewPasskey">
                <x-button @class(['mt-5' => count($passkeys) > 0]) type="button" wire:loading.attr="disabled">
                    {{ __('Create a new passkey') }}
                </x-button>
            </x-confirms-passkey-or-password>
        </div>


        @once
        <x-dialog-modal wire:model="showingConfirmation">
            <x-slot name="title">
                {{ __('Create a new passkey') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Please enter a name for your new passkey that you can easily recognize later.') }}

                <div class="mt-4" x-data="{}"
                     x-on:confirming-create-new-passkey.window="setTimeout(() => $refs.passkeyName.focus(), 250)">
                    <x-input type="text" class="mt-1 block w-3/4"
                             placeholder="{{ __('My Yubikey') }}"
                             x-ref="passkeyName"
                             required
                             wire:model.defer="addNewPasskeyForm.name"
                             wire:keydown.enter="createNewPasskey"/>

                    <x-input-error for="addNewPasskeyForm.name" class="mt-2"/>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('showingConfirmation')"
                                    wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-button class="ml-3" wire:click="createNewPasskey" wire:loading.attr="disabled">
                    {{ __('Create a new passkey') }}
                </x-button>
            </x-slot>
        </x-dialog-modal>
        @endonce
    </x-slot>
</x-action-section>

