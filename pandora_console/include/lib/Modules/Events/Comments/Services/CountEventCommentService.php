<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;

final class CountEventCommentService
{
    public function __construct(
        private EventCommentRepository $eventCommentRepository,
    ) {
    }

    public function __invoke(EventCommentFilter $eventCommentFilter): int
    {
        return $this->eventCommentRepository->count($eventCommentFilter);
    }
}
