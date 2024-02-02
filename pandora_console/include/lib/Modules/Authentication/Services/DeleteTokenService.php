<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteTokenService
{
    public function __construct(
        private Audit $audit,
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(Token $token): void
    {
        $idToken = $token->getIdToken();

        $this->tokenRepository->delete($idToken);

        $this->audit->write(
            'Token Management',
            ' Deleted token #'.$idToken
        );
    }
}
