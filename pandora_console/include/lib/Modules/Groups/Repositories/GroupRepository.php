<?php

namespace PandoraFMS\Modules\Groups\Repositories;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Entities\GroupDataMapper;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class GroupRepository
{
    public function __construct(
        private Repository $repository,
        private GroupDataMapper $groupDataMapper
    ) {
    }

    /**
     * @return Group[],
    */
    public function list(GroupFilter $groupFilter): array
    {
        return $this->repository->__list(
            $groupFilter,
            $this->groupDataMapper
        );
    }

    public function count(GroupFilter $groupFilter): int
    {
        return $this->repository->__count(
            $groupFilter,
            $this->groupDataMapper
        );
    }

    public function getOne(GroupFilter $groupFilter): Group
    {
        return $this->repository->__getOne(
            $groupFilter,
            $this->groupDataMapper
        );
    }

    public function create(Group $group): Group
    {
        $id = $this->repository->__create($group, $this->groupDataMapper);
        return $group->setIdGroup($id);
    }

    public function update(Group $group): Group
    {
        return $this->repository->__update(
            $group,
            $this->groupDataMapper,
            $group->getIdGroup()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->groupDataMapper);
    }

}
