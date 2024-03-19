<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\UpdateTokenService;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

final class UpdateTokenAction
{
    public function __construct(
        private UpdateTokenService $updateTokenService,
        private ValidateAclSystem $acl,
    ) {
    }

    public function __invoke(Token $token, Token $oldToken): Token
    {
        $this->acl->validateAclToken($token);
        return $this->updateTokenService->__invoke($token, $oldToken);
    }
}
