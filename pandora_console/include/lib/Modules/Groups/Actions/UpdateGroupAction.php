<?php

namespace PandoraFMS\Modules\Groups\Actions;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Services\UpdateGroupService;

final class UpdateGroupAction
{
    public function __construct(
        private UpdateGroupService $updateGroupService
    ) {
    }

    public function __invoke(Group $group, Group $oldGroup): Group
    {
        return $this->updateGroupService->__invoke($group, $oldGroup);
    }
}
