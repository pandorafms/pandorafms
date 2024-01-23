<?php

namespace PandoraFMS\Modules\Groups\Actions;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Services\DeleteGroupService;

final class DeleteGroupAction
{
    public function __construct(
        private DeleteGroupService $deleteGroupService
    ) {
    }

    public function __invoke(Group $group): void
    {
        $this->deleteGroupService->__invoke($group);
    }
}
