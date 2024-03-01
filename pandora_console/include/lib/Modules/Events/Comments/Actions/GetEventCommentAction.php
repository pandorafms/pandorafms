<?php

namespace PandoraFMS\Modules\Events\Comments\Actions;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Services\GetEventCommentService;

final class GetEventCommentAction
{
    public function __construct(
        private GetEventCommentService $getEventCommentService
    ) {
    }

    public function __invoke(int $idTypeField, int $idComment): EventComment
    {
        return $this->getEventCommentService->__invoke($idTypeField, $idComment);
    }
}
