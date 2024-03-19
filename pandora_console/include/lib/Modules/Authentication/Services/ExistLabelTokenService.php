<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistLabelTokenService
{
    public function __construct(
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(string $label): bool
    {
        $tokenFilter = new TokenFilter();
        /** @var Token $entityFilter */
        $entityFilter = $tokenFilter->getEntityFilter();
        $entityFilter->setLabel($label);

        try {
            $this->tokenRepository->getOne($tokenFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
