<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Repositories\EventRepository;

final class ListEventService
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function __invoke(EventFilter $eventFilter): array
    {
        return $this->eventRepository->list($eventFilter);
    }
}
