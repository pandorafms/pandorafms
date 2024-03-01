<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;

final class GetTokenService
{
    public function __construct(
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(int $idToken): Token
    {
        $tokenFilter = new TokenFilter();
        /** @var Token $entityFilter */
        $entityFilter = $tokenFilter->getEntityFilter();
        $entityFilter->setIdToken($idToken);

        return $this->tokenRepository->getOne($tokenFilter);
    }
}
