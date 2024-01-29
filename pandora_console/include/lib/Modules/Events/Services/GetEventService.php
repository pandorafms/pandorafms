<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
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
        $eventFilter->setIdEvent($idEvent);

        return $this->eventRepository->getOne($eventFilter);
    }
}
