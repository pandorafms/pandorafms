<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;

final class ListTokenService
{
    public function __construct(
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(TokenFilter $tokenFilter): array
    {
        return $this->tokenRepository->list($tokenFilter);
    }
}
