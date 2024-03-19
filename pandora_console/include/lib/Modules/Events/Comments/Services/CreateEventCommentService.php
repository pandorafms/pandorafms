<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;
use PandoraFMS\Modules\Events\Comments\Validations\EventCommentValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateEventCommentService
{
    public function __construct(
        private EventCommentRepository $eventCommentRepository,
        private EventCommentValidation $eventCommentValidation,
        private Audit $audit
    ) {
    }

    public function __invoke(EventComment $eventComment): EventComment
    {
        $this->eventCommentValidation->__invoke($eventComment);

        $eventComment = $this->eventCommentRepository->create($eventComment);

        //$this->audit->write(
        //    'Incidence Management',
        //    ' Create Field #'.$eventComment->getIdEventComment().'in a incidence type #'.$eventComment->getIdEvent()
        //);

        return $eventComment;
    }
}
