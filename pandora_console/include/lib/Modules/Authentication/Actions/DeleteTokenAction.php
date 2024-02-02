<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\DeleteTokenService;

final class DeleteTokenAction
{
    public function __construct(
        private DeleteTokenService $deleteTokenService
    ) {
    }

    public function __invoke(Token $token): void
    {
        $this->deleteTokenService->__invoke($token);
    }
}
