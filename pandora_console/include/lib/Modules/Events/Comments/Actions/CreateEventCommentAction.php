<?php

namespace PandoraFMS\Modules\Events\Comments\Actions;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Services\CreateEventCommentService;

final class CreateEventCommentAction
{
    public function __construct(
        private CreateEventCommentService $createEventCommentService
    ) {
    }

    public function __invoke(EventComment $eventComment): EventComment
    {
        return $this->createEventCommentService->__invoke($eventComment);
    }
}
