<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Profiles\Services\GetProfileService;
use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

final class DeleteUserProfileService
{
    public function __construct(
        private Config $config,
        private Audit $audit,
        private GetProfileService $getProfileService,
        private UserProfileRepository $userProfileRepository,
    ) {
    }

    public function __invoke(UserProfile $userProfile): void
    {
        $idUser = $userProfile->getIdUser();
        $profile = $this->getProfileService->__invoke($userProfile->getIdprofile());

        $this->userProfileRepository->delete($userProfile->getIdUserProfile());

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Deleted profile: '.$profile->getName().' for user: '.$idUser
        );
    }
}
