<?php

namespace PandoraFMS\Modules\Events\Comments\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class EventCommentDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tevent_comment';
    public const ID_EVENT_COMMENT = 'id';
    public const ID_EVENT = 'id_event';
    public const UTIMESTAMP = 'utimestamp';
    public const COMMENT = 'comment';
    public const ID_USER = 'id_user';
    public const ACTION = 'action';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_EVENT_COMMENT,
        );
    }

    public function getClassName(): string
    {
        return EventComment::class;
    }

    public function fromDatabase(array $data): EventComment
    {
        return $this->builder->build(new EventComment(), [
            'idEventComment' => $data[self::ID_EVENT_COMMENT],
            'idEvent'        => $data[self::ID_EVENT],
            'utimestamp'     => $data[self::UTIMESTAMP],
            'comment'        => $this->repository->safeOutput($data[self::COMMENT]),
            'idUser'         => $this->repository->safeOutput($data[self::ID_USER]),
            'action'         => $this->repository->safeOutput($data[self::ACTION]),
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var EventComment $data */
        return [
            self::ID_EVENT_COMMENT => $data->getIdEventComment(),
            self::ID_EVENT         => $data->getIdEvent(),
            self::UTIMESTAMP       => $data->getUtimestamp(),
            self::COMMENT          => $this->repository->safeInput($data->getComment()),
            self::ID_USER          => $this->repository->safeInput($data->getIdUser()),
            self::ACTION           => $this->repository->safeInput($data->getAction()),
        ];
    }
}
