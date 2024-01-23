<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;
use PandoraFMS\Modules\Groups\Validations\GroupValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateGroupService
{
    public function __construct(
        private Audit $audit,
        private GroupRepository $groupRepository,
        private GroupValidation $groupValidation
    ) {
    }

    public function __invoke(Group $group): Group
    {
        $this->groupValidation->__invoke($group);

        $group = $this->groupRepository->create($group);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Create group '.$group->getIdGroup(),
            json_encode($group->toArray())
        );

        return $group;
    }
}
