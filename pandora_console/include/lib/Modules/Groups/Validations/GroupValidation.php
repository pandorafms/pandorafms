<?php

namespace PandoraFMS\Modules\Groups\Validations;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Services\ExistNameGroupService;
use PandoraFMS\Modules\Groups\Services\GetGroupService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

final class GroupValidation
{
    public function __construct(
        private ValidateAclSystem $acl,
        private GetGroupService $getGroupService,
        private ExistNameGroupService $existNameGroupService
    ) {
    }

    public function __invoke(Group $group, ?Group $oldGroup = null): void
    {
        if (!$group->getName()) {
            throw new BadRequestException(__('Name is missing'));
        }

        if ($oldGroup === null || $oldGroup->getName() !== $group->getName()) {
            if($this->existNameGroupService->__invoke($group->getName()) === true) {
                throw new BadRequestException(
                    __('Name %s is already exists', $group->getName())
                );
            }
        }

        if ($oldGroup === null) {
            $group->setIcon('without-group@groups.svg');
        }

        if ($group->getIsPropagate() === null) {
            $group->setIsPropagate(false);
        }

        if ($group->getIsAlertEnabled() === null) {
            $group->setIsAlertEnabled(true);
        }

        if ($group->getParent() === null) {
            $group->setParent(0);
        }

        if (empty($group->getParent()) === false) {
            $this->validateGroup($group->getParent());
            $this->acl->validate($group->getParent(), 'AR', ' tried to read group');
        }

        if ($group->getIdSkin() === null) {
            $group->setIdSkin(0);
        }

        if (empty($group->getIdSkin()) === false) {
            $this->validateSkin($group->getIdSkin());
        }

        if ($group->getMaxAgents() === null) {
            $group->setMaxAgents(0);
        }
    }

    protected function validateGroup(int $idGroup): void
    {
        $this->getGroupService->__invoke($idGroup);
    }

    protected function validateSkin(int $idSkin): void
    {
        // TODO: create new service for this.
        if (! (bool) \skins_search_skin_id($idSkin)) {
            throw new BadRequestException(__('Invalid id skin'));
        }
    }
}
