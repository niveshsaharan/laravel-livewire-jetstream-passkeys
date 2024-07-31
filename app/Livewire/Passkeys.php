<?php

namespace App\Livewire;

use App\Traits\ConfirmsPasskey;
use Illuminate\Support\Facades\Auth;
use LaravelWebauthn\Actions\PrepareCreationData;
use Livewire\Component;

class Passkeys extends Component
{
    use ConfirmsPasskey;

    public $supported = false;

    public $passkeys = [];

    /**
     * The "add new passkey" form state.
     *
     * @var array
     */
    public $addNewPasskeyForm = [
        'name' => '',
    ];

    public $confirmingCreateNewPasskey = false;
    public $showCreatePasskeyForm = false;

    /**
     * Indicates if the input and button are being displayed.
     *
     * @var bool
     */
    public $showingConfirmation = false;

    protected $listeners = [
        'passkey-saved' => 'saved',
        'passkey-deleted' => 'reload',
        'passkey-supported' => 'supported',
    ];

    public function mount(): void
    {
        $this->passkeys = $this->user->passkeys()->orderBy('name', 'asc')->get();
    }

    public function confirmCreateNewPasskey()
    {
        if($this->canConfirmPasskey){
            $this->ensurePasskeyIsConfirmed();
        }else{
            $this->ensurePasswordIsConfirmed();
        }

        $this->showingConfirmation = true;
    }


    public function saved(): void
    {
        $this->reload();
        $this->reset('addNewPasskeyForm');
    }

    public function reload(): void
    {
        $this->passkeys = $this->user->passkeys()->orderBy('created_at', 'asc')->get();
    }

    public function supported($supported): void
    {
        $this->supported = (bool) $supported;
    }

    /**
     * Add a new key
     *
     * @return void
     */
    public function createNewPasskey()
    {
        $this->resetErrorBag();

        if($this->canConfirmPasskey){
            $this->ensurePasskeyIsConfirmed();
        }else{
            $this->ensurePasswordIsConfirmed();
        }

        $this->showingConfirmation = false;
        $passkeyName = $this->addNewPasskeyForm['name'];
        $this->addNewPasskeyForm['name'] = '';

        $this->dispatch('PublicKeyGenerated', name: $passkeyName,
            publicKey: app(PrepareCreationData::class)($this->user));
    }


    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return Auth::user();
    }

    public function removePasskey($id)
    {
        if($this->canConfirmPasskey){
            $this->ensurePasskeyIsConfirmed();
        }else{
            $this->ensurePasswordIsConfirmed(3);
        }

        $deleted = $this->user->passkeys()->where('id', $id)->delete();

        if ($deleted) {
            $this->dispatch('passkey-deleted');
        }
    }
}
