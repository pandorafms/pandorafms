<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;

final class ListGroupService
{
    public function __construct(
        private GroupRepository $groupRepository,
    ) {
    }

    public function __invoke(GroupFilter $groupFilter): array
    {
        return $this->groupRepository->list($groupFilter);
    }
}
