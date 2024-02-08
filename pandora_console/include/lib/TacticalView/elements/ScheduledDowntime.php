<?php
/**
 * ScheduledDowntime element for tactical view.
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\TacticalView\Element;

/**
 * ScheduledDowntime, this class contain all logic for this section.
 */
class ScheduledDowntime extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        global $config;
        parent::__construct();
        ui_require_javascript_file('pandora_planned_downtimes');
        include_once $config['homedir'].'/include/functions_reporting.php';
        $this->title = __('Scheduled Downtime');
        $this->ajaxMethods = ['getScheduleDowntime'];
    }


    /**
     * List all schedule downtime.
     *
     * @return string
     */
    public function list():string
    {
        $columns = [
            'name',
            'configuration',
            'running',
            'affected',
        ];

        $columnNames = [
            __('Name #Ag.'),
            __('Configuration'),
            __('Running'),
            __('Affected'),
        ];

        return ui_print_datatable(
            [
                'id'                  => 'list_downtime',
                'class'               => 'info_table',
                'style'               => 'width: 90%',
                'dom_elements'        => 'tfp',
                'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                'columns'             => $columns,
                'column_names'        => $columnNames,
                'ajax_url'            => $this->ajaxController,
                'no_sortable_columns' => [
                    1,
                    2,
                ],
                'ajax_data'           => [
                    'method' => 'getScheduleDowntime',
                    'class'  => static::class,
                ],
                'order'               => [
                    'field'     => 'name',
                    'direction' => 'asc',
                ],
                'default_pagination'  => 5,
                'search_button_class' => 'sub filter float-right',
                'return'              => true,
            ]
        );
    }


    /**
     * Return the schedule downtime for datatable by ajax.
     *
     * @return void
     */
    public function getScheduleDowntime():void
    {
        global $config;

        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $orderDatatable = get_datatable_order(true);
        $pagination = '';
        $order = '';

        try {
            ob_start();
            if (isset($orderDatatable)) {
                $order = sprintf(
                    ' ORDER BY %s %s',
                    $orderDatatable['field'],
                    $orderDatatable['direction']
                );
            }

            if (isset($length) && $length > 0
                && isset($start) && $start >= 0
            ) {
                $pagination = sprintf(
                    ' LIMIT %d OFFSET %d ',
                    $length,
                    $start
                );
            }

            $columns = [
                'id',
                'name',
                'description',
                'date_from',
                'date_to',
                'executed',
                'id_group',
                'only_alerts',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
                'periodically_time_from',
                'periodically_time_to',
                'periodically_day_from',
                'periodically_day_to',
                'type_downtime',
                'type_execution',
                'type_periodicity',
                'id_user',
                'cron_interval_from',
                'cron_interval_to',
            ];
            $groups = implode(',', array_keys(users_get_groups($config['user'])));
            $columns_str = implode(',', $columns);
            $sql = sprintf(
                'SELECT %s
                FROM tplanned_downtime
                WHERE id_group IN (%s)
                %s %s',
                $columns_str,
                $groups,
                $order,
                $pagination,
            );

            $sql_count = 'SELECT COUNT(id) AS num
                          FROM tplanned_downtime';

            $downtimes = db_get_all_rows_sql($sql);
            foreach ($downtimes as $key => $downtime) {
                if ((int) $downtime['executed'] === 0) {
                    $downtimes[$key]['running'] = html_print_div(
                        [
                            'content' => '',
                            'class'   => 'square stop',
                            'title'   => 'Not running',
                        ],
                        true
                    );
                } else {
                    $downtimes[$key]['running'] = html_print_div(
                        [
                            'content' => '',
                            'class'   => 'square running',
                            'title'   => 'Running',
                        ],
                        true
                    );
                }

                $downtimes[$key]['configuration'] = reporting_format_planned_downtime_dates($downtime);

                $settings = [
                    'url'         => ui_get_full_url('ajax.php', false, false, false),
                    'loadingText' => __('Loading, this operation might take several minutes...'),
                    'title'       => __('Elements affected'),
                    'id'          => $downtime['id'],
                ];

                $downtimes[$key]['affected'] = '<a style="margin-left: 22px;" href="javascript:" onclick=\'dialogAgentModulesAffected('.json_encode($settings).')\'>';
                $downtimes[$key]['affected'] .= html_print_image(
                    'images/details.svg',
                    true,
                    [
                        'title' => __('Agents and modules affected'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                );
                $downtimes[$key]['affected'] .= '</a>';
            }

            $downtimes_number_res = db_get_all_rows_sql($sql_count);
            $downtimes_number = ($downtimes_number_res !== false) ? $downtimes_number_res[0]['num'] : 0;

            if (empty($downtimes) === true) {
                $downtimes = [];
            }

            echo json_encode(
                [
                    'data'            => $downtimes,
                    'recordsTotal'    => $downtimes_number,
                    'recordsFiltered' => $downtimes_number,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $response;
        } else {
            echo json_encode(
                [
                    'success' => false,
                    'error'   => $response,
                ]
            );
        }

        exit;
    }


    /**
     * Check permission acl for this section.
     *
     * @return boolean
     */
    public function checkAcl():bool
    {
        global $config;
        $read_permisson = (bool) check_acl($config['id_user'], 0, 'AR');
        if ($read_permisson === true) {
            return true;
        } else {
            return false;
        }
    }


}
