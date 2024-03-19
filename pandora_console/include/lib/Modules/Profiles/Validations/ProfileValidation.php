<?php

namespace PandoraFMS\Modules\Profiles\Validations;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Services\ExistNameProfileService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;

final class ProfileValidation
{
    public function __construct(
        private ExistNameProfileService $existNameProfileService
    ) {
    }

    public function __invoke(Profile $profile, ?Profile $oldProfile = null): void
    {
        if (!$profile->getName()) {
            throw new BadRequestException(__('Name is missing'));
        }

        if($oldProfile === null || $oldProfile->getName() !== $profile->getName()) {
            if($this->existNameProfileService->__invoke($profile->getName()) === true) {
                throw new BadRequestException(
                    __('Name %s is already exists', $profile->getName())
                );
            }
        }

        if($profile->getIsAgentView() === null) {
            $profile->setIsAgentView(false);
        }

        if($profile->getIsAgentEdit() === null) {
            $profile->setIsAgentEdit(false);
        }

        if($profile->getIsAlertEdit() === null) {
            $profile->setIsAlertEdit(false);
        }

        if($profile->getIsUserManagement() === null) {
            $profile->setIsUserManagement(false);
        }

        if($profile->getIsDbManagement() === null) {
            $profile->setIsDbManagement(false);
        }

        if($profile->getIsAlertManagement() === null) {
            $profile->setIsAlertManagement(false);
        }

        if($profile->getIsPandoraManagement() === null) {
            $profile->setIsPandoraManagement(false);
        }

        if($profile->getIsReportView() === null) {
            $profile->setIsReportView(false);
        }

        if($profile->getIsReportEdit() === null) {
            $profile->setIsReportEdit(false);
        }

        if($profile->getIsReportManagement() === null) {
            $profile->setIsReportManagement(false);
        }

        if($profile->getIsEventView() === null) {
            $profile->setIsEventView(false);
        }

        if($profile->getIsEventEdit() === null) {
            $profile->setIsEventEdit(false);
        }

        if($profile->getIsEventManagement() === null) {
            $profile->setIsEventManagement(false);
        }

        if($profile->getIsAgentDisable() === null) {
            $profile->setIsAgentDisable(false);
        }

        if($profile->getIsMapView() === null) {
            $profile->setIsMapView(false);
        }

        if($profile->getIsMapEdit() === null) {
            $profile->setIsMapEdit(false);
        }

        if($profile->getIsMapManagement() === null) {
            $profile->setIsMapManagement(false);
        }

        if($profile->getIsVconsoleView() === null) {
            $profile->setIsVconsoleView(false);
        }

        if($profile->getIsVconsoleEdit() === null) {
            $profile->setIsVconsoleEdit(false);
        }

        if($profile->getIsVconsoleManagement() === null) {
            $profile->setIsVconsoleManagement(false);
        }

        if($profile->getIsNetworkConfigView() === null) {
            $profile->setIsNetworkConfigView(false);
        }

        if($profile->getIsNetworkConfigEdit() === null) {
            $profile->setIsNetworkConfigEdit(false);
        }

        if($profile->getIsNetworkConfigManagement() === null) {
            $profile->setIsNetworkConfigManagement(false);
        }
    }
}
