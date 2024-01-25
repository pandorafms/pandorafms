<?php

namespace PandoraFMS\Modules\EventFilters\Services;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Repositories\EventFilterRepository;
use PandoraFMS\Modules\EventFilters\Validations\EventFilterValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateEventFilterService
{
    public function __construct(
        private Audit $audit,
        private EventFilterRepository $eventFilterRepository,
        private EventFilterValidation $eventFilterValidation
    ) {
    }

    public function __invoke(EventFilter $eventFilter): EventFilter
    {
        $this->eventFilterValidation->__invoke($eventFilter);

        $eventFilter = $this->eventFilterRepository->create($eventFilter);

        $this->audit->write(
            AUDIT_LOG_EVENT,
            'Create eventFilter '.$eventFilter->getName(),
            json_encode($eventFilter->toArray())
        );

        return $eventFilter;
    }
}
