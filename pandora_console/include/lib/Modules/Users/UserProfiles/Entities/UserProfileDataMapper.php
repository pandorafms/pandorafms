<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class UserProfileDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tusuario_perfil';
    public const ID_USER_PROFILE = 'id_up';
    public const ID_USER = 'id_usuario';
    public const ID_PROFILE = 'id_perfil';
    public const ID_GROUP = 'id_grupo';
    public const IS_NO_HIERARCHY = 'no_hierarchy';
    public const ASSIGNED_BY = 'assigned_by';
    public const ID_POLICY = 'id_policy';
    public const TAGS = 'tags';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_USER_PROFILE,
        );
    }

    public function getClassName(): string
    {
        return UserProfile::class;
    }

    public function fromDatabase(array $data): UserProfile
    {
        return $this->builder->build(
            new UserProfile(),
            [
                'idUserProfile' => $data[self::ID_USER_PROFILE],
                'idUser'        => $data[self::ID_USER],
                'idProfile'     => $data[self::ID_PROFILE],
                'idGroup'       => $data[self::ID_GROUP],
                'isNoHierarchy' => $data[self::IS_NO_HIERARCHY],
                'assignedBy'    => $data[self::ASSIGNED_BY],
                'idPolicy'      => $data[self::ID_POLICY],
                'tags'          => (empty($data[self::TAGS]) === false) ? explode(',', $data[self::TAGS]) : null,
            ]
        );
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var UserProfile $data */
        return [
            self::ID_USER_PROFILE => $data->getIdUserProfile(),
            self::ID_USER         => $data->getIdUser(),
            self::ID_PROFILE      => $data->getIdProfile(),
            self::ID_GROUP        => $data->getIdGroup(),
            self::IS_NO_HIERARCHY => $data->getIsNoHierarchy(),
            self::ASSIGNED_BY     => $data->getAssignedBy(),
            self::ID_POLICY       => $data->getIdPolicy(),
            self::TAGS            => (empty($data->getTags()) === false) ? implode(',', $data->getTags()) : null,
        ];
    }
}
