<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\ConfirmsPasswords;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Actions\EnsureLoginIsNotThrottled;
use LaravelWebauthn\Http\Requests\WebauthnLoginRequest;
use Livewire\Attributes\On;

trait ConfirmsPasskey
{
    use ConfirmsPasswords;

    /**
     * The ID of the operation being confirmed.
     *
     * @var string|null
     */
    public $confirmableId = null;

    /**
     * The user's passkeys.
     *
     * @var string
     */
    public string $publicKey;

    /**
     * Can user confirm with passkey
     *
     * @var bool
     */
    public bool $canConfirmPasskey = false;

    /**
     * Start confirming the user's passkey.
     *
     * @param  string  $confirmableId
     * @return void
     */
    public function startConfirmingPasskey(string $confirmableId)
    {
        $this->canConfirmPasskey = count(\LaravelWebauthn\Facades\Webauthn::prepareAssertion(Auth::user())->allowCredentials) > 0;

        if ($this->passwordIsConfirmed()) {
            return $this->dispatch('password-confirmed',
                id: $confirmableId,
            );
        }

        if ($this->canConfirmPasskey) {
            if ($this->passkeyIsConfirmed()) {
                return $this->dispatch('passkey-confirmed-'.$confirmableId,
                    id: $confirmableId,
                );
            }

            $this->confirmableId = $confirmableId;

            $this->publicKey = json_encode(\LaravelWebauthn\Facades\Webauthn::prepareAssertion(Auth::user()));;

            $this->dispatch('confirming-passkey-'.$confirmableId, publicKey: $this->publicKey);
        } else {
            $this->dispatch('passkey-unavailable-'.$confirmableId);
        }
    }


    #[On('validate-passkey')]
    public function confirmPasskey($data)
    {
        $request = Request::create('/', 'POST', $data);

        // Create an instance of your FormRequest
        $formRequest = WebauthnLoginRequest::createFromBase($request);
        (new Pipeline(app()))->send($formRequest)->through(array_filter([
            config('webauthn.limiters.login') !== null ? null : EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
        ]))->then(function () {
            session(['auth.passkey_confirmed_at' => time()]);

            $this->dispatch('passkey-confirmed-' . $this->confirmableId,
                id: $this->confirmableId,
            );
        });
    }

    /**
     * Ensure that the user's passkey has been recently confirmed.
     *
     * @param  int|null  $maximumSecondsSinceConfirmation
     * @return void
     */
    protected function ensurePasskeyIsConfirmed($maximumSecondsSinceConfirmation = null)
    {
        $maximumSecondsSinceConfirmation = $maximumSecondsSinceConfirmation ?: config('auth.passkey_timeout', 60);

        $this->passkeyIsConfirmed($maximumSecondsSinceConfirmation) ? null : $this->ensurePasswordIsConfirmed();
    }

    /**
     * Determine if the user's passkey has been recently confirmed.
     *
     * @param  int|null  $maximumSecondsSinceConfirmation
     * @return bool
     */
    protected function passkeyIsConfirmed($maximumSecondsSinceConfirmation = null)
    {
        $maximumSecondsSinceConfirmation = $maximumSecondsSinceConfirmation ?: config('auth.passkey_timeout', 60);

        return (time() - session('auth.passkey_confirmed_at', 0)) < $maximumSecondsSinceConfirmation;
    }
}
