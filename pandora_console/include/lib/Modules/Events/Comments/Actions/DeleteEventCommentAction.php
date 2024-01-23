<?php

namespace PandoraFMS\Modules\Events\Comments\Actions;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Services\DeleteEventCommentService;

final class DeleteEventCommentAction
{
    public function __construct(
        private DeleteEventCommentService $deleteEventCommentService
    ) {
    }

    public function __invoke(EventComment $eventComment): void
    {
        $this->deleteEventCommentService->__invoke($eventComment);
    }
}
