<?php

namespace PandoraFMS\Modules\Events\Comments\Services;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Repositories\EventCommentRepository;
use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Shared\Services\Config;

final class DeleteEventCommentService
{
    public function __construct(
        private Config $config,
        private Audit $audit,
        private EventCommentRepository $eventCommentRepository,
    ) {
    }


    public function __invoke(EventComment $eventComment): void
    {
        $idEventComment = $eventComment->getIdEventComment();

        $this->eventCommentRepository->delete($idEventComment);

        // Audit.
        //$this->audit->write(
        //    'Incidence Management',
        //    ' Deleted field incidence type #'.$idEventComment
        //);
    }
}
