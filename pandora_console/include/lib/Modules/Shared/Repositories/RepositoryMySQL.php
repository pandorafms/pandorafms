<?php

namespace PandoraFMS\Modules\Shared\Repositories;

use Exception;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Services\Config;

class RepositoryMySQL extends Repository
{
    protected function dbGetRow(
        string $field,
        string $table,
        mixed $value
    ): array {
        ob_start();
        $result = \db_get_row($table, $field, $value);
        $error = ob_get_clean();

        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    protected function dbGetValue(
        string $field,
        string $table,
        array $filters,
        string $whereJoin = 'AND'
    ): mixed {
        return \db_get_value_filter($field, $table, $filters, $whereJoin);
    }

    protected function dbGetValueSql(
        string $sql,
        ?bool $cache = false
    ): string {
        ob_start();
        $result = \db_get_value_sql($sql, $cache);
        $error = ob_get_clean();

        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = '';
        }

        return $result;
    }

    protected function dbGetRowSql(
        string $sql
    ): array {
        ob_start();
        $result = \db_get_row_sql($sql);
        $error = ob_get_clean();

        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    protected function dbGetAllRowsSql(
        string $sql,
        ?bool $cache = false
    ): array {
        ob_start();
        $result = \db_get_all_rows_sql($sql, $cache);
        $error = ob_get_clean();

        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    protected function dbInsert(string $table, array $values): mixed
    {
        ob_start();
        $result = \db_process_sql_insert($table, $values);
        $error = ob_get_clean();
        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    protected function dbUpdate(string $table, array $values, array $condition): mixed
    {
        ob_start();
        $result = \db_process_sql_update(
            $table,
            $values,
            $condition
        );
        $error = ob_get_clean();
        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    protected function dbDelete(string $table, array $where): mixed
    {
        ob_start();
        $result = \db_process_sql_delete($table, $where);

        $error = ob_get_clean();
        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        if ($result === false) {
            $result = 0;
        }

        return $result;
    }

    protected function dbFormatWhereClauseSQL(array $values, $prefix = ''): string
    {
        ob_start();
        $values_prefix = [];
        foreach ($values as $key => $value) {
            $values_prefix[$prefix.$key] = $value;
        }

        $result = \db_format_array_where_clause_sql($values_prefix, 'AND');

        $error = ob_get_clean();
        if ($result === false && empty($error) === false) {
            throw new Exception($error);
        }

        return $result;
    }

    public function buildQueryFilters(FilterAbstract $filter, DataMapperAbstract $mapper): string
    {
        $where_clause = '1=1';

        if ($filter->getEntityFilter() !== null) {
            $searchEntity = $mapper->toDatabase($filter->getEntityFilter());
            $searchEntity = array_filter($searchEntity, fn ($value) => !is_null($value) && $value !== '' && $value !== 'null');
            if (empty($searchEntity) === false) {
                $where_clause .= ' AND '.$this->dbFormatWhereClauseSQL($searchEntity, '`'.$mapper->getTableName().'`.');
            }
        }

        if (empty($filter->getFieldsFreeSearch()) === false
            && empty($filter->toArray()['freeSearch']) === false
        ) {
            $where_clause .= $this->freeSearch($filter->getFieldsFreeSearch(), $filter->toArray()['freeSearch']);
        }

        if (empty($filter->getMultipleSearch()) === false) {
            $where_clause .= $this->multipleSearch($filter);
        }

        if (empty($filter->getMultipleSearchString()) === false) {
            $where_clause .= $this->multipleSearchString($filter);
        }

        if (empty($filter->getFieldAclGroupMysql()) === false) {
            $where_clause .= $this->checkAclGroupMysql(
                $filter->getFieldAclGroupMysql(),
                $filter->getModeAclGroupMysql()
            );
        }

        return $where_clause;
    }

    private function freeSearch(array $fields, string $value): string
    {
        $clause = ' AND (';
        $count = count($fields);
        foreach ($fields as $field) {
            $clause .= sprintf('%s LIKE "%%%s%%"', $field, $value);

            $count--;
            if ($count > 0) {
                $clause .= ' OR ';
            }
        }

        $clause .= ') ';
        return $clause;
    }

    private function multipleSearch(FilterAbstract $filter): string
    {
        $fields = $filter->fieldsTranslate();
        $field = '';
        if (empty($fields) === false) {
            $field = ($fields[($filter->getMultipleSearch()['field'] ?? '')] ?? '');
        }

        if (empty($field) === true) {
            return '';
        }

        $clause = ' AND '.$field.' IN ('.implode(',', $filter->getMultipleSearch()['data']).')';
        return $clause;
    }

    private function multipleSearchString(FilterAbstract $filter): string
    {
        $fields = $filter->fieldsTranslate();
        $field = '';
        if (empty($fields) === false) {
            $field = ($fields[($filter->getMultipleSearchString()['field'] ?? '')] ?? '');
        }

        if (empty($field) === true) {
            return '';
        }

        $clause = ' AND '.$field.' IN ("'.implode('","', $filter->getMultipleSearchString()['data']).'")';
        return $clause;
    }

    public function buildQueryPagination(FilterAbstract $filter): string
    {
        $filter->setLimit($filter->getSizePage());
        $filter->setOffset($filter->getPage() * $filter->getSizePage());

        $sqlLimit = '';
        if (empty($filter->getLimit()) === false) {
            $sqlLimit = sprintf(
                ' LIMIT %d OFFSET %d',
                $filter->getLimit(),
                $filter->getOffset()
            );
        }

        return $sqlLimit;
    }

    public function buildQueryOrderBy(FilterAbstract $filter): string
    {
        $default = '';
        if (empty($filter->getDefaultFieldOrder()) === false) {
            $default = sprintf(
                'ORDER BY %s %s',
                $filter->getDefaultFieldOrder(),
                $this->checkDirectionOrderByMsql($filter->getDefaultDirectionOrder())
            );
        }

        $fieldsTranslate = $filter->fieldsTranslate();
        if (empty($fieldsTranslate) === true || isset($fieldsTranslate[$filter->getSortField()]) === false) {
            return $default;
        }

        $field = $fieldsTranslate[$filter->getSortField()];
        $sort = 'ASC';
        if (empty($filter->getSortDirection()) === false) {
            $sort = $this->checkDirectionOrderByMsql($filter->getSortDirection());
        }

        $return = '';
        if (empty($field) === false) {
            $return = 'ORDER BY '.$field.' '.$sort;
        }

        return $return;
    }

    public function buildQueryGroupBy(FilterAbstract $filter): string
    {
        $groupBy = '';
        $fieldsGroupByTranslate = [];
        if (empty($filter->getGroupByFields()) === false) {
            $fieldsTranslate = $filter->fieldsTranslate();
            if (empty($fieldsTranslate) === false) {
                foreach ($filter->getGroupByFields() as $value) {
                    if (isset($fieldsTranslate[$value]) === false) {
                        return $groupBy;
                    }

                    $fieldsGroupByTranslate[] = $fieldsTranslate[$value];
                }

                $groupBy = sprintf('GROUP BY %s', implode(',', $fieldsGroupByTranslate));
            }
        }

        return $groupBy;
    }

    private function checkDirectionOrderByMsql(?string $direction): string
    {
        $directionArray = [
            'DESC' => 'DESC',
            'ASC'  => 'ASC',
        ];

        return (isset($directionArray[$direction]) === true) ? $directionArray[$direction] : 'ASC';
    }

    public function checkAclGroupMysql(string $field, ?string $mode = ''): string
    {
        $config = new Config();
        $isAdmin = \users_is_admin($config->get('id_user'));
        if ($isAdmin === true) {
            return '';
        }

        $userGroups = array_keys(
            \users_get_groups(
                $config->get('id_user'),
                'AR',
                true,
                false
            )
        );

        if (empty($userGroups) === true) {
            return '';
        }

        if ($mode === 'array') {
            $filter = ' AND ( ';
            $i = 0;
            foreach ($userGroups as $group) {
                if ($i !== 0) {
                    $filter .= ' OR ';
                }

                $filter .= $group.' MEMBER OF ('.$field.') ';

                $i++;
            }

            $filter .= ' ) ';

            return $filter;
        }

        $filter = sprintf(
            ' AND %s IN (%s)',
            $field,
            implode(',', $userGroups)
        );

        return $filter;
    }

    public function buildQuery(
        FilterAbstract $filter,
        DataMapperAbstract $mapper,
        bool $count = false
    ): string {
        $filters = $this->buildQueryFilters($filter, $mapper);
        if (empty($mapper->getSearchFieldRelated()) === false) {
            $filters .= sprintf(
                ' AND %s.%s = %d',
                $mapper->getTableRelated(),
                $mapper->getSearchFieldRelated(),
                $mapper->getSearchFieldValueRelated()
            );
        }

        $conditionInnerJoin = '';
        if (empty($mapper->getTableRelated()) === false) {
            $conditionInnerJoin = sprintf(
                'INNER JOIN %s ON %s.%s = %s.%s',
                $mapper->getTableRelated(),
                $mapper->getTableName(),
                $mapper->getPrimaryKey(),
                $mapper->getTableRelated(),
                $mapper->getKeyRelated()
            );
        }

        $pagination = '';
        $orderBy = '';
        $groupBy = '';
        $fields = 'COUNT(*) as count';
        if ($count === false) {
            $pagination = $this->buildQueryPagination($filter);
            $orderBy = $this->buildQueryOrderBy($filter);
            $groupBy = $this->buildQueryGroupBy($filter);
            if (empty($filter->getFields()) === true) {
                $fields = $mapper->getTableName().'.*';
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
            FROM %s
            %s
            WHERE %s
            %s
            %s
            %s',
            $fields,
            $mapper->getTableName(),
            $conditionInnerJoin,
            $filters,
            $groupBy,
            $orderBy,
            $pagination
        );

        return $sql;
    }

    public function maxFieldSql(string $field): string
    {
        return 'MAX('.$field.')';
    }

    public function safeInput(?string $value): ?string
    {
        return \io_safe_input($value);
    }

    public function safeOutput(?string $value): ?string
    {
        return \io_safe_output($value);
    }
}
