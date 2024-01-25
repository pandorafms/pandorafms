<?php

namespace PandoraFMS\Modules\EventFilters\Actions;

use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\EventFilters\Services\CountEventFilterService;
use PandoraFMS\Modules\EventFilters\Services\ListEventFilterService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListEventFilterAction
{
    public function __construct(
        private ListEventFilterService $listEventFilterService,
        private CountEventFilterService $countEventFilterService
    ) {
    }

    public function __invoke(EventFilterFilter $eventFilterFilter): array
    {
        return (new PaginationData(
            $eventFilterFilter->getPage(),
            $eventFilterFilter->getSizePage(),
            $this->countEventFilterService->__invoke($eventFilterFilter),
            $this->listEventFilterService->__invoke($eventFilterFilter)
        ))->toArray();
    }
}
