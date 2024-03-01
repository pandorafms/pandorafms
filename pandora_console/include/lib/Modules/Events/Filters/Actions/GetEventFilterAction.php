<?php

namespace PandoraFMS\Modules\Events\Filters\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Services\GetEventFilterService;

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
