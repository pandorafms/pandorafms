<?php

namespace PandoraFMS\Modules\Groups\Actions;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Services\CreateGroupService;

final class CreateGroupAction
{
    public function __construct(
        private CreateGroupService $createGroupService
    ) {
    }

    public function __invoke(Group $group): Group
    {
        return $this->createGroupService->__invoke($group);
    }
}
