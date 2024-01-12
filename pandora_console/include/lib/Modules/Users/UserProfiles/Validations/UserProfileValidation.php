<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Validations;

use PandoraFMS\Modules\Groups\Services\GetGroupService;
use PandoraFMS\Modules\Profiles\Services\GetProfileService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
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

        if (!$userProfile->getIdGroup()) {
            throw new BadRequestException(__('Id group is missing'));
        }

        if (empty($userProfile->getIdUser()) === false) {
            $this->validateUser($userProfile->getIdUser());
        }

        if (empty($userProfile->getIdProfile()) === false) {
            $this->validateProfile($userProfile->getIdProfile());
            $this->acl->validateUserProfile($userProfile->getIdProfile());
        }

        if (empty($userProfile->getIdGroup()) === false) {
            $this->validateGroup($userProfile->getIdGroup());
            $this->acl->validateUserGroups(
                $userProfile->getIdGroup(),
                'UM',
                ' tried to manage groups'
            );
        }

        if ($this->existUserProfileService->__invoke($userProfile) === true) {
            throw new BadRequestException(__('User profile is already exists'));
        }

        $userProfile->setAssignedBy($this->config->get('id_user'));
    }


    private function validateUser(string $idUser): void
    {
        $this->getUserService->__invoke($idUser);
    }


    private function validateProfile(int $idProfile): void
    {
        $this->getProfileService->__invoke($idProfile);
    }


    private function validateGroup(int $idGroup): void
    {
        $this->getGroupService->__invoke($idGroup);
    }


}
