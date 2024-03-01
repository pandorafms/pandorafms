<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Actions;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Services\DeleteUserProfileService;

final class DeleteUserProfileAction
{
    public function __construct(
        private DeleteUserProfileService $deleteService
    ) {
    }

    public function __invoke(UserProfile $userProfile): void
    {
        $this->deleteService->__invoke($userProfile);
    }
}
