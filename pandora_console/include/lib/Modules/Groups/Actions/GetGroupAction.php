<?php

namespace PandoraFMS\Modules\Groups\Actions;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Services\GetGroupService;

final class GetGroupAction
{
    public function __construct(
        private GetGroupService $getGroupService
    ) {
    }

    public function __invoke(int $idGroup): Group
    {
        return $this->getGroupService->__invoke($idGroup);
    }
}
