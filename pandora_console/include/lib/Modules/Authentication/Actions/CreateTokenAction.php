<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\CreateTokenService;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

final class CreateTokenAction
{
    public function __construct(
        private CreateTokenService $createTokenService,
        private ValidateAclSystem $acl,
    ) {
    }

    public function __invoke(Token $token): Token
    {
        $this->acl->validateAclToken($token);
        return $this->createTokenService->__invoke($token);
    }
}
