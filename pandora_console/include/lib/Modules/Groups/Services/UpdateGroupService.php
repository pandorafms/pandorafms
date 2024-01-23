<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;
use PandoraFMS\Modules\Groups\Validations\GroupValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateGroupService
{
    public function __construct(
        private Audit $audit,
        private GroupRepository $groupRepository,
        private GroupValidation $groupValidation
    ) {
    }

    public function __invoke(Group $group, Group $oldGroup): Group
    {
        $this->groupValidation->__invoke($group, $oldGroup);

        $group = $this->groupRepository->update($group);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Update group '.$group->getIdGroup(),
            json_encode($group->toArray())
        );

        return $group;
    }
}
