<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

final class ExistUserProfileService
{


    public function __construct(
        private UserProfileRepository $userProfileRepository,
    ) {
    }


    public function __invoke(UserProfile $userProfile): bool
    {
        $groupFilter = new UserProfileFilter();
        /*
            @var UserProfile $entityFilter
        */
        $entityFilter = $groupFilter->getEntityFilter();
        $entityFilter->setIdUser($userProfile->getIdUser());
        $entityFilter->setIdProfile($userProfile->getIdProfile());
        $entityFilter->setIdGroup($userProfile->getIdGroup());

        try {
            $this->userProfileRepository->getOne($groupFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }


}
