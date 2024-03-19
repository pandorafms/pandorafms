<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Profiles\Services\GetProfileService;
use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;
use PandoraFMS\Modules\Users\UserProfiles\Validations\UserProfileValidation;

final class CreateUserProfileService
{
    public function __construct(
        private UserProfileRepository $userProfileRepository,
        private UserProfileValidation $userProfileValidation,
        private GetProfileService $getProfileService,
        private Audit $audit
    ) {
    }

    public function __invoke(UserProfile $userProfile): UserProfile
    {
        $this->userProfileValidation->__invoke($userProfile);

        $userProfile = $this->userProfileRepository->create($userProfile);

        $profile = $this->getProfileService->__invoke($userProfile->getIdprofile());

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Added profile: '.$profile->getName().' for user: '.$userProfile->getIdUser(),
            json_encode($userProfile->toArray())
        );

        return $userProfile;
    }
}
