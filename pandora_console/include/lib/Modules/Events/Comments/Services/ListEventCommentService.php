<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;

final class ListEventCommentService
{
    public function __construct(
        private EventCommentRepository $eventCommentRepository,
    ) {
    }

    public function __invoke(EventCommentFilter $eventCommentFilter): array
    {
        return $this->eventCommentRepository->list($eventCommentFilter);
    }
}
