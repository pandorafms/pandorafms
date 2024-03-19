<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Repositories\EventRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteEventService
{
    public function __construct(
        private Audit $audit,
        private EventRepository $eventRepository,
    ) {
    }

    public function __invoke(Event $event): void
    {
        $idEvent = $event->getIdEvent();
        $this->eventRepository->delete($idEvent);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Deleted event '.$idEvent
        );
    }
}
