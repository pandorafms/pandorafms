<?php

namespace PandoraFMS\Modules\Tags\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class TagDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'ttag';
    public const ID_TAG = 'id_tag';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const URL = 'url';
    public const MAIL = 'email';
    public const PHONE = 'phone';
    public const PREVIOUS_NAME = 'previous_name';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_TAG,
        );
    }

    public function getClassName(): string
    {
        return Tag::class;
    }

    public function fromDatabase(array $data): Tag
    {
        return $this->builder->build(new Tag(), [
            'idTag'        => $data[self::ID_TAG],
            'name'         => $this->repository->safeOutput($data[self::NAME]),
            'description'  => $this->repository->safeOutput($data[self::DESCRIPTION]),
            'url'          => $this->repository->safeOutput($data[self::URL]),
            'mail'         => $this->repository->safeOutput($data[self::MAIL]),
            'phone'        => $this->repository->safeOutput($data[self::PHONE]),
            'previousName' => $this->repository->safeOutput($data[self::PREVIOUS_NAME]),
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var Tag $data */
        return [
            self::ID_TAG        => $data->getIdTag(),
            self::NAME          => $this->repository->safeInput($data->getName()),
            self::DESCRIPTION   => $this->repository->safeInput($data->getDescription()),
            self::URL           => $this->repository->safeInput($data->getUrl()),
            self::MAIL          => $this->repository->safeInput($data->getMail()),
            self::PHONE         => $this->repository->safeInput($data->getPhone()),
            self::PREVIOUS_NAME => $this->repository->safeInput($data->getPreviousName()),
        ];
    }
}
