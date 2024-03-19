<?php

namespace PandoraFMS\Modules\Authentication\Actions;

use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Authentication\Services\CountTokenService;
use PandoraFMS\Modules\Authentication\Services\ListTokenService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListTokenAction
{
    public function __construct(
        private ListTokenService $listTokenService,
        private CountTokenService $countTokenService
    ) {
    }

    public function __invoke(TokenFilter $tokenFilter): array
    {
        return (new PaginationData(
            $tokenFilter->getPage(),
            $tokenFilter->getSizePage(),
            $this->countTokenService->__invoke($tokenFilter),
            $this->listTokenService->__invoke($tokenFilter)
        ))->toArray();
    }
}
