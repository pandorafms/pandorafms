<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;

final class GetEventCommentService
{
    public function __construct(
        private EventCommentRepository $eventCommentRepository,
    ) {
    }

    public function __invoke(int $idEvent, int $idEventComment): EventComment
    {
        $eventCommentFilter = new EventCommentFilter();
        /** @var EventComment $entityFilter */
        $entityFilter = $eventCommentFilter->getEntityFilter();
        $entityFilter->setIdEvent($idEvent);
        $entityFilter->setIdEventComment($idEventComment);

        return $this->eventCommentRepository->getOne($eventCommentFilter);
    }
}
