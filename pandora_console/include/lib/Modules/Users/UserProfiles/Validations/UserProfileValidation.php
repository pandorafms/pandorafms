<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Validations;

use PandoraFMS\Modules\Groups\Services\GetGroupService;
use PandoraFMS\Modules\Profiles\Services\GetProfileService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Tags\Services\GetTagService;
use PandoraFMS\Modules\Users\Services\GetUserService;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Services\ExistUserProfileService;

final class UserProfileValidation
{
    public function __construct(
        private GetGroupService $getGroupService,
        private GetUserService $getUserService,
        private GetProfileService $getProfileService,
        private ExistUserProfileService $existUserProfileService,
        private GetTagService $getTagService,
        private ValidateAclSystem $acl,
        private Config $config
    ) {
    }

    public function __invoke(UserProfile $userProfile): void
    {
        if (!$userProfile->getIdUser()) {
            throw new BadRequestException(__('Id user is missing'));
        }

        if (!$userProfile->getIdProfile()) {
            throw new BadRequestException(__('Id profile is missing'));
        }

        if ($userProfile->getIdGroup() === null || $userProfile->getIdGroup() === '') {
            throw new BadRequestException(__('Id group is missing'));
        }

        if (empty($userProfile->getIdUser()) === false) {
            $this->validateUser($userProfile->getIdUser());
        }

        if (empty($userProfile->getIdProfile()) === false) {
            $this->validateProfile($userProfile->getIdProfile());
        }

        if (empty($userProfile->getIdGroup()) === false) {
            $this->validateGroup($userProfile->getIdGroup());
        }

        if ($this->existUserProfileService->__invoke($userProfile) === true) {
            throw new BadRequestException(__('User profile is already exists'));
        }

        $userProfile->setAssignedBy($this->config->get('id_user'));

        if ($userProfile->getIsNoHierarchy() === null) {
            $userProfile->setIsNoHierarchy(false);
        }

        if ($userProfile->getIdPolicy() === null) {
            $userProfile->setIdPolicy(0);
        }

        if (empty($userProfile->getIdPolicy()) === false) {
            $this->validatePolicy($userProfile->getIdPolicy());
        }

        if (empty($userProfile->getTags()) === false) {
            $this->validateTags($userProfile->getTags());
        }
    }

    private function validateUser(string $idUser): void
    {
        $this->getUserService->__invoke($idUser);
    }

    private function validateProfile(int $idProfile): void
    {
        $this->getProfileService->__invoke($idProfile);
    }

    protected function validateGroup(int $idGroup): void
    {
        $this->getGroupService->__invoke($idGroup);
    }

    protected function validatePolicy(int $idPolicy): void
    {
        // TODO: create new service for this.
        if (! (bool) \policies_get_policy($idPolicy)) {
            throw new BadRequestException(__('Invalid id policy'));
        }
    }

    protected function validateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->getTagService->__invoke((int) $tag);
        }
    }
}
