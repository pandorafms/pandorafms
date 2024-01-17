<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;
use PandoraFMS\Modules\Users\UserProfiles\Validations\UserProfileValidation;

final class CreateUserProfileService
{
    public function __construct(
        private UserProfileRepository $userProfileRepository,
        private UserProfileValidation $userProfileValidation,
        private Audit $audit
    ) {
    }

    public function __invoke(UserProfile $userProfile): UserProfile
    {
        $this->userProfileValidation->__invoke($userProfile);

        $userProfile = $this->userProfileRepository->create($userProfile);

        $this->audit->write(
            'User Management',
            ' create in this user #'.$userProfile->getIdUser().' profile #'.$userProfile->getIdprofile()
        );

        return $userProfile;
    }
}
