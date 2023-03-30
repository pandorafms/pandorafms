<?php
/**
 * Massive Operations Functions
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
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


/**
 * Generate a action button for submit the form.
 *
 * @param string  $action       Action to send in form.
 * @param string  $buttonAction Action of the button: Create, Update or Delete.
 * @param string  $tableWidth   Set the table width for the container.
 * @param boolean $return       If true, return a formed string.
 *
 * @return string
 */
function attachActionButton(
    string $action,
    string $buttonAction,
    string $tableWidth,
    bool $return=false,
    string $SelectAction=''
) {
    switch ($buttonAction) {
        case 'add':
            $caption = 'Add';
            $class = 'add';
        break;

        case 'copy':
            $caption = 'Copy';
            $class = 'wand';
        break;

        case 'create':
            $caption = 'Create';
            $class = 'upd';
        break;

        case 'update':
            $caption = 'Update';
            $class = 'upd';
        break;

        case 'delete':
            $caption = 'Delete';
            $class = 'delete';
        break;

        default:
            // Do none.
        break;
    }

    html_print_action_buttons(
        html_print_input_hidden(
            $action,
            1
        ).html_print_button(
            __($caption),
            'go',
            false,
            '',
            ['icon' => $class],
            true
        ),
        ['right_content' => $SelectAction],
        $return
    );
}


/**
 * Get table inputs for massive operation agents edit and delete.
 *
 * @param array $params Params.
 *
 * @return string Output.
 */
function get_table_inputs_masive_agents($params)
{
    global $config;

    $table = new stdClass;
    $table->id = 'delete_table';
    $table->class = 'databox filters';
    $table->width = '100%';
    $table->data = [];
    $table->style = [];
    $table->style[0] = 'font-weight: bold;';
    $table->style[2] = 'font-weight: bold';
    $table->size = [];
    $table->size[0] = '15%';
    $table->size[1] = '35%';
    $table->size[2] = '15%';
    $table->size[3] = '35%';

    $table->data = [];
    $table->data[0][0] = __('Group');
    $table->data[0][1] = html_print_select_groups(
        false,
        'AW',
        true,
        'id_group',
        $params['id_group'],
        false,
        '',
        '',
        true
    );
    $table->data[0][2] = __('Group recursion');
    $table->data[0][3] = html_print_checkbox(
        'recursion',
        1,
        $params['recursion'],
        true,
        false
    );

    $status_list = [];
    $status_list[AGENT_STATUS_NORMAL] = __('Normal');
    $status_list[AGENT_STATUS_WARNING] = __('Warning');
    $status_list[AGENT_STATUS_CRITICAL] = __('Critical');
    $status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
    $status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
    $status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
    $table->data[1][0] = __('Status');
    $table->data[1][1] = html_print_select(
        $status_list,
        'status_agents',
        'selected',
        '',
        __('All'),
        AGENT_STATUS_ALL,
        true
    );

    $table->data[1][2] = __('Show agents');
    $table->data[1][3] = html_print_select(
        [
            0 => 'Only enabled',
            1 => 'Only disabled',
        ],
        'disabled',
        2,
        '',
        __('All'),
        2,
        true,
        false,
        true,
        '',
        false,
        'width:30%;'
    );

    if (is_metaconsole() === true) {
        $servers = metaconsole_get_servers();
        $server_fields = [];
        foreach ($servers as $key => $server) {
            $server_fields[$key] = $server['server_name'];
        }

        $table->data[2][2] = __('Node');
        $table->data[2][3] = html_print_select(
            $server_fields,
            'nodes[]',
            0,
            false,
            '',
            '',
            true,
            true,
            true,
            '',
            false,
            'min-width: 500px; max-width: 500px; max-height: 100px',
            false,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            true,
            true,
            true
        );
    }

    $table->data[3][0] = __('Agents');
    $table->data[3][0] .= '<span id="agent_loading" class="invisible">';
    $table->data[3][0] .= html_print_image('images/spinner.png', true);
    $table->data[3][0] .= '</span>';

    $agents = [];
    if (is_metaconsole() === false) {
        $agents = agents_get_group_agents(
            array_keys(users_get_groups($config['id_user'], 'AW', false)),
            ['disabled' => 2],
            'none'
        );
    }

    $table->data[3][1] = html_print_select(
        $agents,
        'id_agents[]',
        0,
        false,
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'min-width: 500px; max-width: 500px; max-height: 100px',
        false,
        false,
        false,
        '',
        false,
        false,
        false,
        false,
        true,
        true,
        true
    );

    $output = html_print_table($table, true);

    return $output;
}
