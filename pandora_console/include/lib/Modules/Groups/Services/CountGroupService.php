<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;

final class CountGroupService
{
    public function __construct(
        private GroupRepository $groupRepository,
    ) {
    }

    public function __invoke(GroupFilter $groupFilter): int
    {
        return $this->groupRepository->count($groupFilter);
    }
}
