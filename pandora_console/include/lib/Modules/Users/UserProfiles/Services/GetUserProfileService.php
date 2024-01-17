<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

final class GetUserProfileService
{
    public function __construct(
        private UserProfileRepository $userProfileRepository,
    ) {
    }

    public function __invoke(string $idUser, int $idProfile): UserProfile
    {
        $userProfileFilter = new UserProfileFilter();
        /** @var UserProfile $entityFilter */
        $entityFilter = $userProfileFilter->getEntityFilter();
        $entityFilter->setIdUser($idUser);
        $entityFilter->setIdProfile($idProfile);

        return $this->userProfileRepository->getOne($userProfileFilter);
    }
}
