<?php

namespace PandoraFMS\Modules\Groups\Repositories;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Entities\GroupDataMapper;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Repositories\RepositoryMySQL;

class GroupRepositoryMySQL extends RepositoryMySQL implements GroupRepository
{
    public function __construct(
        private GroupDataMapper $groupDataMapper,
        private Config $config
    ) {
    }

    /**
     * @return Group[],
     */
    public function list(GroupFilter $groupFilter): array
    {
        try {
            $sql = $this->getGroupsQuery($groupFilter, $this->groupDataMapper);
            $list = $this->dbGetAllRowsSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($list) === false) {
            throw new NotFoundException(__('%s not found', $this->groupDataMapper->getStringNameClass()));
        }

        $result = [];
        foreach ($list as $fields) {
            $result[] = $this->groupDataMapper->fromDatabase($fields);
        }

        return $result;
    }

    public function count(GroupFilter $groupFilter): int
    {
        $sql = $this->getGroupsQuery($groupFilter, $this->groupDataMapper, true);
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

    public function getOne(GroupFilter $groupFilter): Group
    {
        try {
            $sql = $this->getGroupsQuery($groupFilter, $this->groupDataMapper);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->groupDataMapper->getStringNameClass()));
        }

        return $this->groupDataMapper->fromDatabase($result);
    }

    public function create(Group $group): Group
    {
        $id = $this->__create($group, $this->groupDataMapper);
        return $group->setIdGroup($id);
    }

    public function update(Group $group): Group
    {
        return $this->__update(
            $group,
            $this->groupDataMapper,
            $group->getIdGroup()
        );
    }

    public function delete(int $id): void
    {
        $this->__delete($id, $this->groupDataMapper);
    }

    private function getGroupsQuery(
        FilterAbstract $filter,
        DataMapperAbstract $mapper,
        bool $count = false
    ): string {
        $pagination = '';
        $orderBy = '';
        $fields = 'COUNT(DISTINCT tgrupo.id_grupo) as count';
        $filters = $this->buildQueryFilters($filter, $mapper);

        // Check ACL for user list.
        if (users_can_manage_group_all('AR') === false) {
            $user_groups_acl = users_get_groups(false, 'AR', false);
            // Si no tiene ningun grupo y no es administrador,
            // se fuerza a que busque en el grupo 0, que no existe,
            // ya que no tendra accesoa a ningun grupo.
            if (empty($user_groups_acl) === true) {
                $user_groups_acl = [0];
            }

            $filters .= sprintf(
                ' AND tgrupo.id_grupo IN (%s)',
                implode(',', array_keys($user_groups_acl))
            );
        }

        if ($count === false) {
            $pagination = $this->buildQueryPagination($filter);
            $orderBy = $this->buildQueryOrderBy($filter);
            if (empty($filter->getFields()) === true) {
                $fields = 'tgrupo.*, tparent.nombre AS parent_name, IF(tgrupo.parent=tparent.id_grupo, 1, 0) AS has_child';
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
		    FROM tgrupo
		    LEFT JOIN tgrupo tparent
			    ON tgrupo.parent=tparent.id_grupo
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
