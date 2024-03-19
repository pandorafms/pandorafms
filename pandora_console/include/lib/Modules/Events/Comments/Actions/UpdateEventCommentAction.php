<?php

namespace PandoraFMS\Modules\Events\Comments\Actions;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Services\UpdateEventCommentService;

final class UpdateEventCommentAction
{
    public function __construct(
        private UpdateEventCommentService $updateEventCommentService
    ) {
    }

    public function __invoke(EventComment $eventComment, EventComment $oldEventComment): EventComment
    {
        return $this->updateEventCommentService->__invoke($eventComment, $oldEventComment);
    }
}
