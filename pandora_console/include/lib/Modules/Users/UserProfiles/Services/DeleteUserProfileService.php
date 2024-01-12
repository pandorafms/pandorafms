<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Shared\Services\Config;

final class DeleteUserProfileService
{


    public function __construct(
        private Config $config,
        private Audit $audit,
        private UserProfileRepository $userProfileRepository,
    ) {
    }


    public function __invoke(UserProfile $userProfile): void
    {
        $id = $userProfile->getIdUserProfile();

        $this->userProfileRepository->delete($id);

        $this->audit->write(
            'Incidence Management',
            ' Deleted field incidence type #'.$id
        );
    }


}
