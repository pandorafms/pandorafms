<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Entities\EventFilter;
use PandoraFMS\Modules\Events\Repositories\EventRepository;

final class GetEventService
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function __invoke(int $idEvent): Event
    {
        $eventFilter = new EventFilter();
        /** @var Event $entityFilter */
        $entityFilter = $eventFilter->getEntityFilter();
        $entityFilter->setIdEvent($idEvent);

        return $this->eventRepository->getOne($eventFilter);
    }
}
