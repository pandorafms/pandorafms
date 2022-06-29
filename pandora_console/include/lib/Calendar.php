<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Calendar entity class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage OpenSource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
namespace PandoraFMS;

/**
 * PandoraFMS agent entity.
 */
class Calendar extends Entity
{


    /**
     * Search Calendar.
     *
     * @param array $filter Filters.
     *
     * @return array Rows.
     */
    public static function search(array $filter)
    {
        $table = '`talert_calendar`';
        $rows = \db_get_all_rows_filter(
            $table,
            $filter,
            ['`talert_calendar`.*']
        );

        return $rows;
    }


    /**
     * Builds a PandoraFMS\Calendar object from given id.
     *
     * @param integer $id Id special day.
     */
    public function __construct(?int $id=null)
    {
        $table = 'talert_calendar';
        $filter = ['id' => $id];

        $this->existsInDB = false;

        if (is_numeric($id) === true
            && $id > 0
        ) {
            parent::__construct(
                $table,
                $filter,
                null,
                false
            );
            $this->existsInDB = true;
        } else {
            // Create empty skel.
            parent::__construct($table, null);
        }
    }


    /**
     * Saves current definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if ($this->fields['id'] > 0) {
            // Update.
            $updates = $this->fields;

            $rs = \db_process_sql_update(
                $this->table,
                $updates,
                ['id' => $this->fields['id']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }
        } else {
            // Creation.
            $inserts = $this->fields;

            // Clean null fields.
            foreach ($inserts as $k => $v) {
                if ($v === null) {
                    unset($inserts[$k]);
                }
            }

            $rs = \db_process_sql_insert(
                $this->table,
                $inserts
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id'] = $rs;
        }

        return true;
    }


    /**
     * Remove this calendar.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->existsInDB === true) {
            \db_process_delete_temp(
                $this->table,
                'id',
                $this->fields['id']
            );
        }
    }


    /**
     * Returns an array with all calendar filtered.
     *
     * @param array   $fields         Fields array or 'count' keyword to retrieve count.
     * @param array   $filter         Filters to be applied.
     * @param boolean $count          Retrieve count of items instead results.
     * @param integer $offset         Offset (pagination).
     * @param integer $limit          Limit (pagination).
     * @param string  $order          Sort order.
     * @param string  $sort_field     Sort field.
     * @param boolean $select_options Array options for select.
     *
     * @return array With all results.
     * @throws \Exception On error.
     */
    public static function calendars(
        array $fields=[ '`talert_calendar`.*' ],
        array $filter=[],
        bool $count=false,
        ?int $offset=null,
        ?int $limit=null,
        ?string $order=null,
        ?string $sort_field=null,
        ?bool $select_options=false
    ) {
        $sql_filters = [];
        $order_by = '';
        $pagination = '';

        $user_groups = users_get_groups();
        $user_groups_ids = implode(',', array_keys($user_groups));

        if (isset($filter['free_search']) === true
            && empty($filter['free_search']) === false
        ) {
            $sql_filters[] = vsprintf(
                ' AND (`talert_calendar`.`name` like "%%%s%%"
                    OR `talert_calendar`.`description` like "%%%s%%")',
                array_fill(0, 2, $filter['free_search'])
            );
        }

        $sql_filters[] = ' AND id_group IN ('.$user_groups_ids.')';

        if (isset($order) === true) {
            $dir = 'asc';
            if ($order === 'desc') {
                $dir = 'desc';
            };

            if (in_array(
                $sort_field,
                [ 'name' ]
            ) === true
            ) {
                $order_by = sprintf(
                    'ORDER BY `talert_calendar`.`%s` %s',
                    $sort_field,
                    $dir
                );
            } else {
                // Custom field order.
                $order_by = sprintf(
                    'ORDER BY `%s` %s',
                    $sort_field,
                    $dir
                );
            }
        }

        if (isset($limit) === true && $limit > 0
            && isset($offset) === true && $offset >= 0
        ) {
            $pagination = sprintf(
                ' LIMIT %d OFFSET %d ',
                $limit,
                $offset
            );
        }

        $sql = sprintf(
            'SELECT %s
            FROM `talert_calendar`
            WHERE 1=1
            %s
            %s
            %s',
            join(',', $fields),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        if ($count === true) {
            $sql = sprintf('SELECT count(*) as n FROM ( %s ) tt', $sql);

            return ['count' => \db_get_value_sql($sql)];
        }

        $return = \db_get_all_rows_sql($sql);

        if (is_array($return) === false) {
            return [];
        }

        if ($select_options === true) {
            $return = array_reduce(
                $return,
                function ($carry, $item) {
                    $carry[$item['id']] = $item['name'];
                    return $carry;
                }
            );
        }

        return $return;
    }


}
