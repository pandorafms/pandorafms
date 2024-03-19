<?php

namespace PandoraFMS\Modules\Events\Comments\Actions;

use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;
use PandoraFMS\Modules\Events\Comments\Services\CountEventCommentService;
use PandoraFMS\Modules\Events\Comments\Services\ListEventCommentService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListEventCommentAction
{
    public function __construct(
        private ListEventCommentService $listEventCommentService,
        private CountEventCommentService $countEventCommentService
    ) {
    }


    public function __invoke(EventCommentFilter $eventCommentFilter): array
    {
        return (new PaginationData(
            $eventCommentFilter->getPage(),
            $eventCommentFilter->getSizePage(),
            $this->countEventCommentService->__invoke($eventCommentFilter),
            $this->listEventCommentService->__invoke($eventCommentFilter)
        ))->toArray();
    }
}
