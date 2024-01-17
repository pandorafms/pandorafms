<?php

namespace PandoraFMS\Modules\Profiles\Actions;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Services\UpdateProfileService;

final class UpdateProfileAction
{
    public function __construct(
        private UpdateProfileService $updateProfileService
    ) {
    }

    public function __invoke(Profile $profile, Profile $oldProfile): Profile
    {
        return $this->updateProfileService->__invoke($profile, $oldProfile);
    }
}
