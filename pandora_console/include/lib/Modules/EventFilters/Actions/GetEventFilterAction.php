<?php

namespace PandoraFMS\Modules\EventFilters\Actions;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Services\GetEventFilterService;

final class GetEventFilterAction
{
    public function __construct(
        private GetEventFilterService $getEventFilterService
    ) {
    }

    public function __invoke(int $idEventFilter): EventFilter
    {
        return $this->getEventFilterService->__invoke($idEventFilter);
    }
}
