<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteProfileService
{
    public function __construct(
        private Audit $audit,
        private ProfileRepository $profileRepository,
    ) {
    }

    public function __invoke(Profile $profile): void
    {
        $idProfile = $profile->getIdProfile();
        $nameProfile = $profile->getName();
        $this->profileRepository->delete($idProfile);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Deleted profile '.$nameProfile
        );
    }
}
