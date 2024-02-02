<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\UpdateTokenService;

final class UpdateTokenAction
{
    public function __construct(
        private UpdateTokenService $updateTokenService
    ) {
    }

    public function __invoke(Token $token, Token $oldToken): Token
    {
        return $this->updateTokenService->__invoke($token, $oldToken);
    }
}
