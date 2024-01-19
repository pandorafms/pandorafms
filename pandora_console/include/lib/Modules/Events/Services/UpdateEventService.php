<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Repositories\EventRepository;
use PandoraFMS\Modules\Events\Validations\EventValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateEventService
{
    public function __construct(
        private Audit $audit,
        private EventRepository $eventRepository,
        private EventValidation $eventValidation
    ) {
    }

    public function __invoke(Event $event, Event $oldEvent): Event
    {
        $this->eventValidation->__invoke($event, $oldEvent);

        $event = $this->eventRepository->update($event);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Update event '.$event->getIdEvent(),
            json_encode($event->toArray())
        );

        return $event;
    }
}
