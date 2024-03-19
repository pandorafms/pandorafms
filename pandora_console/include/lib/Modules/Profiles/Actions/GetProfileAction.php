<?php

namespace PandoraFMS\Modules\Profiles\Actions;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Services\GetProfileService;

final class GetProfileAction
{
    public function __construct(
        private GetProfileService $getProfileService
    ) {
    }

    public function __invoke(int $idProfile): Profile
    {
        return $this->getProfileService->__invoke($idProfile);
    }
}
