<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Repositories\EventRepository;
use PandoraFMS\Modules\Events\Validations\EventValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateEventService
{
    public function __construct(
        private Audit $audit,
        private EventRepository $eventRepository,
        private EventValidation $eventValidation,
        private KeepInProcessStatusExtraIdEventService $keepInProcessStatusExtraIdEventService,
        private UpdateEventService $updateEventService
    ) {
    }

    public function __invoke(Event $event): Event
    {
        $this->eventValidation->__invoke($event);

        if (empty($event->getIdExtra()) === false) {
            $event = $this->keepInProcessStatusExtraIdEventService->__invoke($event);
        }

        $event = $this->eventRepository->create($event);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Create event '.$event->getIdEvent(),
            json_encode($event->toArray())
        );

        if (empty($event->getIdExtra()) === false) {
            //$this->updateEventService->__invoke();
            //'UPDATE tevento SET estado = 1, ack_utimestamp = ? WHERE estado IN (0,2) AND id_extra=?'
        }

        return $event;
    }
}
