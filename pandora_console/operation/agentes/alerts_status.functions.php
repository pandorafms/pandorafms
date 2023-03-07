<?php
/**
 * Alerts Status functions script
 *
 * @category   Functions
 * @package    Pandora FMS
 * @subpackage Alert Status
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
function forceExecution($id_group)
{
    global $config;

    include_once 'include/functions_alerts.php';
    $id_alert = (int) get_parameter('id_alert');
    alerts_agent_module_force_execution($id_alert);
}


function validateAlert($ids)
{
    if (!empty($ids)) {
        include_once 'include/functions_alerts.php';
        $result = alerts_validate_alert_agent_module($ids);

        return ui_print_result_message(
            $result,
            __('Alert(s) validated'),
            __('Error processing alert(s)'),
            '',
            true
        );
    } else {
        return ui_print_error_message(__('No alert selected'));
    }
}


function printFormFilterAlert($id_group, $filter, $free_search, $url, $filter_standby=false, $tag_filter=false, $action_filter=false, $return=false, $strict_user=false, $access='AR')
{
    global $config;
    include_once $config['homedir'].'/include/functions_tags.php';

    $table = new StdClass();
    $table->width = '100%';
    $table->class = 'filter-table-adv p020';
    $table->size = [];
    $table->size[0] = '33%';
    $table->size[1] = '33%';
    $table->size[2] = '33%';
    $table->data = [];
    $table->data[0][0] = html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            $config['id_user'],
            $access,
            true,
            'ag_group',
            $id_group,
            '',
            '',
            '',
            true,
            false,
            false,
            '',
            false,
            '',
            false,
            false,
            'id_grupo',
            $strict_user
        )
    );

    $alert_status_filter = [];
    $alert_status_filter['all_enabled'] = __('All (Enabled)');
    $alert_status_filter['all'] = __('All');
    $alert_status_filter['fired'] = __('Fired');
    $alert_status_filter['notfired'] = __('Not fired');
    $alert_status_filter['disabled'] = __('Disabled');

    $alert_standby = [];
    $alert_standby['1'] = __('Standby on');
    $alert_standby['0'] = __('Standby off');

    $table->data[0][1] = html_print_label_input_block(
        __('Status'),
        html_print_select(
            $alert_status_filter,
            'disabled',
            $filter,
            '',
            '',
            '',
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    $tags = tags_get_user_tags();
    if (empty($tags) === true) {
        $callbackTag = html_print_input_text('tags', __('No tags'), '', 20, 40, true, true);
    } else {
        $callbackTag = html_print_select(
            $tags,
            'tag',
            $tag_filter,
            '',
            __('All'),
            '',
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        );
    }

    $table->data[0][2] = html_print_label_input_block(
        __('Tags').ui_print_help_tip(__('Only it is show tags in use.'), true),
        $callbackTag
    );

    $table->data[1][0] = html_print_label_input_block(
        __('Free text for search').ui_print_help_tip(
            __('Filter by agent name, module name, template name or action name'),
            true
        ),
        html_print_input_text('free_search', $free_search, '', 20, 40, true)
    );

    $table->data[1][1] = html_print_label_input_block(
        __('Standby'),
        html_print_select(
            $alert_standby,
            'standby',
            $filter_standby,
            '',
            __('All'),
            '',
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    $alert_action = alerts_get_alert_actions_filter();
    $table->data[1][2] = html_print_label_input_block(
        __('Action'),
        html_print_select(
            $alert_action,
            'action',
            $action_filter,
            '',
            __('All'),
            '',
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    $data .= html_print_table($table, true);

    if ($return) {
        return $data;
    } else {
        echo $data;
    }
}


function printFormFilterAlertAgent($agent_view_page, $free_search, $id_agent, $return=false)
{
    $table_filter = new stdClass();
    $table_filter->width = '100%';

    if ($agent_view_page === true) {
        $table_filter->class = 'info_table';
        $table_filter->styleTable = 'border-radius: 0;padding: 0;margin: 0;';
        $free_search_name = 'free_search_alert';
    } else {
        $table_filter->class = 'databox filters';
        $free_search_name = 'free_search';
    }

    $table_filter->style = [];
    $table_filter->style[0] = 'font-weight: bold';
    $table_filter->data = [];

    $table_filter->data[0][0] = __('Free text for search (*):').ui_print_help_tip(
        __('Filter by module name, template name or action name'),
        true
    );

    $table_filter->data[0][0] .= '<span class="mrgn_lft_10px">'.html_print_input_text(
        $free_search_name,
        $free_search,
        '',
        20,
        100,
        true
    ).'</span>';

    $form = html_print_table($table_filter, true);

    if ($return === true) {
        return $form;
    } else {
        echo $form;
    }
}
