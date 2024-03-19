<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;
use PandoraFMS\Modules\Events\Filters\Validations\EventFilterValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateEventFilterService
{
    public function __construct(
        private Audit $audit,
        private EventFilterRepository $eventFilterRepository,
        private EventFilterValidation $eventFilterValidation
    ) {
    }

    public function __invoke(EventFilter $eventFilter, EventFilter $oldEventFilter): EventFilter
    {
        $this->eventFilterValidation->__invoke($eventFilter, $oldEventFilter);

        $eventFilter = $this->eventFilterRepository->update($eventFilter);

        $this->audit->write(
            AUDIT_LOG_EVENT,
            'Update eventFilter '.$eventFilter->getName(),
            json_encode($eventFilter->toArray())
        );

        return $eventFilter;
    }
}
