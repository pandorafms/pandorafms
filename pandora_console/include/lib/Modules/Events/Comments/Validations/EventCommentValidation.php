<?php

namespace PandoraFMS\Modules\Events\Comments\Validations;

use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Services\GetEventCommentService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Timestamp;

final class EventCommentValidation
{
    public function __construct(
        private Config $config,
        private Timestamp $timestamp,
        private GetEventCommentService $getEventCommentService,
    ) {
    }

    public function __invoke(EventComment $eventComment, ?EventComment $oldEventComment = null): void
    {
        if (!$eventComment->getComment()) {
            throw new BadRequestException(__('Comment is missing'));
        }

        if ($eventComment->getAction() === null) {
            $eventComment->setAction('Added comment');
        }

        if ($oldEventComment === null) {
            $eventComment->setUtimestamp($this->getCurrentUtimestamp());
            $eventComment->setIdUser($this->config->get('id_user'));
        }
    }

    protected function getCurrentUtimestamp(): int
    {
        return $this->timestamp->getMysqlSystemUtimestamp();
    }
}
