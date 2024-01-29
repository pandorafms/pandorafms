<?php

namespace PandoraFMS\Modules\Events\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Services\CountEventService;
use PandoraFMS\Modules\Events\Services\ListEventService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListEventAction
{
    public function __construct(
        private ListEventService $listEventService,
        private CountEventService $countEventService
    ) {
    }

    public function __invoke(EventFilter $eventFilter): array
    {
        return (new PaginationData(
            $eventFilter->getPage(),
            $eventFilter->getSizePage(),
            $this->countEventService->__invoke($eventFilter),
            $this->listEventService->__invoke($eventFilter)
        ))->toArray();
    }
}
