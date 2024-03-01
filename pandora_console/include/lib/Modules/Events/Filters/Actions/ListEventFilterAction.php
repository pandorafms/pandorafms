<?php

namespace PandoraFMS\Modules\Events\Filters\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Events\Filters\Services\CountEventFilterService;
use PandoraFMS\Modules\Events\Filters\Services\ListEventFilterService;

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
