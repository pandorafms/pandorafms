<?php

namespace PandoraFMS\Modules\Shared\Repositories;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

abstract class Repository
{
    abstract protected function dbGetRow(
        string $field,
        string $table,
        mixed $value
    ): array;

    abstract protected function dbGetValue(
        string $field,
        string $table,
        array $filters,
        string $whereJoin = 'AND'
    ): mixed;

    abstract protected function dbGetValueSql(
        string $sql,
        ?bool $cache = false
    ): string;

    abstract protected function dbGetRowSql(
        string $sql
    ): array;

    abstract protected function dbGetAllRowsSql(
        string $sql,
        ?bool $cache = false
    ): array;

    abstract protected function dbInsert(string $table, array $values): mixed;

    abstract protected function dbUpdate(string $table, array $values, array $condition): mixed;

    abstract protected function dbDelete(string $table, array $where): mixed;

    abstract protected function dbFormatWhereClauseSQL(array $values, $prefix = ''): string;

    abstract public function buildQueryFilters(FilterAbstract $filter, DataMapperAbstract $mapper): string;

    abstract public function buildQueryPagination(FilterAbstract $filter): string;

    abstract public function buildQueryOrderBy(FilterAbstract $filter): string;

    abstract public function checkAclGroupMysql(string $field, ?string $mode = ''): string;

    abstract public function buildQuery(
        FilterAbstract $filter,
        DataMapperAbstract $mapper,
        bool $count = false
    ): string;

    abstract public function maxFieldSql(string $field): string;

    abstract public function safeInput(?string $value): ?string;

    abstract public function safeOutput(?string $value): ?string;

    /**
     * @return object[],
     */
    public function __list(FilterAbstract $filter, DataMapperAbstract $mapper): array
    {
        try {
            $sql = $this->buildQuery($filter, $mapper);
            $list = $this->dbGetAllRowsSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($list) === false) {
            throw new NotFoundException(__('%s not found', $mapper->getStringNameClass()));
        }

        $result = [];
        foreach ($list as $fields) {
            $result[] = $mapper->fromDatabase($fields);
        }

        return $result;
    }

    public function __rows(FilterAbstract $filter, DataMapperAbstract $mapper): array
    {
        try {
            $sql = $this->buildQuery($filter, $mapper);
            $rows = $this->dbGetAllRowsSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return $rows;
    }

    public function __count(FilterAbstract $filter, DataMapperAbstract $mapper): int
    {
        $sql = $this->buildQuery($filter, $mapper, true);
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

    public function __getOne(FilterAbstract $filter, DataMapperAbstract $mapper): object
    {
        try {
            $sql = $this->buildQuery($filter, $mapper);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $mapper->getStringNameClass()));
        }

        return $mapper->fromDatabase($result);
    }

    public function __create(Entity $entity, DataMapperAbstract $mapper): int
    {
        try {
            $id = $this->dbInsert(
                $mapper->getTableName(),
                $mapper->toDatabase($entity)
            );

            // Create Relation.
            if (empty($mapper->getSearchFieldValueRelated()) === false) {
                $this->dbInsert(
                    $mapper->getTableRelated(),
                    [
                        $mapper->getSearchFieldRelated() => $mapper->getSearchFieldValueRelated(),
                        $mapper->getKeyRelated()         => $id,
                    ]
                );
            }
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return $id;
    }

    public function __update(Entity $entity, DataMapperAbstract $mapper, mixed $id): object
    {
        $values = $mapper->toDatabase($entity);
        unset($values[$mapper->getPrimaryKey()]);
        try {
            $this->dbUpdate(
                $mapper->getTableName(),
                $values,
                [$mapper->getPrimaryKey() => $id]
            );
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return $entity;
    }

    public function __delete(
        mixed $id,
        DataMapperAbstract $mapper,
        ?string $key = null,
        ?array $where = null
    ): void {
        try {
            if (empty($key) === true) {
                $key = $mapper->getPrimaryKey();
            }

            $whereDelete = [$key => $id];
            if ($where !== null) {
                $whereDelete = array_merge($whereDelete, $where);
            }

            $this->dbDelete($mapper->getTableName(), $whereDelete);

            // Delete relation.
            if (empty($mapper->getTableRelated()) === false) {
                $this->dbDelete(
                    $mapper->getTableRelated(),
                    [
                        $mapper->getSearchFieldRelated() => $mapper->getSearchFieldValueRelated(),
                        $mapper->getKeyRelated()         => $id,
                    ]
                );
            }
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }
    }

    public function __getValue(FilterAbstract $filter, DataMapperAbstract $mapper): mixed
    {
        try {
            $sql = $this->buildQuery($filter, $mapper);
            $result = $this->dbGetValueSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return $result;
    }

    public function __maxField(string $field): string
    {
        return $this->maxFieldSql($field);
    }
}
