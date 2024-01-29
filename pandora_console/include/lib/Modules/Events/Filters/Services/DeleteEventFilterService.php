<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteEventFilterService
{
    public function __construct(
        private Audit $audit,
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(EventFilter $eventFilter): void
    {
        $idEventFilter = $eventFilter->getIdEventFilter();
        $nameEventFilter = $eventFilter->getName();
        $this->eventFilterRepository->delete($idEventFilter);

        $this->audit->write(
            AUDIT_LOG_EVENT,
            'Deleted eventFilter '.$nameEventFilter
        );
    }
}
