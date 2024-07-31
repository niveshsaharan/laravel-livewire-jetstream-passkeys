<?php

namespace App\Models;

use LaravelWebauthn\Models\WebauthnKey;

class Passkey extends WebauthnKey
{
    protected $table = 'passkeys';
}
