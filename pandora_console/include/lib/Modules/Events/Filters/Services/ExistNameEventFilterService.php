<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistNameEventFilterService
{
    public function __construct(
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(string $name): bool
    {
        $eventFilterFilter = new EventFilterFilter();
        /** @var EventFilter $entityFilter */
        $entityFilter = $eventFilterFilter->getEntityFilter();
        $entityFilter->setName($name);

        try {
            $this->eventFilterRepository->getOne($eventFilterFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
