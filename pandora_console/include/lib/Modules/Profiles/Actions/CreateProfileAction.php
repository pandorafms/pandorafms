<?php

namespace PandoraFMS\Modules\Profiles\Actions;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Services\CreateProfileService;

final class CreateProfileAction
{
    public function __construct(
        private CreateProfileService $createProfileService
    ) {
    }

    public function __invoke(Profile $profile): Profile
    {
        return $this->createProfileService->__invoke($profile);
    }
}
