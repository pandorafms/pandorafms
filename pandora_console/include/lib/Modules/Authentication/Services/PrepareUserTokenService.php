<?php

namespace PandoraFMS\Modules\Authentication\Services;

final class PrepareUserTokenService
{
    public function __construct(
    ) {
    }

    public function __invoke(string $plainToken): string
    {
        return password_hash($plainToken, PASSWORD_DEFAULT);
    }
}
