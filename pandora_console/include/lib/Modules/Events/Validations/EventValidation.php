<?php

namespace PandoraFMS\Modules\Events\Validations;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Services\ExistNameEventService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;

final class EventValidation
{
    public function __construct(
        private ExistNameEventService $existNameEventService
    ) {
    }

    public function __invoke(Event $event, ?Event $oldEvent = null): void
    {
        if (!$event->getName()) {
            throw new BadRequestException(__('Name is missing'));
        }

        if($oldEvent === null || $oldEvent->getName() !== $event->getName()) {
            if($this->existNameEventService->__invoke($event->getName()) === true) {
                throw new BadRequestException(
                    __('Name %s is already exists', $event->getName())
                );
            }
        }

        if($event->getIsAgentView() === null) {
            $event->setIsAgentView(false);
        }

        if($event->getIsAgentEdit() === null) {
            $event->setIsAgentEdit(false);
        }

        if($event->getIsAlertEdit() === null) {
            $event->setIsAlertEdit(false);
        }

        if($event->getIsUserManagement() === null) {
            $event->setIsUserManagement(false);
        }

        if($event->getIsDbManagement() === null) {
            $event->setIsDbManagement(false);
        }

        if($event->getIsAlertManagement() === null) {
            $event->setIsAlertManagement(false);
        }

        if($event->getIsPandoraManagement() === null) {
            $event->setIsPandoraManagement(false);
        }

        if($event->getIsReportView() === null) {
            $event->setIsReportView(false);
        }

        if($event->getIsReportEdit() === null) {
            $event->setIsReportEdit(false);
        }

        if($event->getIsReportManagement() === null) {
            $event->setIsReportManagement(false);
        }

        if($event->getIsEventView() === null) {
            $event->setIsEventView(false);
        }

        if($event->getIsEventEdit() === null) {
            $event->setIsEventEdit(false);
        }

        if($event->getIsEventManagement() === null) {
            $event->setIsEventManagement(false);
        }

        if($event->getIsAgentDisable() === null) {
            $event->setIsAgentDisable(false);
        }

        if($event->getIsMapView() === null) {
            $event->setIsMapView(false);
        }

        if($event->getIsMapEdit() === null) {
            $event->setIsMapEdit(false);
        }

        if($event->getIsMapManagement() === null) {
            $event->setIsMapManagement(false);
        }

        if($event->getIsVconsoleView() === null) {
            $event->setIsVconsoleView(false);
        }

        if($event->getIsVconsoleEdit() === null) {
            $event->setIsVconsoleEdit(false);
        }

        if($event->getIsVconsoleManagement() === null) {
            $event->setIsVconsoleManagement(false);
        }

        if($event->getIsNetworkConfigView() === null) {
            $event->setIsNetworkConfigView(false);
        }

        if($event->getIsNetworkConfigEdit() === null) {
            $event->setIsNetworkConfigEdit(false);
        }

        if($event->getIsNetworkConfigManagement() === null) {
            $event->setIsNetworkConfigManagement(false);
        }
    }
}
