<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Services;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;
use PandoraFMS\Modules\Users\UserProfiles\Repositories\UserProfileRepository;

final class ListUserProfileService
{
    public function __construct(
        private UserProfileRepository $userProfileRepository,
    ) {
    }

    public function __invoke(UserProfileFilter $userProfileFilter): array
    {
        return $this->userProfileRepository->list($userProfileFilter);
    }
}
