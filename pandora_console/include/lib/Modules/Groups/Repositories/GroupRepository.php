<?php

namespace PandoraFMS\Modules\Groups\Repositories;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;

interface GroupRepository
{
    /**
     * @return Group[],
     */
    public function list(GroupFilter $groupFilter): array;

    public function count(GroupFilter $groupFilter): int;

    public function getOne(GroupFilter $groupFilter): Group;

    public function create(Group $group): Group;

    public function update(Group $group): Group;

    public function delete(int $id): void;
}
