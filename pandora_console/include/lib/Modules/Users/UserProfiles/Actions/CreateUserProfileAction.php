<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Actions;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Services\CreateUserProfileService;

final class CreateUserProfileAction
{
    public function __construct(
        private CreateUserProfileService $createUserProfileService
    ) {
    }

    public function __invoke(UserProfile $userProfile): UserProfile
    {
        return $this->createUserProfileService->__invoke($userProfile);
    }
}
