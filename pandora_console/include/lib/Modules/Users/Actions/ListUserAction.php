<?php

namespace PandoraFMS\Modules\Users\Actions;

use PandoraFMS\Modules\Shared\Entities\PaginationData;
use PandoraFMS\Modules\Users\Entities\UserFilter;
use PandoraFMS\Modules\Users\Services\CountUserService;
use PandoraFMS\Modules\Users\Services\ListUserService;

final class ListUserAction
{
    public function __construct(
        private ListUserService $listUserService,
        private CountUserService $countUserService
    ) {
    }

    public function __invoke(UserFilter $userFilter): array
    {
        return (new PaginationData(
            $userFilter->getPage(),
            $userFilter->getSizePage(),
            $this->countUserService->__invoke($userFilter),
            $this->listUserService->__invoke($userFilter)
        ))->toArray();
    }
}
