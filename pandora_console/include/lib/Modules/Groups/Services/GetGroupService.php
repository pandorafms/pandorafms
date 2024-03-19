<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;

final class GetGroupService
{
    public function __construct(
        private GroupRepository $groupRepository,
    ) {
    }

    public function __invoke(int $idGroup): Group
    {
        $groupFilter = new GroupFilter();
        /** @var Group $entityFilter */
        $entityFilter = $groupFilter->getEntityFilter();
        $entityFilter->setIdGroup($idGroup);

        return $this->groupRepository->getOne($groupFilter);
    }
}
