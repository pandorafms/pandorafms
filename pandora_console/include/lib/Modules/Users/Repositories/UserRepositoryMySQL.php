<?php

namespace PandoraFMS\Modules\Users\Repositories;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Repositories\RepositoryMySQL;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Entities\UserDataMapper;
use PandoraFMS\Modules\Users\Entities\UserFilter;

final class UserRepositoryMySQL extends RepositoryMySQL implements UserRepository
{
    public function __construct(
        private UserDataMapper $userDataMapper,
        private Config $config
    ) {
    }

    /**
     * @return User[],
     */
    public function list(UserFilter $userFilter): array
    {
        try {
            $sql = $this->getUsersQuery($userFilter, $this->userDataMapper);
            $list = $this->dbGetAllRowsSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($list) === false) {
            throw new NotFoundException(__('%s not found', $this->userDataMapper->getStringNameClass()));
        }

        $result = [];
        foreach ($list as $fields) {
            $result[] = $this->userDataMapper->fromDatabase($fields);
        }

        return $result;
    }

    public function count(UserFilter $userFilter): int
    {
        $sql = $this->getUsersQuery($userFilter, $this->userDataMapper, true);
        try {
            $count = $this->dbGetValueSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return (int) $count;
    }

    public function getOne(UserFilter $userFilter): User
    {
        try {
            $sql = $this->getUsersQuery($userFilter, $this->userDataMapper);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->userDataMapper->getStringNameClass()));
        }

        return $this->userDataMapper->fromDatabase($result);
    }

    public function getExistUser(string $idUser): User
    {
        try {
            $sql = sprintf('SELECT * FROM `tusuario` WHERE `id_user` = "%s"', $idUser);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->userDataMapper->getStringNameClass()));
        }

        return $this->userDataMapper->fromDatabase($result);
    }

    public function create(User $user): User
    {
        $this->__create($user, $this->userDataMapper);
        return $user;
    }

    public function update(User $user): User
    {
        return $this->__update(
            $user,
            $this->userDataMapper,
            $user->getIdUser()
        );
    }

    public function delete(string $id): void
    {
        $this->__delete($id, $this->userDataMapper);
    }

    private function getUsersQuery(
        FilterAbstract $filter,
        DataMapperAbstract $mapper,
        bool $count = false
    ): string {
        $pagination = '';
        $orderBy = '';
        $fields = 'COUNT(DISTINCT tusuario.id_user) as count';
        $filters = $this->buildQueryFilters($filter, $mapper);

        // Check ACL for user list.
        if (\users_is_admin() !== true) {
            // No admin.
            $filters .= ' AND tusuario.is_admin = 0';

            // Only search in groups UM User management.
            $group_um = \users_get_groups_UM($this->config->get('id_user'));
            if (empty($group_um) === false && isset($group_um[0]) === false) {
                $filters .= sprintf(
                    ' AND tusuario_perfil.id_grupo IN (%s)',
                    implode(',', array_keys($group_um))
                );
            }
        }

        if ($count === false) {
            $pagination = $this->buildQueryPagination($filter);
            $orderBy = $this->buildQueryOrderBy($filter);
            if (empty($filter->getFields()) === true) {
                $fields = 'DISTINCT tusuario.*';
            } else {
                $buildFields = '';
                foreach ($filter->getFields() as $field) {
                    if (empty($buildFields) === false) {
                        $buildFields .= ' , ';
                    }

                    $buildFields .= $field;
                }

                $fields = $buildFields;
            }
        }

        $sql = sprintf(
            'SELECT %s
            FROM tusuario
            LEFT JOIN tusuario_perfil
                ON tusuario.id_user = tusuario_perfil.id_usuario
            WHERE %s
            %s
            %s',
            $fields,
            $filters,
            $orderBy,
            $pagination
        );

        return $sql;
    }
}
