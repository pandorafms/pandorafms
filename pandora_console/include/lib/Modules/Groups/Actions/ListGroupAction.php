<?php

namespace PandoraFMS\Modules\Groups\Actions;

use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Groups\Services\CountGroupService;
use PandoraFMS\Modules\Groups\Services\ListGroupService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListGroupAction
{
    public function __construct(
        private ListGroupService $listGroupService,
        private CountGroupService $countGroupService
    ) {
    }

    public function __invoke(GroupFilter $groupFilter): array
    {
        return (new PaginationData(
            $groupFilter->getPage(),
            $groupFilter->getSizePage(),
            $this->countGroupService->__invoke($groupFilter),
            $this->listGroupService->__invoke($groupFilter)
        ))->toArray();
    }
}
