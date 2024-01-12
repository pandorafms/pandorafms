<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

final class CountUserProfileService
{


    public function __construct(
        private UserProfileRepository $userProfileRepository,
    ) {
    }


    public function __invoke(UserProfileFilter $userProfileFilter): int
    {
        return $this->userProfileRepository->count($userProfileFilter);
    }


}
