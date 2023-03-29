<?php
/**
 * View for edit modules in Massive Operations
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive module update'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_categories.php';

$module_type = (int) get_parameter('module_type');
$idGroupMassive = (int) get_parameter('id_group_massive');
$idAgentMassive = (int) get_parameter('id_agent_massive');
$group_select = get_parameter('groups_select');

$module_name = get_parameter('module_name');
$agents_select = get_parameter('agents', []);
$agents_id = get_parameter('id_agents');
$modules_select = get_parameter('module');
$selection_mode = get_parameter('selection_mode', 'modules');
$recursion = get_parameter('recursion');
$modules_selection_mode = get_parameter('modules_selection_mode');

$update = (bool) get_parameter_post('update');

if ($update) {
    $agents_ = '';

    $module_status = get_parameter('status_module');

    if ($selection_mode == 'modules') {
        $agents_ = [];

        $force = get_parameter('force_type', false);

        if ($agents_select == false) {
            $agents_select = [];
        }

        $agents_ = $agents_select;
        $modules_ = $module_name;
    } else if ($selection_mode == 'agents') {
        $force = get_parameter('force_group', false);

        $agents_ = $agents_id;
        $modules_ = $modules_select;
    }

    $success = 0;
    $count = 0;

    if ($agents_ == false) {
        $agents_ = [];
    }

    // If the option to select all of one group or module type is checked.
    if ($force) {
        if ($force === 'type') {
            $type_condition = '';
            if ($module_type != 0) {
                $type_condition = "AND tam.id_tipo_modulo = $module_type";
            }

            $sql = "SELECT ta.id_agente
					FROM tagente ta
					INNER JOIN tagente_modulo tam
						ON ta.id_agente = tam.id_agente
							AND tam.delete_pending = 0
							$type_condition
					GROUP BY ta.id_agente";
            $agents_ = db_get_all_rows_sql($sql);
            if ($agents_ === false) {
                $agents_ = [];
            }

            // Create an array of agent ids.
            $agents_ = extract_column($agents_, 'id_agente');

            foreach ($agents_ as $id_agent) {
                $filter = [
                    'id_agente'      => $id_agent,
                    'delete_pending' => 0,
                ];
                if ($module_type != 0) {
                    $filter['id_tipo_modulo'] = $module_type;
                }

                $module_name = db_get_all_rows_filter('tagente_modulo', $filter, 'nombre');
                if ($module_name === false) {
                    $module_name = [];
                }

                foreach ($module_name as $mod_name) {
                    $result = process_manage_edit($mod_name['nombre'], $id_agent, $module_status, $modules_selection_mode);
                    $count++;
                    $success += (int) $result;
                }
            }

            if ($success == 0) {
                $error_msg = __('Error updating the modules from a module type');
            }
        } else if ($force === 'group') {
            $agents_ = array_keys(agents_get_group_agents($group_select, false, 'none'));

            foreach ($agents_ as $id_agent) {
                $filter = [
                    'id_agente'      => $id_agent,
                    'delete_pending' => 0,
                ];
                $module_name = db_get_all_rows_filter('tagente_modulo', $filter, 'nombre');
                if ($module_name === false) {
                    $module_name = [];
                }

                foreach ($module_name as $mod_name) {
                    $result = process_manage_edit($mod_name['nombre'], $id_agent, $module_status, $modules_selection_mode);
                    $count++;
                    $success += (int) $result;
                }
            }

            if ($success == 0) {
                $error_msg = __('Error updating the modules from an agent group');
            }
        }
    } else {
        // Standard procedure.
        foreach ($agents_ as $agent_) {
            if ($modules_ == false) {
                $modules_ = [];
            }

            foreach ($modules_ as $module_) {
                $result = process_manage_edit($module_, $agent_, $module_status, $modules_selection_mode);
                $count++;
                $success += (int) $result;
            }
        }

        if ($success == 0) {
            $error_msg = __('Error updating the modules (maybe there was no field to update)');
        }
    }

    ui_print_result_message(
        $success > 0,
        __('Successfully updated').'('.$success.'/'.$count.')',
        $error_msg
    );

    $info = '{"Modules":"'.implode(',', $modules_).'","Agents":"'.implode(',', $agents_).'"}';
    if ($success > 0) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Edit module',
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail try to edit module',
            false,
            false,
            $info
        );
    }
}

$table = new stdClass();
$table->id = 'delete_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold';
$table->rowstyle = [];
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';
if (! $module_type) {
    $table->rowstyle['edit1'] = 'display: none';
    $table->rowstyle['edit0'] = 'display: none';
    $table->rowstyle['edit1_1'] = 'display: none';
    $table->rowstyle['edit2'] = 'display: none';
    $table->rowstyle['edit3'] = 'display: none';
    $table->rowstyle['edit35'] = 'display: none';
    $table->rowstyle['edit4'] = 'display: none';
    $table->rowstyle['edit5'] = 'display: none';
    $table->rowstyle['edit6'] = 'display: none';
    $table->rowstyle['edit7'] = 'display: none';
}

$agents = agents_get_group_agents(
    array_keys(users_get_groups()),
    false,
    'none'
);
switch ($config['dbtype']) {
    case 'mysql':
        $module_types = db_get_all_rows_filter(
            'tagente_modulo,ttipo_modulo',
            ['tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
                'id_agente' => array_keys($agents),
                'disabled' => 0,
                'order' => 'ttipo_modulo.nombre'
            ],
            [
                'DISTINCT(id_tipo)',
                'CONCAT(ttipo_modulo.descripcion," (",ttipo_modulo.nombre,")") AS description',
            ]
        );
    break;

    case 'oracle':
        $module_types = db_get_all_rows_filter(
            'tagente_modulo,ttipo_modulo',
            ['tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
                'id_agente' => array_keys($agents),
                'disabled' => 0,
                'order' => 'ttipo_modulo.nombre'
            ],
            [
                'id_tipo',
                'ttipo_modulo.descripcion || \' (\' || ttipo_modulo.nombre || \')\' AS description',
            ]
        );
    break;

    case 'postgresql':
        $module_types = db_get_all_rows_filter(
            'tagente_modulo,ttipo_modulo',
            ['tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
                'id_agente' => array_keys($agents),
                'disabled' => 0,
                'order' => 'description'
            ],
            [
                'DISTINCT(id_tipo)',
                'ttipo_modulo.descripcion || \' (\' || ttipo_modulo.nombre || \')\' AS description',
            ]
        );
    break;
}

if ($module_types === false) {
    $module_types = [];
}

$types = [];
foreach ($module_types as $type) {
    $types[$type['id_tipo']] = $type['description'];
}

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$table->width = '100%';
$table->data = [];

$table->data['selection_mode'][0] = __('Selection mode');
$table->data['selection_mode'][1] = '<span class="massive_span">'.__('Select modules first ').'</span>'.html_print_radio_button_extended('selection_mode', 'modules', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true).'<br>';
$table->data['selection_mode'][1] .= '<span class="massive_span">'.__('Select agents first ').'</span>'.html_print_radio_button_extended('selection_mode', 'agents', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true);

$table->rowclass['form_modules_1'] = 'select_modules_row';
$table->data['form_modules_1'][0] = __('Module type');

$types[0] = __('All');
$table->colspan['form_modules_1'][1] = 2;
$table->data['form_modules_1'][1] = html_print_select(
    $types,
    'module_type',
    '',
    false,
    __('Select'),
    -1,
    true,
    false,
    true
);

$table->data['form_modules_1'][3] = __('Select all modules of this type').' '.html_print_checkbox_extended(
    'force_type',
    'type',
    '',
    '',
    false,
    'class="mrgn_right_40px"',
    true,
    ''
);

$modules = [];
if ($module_type != '') {
    $filter = ['id_tipo_modulo' => $module_type];
} else {
    $filter = false;
}

$names = agents_get_modules(
    array_keys($agents),
    'tagente_modulo.nombre',
    $filter,
    false
);
foreach ($names as $name) {
    $modules[$name['nombre']] = $name['nombre'];
}



$table->rowclass['form_agents_1'] = 'select_agents_row';
$table->data['form_agents_1'][0] = __('Agent group');
$groups = groups_get_all(true);
$groups[0] = __('All');
$table->colspan['form_agents_1'][1] = 2;
$table->data['form_agents_1'][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'groups_select',
    '',
    false,
    '',
    '',
    true
).' '.__('Group recursion').' '.html_print_checkbox('recursion', 1, false, true, false);
$table->data['form_agents_1'][3] = __('Select all modules of this group').' '.html_print_checkbox_extended(
    'force_group',
    'group',
    '',
    '',
    false,
    '',
    'class="mrgn_right_40px"'
);

$table->rowclass['form_modules_3'] = '';
$table->data['form_modules_3'][0] = __('Module Status');
$table->colspan['form_modules_3'][1] = 2;
$status_list = [];
$status_list[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$status_list[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$status_list[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
$table->data['form_modules_3'][1] = html_print_select(
    $status_list,
    'status_module',
    'selected',
    '',
    __('All'),
    AGENT_MODULE_STATUS_ALL,
    true
);
$table->data['form_modules_3'][3] = '';

$tags = tags_get_user_tags();
$table->rowstyle['form_modules_4'] = 'vertical-align: top;';
$table->rowclass['form_modules_4'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_4'][0] = __('Tags');
$table->data['form_modules_4'][1] = html_print_select(
    $tags,
    'tags[]',
    $tags_name,
    false,
    __('Any'),
    -1,
    true,
    true,
    true
);

$table->rowstyle['form_modules_filter'] = 'vertical-align: top;';
$table->rowclass['form_modules_filter'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_filter'][0] = __('Filter Modules');
$table->data['form_modules_filter'][1] = html_print_input_text('filter_modules', '', '', 20, 255, true);

$table->rowstyle['form_modules_2'] = 'vertical-align: top;';
$table->rowclass['form_modules_2'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_2'][0] = __('Modules');
$table->data['form_modules_2'][1] = html_print_select(
    $modules,
    'module_name[]',
    $module_name,
    false,
    __('Select'),
    -1,
    true,
    true,
    true
).' '.__('Select all modules').' '.html_print_checkbox('select_all_modules', 1, false, true, false, '', false, "class='static'");

$table->data['form_modules_2'][2] = __('When select modules');
$table->data['form_modules_2'][2] .= '<br>';
$table->data['form_modules_2'][2] .= html_print_select(
    [
        'common' => __('Show common agents'),
        'all'    => __('Show all agents'),
    ],
    'agents_selection_mode',
    'common',
    false,
    '',
    '',
    true
);

$table->data['form_modules_2'][3] = html_print_select(
    [],
    'agents[]',
    $agents_select,
    false,
    __('None'),
    0,
    true,
    true,
    false
);



$table->rowclass['form_agents_2'] = 'select_agents_row';
$table->data['form_agents_2'][0] = __('Agent Status');
$table->colspan['form_agents_2'][1] = 2;
$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data['form_agents_2'][1] = html_print_select(
    $status_list,
    'status_agents',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);
$table->data['form_agents_2'][3] = '';

$tags = tags_get_user_tags();
$table->rowstyle['form_agents_4'] = 'vertical-align: top;';
$table->rowclass['form_agents_4'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_4'][0] = __('Tags');
$table->data['form_agents_4'][1] = html_print_select(
    $tags,
    'tags[]',
    $tags_name,
    false,
    __('Any'),
    -1,
    true,
    true,
    true
);

$table->rowstyle['form_agents_filter'] = 'vertical-align: top;';
$table->rowclass['form_agents_filter'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_filter'][0] = __('Filter agents');
$table->data['form_agents_filter'][1] = html_print_input_text('filter_agents', '', '', 20, 255, true);

$table->rowstyle['form_agents_3'] = 'vertical-align: top;';
$table->rowclass['form_agents_3'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_3'][0] = __('Agents');
$table->data['form_agents_3'][1] = html_print_select(
    $agents,
    'id_agents[]',
    $agents_id,
    false,
    '',
    '',
    true,
    true,
    false
).' '.__('Select all agents').' '.html_print_checkbox('select_all_agents', 1, false, true, false, '', false, "class='static'");

$table->data['form_agents_3'][2] = __('When select agents');
$table->data['form_agents_3'][2] .= '<br>';
$table->data['form_agents_3'][2] .= html_print_select(
    [
        'common'  => __('Show common modules'),
        'all'     => __('Show all modules'),
        'unknown' => __('Show unknown and not init modules'),
    ],
    'modules_selection_mode',
    'common',
    false,
    '',
    '',
    true
);
$table->data['form_agents_3'][3] = html_print_select(
    [],
    'module[]',
    $modules_select,
    false,
    '',
    '',
    true,
    true,
    false
);


$table->data['edit0'][0] = __('Dynamic Interval');
$table->data['edit0'][1] = html_print_extended_select_for_time(
    'dynamic_interval',
    -2,
    '',
    'None',
    '0',
    10,
    true,
    'width:150px',
    false,
    '',
    false,
    false,
    '',
    true
);
$table->data['edit0'][2] = '<table width="100%"><tr><td><em>'.__('Dynamic Min.').'</em></td>';
$table->data['edit0'][2] .= '<td align="right">'.html_print_input_text('dynamic_min', '', '', 10, 255, true).'</td></tr>';
$table->data['edit0'][2] .= '<tr><td><em>'.__('Dynamic Max.').'</em></td>';
$table->data['edit0'][2] .= '<td align="right">'.html_print_input_text('dynamic_max', '', '', 10, 255, true).'</td></tr></table>';
$table->data['edit0'][3] = __('Dynamic Two Tailed: ');
$table->data['edit0'][3] .= html_print_checkbox('dynamic_two_tailed', 1, '', true);

$table->data['edit1'][0] = __('Warning status');
$table->data['edit1'][1] = '<table width="100%">';
    $table->data['edit1'][1] .= "<tr id='edit1-1-min'>";
        $table->data['edit1'][1] .= '<td>';
            $table->data['edit1'][1] .= '<em>'.__('Min.').'</em>';
        $table->data['edit1'][1] .= '</td>';
        $table->data['edit1'][1] .= '<td align="right">';
            $table->data['edit1'][1] .= html_print_input_text(
                'min_warning',
                '',
                '',
                5,
                255,
                true
            );
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '</tr>';
            $table->data['edit1'][1] .= "<tr id='edit1-1-max'>";
            $table->data['edit1'][1] .= '<td>';
            $table->data['edit1'][1] .= '<em>'.__('Max.').'</em>';
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '<td align="right">';
            $table->data['edit1'][1] .= html_print_input_text(
                'max_warning',
                '',
                '',
                5,
                255,
                true
            );
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '</tr>';
            $table->data['edit1'][1] .= "<tr id='edit1-1-str'>";
            $table->data['edit1'][1] .= '<td>';
            $table->data['edit1'][1] .= '<em>'.__('Str.').'</em>';
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '<td align="right">';
            $table->data['edit1'][1] .= html_print_input_text(
                'str_warning',
                '',
                '',
                5,
                1024,
                true
            );
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '</tr>';
            $table->data['edit1'][1] .= '<tr>';
            $table->data['edit1'][1] .= '<td>';
            $table->data['edit1'][1] .= '<em>'.__('Inverse interval').'</em>';
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '<td align="right">';
            $table->data['edit1'][1] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'warning_inverse',
                '',
                '',
                '',
                '',
                true
            );
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '</tr>';
            $table->data['edit1'][1] .= '<tr>';
            $table->data['edit1'][1] .= '<td>';
            $table->data['edit1'][1] .= '<em>'.__('Percentage').'</em>';
            $table->data['edit1'][1] .= ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true);

            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '<td align="right">';
            $table->data['edit1'][1] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'percentage_warning',
                '',
                '',
                '',
                '',
                true
            );
            $table->data['edit1'][1] .= '</td>';
            $table->data['edit1'][1] .= '</tr>';
            $table->data['edit1'][1] .= '</table>';

            $table->data['edit1'][2] = __('Critical status');
            $table->data['edit1'][3] = '<table width="100%">';
            $table->data['edit1'][3] .= "<tr id='edit1-3-min'>";
            $table->data['edit1'][3] .= '<td>';
            $table->data['edit1'][3] .= '<em>'.__('Min.').'</em>';
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '<td align="right">';
            $table->data['edit1'][3] .= html_print_input_text(
                'min_critical',
                '',
                '',
                5,
                255,
                true
            );
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '</tr>';
            $table->data['edit1'][3] .= "<tr id='edit1-3-max'>";
            $table->data['edit1'][3] .= '<td>';
            $table->data['edit1'][3] .= '<em>'.__('Max.').'</em>';
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '<td align="right">';
            $table->data['edit1'][3] .= html_print_input_text(
                'max_critical',
                '',
                '',
                5,
                255,
                true
            );
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '</tr>';
            $table->data['edit1'][3] .= "<tr id='edit1-3-str'>";
            $table->data['edit1'][3] .= '<td>';
            $table->data['edit1'][3] .= '<em>'.__('Str.').'</em>';
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '<td align="right">';
            $table->data['edit1'][3] .= html_print_input_text(
                'str_critical',
                '',
                '',
                5,
                1024,
                true
            );
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '</tr>';
            $table->data['edit1'][3] .= '<tr>';
            $table->data['edit1'][3] .= '<td>';
            $table->data['edit1'][3] .= '<em>'.__('Inverse interval').'</em>';
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '<td align="right">';
            $table->data['edit1'][3] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'critical_inverse',
                '',
                '',
                '',
                '',
                true
            );
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '</tr>';

            $table->data['edit1'][3] .= '<tr>';
            $table->data['edit1'][3] .= '<td>';
            $table->data['edit1'][3] .= '<em>'.__('Percentage').'</em>';
            $table->data['edit1'][3] .= ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true);
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '<td align="right">';
            $table->data['edit1'][3] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'percentage_critical',
                '',
                '',
                '',
                '',
                true
            );
            $table->data['edit1'][3] .= '</td>';
            $table->data['edit1'][3] .= '</tr>';
            $table->data['edit1'][3] .= '</table>';

            $table->data['edit1_1'][0] = '<b>'.__('Description').'</b>';
            $table->data['edit1_1'][1] = html_print_textarea(
                'descripcion',
                2,
                50,
                '',
                '',
                true
            );
            $table->colspan['edit1_1'][1] = 3;

            $table->data['edit2'][0] = __('Interval');
            $table->data['edit2'][1] = html_print_extended_select_for_time(
                'module_interval',
                0,
                '',
                __('No change'),
                '0',
                10,
                true,
                'width: 150px'
            );
            $table->data['edit2'][2] = __('Disabled');
            $table->data['edit2'][3] = html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'disabled',
                '',
                '',
                '',
                '',
                true
            );

            $table->data['edit3'][0] = __('Post process');

            $table->data['edit3'][1] = html_print_extended_select_for_post_process(
                'post_process',
                -1,
                '',
                '',
                0,
                false,
                true,
                'width:150px;',
                true,
                false,
                1
            );

            $table->data['edit3'][2] = __('SNMP community');
            $table->data['edit3'][3] = html_print_input_text(
                'snmp_community',
                '',
                '',
                10,
                100,
                true
            );

            $table->data['edit15'][2] = __('SNMP OID');
            $table->data['edit15'][3] = html_print_input_text(
                'snmp_oid',
                '',
                '',
                80,
                80,
                true
            );

            $target_ip_values = [];
            $target_ip_values['auto']      = __('Auto');
            $target_ip_values['force_pri'] = __('Force primary key');
            $target_ip_values['custom']    = __('Custom');

            $table->data['edit35'][0] = __('Target IP');
            $table->data['edit35'][1] = html_print_select(
                $target_ip_values,
                'ip_target',
                '',
                '',
                __('No change'),
                '',
                true,
                false,
                false,
                '',
                false,
                'width:200px;'
            );

            $table->data['edit35'][1] .= html_print_input_text('custom_ip_target', '', '', 15, 60, true);

            $table->data['edit35'][2] = __('SNMP version');
            $table->data['edit35'][3] = html_print_select(
                $snmp_versions,
                'snmp_version',
                '',
                '',
                __('No change'),
                '',
                true,
                false,
                false,
                ''
            );
            $table->data['edit36'][0] = __('Auth user');
            $table->data['edit36'][1] = html_print_input_text(
                'plugin_user_snmp',
                '',
                '',
                15,
                60,
                true
            );
            $table->data['edit36'][2] = __('Auth password').ui_print_help_tip(__('The pass length must be eight character minimum.'), true);
            $table->data['edit36'][3] = html_print_input_password('plugin_pass_snmp', '', '', 15, 60, true);
            $table->data['edit37'][0] = __('Privacy method');
            $table->data['edit37'][1] = html_print_select(['DES' => __('DES'), 'AES' => __('AES')], 'snmp3_privacy_method', '', '', __('No change'), '', true);
            $table->data['edit37'][2] = __('Privacy pass').ui_print_help_tip(__('The pass length must be eight character minimum.'), true);
            $table->data['edit37'][3] = html_print_input_password('snmp3_privacy_pass', '', '', 15, 60, true);
            $table->data['edit38'][0] = __('Auth method');
            $table->data['edit38'][1] = html_print_select(['MD5' => __('MD5'), 'SHA' => __('SHA')], 'plugin_parameter', '', '', __('No change'), '', true);
            $table->data['edit38'][2] = __('Security level');
            $table->data['edit38'][3] = html_print_select(
                [
                    'noAuthNoPriv' => __('Not auth and not privacy method'),
                    'authNoPriv'   => __('Auth and not privacy method'),
                    'authPriv'     => __('Auth and privacy method'),
                ],
                'custom_string_3',
                '',
                '',
                __('No change'),
                '',
                true
            );

            $table->data['edit4'][0] = __('Value');
            $table->data['edit4'][1] = '<em>'.__('Min.').'</em>';
            $table->data['edit4'][1] .= html_print_input_text('min', '', '', 5, 15, true);
            $table->data['edit4'][1] .= '<br /><em>'.__('Max.').'</em>';
            $table->data['edit4'][1] .= html_print_input_text('max', '', '', 5, 15, true);
            $table->data['edit4'][2] = __('Module group');
            // Create module groups values for select
            $module_groups = modules_get_modulegroups();
            $module_groups[0] = __('Not assigned');

            $table->data['edit4'][3] = html_print_select(
                $module_groups,
                'id_module_group',
                '',
                '',
                __('No change'),
                '',
                true,
                false,
                false
            );

            $table->data['edit5'][0] = __('Username');
            $table->data['edit5'][1] = html_print_input_text('plugin_user', '', '', 15, 60, true);
            $table->data['edit5'][2] = __('Password');
            $table->data['edit5'][3] = html_print_input_password('plugin_pass', '', '', 15, 60, true);

            // Export target
            $table->data['edit6'][0] = __('Export target');
            $targets2 = db_get_all_rows_sql('SELECT id, name FROM tserver_export ORDER by name');
            if ($targets2 === false) {
                $targets2 = [];
            }

            $targets = [];
            $targets[0] = __('None');
            foreach ($targets2 as $t) {
                 $targets[$t['id']] = $t['name'];
            }

            $table->data['edit6'][1] = html_print_select($targets, 'id_export', '', '', __('No change'), '', true, false, false);
            $table->data['edit6'][2] = __('Unit');
            $table->data['edit6'][3] = html_print_extended_select_for_unit('unit', '-1', '', '', '0', '15', true, false, false, false, 1);


            // FF stands for Flip-flop.
            $table->data['edit7'][0] = __('FF threshold').' ';

            $table->colspan['edit7'][1] = 3;
            $table->data['edit7'][1] = __('Mode').' ';
            $table->data['edit7'][1] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Each state changing'),
                    '0' => __('All state changing'),
                ],
                'each_ff',
                '',
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false,
                'width: 400px;'
            ).'<br />';

            $table->data['edit7'][1] .= __('All state changing').' : ';
            $table->data['edit7'][1] .= html_print_input_text(
                'min_ff_event',
                '',
                '',
                5,
                15,
                true
            ).'<br />';

            $table->data['edit7'][1] .= __('Each state changing').' : ';
            $table->data['edit7'][1] .= __('To normal').' ';
            $table->data['edit7'][1] .= html_print_input_text(
                'min_ff_event_normal',
                '',
                '',
                5,
                15,
                true
            ).' ';

            $table->data['edit7'][1] .= __('To warning').' ';
            $table->data['edit7'][1] .= html_print_input_text(
                'min_ff_event_warning',
                '',
                '',
                5,
                15,
                true
            ).' ';

            $table->data['edit7'][1] .= __('To critical').' ';
            $table->data['edit7'][1] .= html_print_input_text(
                'min_ff_event_critical',
                '',
                '',
                5,
                15,
                true
            ).'<br>';

            $table->data['edit7'][1] .= __('Keep counters').' ';
            $table->data['edit7'][1] .= html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Active Counters'),
                    '0' => __('Inactive Counters'),
                ],
                'ff_type',
                '',
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false,
                'width: 400px;'
            );

            $table->data['edit8'][0] = __('FF interval');
            $table->data['edit8'][1] = html_print_input_text(
                'module_ff_interval',
                '',
                '',
                5,
                10,
                true
            );
            $table->data['edit8'][1] .= ui_print_help_tip(
                __('Module execution flip flop time interval (in secs).'),
                true
            );

            $table->data['edit8'][2] = __('FF timeout');
            $table->data['edit8'][3] = html_print_input_text(
                'ff_timeout',
                '',
                '',
                5,
                10,
                true
            );
            $table->data['edit8'][3] .= ui_print_help_tip(
                __('Timeout in secs from start of flip flop counting. If this value is exceeded, FF counter is reset. Set to 0 for no timeout.'),
                true
            );

            $table->data['edit9'][0] = __('Historical data');
            $table->data['edit9'][1] = html_print_select(['' => __('No change'), '1' => __('Yes'), '0' => __('No')], 'history_data', '', '', '', '', true);

            // Tags avalaible
            $id_tag = [];
            $table->data['edit10'][0] = __('Tags');
            $table->data['edit10'][1] = html_print_select_from_sql(
                'SELECT id_tag, name FROM ttag ORDER BY name',
                'id_tag[]',
                $id_tag,
                '',
                __('None'),
                '0',
                true,
                true,
                false,
                false
            );
            $table->data['edit10'][2] = __('Category');
            $table->data['edit10'][3] = html_print_select(categories_get_all_categories('forselect'), 'id_category', '', '', __('No change'), '', true, false, false);

            if (enterprise_installed()) {
                $table->rowspan['edit10'][0] = $table->rowspan['edit10'][1] = 2;

                $table->data['edit101'][2] = __('Policy linking status').ui_print_help_tip(__('This field only has sense in modules adopted by a policy.'), true);
                $table->data['edit101'][3] = html_print_select([MODULE_PENDING_LINK => __('Linked'), MODULE_PENDING_UNLINK => __('Unlinked')], 'policy_linked', '', '', __('No change'), '', true, false, false);
            }

            if ($table->rowspan['edit10'][0] == 2) {
                $table->rowspan['edit10'][0] = $table->rowspan['edit10'][1] = 3;
            } else {
                $table->rowspan['edit10'][0] = $table->rowspan['edit10'][1] = 2;
            }

            $table->data['edit102'][2] = __('Discard unknown events');

            $table->data['edit102'][3] = html_print_select(
                [
                    ''  => __('No change'),
                    '1' => __('Yes'),
                    '0' => __('No'),
                ],
                'throw_unknown_events',
                '',
                '',
                '',
                '',
                true
            );

            $table->data['edit12'][0] = '<b>'.__('Critical instructions').'</b>'.ui_print_help_tip(__('Instructions when the status is critical'), true);
            $table->data['edit12'][1] = html_print_textarea('critical_instructions', 2, 50, '', '', true);
            $table->colspan['edit12'][1] = 3;

            $table->data['edit13'][0] = '<b>'.__('Warning instructions').'</b>'.ui_print_help_tip(__('Instructions when the status is warning'), true);
            $table->data['edit13'][1] = html_print_textarea('warning_instructions', 2, 50, '', '', true);
            $table->colspan['edit13'][1] = 3;

            $table->data['edit14'][0] = '<b>'.__('Unknown instructions').'</b>'.ui_print_help_tip(__('Instructions when the status is unknown'), true);
            $table->data['edit14'][1] = html_print_textarea('unknown_instructions', 2, 50, '', '', true);
            $table->colspan['edit14'][1] = 3;

            $table->data['edit11'][0] = __('Quiet');
            $table->data['edit11'][0] .= ui_print_help_tip(__('The module still store data but the alerts and events will be stop'), true);
            $table->data['edit11'][1] = html_print_select(
                [
                    -1 => __('No change'),
                    1  => __('Yes'),
                    0  => __('No'),
                ],
                'quiet_select',
                -1,
                '',
                '',
                0,
                true
            );



            $table->data['edit11'][2] = __('Timeout');
            $table->data['edit11'][3] = html_print_input_text(
                'max_timeout',
                '',
                '',
                5,
                10,
                true
            ).' '.ui_print_help_tip(
                __('Seconds that agent will wait for the execution of the module.'),
                true
            );

            $table->data['edit16'][0] = __('Retries');
            $table->data['edit16'][1] = html_print_input_text('max_retries', '', '', 5, 10, true).' '.ui_print_help_tip(
                __('Number of retries that the module will attempt to run.'),
                true
            );

            $table->data['edit22'][0] = __('Web checks');
            ;
            $table->data['edit22'][1] = '<textarea id="textarea_plugin_parameter" name="plugin_parameter_text" cols="65" rows="15"></textarea>';

            $table->data['edit16'][2] = __('Port');
            $table->data['edit16'][3] = html_print_input_text('tcp_port', '', '', 5, 20, true);

            $table->data['edit17'][0] = __('TCP send');
            $table->data['edit17'][1] = html_print_textarea('tcp_send2', 2, 65, '', '', true);

            $table->data['edit17'][2] = __('TCP receive');
            $table->data['edit17'][3] = html_print_textarea('tcp_rcv', 2, 65, '', '', true);

            $table->data['edit18'][0] = __('WMI query');
            $table->data['edit18'][1] = html_print_input_text('wmi_query', '', '', 35, 255, true);

            $table->data['edit18'][2] = __('Key string');
            $table->data['edit18'][3] = html_print_input_text('key_string', '', '', 20, 60, true);

            $table->data['edit19'][0] = __('Field number');
            $table->data['edit19'][1] = html_print_input_text('field_number', '', '', 5, 15, true);

            $table->data['edit20'][0] = __('Plugin').ui_print_help_icon('plugin_macros', true);
            $table->data['edit20'][1] = html_print_select_from_sql(
                'SELECT id, name FROM tplugin ORDER BY name',
                'id_plugin',
                '',
                'changePluginSelect();',
                __('None'),
                0,
                true,
                false,
                false
            );


            // Store the macros in base64 into a hidden control to move between pages
            $table->data['edit21'][0] = html_print_input_hidden('macros', base64_encode($macros), true);

            $table->colspan['edit23'][1] = 3;
            $table->data['edit23'][0] = __('Command');
            $table->data['edit23'][1] = html_print_input_text_extended(
                'tcp_send',
                '',
                'command_text',
                '',
                100,
                10000,
                false,
                '',
                '',
                true
            );

            require_once $config['homedir'].'/include/class/CredentialStore.class.php';
            $array_credential_identifier = CredentialStore::getKeys('CUSTOM');

            $table->data['edit24'][0] = __('Credential identifier');
            $table->data['edit24'][1] = html_print_select(
                $array_credential_identifier,
                'custom_string_1',
                '',
                '',
                __('None'),
                '',
                true,
                false,
                false
            );

            $array_os = [
                ''          => __('No change'),
                'inherited' => __('Inherited'),
                'linux'     => __('Linux'),
                'windows'   => __('Windows'),
            ];

            $table->data['edit24'][2] = __('Target OS');
            $table->data['edit24'][3] = html_print_select(
                $array_os,
                'custom_string_2',
                '',
                '',
                '',
                '',
                true,
                false,
                false,
                ''
            );

            if (empty($id_plugin) === false) {
                $preload = db_get_sql(
                    sprintf(
                        'SELECT description FROM tplugin WHERE id = %s',
                        $id_plugin
                    )
                );
                $preload = io_safe_output($preload);
                $preload = str_replace("\n", '<br>', $preload);
            } else {
                $preload = '';
            }

            $table->data['edit21'][1] = sprintf(
                '<span class="normal" id="plugin_description">%s</span>',
                $preload
            );

            echo '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_modules" id="form_edit">';
            html_print_table($table);

            attachActionButton('update', 'update', $table->width, false, $SelectAction);

            echo '</form>';

            echo '<h3 class="error invisible" id="message"> </h3>';
            // Hack to translate text "none" in PHP to javascript.
            echo '<span id ="none_text" class="invisible">'.__('None').'</span>';
            echo '<span id ="select_agent_first_text" class="invisible">'.__('Please, select an agent first').'</span>';
            // Load JS files.
            ui_require_javascript_file('pandora_modules');
            ui_require_jquery_file('pandora.controls');

            if ($selection_mode == 'modules') {
                $modules_row = '';
                $agents_row = 'none';
            } else {
                $modules_row = 'none';
                $agents_row = '';
            }

            ?>
<script type="text/javascript">
/* <![CDATA[ */
flag_load_plugin_component = false;

$(document).ready (function () {

    $("#checkbox-select_all_modules").change(function() {
        if( $('#checkbox-select_all_modules').prop('checked')) {
            $("#module_name option").prop('selected', 'selected');
            $("#module_name").trigger('change');
        } else {
            $("#module_name option").prop('selected', false);
            $("#module_name").trigger('change');
        }
    });

    $("#module_name").change(function() {
        var options_length = $("#module_name option").length;
        var options_selected_length = $("#module_name option:selected").length;

        if (options_selected_length < options_length) {
            $('#checkbox-select_all_modules').prop("checked", false);
        }
    });

    $("#checkbox-select_all_agents").change(function() {
        if( $('#checkbox-select_all_agents').prop('checked')) {
            $("#id_agents option").prop('selected', 'selected');
            $("#id_agents").trigger('change');
        } else {
            $("#id_agents option").prop('selected', false);
            $("#id_agents").trigger('change');
        }
    });

    $("#id_agents").change(function() {
        var options_length = $("#id_agents option").length;
        var options_selected_length = $("#id_agents option:selected").length;

        if (options_selected_length < options_length) {
            $('#checkbox-select_all_agents').prop("checked", false);
        }
    });

    $("#text-custom_ip_target").hide();
    
    $("#id_agents").change(agent_changed_by_multiple_agents);
    $("#module_name").change(module_changed_by_multiple_modules);
    
    clean_lists();
    
    $(".select_modules_row").css('display', '<?php echo $modules_row; ?>');
    $(".select_agents_row").css('display', '<?php echo $agents_row; ?>');
    $(".select_modules_row_2").css('display', 'none');
    
    // Trigger change to refresh selection when change selection mode
    $("#agents_selection_mode").change (function() {
        $("#module_name").trigger('change');
    });
    $("#modules_selection_mode").change (function() {
        $("#id_agents").trigger('change');
    });
    
    $("#module_type").change (function () {
        $('input[type=checkbox]').attr('checked', false);
        if (this.value < 0) {
            clean_lists();
            $(".select_modules_row_2").css('display', 'none');
            return;
        }
        else {
            $("#module").html('<?php echo __('None'); ?>');
            $("#module_name").html('');
            $('input[type=checkbox]').removeAttr('disabled');
            $(".select_modules_row_2").css('display', '');
        }
        
        $("tr#delete_table-edit1, " +
            "tr#delete_table-edit0, " +
            "tr#delete_table-edit1_1, " +
            "tr#delete_table-edit2, " +
            "tr#delete_table-edit3, " +
            "tr#delete_table-edit35, " +
            "tr#delete_table-edit4, " +
            "tr#delete_table-edit5, " +
            "tr#delete_table-edit6, " +
            "tr#delete_table-edit7, " +
            "tr#delete_table-edit8, " +
            "tr#delete_table-edit9, " +
            "tr#delete_table-edit10, " +
            "tr#delete_table-edit101, " +
            "tr#delete_table-edit102, " +
            "tr#delete_table-edit11, " +
            "tr#delete_table-edit12, " +
            "tr#delete_table-edit13, " +
            "tr#delete_table-edit14, " +
            "tr#delete_table-edit16, " +
            "tr#delete_table-edit17, " +
            "tr#delete_table-edit18, " +
            "tr#delete_table-edit19, " +
            "tr#delete_table-edit20, " +
            "tr#delete_table-edit21, " +
            "tr#delete_table-edit22, " +
            "tr#delete_table-edit23, " +
            "tr#delete_table-edit24, " +
            "tr#delete_table-edit15").hide ();
        
        var params = {
            "page" : "operation/agentes/ver_agente",
            "get_agent_modules_json" : 1,
            "truncate_module_names": 1,
            "get_distinct_name" : 1,
            "indexed" : 0,
            "safe_name" : 1
        };

        if (this.value != '0')
            params['id_tipo_modulo'] = this.value;

        var status_module = $('#status_module').val();
        if (status_module != '-1')
            params['status_module'] = status_module;

        var tags_to_search = $('#tags').val();
        if (tags_to_search != null) {
            if (tags_to_search[0] != -1) {
                params['tags'] = tags_to_search;
            }
        }

        showSpinner();
        $("tr#delete_table-edit1, tr#delete_table-edit0, tr#delete_table-edit2").hide ();
        $("#module_name").attr ("disabled", "disabled")
        $("#module_name option[value!=0]").remove();
        jQuery.post ("ajax.php",
            params,
            function (data, status) {
                jQuery.each (data, function (id, value) {
                    option = $("<option></option>").attr({value: value["nombre"], title: value["nombre"]}).html(value["safe_name"]);
                    $("#module_name").append (option);
                });
                hideSpinner();
                $("#module_name").removeAttr ("disabled");
                //Filter modules. Call the function when the select is fully loaded.
                var textNoData = "<?php echo __('None'); ?>";
                filterByText($('#module_name'), $("#text-filter_modules"), textNoData);
            },
            "json"
        );
    });
    function show_form() {
        $("td#delete_table-0-1, " +
            "td#delete_table-edit1-1, " +
            "td#delete_table-edit2-1").css ("width", "300px");
        $("#form_edit input[type=text]").attr ("value", "");
        $("#form_edit input[type=checkbox]").not ("#checkbox-recursion").removeAttr ("checked");
        $("tr#delete_table-edit1, " +
            "tr#delete_table-edit0, " +
            "tr#delete_table-edit1_1, " +
            "tr#delete_table-edit2, " +
            "tr#delete_table-edit3, " +
            "tr#delete_table-edit35, " +
            "tr#delete_table-edit4, " +
            "tr#delete_table-edit5, " +
            "tr#delete_table-edit6, " +
            "tr#delete_table-edit7, " +
            "tr#delete_table-edit8, " +
            "tr#delete_table-edit9, " +
            "tr#delete_table-edit10, " +
            "tr#delete_table-edit101, " +
            "tr#delete_table-edit102, " +
            "tr#delete_table-edit11, " +
            "tr#delete_table-edit12, " +
            "tr#delete_table-edit13, " +
            "tr#delete_table-edit14, " +
            "tr#delete_table-edit16, " +
            "tr#delete_table-edit17, " +
            "tr#delete_table-edit18, " +
            "tr#delete_table-edit19, " +
            "tr#delete_table-edit20, " +
            "tr#delete_table-edit21, " +
            "tr#delete_table-edit22, " +
            "tr#delete_table-edit23, " +
            "tr#delete_table-edit24, " +
            "tr#delete_table-edit15").show ();
        
        switch($('#module_type').val()) {
        case '3':
            case '23':
            case '33':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max,#delete_table-edit15," +
                    "#delete_table-edit3-2,#delete_table-edit3-3,#delete_table-edit35").hide();
                $("#edit1-1-str,#edit1-3-str").show();
                break;
            case '6':
            case '7':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit15,#delete_table-edit3-2," +
                    "#delete_table-edit3-3,#delete_table-edit35-2,#delete_table-edit35-3," +
                    "#delete_table-edit5").hide();
                break;
            case '8':
            case '9':
            case '11':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit15,#delete_table-edit3-2," +
                    "#delete_table-edit3-3,#delete_table-edit35-2,#delete_table-edit35-3," +
                    "#delete_table-edit5").hide();
                break;
            case '10':
                $("#edit1-1-str,#edit1-3-str").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit15,#delete_table-edit3-2," +
                    "#delete_table-edit3-3,#delete_table-edit35-2,#delete_table-edit35-3," +
                    "#delete_table-edit5").hide();
                break;
            case '15':
            case '16':
            case '18':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit5").hide();
                break;
            case '17':
                $("#edit1-1-str,#edit1-3-str").show();
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max,#delete_table-edit5").hide();
                break;
            case '1':
            case '2':
            case '4':
            case '5':
            case '21':
            case '22':
            case '24':
            case '25':
            case '30':
            case '31':
            case '32':
            case '100':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit15,#delete_table-edit3-2," +
                "#delete_table-edit3-3,#delete_table-edit35").hide();
                break;
            case '34':
            case '35':
            case '36':
            case '37':
                $("#edit1-1-min,#edit1-1-max,#edit1-3-min,#edit1-3-max").show();
                $("#edit1-1-str,#edit1-3-str,#delete_table-edit5").hide();
                break;
        default:
            }
    }
    
    function clean_lists() {
        $("#id_agents").html('<?php echo __('None'); ?>');
        $("#module_name").html('<?php echo __('None'); ?>');
        $("#agents").html('<?php echo __('None'); ?>');
        $("#module").html('<?php echo __('None'); ?>');
        $("tr#delete_table-edit1, "  +
            "tr#delete_table-edit0, " +
            "tr#delete_table-edit1_1, " +
            "tr#delete_table-edit2, " +
            "tr#delete_table-edit3, " +
            "tr#delete_table-edit35, " +
            "tr#delete_table-edit36, " +
            "tr#delete_table-edit37, " +
            "tr#delete_table-edit38, " +
            "tr#delete_table-edit4, " +
            "tr#delete_table-edit5, " +
            "tr#delete_table-edit6, " +
            "tr#delete_table-edit7, " +
            "tr#delete_table-edit8, " +
            "tr#delete_table-edit9, " +
            "tr#delete_table-edit10, " +
            "tr#delete_table-edit101, " +
            "tr#delete_table-edit102, " +
            "tr#delete_table-edit11, " +
            "tr#delete_table-edit12, " +
            "tr#delete_table-edit13, " +
            "tr#delete_table-edit14, " +
            "tr#delete_table-edit16, " +
            "tr#delete_table-edit17, " +
            "tr#delete_table-edit18, " +
            "tr#delete_table-edit19, " +
            "tr#delete_table-edit20, " +
            "tr#delete_table-edit21, " +
            "tr#delete_table-edit22, " +
            "tr#delete_table-edit23, " +
            "tr#delete_table-edit24, " +
            "tr#delete_table-edit15").hide ();
        $('input[type=checkbox]').attr('checked', false);
        $('input[type=checkbox]').attr('disabled', true);
        
        $('#module_type').val(-1);
        $('#groups_select').val(-1);
    }
    
    $('input[type=checkbox]').not(".static").change (
        function () {
            if (this.id == "checkbox-force_type") {
                if (this.checked) {
                    $(".select_modules_row_2").css('display', 'none');
                    $("tr#delete_table-edit1, " +
                        "tr#delete_table-edit0, " +
                        "tr#delete_table-edit1_1, " +
                        "tr#delete_table-edit2, " +
                        "tr#delete_table-edit3, " +
                        "tr#delete_table-edit35, " +
                        "tr#delete_table-edit4, " +
                        "tr#delete_table-edit5, " +
                        "tr#delete_table-edit6, " +
                        "tr#delete_table-edit7, " +
                        "tr#delete_table-edit8, " +
                        "tr#delete_table-edit9, " +
                        "tr#delete_table-edit10").show ();
                }
                else {
                    $(".select_modules_row_2").css('display', '');
                    if ($('#module_name option:selected').val() == undefined) {
                        $("tr#delete_table-edit1, " +
                            "tr#delete_table-edit0, " +
                            "tr#delete_table-edit1_1, " +
                            "tr#delete_table-edit2, " +
                            "tr#delete_table-edit3, " +
                            "tr#delete_table-edit35, " +
                            "tr#delete_table-edit4, " +
                            "tr#delete_table-edit5, " +
                            "tr#delete_table-edit6, " +
                            "tr#delete_table-edit7, " +
                            "tr#delete_table-edit8, " +
                            "tr#delete_table-edit9, " +
                            "tr#delete_table-edit10, " +
                            "tr#delete_table-edit101, " +
                            "tr#delete_table-edit102, " +
                            "tr#delete_table-edit11, " +
                            "tr#delete_table-edit12, " +
                            "tr#delete_table-edit13, " +
                            "tr#delete_table-edit14, " +
                            "tr#delete_table-edit16, " +
                            "tr#delete_table-edit17, " +
                            "tr#delete_table-edit18, " +
                            "tr#delete_table-edit19, " +
                            "tr#delete_table-edit20, " +
                            "tr#delete_table-edit21, " +
                            "tr#delete_table-edit22, " +
                            "tr#delete_table-edit23, " +
                            "tr#delete_table-edit24, " +
                            "tr#delete_table-edit15").hide ();
                    }
                }
            }
            else if (this.id == "checkbox-recursion") {
                $("#checkbox-force_group").prop("checked", false);
                $("#groups_select").trigger("change");
            }
            else if (this.id == "checkbox-warning_inverse") {
                return; //Do none
            }
            else if (this.id == "checkbox-percentage_critical") {
                return; //Do none
            }
            else if (this.id == "checkbox-percentage_warning") {
                return; //Do none
            }
            else if (this.id == "checkbox-critical_inverse") {
                return; //Do none
            }
            else if (this.id == "checkbox-dynamic_two_tailed") {
                return; //Do none
            }
            else {
                if (this.id == "checkbox-force_group") {
                    $("#checkbox-recursion").prop("checked", false);
                }
                
                if (this.checked) {
                    $(".select_agents_row_2").css('display', 'none');
                    $("tr#delete_table-edit1, " +
                        "tr#delete_table-edit0, " +
                        "tr#delete_table-edit1_1, " +
                        "tr#delete_table-edit2, " +
                        "tr#delete_table-edit3, " +
                        "tr#delete_table-edit35, " +
                        "tr#delete_table-edit4, " +
                        "tr#delete_table-edit5, " +
                        "tr#delete_table-edit6, " +
                        "tr#delete_table-edit7, " +
                        "tr#delete_table-edit8, " +
                        "tr#delete_table-edit9, " +
                        "tr#delete_table-edit10, " +
                        "tr#delete_table-edit101, " +
                        "tr#delete_table-edit102, " +
                        "tr#delete_table-edit11, " +
                        "tr#delete_table-edit12, " +
                        "tr#delete_table-edit13, " +
                        "tr#delete_table-edit14, " +
                        "tr#delete_table-edit16, " +
                        "tr#delete_table-edit17, " +
                        "tr#delete_table-edit18, " +
                        "tr#delete_table-edit19, " +
                        "tr#delete_table-edit20, " +
                        "tr#delete_table-edit21, " +
                        "tr#delete_table-edit22, " +
                        "tr#delete_table-edit23, " +
                        "tr#delete_table-edit24, " +
                        "tr#delete_table-edit15").show ();
                }
                else {
                    $(".select_agents_row_2").css('display', '');
                    if ($('#id_agents option:selected').val() == undefined) {
                        $("tr#delete_table-edit1, " +
                            "tr#delete_table-edit0, " +
                            "tr#delete_table-edit1_1, " +
                            "tr#delete_table-edit2, " +
                            "tr#delete_table-edit3, " +
                            "tr#delete_table-edit35, " +
                            "tr#delete_table-edit4, " +
                            "tr#delete_table-edit5, " +
                            "tr#delete_table-edit6, " +
                            "tr#delete_table-edit7, " +
                            "tr#delete_table-edit8, " +
                            "tr#delete_table-edit9, " +
                            "tr#delete_table-edit10, " +
                            "tr#delete_table-edit101, " +
                            "tr#delete_table-edit102, " +
                            "tr#delete_table-edit11, " +
                            "tr#delete_table-edit12, " +
                            "tr#delete_table-edit13, " +
                            "tr#delete_table-edit14, " +
                            "tr#delete_table-edit16, " +
                            "tr#delete_table-edit17, " +
                            "tr#delete_table-edit18, " +
                            "tr#delete_table-edit19, " +
                            "tr#delete_table-edit20, " +
                            "tr#delete_table-edit21, " +
                            "tr#delete_table-edit22, " +
                            "tr#delete_table-edit23, " +
                            "tr#delete_table-edit24, " +
                            "tr#delete_table-edit15").hide ();
                    }
                }
            }
        }
    );
    
    $("#module_name").change (show_form);
    $("#id_agents").change (show_form);
    
    $("#form_edit input[name=selection_mode]").change (function () {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        clean_lists();
        
        if(selector == 'agents') {
            $(".select_modules_row").hide();
            $(".select_agents_row").show();
            $("#groups_select").trigger("change");
        }
        else if(selector == 'modules') {
            $(".select_agents_row").hide();
            $(".select_modules_row").show();
            $("#module_type").trigger("change");
        }
    });
    
    $('#snmp_version').change(function() {
        if($(this).val() == 3) {
            $("tr#delete_table-edit36, tr#delete_table-edit37, tr#delete_table-edit38").show();
        }
        else {
            $("tr#delete_table-edit36, tr#delete_table-edit37, tr#delete_table-edit38").hide();
        }
    });

    $('#ip_target').change(function() {
        if($(this).val() == 'custom') {
            $("#text-custom_ip_target").show();    
        }
        else{
            $("#text-custom_ip_target").hide();    
        }
    });

    var recursion;

    $("#checkbox-recursion").click(function () {
        recursion = this.checked ? 1 : 0;
    });

    $("#groups_select").change (
        function () {
            if (this.value < 0) {
                clean_lists();
                $(".select_agents_row_2").css('display', 'none');
                return;
            }
            else {
                $("#module").html('<?php echo __('None'); ?>');
                $("#id_agents").html('');
                $('input[type=checkbox]').removeAttr('disabled');
                $(".select_agents_row_2").css('display', '');
            }
            
            $("tr#delete_table-edit1, " +
                "tr#delete_table-edit0, " +
                "tr#delete_table-edit1_1, " +
                "tr#delete_table-edit2, " +
                "tr#delete_table-edit3, " +
                "tr#delete_table-edit35, " +
                "tr#delete_table-edit4, " +
                "tr#delete_table-edit5, " +
                "tr#delete_table-edit6, " +
                "tr#delete_table-edit7, " +
                "tr#delete_table-edit8, " +
                "tr#delete_table-edit9, " +
                "tr#delete_table-edit10, " +
                "tr#delete_table-edit101, " +
                "tr#delete_table-edit102, " +
                "tr#delete_table-edit11, " +
                "tr#delete_table-edit12, " +
                "tr#delete_table-edit13, " +
                "tr#delete_table-edit14, " +
                "tr#delete_table-edit16, " +
                "tr#delete_table-edit17, " +
                "tr#delete_table-edit18, " +
                "tr#delete_table-edit19, " +
                "tr#delete_table-edit20, " +
                "tr#delete_table-edit21, " +
                "tr#delete_table-edit22, " +
                "tr#delete_table-edit23, " +
                "tr#delete_table-edit24, " +
                "tr#delete_table-edit15").hide ();
            
            jQuery.post ("ajax.php",
                {"page" : "operation/agentes/ver_agente",
                    "get_agents_group_json" : 1,
                    "recursion" : recursion,
                    "id_group" : this.value,
                    status_agents: function () {
                        return $("#status_agents").val();
                    },
                    // Add a key prefix to avoid auto sorting in js object conversion
                    "keys_prefix" : "_"
                },
                function (data, status) {
                    $("#id_agents").html('');
                    
                    jQuery.each (data, function(id, value) {
                        // Remove keys_prefix from the index
                        id = id.substring(1);
                        
                        option = $("<option></option>")
                            .attr("value", value["id_agente"])
                            .html(value["alias"]);
                        $("#id_agents").append (option);
                    });
                    //Filter agents. Call the function when the select is fully loaded.
                    var textNoData = "<?php echo __('None'); ?>";
                    filterByText($('#id_agents'), $("#text-filter_agents"), textNoData);
                },
                "json"
            );
        }
    );
    
    $("#status_agents").change(function() {
        $("#groups_select").trigger("change");
    });
    
    if("<?php echo $update; ?>"){
        if("<?php echo $selection_mode; ?>" == 'agents'){
            $("#groups_select").trigger("change");
        }    
    }

    $("#status_module").change(function() {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        if(selector == 'agents') {
            $("#id_agents").trigger("change");
        }
        else if(selector == 'modules') {
            $("#module_type").trigger("change");
        }
    });

    $("#tags").change(function() {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        $("#module_type").trigger("change");
    });
    $("#tags1").change(function() {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        $("#id_agents").trigger("change");
    });
    
    $('#agents').change(function(e){
        for(var i=0;i<document.forms["form_edit"].agents.length;i++)    {
            
            if(document.forms["form_edit"].agents[0].selected == true){
                var any = true;
            }
            if(i != 0 && document.forms["form_edit"].agents[i].selected){
                    var others = true;
            }
            if(any && others){
                    document.forms["form_edit"].agents[0].selected = false;
            }    
        }
    });
    
    $('#module').change(function(e){
        for(var i=0;i<document.forms["form_edit"].module.length;i++)    {
            
            if(document.forms["form_edit"].module[0].selected == true){
                var any = true;
            }
            if(i != 0 && document.forms["form_edit"].module[i].selected){
                    var others = true;
            }
            if(any && others){
                    document.forms["form_edit"].module[0].selected = false;
            }    
        }
    });

    $('#warning_inverse').change(function() {
            if($(this).val() == 1) {
                $("#percentage_warning").val('0').change()
            }
        });

        $('#critical_inverse').change(function() {
            if($(this).val() == 1) {
                $("#percentage_critical").val('0').change();
            }
        });

        $('#percentage_warning').change(function() {
            if($(this).val() == 1) {
                $("#warning_inverse").val('0').change()
            }
        });

        $('#percentage_critical').change(function() {
            if($(this).val() == 1) {
                $("#critical_inverse").val('0').change()
            }
        });
        
    
});

function changePluginSelect() {
        if (flag_load_plugin_component) {
            flag_load_plugin_component = false;
            
            return;
        }
        
        load_plugin_description($("#id_plugin").val());
        
        load_plugin_macros_fields_massive('simple-macro');
        
        forced_title_callback();
    }
    
    function load_plugin_macros_fields_massive(row_model_id) {
        // Get plugin macros when selected and load macros fields
        var id_plugin = $('#id_plugin').val();
        
        var params = [];
        params.push("page=include/ajax/module");
        params.push("get_plugin_macros=1");
        params.push("id_plugin=" + id_plugin);
        
        jQuery.ajax ({
            data: params.join ("&"),
            type: 'POST',
            url: action = get_php_value('absolute_homeurl') + "ajax.php",
            dataType: 'json',
            success: function (data) {
                // Delete all the macro fields
                $('.macro_field').remove();
                
                if (data['array'] != null) {
                    $('#hidden-macros').val(data['base64']);
                    
                    jQuery.each (data['array'], function (i, macro) {
                        if (macro['desc'] != '') {
                            $("#delete_table-edit21").after("<tr class='macro_field' id='delete_table-edit"+(80+parseInt(i))+"'><td class='bolder'>"+macro['desc']+"<input type='hidden' name='desc"+macro['macro']+"' value='"+macro['desc']+"'></td><td><input type='text' name='"+macro['macro']+"'></td></tr>");
                        }
                    });
                    //Plugin text can be larger
                    $(".macro_field").find(":input").attr("maxlength", 1023);
                    // Add again the hover event to the 'force_callback' elements
                    forced_title_callback();
                }
            }
        });
    }

function disabled_status () {
    if($('#dynamic_interval_select').val() != 0){
        $('#text-min_warning').prop('readonly', true);
        $('#text-min_warning').addClass('readonly');
        $('#text-max_warning').prop('readonly', true);
        $('#text-max_warning').addClass('readonly');
        $('#text-min_critical').prop('readonly', true);
        $('#text-min_critical').addClass('readonly');
        $('#text-max_critical').prop('readonly', true);
        $('#text-max_critical').addClass('readonly');
    } else {
        $('#text-min_warning').prop('readonly', false);
        $('#text-min_warning').removeClass('readonly');
        $('#text-max_warning').prop('readonly', false);
        $('#text-max_warning').removeClass('readonly');
        $('#text-min_critical').prop('readonly', false);
        $('#text-min_critical').removeClass('readonly');
        $('#text-max_critical').prop('readonly', false);
        $('#text-max_critical').removeClass('readonly');
    }
}
/* ]]> */
</script>
<?php
function process_manage_edit($module_name, $agents_select=null, $module_status='-1', $selection_mode='all')
{
    if (is_int($module_name) && $module_name < 0) {
        ui_print_error_message(__('No modules selected'));

        return false;
    }

    if (!is_array($agents_select)) {
        $agents_select = [$agents_select];
    }

    // List of fields which can be updated.
    $fields = [
        'dynamic_interval',
        'dynamic_max',
        'dynamic_min',
        'dynamic_two_tailed',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'min_ff_event',
        'module_interval',
        'disabled',
        'post_process',
        'unit_select',
        'snmp_community',
        'snmp_oid',
        'tcp_send',
        'custom_string_1',
        'plugin_parameter',
        'custom_string_2',
        'custom_string_3',
        'min',
        'max',
        'id_module_group',
        'plugin_user',
        'plugin_pass',
        'id_export',
        'history_data',
        'critical_inverse',
        'warning_inverse',
        'percentage_warning',
        'percentage_critical',
        'critical_instructions',
        'warning_instructions',
        'unknown_instructions',
        'policy_linked',
        'id_category',
        'disabled_types_event',
        'ip_target',
        'custom_ip_target',
        'descripcion',
        'min_ff_event_normal',
        'min_ff_event_warning',
        'min_ff_event_critical',
        'ff_type',
        'each_ff',
        'module_ff_interval',
        'ff_timeout',
        'max_timeout',
        'tcp_port',
        'max_retries',
        'tcp_rcv',
        'id_plugin',
        'wmi_query',
        'key_string',
        'field_number',
        'tcp_send2',
        'plugin_parameter_text',
        'command_text',
        'command_credential_identifier',
        'command_os',
        'snmp_version',
    ];
    $values = [];

    foreach ($fields as $field) {
        $value = get_parameter($field, '');

        switch ($field) {
            case 'id_plugin':
                if ($value != 0) {
                    for ($i = 0; $i <= 15; $i++) {
                        $value_field = get_parameter('_field'.$i.'_', '');
                        $value_field_desc = get_parameter('desc_field'.$i.'_', '');
                        if ($value_field_desc != '') {
                            $values['macros'][$i] = [
                                'macro' => '_field'.$i.'_',
                                'desc'  => io_safe_input($value_field_desc),
                                'help'  => io_safe_input($value_field_desc),
                                'value' => $value_field,
                            ];
                        }
                    }

                    $values['macros'] = json_encode($values['macros']);
                    $values[$field] = $value;
                }
            break;

            case 'module_interval':
                if ($value != 0) {
                    $values[$field] = $value;
                }
            break;

            case 'dynamic_interval':
                if ($value !== '-2') {
                    $values[$field] = $value;
                }
            break;

            case 'plugin_pass':
                if ($value != '') {
                    $values['plugin_pass'] = io_input_password($value);
                }
            break;

            case 'post_process':
                if ($value !== '-1') {
                    $values['post_process'] = $value;
                }
            break;

            case 'unit_select':
                if ($value != -1) {
                    $values['unit'] = (string) get_parameter('unit');
                }
            break;

            case 'wmi_query':
                if ($value != '') {
                    $values['snmp_oid'] = $value;
                }
            break;

            case 'key_string':
                if ($value != '') {
                    $values['snmp_community'] = $value;
                }
            break;

            case 'field_number':
                if ($value != '') {
                    $values['tcp_port'] = $value;
                }
            break;

            case 'tcp_send2':
                $tcp_send2 = $value;
            break;

            case 'plugin_parameter_text':
                if ($value != '') {
                    $values['plugin_parameter'] = $value;
                }
            break;

            case 'snmp_version':
                $snmp_version = $value;
            break;

            default:
                if ($value != '') {
                    $values[$field] = $value;
                }
            break;
        }
    }

    // Specific snmp reused fields
    if (get_parameter('snmp_version', '') == 3) {
        $plugin_user_snmp = get_parameter('plugin_user_snmp', '');
        if ($plugin_user_snmp != '') {
            $values['plugin_user'] = $plugin_user_snmp;
        }

        $plugin_pass_snmp = get_parameter('plugin_pass_snmp', '');
        if ($plugin_pass_snmp != '') {
            $values['plugin_pass'] = io_input_password($plugin_pass_snmp);
        }

        $snmp3_privacy_method = get_parameter('snmp3_privacy_method', '');
        if ($snmp3_privacy_method != '') {
            $values['custom_string_1'] = io_input_password($snmp3_privacy_method);
        }

        $snmp3_privacy_pass = get_parameter('snmp3_privacy_pass', '');
        if ($snmp3_privacy_pass != '') {
            $values['custom_string_2'] = io_input_password($snmp3_privacy_pass);
        }
    }

    $throw_unknown_events = get_parameter('throw_unknown_events', '');
    if ($throw_unknown_events !== '') {
        // Set the event type that can show.
        $disabled_types_event = [
            EVENTS_GOING_UNKNOWN => (int) $throw_unknown_events,
        ];
        $values['disabled_types_event'] = json_encode($disabled_types_event);
    }

    if (strlen(get_parameter('history_data')) > 0) {
        $values['history_data'] = get_parameter('history_data');
    }

    if (get_parameter('quiet_select', -1) != -1) {
        $values['quiet'] = get_parameter('quiet_select');
    }

    // Whether to update module tag info.
    $update_tags = get_parameter('id_tag', false);

    if (array_search(0, $agents_select) !== false) {
        if (is_numeric($module_name) === false || ($module_name !== 0)) {
            $filterModules = sprintf('AND tam.nombre = \'%s\'', $module_name);
        } else {
            $filterModules = '';
        }

        // Apply at All agents (within valid groups).
        $modules = db_get_all_rows_sql(
            sprintf(
                'SELECT tam.id_agente_modulo, tam.id_tipo_modulo,tam.macros, tam.id_plugin
                FROM tagente_modulo tam INNER JOIN tagente ta
                ON ta.id_agente = tam.id_agente
                WHERE ta.id_grupo IN (%s) %s;',
                implode(',', array_keys(users_get_groups())),
                $filterModules
            )
        );
    } else {
        if ($module_name === '0') {
            // Any module.
            $modules = db_get_all_rows_filter(
                'tagente_modulo',
                ['id_agente' => $agents_select],
                [
                    'id_agente_modulo',
                    'id_tipo_modulo',
                    'macros',
                    'id_plugin',
                ]
            );
        } else {
            $modules = db_get_all_rows_filter(
                'tagente_modulo',
                [
                    'id_agente' => $agents_select,
                    'nombre'    => $module_name,
                ],
                [
                    'id_agente_modulo',
                    'id_tipo_modulo',
                    'macros',
                    'id_plugin',
                ]
            );
        }
    }

    if ($modules === false) {
        return false;
    }

    if (($module_status === 'unknown') && ($module_name == '0')) {
        $modules_to_delete = [];
        foreach ($modules as $mod_id) {
            $mod_status = (int) db_get_value_filter('estado', 'tagente_estado', ['id_agente_modulo' => $mod_id]);

            // Unknown, not init and no data modules.
            if ($mod_status == 3 || $mod_status == 4 || $mod_status == 5) {
                $modules_to_delete[$mod_id] = $mod_id;
            }
        }

        $modules = $modules_to_delete;
    }

    foreach ($modules as $module) {
        if ($module_status !== '-1') {
            if (modules_is_not_init($module['id_agente_modulo']) === true) {
                if ($module_status != AGENT_MODULE_STATUS_NO_DATA && $module_status != AGENT_MODULE_STATUS_NOT_INIT) {
                    continue;
                }
            } else {
                $status = modules_get_agentmodule_status($module['id_agente_modulo']);

                if ($module_status !== $status) {
                    continue;
                }
            }
        }

        // Set tcp_send value according to module type since the purpose of this field in database varies in case of SNMP modules.
        if ($module['id_tipo_modulo'] == MODULE_TYPE_REMOTE_SNMP
            || $module['id_tipo_modulo'] == MODULE_TYPE_REMOTE_SNMP_INC
            || $module['id_tipo_modulo'] == MODULE_TYPE_REMOTE_SNMP_STRING
            || $module['id_tipo_modulo'] <= MODULE_TYPE_REMOTE_SNMP_PROC
        ) {
            if ($snmp_version != '') {
                $values['tcp_send'] = $snmp_version;
            } else {
                unset($values['tcp_send']);
            }
        } else {
            if ($tcp_send2 != '') {
                $values['tcp_send'] = $tcp_send2;
            } else {
                unset($values['tcp_send']);
            }
        }

        if ($module['macros'] && $module['id_plugin'] == $values['id_plugin']) {
            $module_macros = json_decode($module['macros'], true);
            $values_macros = json_decode($values['macros'], true);

            foreach ($values_macros as $k => $value_macro) {
                foreach ($module_macros as $s => $module_macro) {
                    if ($value_macro['macro'] == $module_macro['macro'] && $value_macro['value'] !== '') {
                        $module_macros[$s]['value'] = $value_macro['value'];
                        $module_macros[$s]['desc'] = $value_macro['desc'];
                        $module_macros[$s]['help'] = $value_macro['help'];
                    }
                }
            }

            $values['macros'] = json_encode($module_macros);
        }

        $result = modules_update_agent_module(
            $module['id_agente_modulo'],
            $values,
            true,
            $update_tags
        );

        if (is_error($result)) {
            return false;
        }
    }

    return true;
}
