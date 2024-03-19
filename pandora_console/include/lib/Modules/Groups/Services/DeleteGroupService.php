<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteGroupService
{
    public function __construct(
        private Audit $audit,
        private GroupRepository $groupRepository,
    ) {
    }

    public function __invoke(Group $group): void
    {
        $idGroup = $group->getIdGroup();

        // TODO: XXX
        db_process_sql_update(
            'tgrupo',
            ['parent' => $group->getParent()],
            ['parent' => $idGroup]
        );

        // TODO: XXX
        db_process_sql_delete(
            'tgroup_stat',
            ['id_group' => $idGroup]
        );

        $this->groupRepository->delete($idGroup);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Deleted group '.$idGroup
        );
    }
}
