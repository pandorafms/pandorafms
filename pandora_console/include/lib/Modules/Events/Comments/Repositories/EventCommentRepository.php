<?php

namespace PandoraFMS\Modules\Events\Comments\Repositories;

use PandoraFMS\Modules\Events\Comments\Entities\EventComment;
use PandoraFMS\Modules\Events\Comments\Entities\EventCommentDataMapper;
use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class EventCommentRepository
{
    public function __construct(
        private Repository $repository,
        private EventCommentDataMapper $eventCommentDataMapper
    ) {
    }

    /**
     * @return EventComments[],
    */
    public function list(EventCommentFilter $eventCommentFilter): array
    {
        return $this->repository->__list(
            $eventCommentFilter,
            $this->eventCommentDataMapper
        );
    }

    public function count(EventCommentFilter $eventCommentFilter): int
    {
        return $this->repository->__count(
            $eventCommentFilter,
            $this->eventCommentDataMapper
        );
    }

    public function getOne(EventCommentFilter $eventCommentFilter): EventComment
    {
        return $this->repository->__getOne(
            $eventCommentFilter,
            $this->eventCommentDataMapper
        );
    }

    public function create(EventComment $eventComment): EventComment
    {
        $id = $this->repository->__create($eventComment, $this->eventCommentDataMapper);
        return $eventComment->setIdEventComment($id);
    }

    public function update(EventComment $eventComment): EventComment
    {
        return $this->repository->__update(
            $eventComment,
            $this->eventCommentDataMapper,
            $eventComment->getIdEventComment()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->eventCommentDataMapper);
    }

}
