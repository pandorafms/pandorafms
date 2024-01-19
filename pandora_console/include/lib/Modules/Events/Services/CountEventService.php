<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Entities\EventFilter;
use PandoraFMS\Modules\Events\Repositories\EventRepository;

final class CountEventService
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function __invoke(EventFilter $eventFilter): int
    {
        return $this->eventRepository->count($eventFilter);
    }
}
