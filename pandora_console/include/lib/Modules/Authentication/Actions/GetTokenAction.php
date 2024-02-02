<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\GetTokenService;

final class GetTokenAction
{
    public function __construct(
        private GetTokenService $getTokenService
    ) {
    }

    public function __invoke(int $idToken): Token
    {
        return $this->getTokenService->__invoke($idToken);
    }
}
