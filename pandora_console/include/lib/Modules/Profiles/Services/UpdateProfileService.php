<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;
use PandoraFMS\Modules\Profiles\Validations\ProfileValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateProfileService
{
    public function __construct(
        private Audit $audit,
        private ProfileRepository $profileRepository,
        private ProfileValidation $profileValidation
    ) {
    }

    public function __invoke(Profile $profile, Profile $oldProfile): Profile
    {
        $this->profileValidation->__invoke($profile, $oldProfile);

        $profile = $this->profileRepository->update($profile);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Update profile '.$profile->getName(),
            json_encode($profile->toArray())
        );

        return $profile;
    }
}
