<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Special day entity class.
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
class SpecialDay extends Entity
{


    /**
     * Builds a PandoraFMS\SpecialDay object from given id.
     *
     * @param integer $id Id special day.
     */
    public function __construct(?int $id=null)
    {
        $table = 'talert_special_days';
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
     * Remove this Special day.
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
     * Returns an array with all special days filtered.
     *
     * @param array   $fields     Fields array or 'count' keyword to retrieve count.
     * @param array   $filter     Filters to be applied.
     * @param boolean $count      Retrieve count of items instead results.
     * @param integer $offset     Offset (pagination).
     * @param integer $limit      Limit (pagination).
     * @param string  $order      Sort order.
     * @param string  $sort_field Sort field.
     * @param boolean $reduce     Reduce result [Year][month][day].
     *
     * @return array With all results.
     * @throws \Exception On error.
     */
    public static function specialDays(
        array $fields=[ '`talert_special_days`.*' ],
        array $filter=[],
        bool $count=false,
        ?int $offset=null,
        ?int $limit=null,
        ?string $order=null,
        ?string $sort_field=null,
        ?bool $reduce=false
    ) {
        $sql_filters = [];
        $order_by = '';
        $pagination = '';

        if (isset($filter['free_search']) === true
            && empty($filter['free_search']) === false
        ) {
            $sql_filters[] = vsprintf(
                ' AND `talert_special_days`.`name` like "%%%s%%"',
                array_fill(0, 1, $filter['free_search'])
            );
        }

        if (isset($filter['id_calendar']) === true
            && empty($filter['id_calendar']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`id_calendar` = %d',
                $filter['id_calendar']
            );
        }

        if (isset($filter['date']) === true
            && empty($filter['date']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`date` >= "%s"',
                $filter['date']
            );
        }

        if (isset($filter['futureDate']) === true
            && empty($filter['futureDate']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`date` <= "%s"',
                $filter['futureDate']
            );
        }

        if (isset($filter['id_group']) === true
            && empty($filter['id_group']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`id_group` IN (%s)',
                implode(',', $filter['id_group'])
            );
        }

        if (isset($filter['date_match']) === true
            && empty($filter['date_match']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`date` = "%s"',
                $filter['date_match']
            );
        }

        if (isset($filter['day_code']) === true
            && empty($filter['day_code']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND `talert_special_days`.`day_code` = %d',
                $filter['day_code']
            );
        }

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
                    'ORDER BY `talert_special_days`.`%s` %s',
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
            FROM `talert_special_days`
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

        if ($reduce === true) {
            $return = array_reduce(
                $return,
                function ($carry, $item) {
                    $year = date('Y', strtotime($item['date']));
                    $month = date('n', strtotime($item['date']));
                    $day = date('j', strtotime($item['date']));
                    $carry[$year][$month][$day][] = $item;
                    return $carry;
                }
            );
        }

        return $return;
    }


}
