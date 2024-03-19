<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Actions;

use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Services\GetUserProfileService;

final class GetUserProfileAction
{
    public function __construct(
        private GetUserProfileService $getUserProfileService
    ) {
    }

    public function __invoke(string $idUser, int $idProfile): UserProfile
    {
        return $this->getUserProfileService->__invoke($idUser, $idProfile);
    }
}
