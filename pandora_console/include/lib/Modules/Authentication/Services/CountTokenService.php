<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;

final class CountTokenService
{
    public function __construct(
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(TokenFilter $tokenFilter): int
    {
        return $this->tokenRepository->count($tokenFilter);
    }
}
