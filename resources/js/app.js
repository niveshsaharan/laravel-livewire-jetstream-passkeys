import './bootstrap';

document.addEventListener('livewire:init', () => {

    const webauthn = new WebAuthn();
    webauthn.setNotify(function(name, message){
        alert(message)
    });

    if (webauthn.webAuthnSupport()) {
        Livewire.dispatch('passkey-supported', true);
    }

    Livewire.on('PublicKeyGenerated', ({name, publicKey}) => {
        webauthn.register(
            publicKey,
            function (data) {
                axios.post("/passkeys/keys", {
                    ...data,
                    name: name,
                })
                    .then(function (response) {
                        Livewire.dispatch('passkey-saved')
                    })
                    .catch(function (error) {
                        alert(error);
                    });
            }
        );
    })
});
