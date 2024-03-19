<?php

namespace PandoraFMS\Modules\Profiles\Actions;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Services\DeleteProfileService;

final class DeleteProfileAction
{
    public function __construct(
        private DeleteProfileService $deleteProfileService
    ) {
    }

    public function __invoke(Profile $profile): void
    {
        $this->deleteProfileService->__invoke($profile);
    }
}
