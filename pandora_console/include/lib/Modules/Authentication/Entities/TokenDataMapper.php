<?php

namespace PandoraFMS\Modules\Authentication\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class TokenDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'ttoken';
    public const ID_TOKEN = 'id';
    public const LABEL = 'label';
    public const UUID = 'uuid';
    public const CHALLENGE = 'challenge';
    public const ID_USER = 'id_user';
    public const VALIDITY = 'validity';
    public const LAST_USAGE = 'last_usage';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_TOKEN,
        );
    }

    public function getClassName(): string
    {
        return Token::class;
    }

    public function fromDatabase(array $data): Token
    {
        return $this->builder->build(new Token(), [
            'idToken'   => $data[self::ID_TOKEN],
            'label'     => $this->repository->safeOutput($data[self::LABEL]),
            'uuid'      => $data[self::UUID],
            'challenge' => $data[self::CHALLENGE],
            'idUser'    => $data[self::ID_USER],
            'validity'  => $data[self::VALIDITY],
            'lastUsage' => $data[self::LAST_USAGE],
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var Token $data */
        return [
            self::ID_TOKEN   => $data->getIdToken(),
            self::LABEL      => $this->repository->safeInput($data->getLabel()),
            self::UUID       => $data->getUuid(),
            self::CHALLENGE  => $data->getChallenge(),
            self::ID_USER    => $data->getIdUser(),
            self::VALIDITY   => $data->getValidity(),
            self::LAST_USAGE => $data->getLastUsage(),
        ];
    }
}
