<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;
use PandoraFMS\Modules\Events\Comments\Validations\EventCommentValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class UpdateEventCommentService
{
    public function __construct(
        private Audit $audit,
        private EventCommentRepository $eventCommentRepository,
        private EventCommentValidation $eventCommentValidation
    ) {
    }

    public function __invoke(EventComment $eventComment, EventComment $oldEventComment): EventComment
    {
        $this->eventCommentValidation->__invoke($eventComment, $oldEventComment);

        $eventComment = $this->eventCommentRepository->update($eventComment);

        //$this->audit->write(
        //    'Incidence Management',
        //    ' Update Field #'.$eventComment->getIdEventComment().' in a incidence type #'.$eventComment->getIdEvent()
        //);

        return $eventComment;
    }
}
