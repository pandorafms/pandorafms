<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;
use PandoraFMS\Modules\Authentication\Validations\TokenValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateTokenService
{
    public function __construct(
        private Audit $audit,
        private TokenRepository $tokenRepository,
        private TokenValidation $tokenValidation
    ) {
    }

    public function __invoke(Token $token, Token $oldToken): Token
    {
        $this->tokenValidation->__invoke($token, $oldToken);

        $token = $this->tokenRepository->update($token);

        $this->audit->write(
            'Token Management',
            ' Update token #'.$token->getIdToken()
        );

        return $token;
    }
}
