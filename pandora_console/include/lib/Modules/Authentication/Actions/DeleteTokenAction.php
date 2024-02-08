<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\DeleteTokenService;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

final class DeleteTokenAction
{
    public function __construct(
        private DeleteTokenService $deleteTokenService,
        private ValidateAclSystem $acl,
    ) {
    }

    public function __invoke(Token $token): void
    {
        $this->acl->validateAclToken($token);
        $this->deleteTokenService->__invoke($token);
    }
}
