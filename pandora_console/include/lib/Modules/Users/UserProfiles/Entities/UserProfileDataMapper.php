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
    public const ASSIGNED_BY = 'assigned_by';


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
                'assignedBy'    => $data[self::ASSIGNED_BY],
            ]
        );
    }


    public function toDatabase(MappeableInterface $data): array
    {
        /*
            @var UserProfile $data
        */
        return [
            self::ID_USER_PROFILE => $data->getIdUserProfile(),
            self::ID_USER         => $data->getIdUser(),
            self::ID_PROFILE      => $data->getIdProfile(),
            self::ID_GROUP        => $data->getIdGroup(),
            self::ASSIGNED_BY     => $data->getAssignedBy(),
        ];
    }


}
