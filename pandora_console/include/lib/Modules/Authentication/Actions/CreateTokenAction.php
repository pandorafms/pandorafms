<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\CreateTokenService;

final class CreateTokenAction
{
    public function __construct(
        private CreateTokenService $createTokenService
    ) {
    }

    public function __invoke(Token $token): Token
    {
        return $this->createTokenService->__invoke($token);
    }
}
