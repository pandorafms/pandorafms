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
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
            if (empty($modules_) === true) {
                $modules_ = [];
            }

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
                    'id_agente'        => $id_agent,
                    'delete_pending'   => 0,
                    'id_policy_module' => 0,
                    'policy_linked'    => 0,
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
                    $modules_[] = $mod_name['nombre'];
                    $success += (int) $result;
                }
            }

            if ($success == 0) {
                $error_msg = __('Error updating the modules from a module type');
            }
        } else if ($force === 'group') {
            if (empty($modules_) === true) {
                $modules_ = [];
            }

            $agents_ = array_keys(agents_get_group_agents($group_select, false, 'none'));

            foreach ($agents_ as $id_agent) {
                $filter = [
                    'id_agente'        => $id_agent,
                    'delete_pending'   => 0,
                    'id_policy_module' => 0,
                    'policy_linked'    => 0,
                ];
                $module_name = db_get_all_rows_filter('tagente_modulo', $filter, 'nombre');
                if ($module_name === false) {
                    $module_name = [];
                }

                foreach ($module_name as $mod_name) {
                    $result = process_manage_edit($mod_name['nombre'], $id_agent, $module_status, $modules_selection_mode);
                    $count++;
                    $modules_[] = $mod_name['nombre'];
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
    if (empty($modules_) === true || is_array($modules_) === false) {
        $modules_ = [];
    }

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
$table->class = 'databox filters filter-table-adv';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->rowstyle = [];
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

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

$table->data[0][0] = html_print_label_input_block(
    __('Selection mode'),
    '<div class="flex"><span class="massive_span">'.__('Select modules first ').'</span>'.html_print_radio_button_extended('selection_mode', 'modules', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true).'<br><span class="massive_span">'.__('Select agents first ').'</span>'.html_print_radio_button_extended('selection_mode', 'agents', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true).'</div>'
);

$table->rowclass[1] = 'select_modules_row';
$types[0] = __('All');
$table->data[1][0] = html_print_label_input_block(
    __('Module type'),
    html_print_select(
        $types,
        'module_type',
        '',
        false,
        __('Select'),
        -1,
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);
$table->data[1][1] = html_print_label_input_block(
    __('Select all modules of this type'),
    html_print_checkbox_extended(
        'force_type',
        'type',
        '',
        '',
        false,
        '',
        true
    )
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



$table->rowclass[2] = 'select_agents_row';
$groups = groups_get_all(true);
$groups[0] = __('All');
$table->data[2][0] = html_print_label_input_block(
    __('Agent group'),
    html_print_select_groups(
        false,
        'AW',
        true,
        'groups_select',
        '',
        false,
        '',
        '',
        true,
        false,
        false,
        'w100p',
        false,
        'width:100%'
    ).' '.__('Group recursion').' '.html_print_checkbox('recursion', 1, false, true, false)
);
$table->data[2][1] = html_print_label_input_block(
    __('Select all modules of this group'),
    html_print_checkbox_extended(
        'force_group',
        'group',
        '',
        '',
        false,
        '',
        true
    )
);

$table->rowclass[3] = '';
$status_list = [];
$table->colspan[3][0] = 2;
$status_list[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$status_list[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$status_list[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
$table->data[3][0] = html_print_label_input_block(
    __('Module Status'),
    html_print_select(
        $status_list,
        'status_module',
        'selected',
        '',
        __('All'),
        AGENT_MODULE_STATUS_ALL,
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$tags = tags_get_user_tags();
$table->rowstyle[4] = 'vertical-align: top;';
$table->rowclass[4] = 'select_modules_row select_modules_row_2';
$table->colspan[4][0] = 2;
$table->data[4][0] = html_print_label_input_block(
    __('Tags'),
    html_print_select(
        $tags,
        'tags[]',
        $tags_name,
        false,
        __('Any'),
        -1,
        true,
        true,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->rowstyle[5] = 'vertical-align: top;';
$table->rowclass[5] = 'select_modules_row select_modules_row_2';
$table->data[5][0] = html_print_label_input_block(
    __('Filter Modules'),
    html_print_input_text('filter_modules', '', '', false, 255, true)
);

$table->data[5][1] = html_print_label_input_block(
    __('When select modules'),
    html_print_select(
        [
            'common' => __('Show common agents'),
            'all'    => __('Show all agents'),
        ],
        'agents_selection_mode',
        'common',
        false,
        '',
        '',
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->rowclass[6] = 'select_modules_row select_modules_row_2';
$table->data[6][0] = html_print_label_input_block(
    __('Modules'),
    html_print_select(
        $modules,
        'module_name[]',
        $module_name,
        false,
        __('Select'),
        -1,
        true,
        true,
        true,
        'w100p',
        false,
        'width:100%'
    ).' '.__('Select all modules').' '.html_print_checkbox('select_all_modules', 1, false, true, false, '', false, "class='static'")
);

$table->data[6][1] = html_print_label_input_block(
    __('Agent'),
    html_print_select(
        [],
        'agents[]',
        $agents_select,
        false,
        __('None'),
        0,
        true,
        true,
        false,
        'w100p',
        false,
        'width:100%'
    )
);



$table->rowclass[7] = 'select_agents_row';
$status_list = [];
$table->colspan[7][0] = 2;
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[7][0] = html_print_label_input_block(
    __('Agent Status'),
    html_print_select(
        $status_list,
        'status_agents',
        'selected',
        '',
        __('All'),
        AGENT_STATUS_ALL,
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$tags = tags_get_user_tags();
$table->colspan[8][0] = 2;
$table->rowstyle[8] = 'vertical-align: top;';
$table->rowclass[8] = 'select_agents_row select_agents_row_2';
$table->data[8][0] = html_print_label_input_block(
    __('Tags'),
    html_print_select(
        $tags,
        'tags[]',
        $tags_name,
        false,
        __('Any'),
        -1,
        true,
        true,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->rowstyle[9] = 'vertical-align: top;';
$table->rowclass[9] = 'select_agents_row select_agents_row_2';
$table->data[9][0] = html_print_label_input_block(
    __('Filter agents'),
    html_print_input_text('filter_agents', '', '', false, 255, true)
);

$table->data[9][1] = html_print_label_input_block(
    __('When select agents'),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->rowstyle[10] = 'vertical-align: top;';
$table->rowclass[10] = 'select_agents_row select_agents_row_2';
$table->data[10][0] = html_print_label_input_block(
    __('Agents'),
    html_print_select(
        $agents,
        'id_agents[]',
        $agents_id,
        false,
        '',
        '',
        true,
        true,
        false,
        'w100p',
        false,
        'width:100%'
    ).' '.__('Select all agents').' '.html_print_checkbox('select_all_agents', 1, false, true, false, '', false, "class='static'")
);

$table->data[10][1] = html_print_label_input_block(
    __('Modules'),
    html_print_select(
        [],
        'module[]',
        $modules_select,
        false,
        '',
        '',
        true,
        true,
        false,
        'w100p',
        false,
        'width:100%'
    )
);


$table->data[11][0] = html_print_label_input_block(
    __('Dynamic Interval'),
    html_print_extended_select_for_time(
        'dynamic_interval',
        -2,
        '',
        'None',
        '0',
        10,
        true,
        'width:100%',
        false,
        '',
        false,
        false,
        '',
        true
    )
);
$table->data[11][1] = html_print_label_input_block(
    __('Dynamic  Two Tailed: '),
    html_print_checkbox('dynamic_two_tailed', 1, '', true)
);


$table->data[12][0] = html_print_label_input_block(
    __('Dynamic Min.'),
    html_print_input_text('dynamic_min', '', '', false, 255, true)
);
$table->data[12][1] = html_print_label_input_block(
    __('Dynamic Max.'),
    html_print_input_text('dynamic_max', '', '', false, 255, true)
);

$table_warning = new stdClass();
$table_warning->class = 'filters filter-table-adv';
$table_warning->width = '100%';
$table_warning->size[0] = '33%';
$table_warning->size[1] = '33%';
$table_warning->size[2] = '33%';
$table_warning->tdid[0][0] = 'edit1-1-min';
$table_warning->data[0][0] = html_print_label_input_block(
    __('Min.'),
    html_print_input_text(
        'min_warning',
        '',
        '',
        false,
        255,
        true
    )
);

$table_warning->tdid[0][1] = 'edit1-1-max';
$table_warning->data[0][1] = html_print_label_input_block(
    __('Max.'),
    html_print_input_text(
        'max_warning',
        '',
        '',
        false,
        255,
        true
    )
);

$table_warning->tdid[0][2] = 'edit1-1-str';
$table_warning->data[0][2] = html_print_label_input_block(
    __('Str.'),
    html_print_input_text(
        'str_warning',
        '',
        '',
        false,
        1024,
        true
    )
);

$table_warning->data[1][0] = html_print_label_input_block(
    __('Inverse interval'),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table_warning->data[1][1] = html_print_label_input_block(
    __('Percentage').ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table_warning->data[1][2] = html_print_label_input_block(
    __('Change to critical status after.'),
    html_print_input_text(
        'warning_time',
        '',
        '',
        false,
        15,
        true
    )
);

$table->colspan[13][0] = 2;
$table->data[13][0] = html_print_label_input_block(__('Warning status'), html_print_table($table_warning, true));



$table_critical = new stdClass();
$table_critical->class = 'filters filter-table-adv';
$table_critical->width = '100%';
$table_critical->size[0] = '33%';
$table_critical->size[1] = '33%';
$table_critical->size[2] = '33%';
$table_critical->tdid[0][0] = 'edit1-3-min';
$table_critical->data[0][0] = html_print_label_input_block(
    __('Min.'),
    html_print_input_text(
        'min_critical',
        '',
        '',
        false,
        255,
        true
    )
);

$table_critical->tdid[0][1] = 'edit1-3-max';
$table_critical->data[0][1] = html_print_label_input_block(
    __('Max.'),
    html_print_input_text(
        'max_critical',
        '',
        '',
        false,
        255,
        true
    )
);

$table_critical->tdid[0][2] = 'edit1-3-str';
$table_critical->data[0][2] = html_print_label_input_block(
    __('Str.'),
    html_print_input_text(
        'str_warning',
        '',
        '',
        false,
        1024,
        true
    )
);

$table_critical->data[1][0] = html_print_label_input_block(
    __('Inverse interval'),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table_critical->data[1][1] = html_print_label_input_block(
    __('Percentage').ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->colspan[14][0] = 2;
$table->data[14][0] = html_print_label_input_block(__('Critical status'), html_print_table($table_critical, true));

$table->colspan[15][0] = 2;
$table->data[15][0] = html_print_label_input_block(
    __('Description'),
    html_print_textarea(
        'descripcion',
        2,
        50,
        '',
        '',
        true
    )
);

$table->data[16][0] = html_print_label_input_block(
    __('Interval'),
    html_print_extended_select_for_time(
        'module_interval',
        0,
        '',
        __('No change'),
        '0',
        10,
        true,
        'width:100%'
    )
);

$table->data[16][1] = html_print_label_input_block(
    __('Disabled'),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table->data[17][0] = html_print_label_input_block(
    __('Post process'),
    html_print_extended_select_for_post_process(
        'post_process',
        -1,
        '',
        '',
        0,
        false,
        true,
        'width:95%',
        true,
        false,
        1
    )
);

$table->data['made_enabled'][1] = html_print_label_input_block(
    __('MADE enabled').ui_print_help_tip(
        __('By activating this option, the module data will be processed by the MADE engine (if active), and events will be generated automatically by the IA engine'),
        true
    ),
    html_print_checkbox_switch(
        'made_enabled',
        1,
        false,
        true,
        false,
        '',
        false,
        'wp100 static'
    )
);

$table->data[17][2] = html_print_label_input_block(
    __('SNMP community'),
    html_print_input_text(
        'snmp_community',
        '',
        '',
        false,
        100,
        true
    )
);

$table->colspan[18][0] = 2;
$table->data[18][0] = html_print_label_input_block(
    __('SNMP OID'),
    html_print_input_text(
        'snmp_oid',
        '',
        '',
        false,
        80,
        true
    )
);

$target_ip_values = [];
$target_ip_values['auto']      = __('Auto');
$target_ip_values['force_pri'] = __('Force primary key');
$target_ip_values['custom']    = __('Custom');

$table->data[19][0] = html_print_label_input_block(
    __('Target IP'),
    html_print_select(
        $target_ip_values,
        'ip_target',
        '',
        '',
        __('No change'),
        '',
        true,
        false,
        false,
        'w100p',
        false,
        'width:100%'
    ).html_print_input_text('custom_ip_target', '', '', false, 60, true)
);

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$table->data[19][1] = html_print_label_input_block(
    __('SNMP version'),
    html_print_select(
        $snmp_versions,
        'snmp_version',
        '',
        '',
        __('No change'),
        '',
        true,
        false,
        false,
        'w100p',
        false,
        'width:100%'
    )
);

$table->data[20][0] = html_print_label_input_block(
    __('Auth user'),
    html_print_input_text(
        'plugin_user_snmp',
        '',
        '',
        false,
        60,
        true
    )
);

$table->data[20][1] = html_print_label_input_block(
    __('Auth password').ui_print_help_tip(__('The pass length must be eight character minimum.'), true),
    html_print_input_password('plugin_pass_snmp', '', '', 15, 60, true)
);

$table->data[21][0] = html_print_label_input_block(
    __('Privacy method'),
    html_print_select(['DES' => __('DES'), 'AES' => __('AES')], 'snmp3_privacy_method', '', '', __('No change'), '', true, false, true, 'w100p', false, 'width:100%')
);

$table->data[21][1] = html_print_label_input_block(
    __('Privacy pass').ui_print_help_tip(__('The pass length must be eight character minimum.'), true),
    html_print_input_password('snmp3_privacy_pass', '', '', 15, 60, true)
);

$table->data[22][0] = html_print_label_input_block(
    __('Auth method'),
    html_print_select(['MD5' => __('MD5'), 'SHA' => __('SHA')], 'plugin_parameter', '', '', __('No change'), '', true, false, true, 'w100p', false, 'width:100%')
);

$table->data[22][1] = html_print_label_input_block(
    __('Security level'),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);


$table_value = new stdClass();
$table_value->class = 'filters filter-table-adv';
$table_value->width = '100%';
$table_value->size[0] = '50%';
$table_value->size[1] = '50%';

$table_value->data[0][0] = html_print_label_input_block(
    __('Min.'),
    html_print_input_text('min', '', '', false, 15, true)
);

$table_value->data[0][1] = html_print_label_input_block(
    __('Max.'),
    html_print_input_text('max', '', '', false, 15, true)
);

$table->data[23][0] = html_print_label_input_block(
    __('Value'),
    html_print_table($table_value, true)
);


// Create module groups values for select.
$module_groups = modules_get_modulegroups();
$module_groups[0] = __('Not assigned');
$table->data[23][1] = html_print_label_input_block(
    __('Module group'),
    html_print_select(
        $module_groups,
        'id_module_group',
        '',
        '',
        __('No change'),
        '',
        true,
        false,
        false,
        'w100p',
        false,
        'width:100%'
    )
);

$table->data[24][0] = html_print_label_input_block(
    __('Username'),
    html_print_input_text('plugin_user', '', '', false, 60, true)
);

$table->data[24][1] = html_print_label_input_block(
    __('Password'),
    html_print_input_password('plugin_pass', '', '', 15, 60, true)
);


// Export target.
$targets2 = db_get_all_rows_sql('SELECT id, name FROM tserver_export ORDER by name');
if ($targets2 === false) {
    $targets2 = [];
}

$targets = [];
$targets[0] = __('None');
foreach ($targets2 as $t) {
    $targets[$t['id']] = $t['name'];
}

$table->data[25][0] = html_print_label_input_block(
    __('Export target'),
    html_print_select($targets, 'id_export', '', '', __('No change'), '', true, false, false, 'w100p', false, 'width:100%')
);

$table->data[25][1] = html_print_label_input_block(
    __('Unit'),
    html_print_extended_select_for_unit('unit', '-1', '', '', '0', false, true, 'width:100%;', false, false, 1, 'w100p flex')
);

// FF stands for Flip-flop.
$table_threshold = new stdClass();
$table_threshold->class = 'filters filter-table-adv';
$table_threshold->width = '100%';
$table_threshold->size[0] = '33%';
$table_threshold->size[1] = '33%';
$table_threshold->size[2] = '33%';

$table_threshold->data[0][0] = html_print_label_input_block(
    __('Mode'),
    html_print_select(
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
        'w100p',
        false,
        'width:100%'
    )
);

$table_threshold->data[0][1] = html_print_label_input_block(
    __('All state changing'),
    html_print_input_text(
        'min_ff_event',
        '',
        '',
        false,
        15,
        true,
        false,
        false,
        '',
        'w100p'
    )
);

$table_threshold->data[0][2] = html_print_label_input_block(
    __('Keep counters'),
    html_print_select(
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
        'w100p',
        false,
        'width:100%'
    )
);

$table_change_status = new stdClass();
$table_change_status->class = 'filters filter-table-adv';
$table_change_status->width = '100%';
$table_change_status->size[0] = '33%';
$table_change_status->size[1] = '33%';
$table_change_status->size[2] = '33%';


$table_change_status->data[0][0] = html_print_label_input_block(
    __('To normal'),
    html_print_input_text(
        'min_ff_event_normal',
        '',
        '',
        false,
        15,
        true
    )
);

$table_change_status->data[0][1] = html_print_label_input_block(
    __('To warning'),
    html_print_input_text(
        'min_ff_event_warning',
        '',
        '',
        false,
        15,
        true
    )
);

$table_change_status->data[0][2] = html_print_label_input_block(
    __('To critical'),
    html_print_input_text(
        'min_ff_event_critical',
        '',
        '',
        false,
        15,
        true
    )
);

$table_threshold->colspan[1][0] = 3;
$table_threshold->data[1][0] = html_print_label_input_block(
    __('Each state changing'),
    html_print_table($table_change_status, true)
);

$table->colspan[26][0] = 2;
$table->data[26][0] = html_print_label_input_block(
    __('FF threshold'),
    html_print_table($table_threshold, true)
);

$table->data[27][0] = html_print_label_input_block(
    __('FF interval').ui_print_help_tip(
        __('Module execution flip flop time interval (in secs).'),
        true
    ),
    html_print_input_text(
        'module_ff_interval',
        '',
        '',
        false,
        10,
        true
    )
);

$table->data[27][1] = html_print_label_input_block(
    __('FF timeout').ui_print_help_tip(
        __('Timeout in secs from start of flip flop counting. If this value is exceeded, FF counter is reset. Set to 0 for no timeout.'),
        true
    ),
    html_print_input_text(
        'ff_timeout',
        '',
        '',
        false,
        10,
        true
    )
);

$table->data[28][0] = html_print_label_input_block(
    __('Historical data'),
    html_print_select(['' => __('No change'), '1' => __('Yes'), '0' => __('No')], 'history_data', '', '', '', '', true, false, true, 'w100p', false, 'width:100%')
);

$table->data[28][1] = html_print_label_input_block(
    __('Category'),
    html_print_select(categories_get_all_categories('forselect'), 'id_category', '', '', __('No change'), '', true, false, false, 'w100p', false, 'width:100%')
);

// Tags avalaible.
$table->data[29][0] = html_print_label_input_block(
    __('Tags'),
    html_print_select_from_sql(
        'SELECT id_tag, name FROM ttag ORDER BY name',
        'id_tag[]',
        $id_tag,
        '',
        __('None'),
        '0',
        true,
        true,
        false,
        false,
        'width: 100%',
        false,
        'width:100%'
    )
);

$table->data[29][1] = html_print_label_input_block(
    __('Discard unknown events').ui_print_help_tip(__('With this mode, the unknown state will be detected, but it will not generate events.'), true),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

if (enterprise_installed()) {
    $table->data[30][0] = html_print_label_input_block(
        __('Policy linking status').ui_print_help_tip(__('This field only has sense in modules adopted by a policy.'), true),
        html_print_select([MODULE_PENDING_LINK => __('Linked'), MODULE_PENDING_UNLINK => __('Unlinked')], 'policy_linked', '', '', __('No change'), '', true, false, false, 'w100p', false, 'width:100%')
    );
}

$table->data[32][0] = html_print_label_input_block(
    __('Critical instructions').ui_print_help_tip(__('Instructions when the status is critical'), true),
    html_print_textarea('critical_instructions', 2, 50, '', '', true)
);

$table->data[32][1] = html_print_label_input_block(
    __('Warning instructions').ui_print_help_tip(__('Instructions when the status is warning'), true),
    html_print_textarea('warning_instructions', 2, 50, '', '', true)
);

$table->data[33][0] = html_print_label_input_block(
    __('Unknown instructions').ui_print_help_tip(__('Instructions when the status is unknown'), true),
    html_print_textarea('unknown_instructions', 2, 50, '', '', true)
);

$table->data[33][1] = html_print_label_input_block(
    __('Quiet').ui_print_help_tip(__('The module still store data but the alerts and events will be stop'), true),
    html_print_select(
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
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table_timeout = new stdClass();
$table_timeout->class = 'filters filter-table-adv';
$table_timeout->width = '100%';
$table_timeout->size[0] = '33%';
$table_timeout->size[1] = '33%';
$table_timeout->size[2] = '33%';

$table_timeout->data[0][0] = html_print_label_input_block(
    __('Timeout').ui_print_help_tip(
        __('Seconds that agent will wait for the execution of the module.'),
        true
    ),
    html_print_input_text(
        'max_timeout',
        '',
        '',
        false,
        10,
        true
    )
);

$table_timeout->data[0][1] = html_print_label_input_block(
    __('Retries').ui_print_help_tip(__('Number of retries that the module will attempt to run.'), true),
    html_print_input_text('max_retries', '', '', false, 10, true),
);

$table_timeout->data[0][2] = html_print_label_input_block(
    __('Port'),
    html_print_input_text('tcp_port', '', '', false, 20, true)
);

$table->colspan[34][0] = 2;
$table->data[34][0] = html_print_label_input_block(
    '',
    html_print_table($table_timeout, true)
);

$table->colspan[35][0] = 2;
$table->data[35][0] = html_print_label_input_block(
    __('Web checks'),
    '<textarea id="textarea_plugin_parameter" name="plugin_parameter_text" cols="65" rows="15"></textarea>'
);

$table->data[36][0] = html_print_label_input_block(
    __('TCP send'),
    html_print_textarea('tcp_send2', 2, 65, '', '', true)
);

$table->data[36][1] = html_print_label_input_block(
    __('TCP receive'),
    html_print_textarea('tcp_rcv', 2, 65, '', '', true)
);

$table->data[37][0] = html_print_label_input_block(
    __('WMI query'),
    html_print_input_text('wmi_query', '', '', false, 255, true)
);

$table->data[37][1] = html_print_label_input_block(
    __('Key string'),
    html_print_input_text('key_string', '', '', false, 60, true)
);

$table->data[38][0] = html_print_label_input_block(
    __('Field number'),
    html_print_input_text('field_number', '', '', false, 15, true)
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

$table->data[38][1] = html_print_label_input_block(
    __('Plugin').ui_print_help_icon('plugin_macros', true),
    html_print_select_from_sql(
        'SELECT id, name FROM tplugin ORDER BY name',
        'id_plugin',
        '',
        'changePluginSelect();',
        __('None'),
        0,
        true,
        false,
        false,
        false,
        'width:100%'
    ).sprintf(
        '<span class="normal" id="plugin_description">%s</span>',
        $preload
    )
);

// Store the macros in base64 into a hidden control to move between pages.
$table->data[39][0] = html_print_label_input_block(
    __('Command'),
    html_print_input_text_extended(
        'tcp_send',
        '',
        'command_text',
        '',
        false,
        10000,
        false,
        '',
        '',
        true
    ).html_print_input_hidden('macros', base64_encode(($macros ?? '')), true)
);

require_once $config['homedir'].'/include/class/CredentialStore.class.php';
$array_credential_identifier = CredentialStore::getKeys('CUSTOM');

$table->data[39][1] = html_print_label_input_block(
    __('Credential identifier'),
    html_print_select(
        $array_credential_identifier,
        'custom_string_1',
        '',
        '',
        __('None'),
        '',
        true,
        false,
        false,
        'w100p'
    )
);

$array_os = [
    ''          => __('No change'),
    'inherited' => __('Inherited'),
    'linux'     => __('Linux'),
    'windows'   => __('Windows'),
];
$table->data[40][0] = html_print_label_input_block(
    __('Target OS'),
    html_print_select(
        $array_os,
        'custom_string_2',
        '',
        '',
        '',
        '',
        true,
        false,
        false,
        'w100p'
    )
);

$table->data[40][1] = html_print_label_input_block(
    __('Ignore unknown').ui_print_help_tip(_('This disables the module\'s state calculation to unknown, so it will never transition to unknown. The state it reflects is the last known status.'), true),
    html_print_select(
        [
            ''  => __('No change'),
            '1' => __('Yes'),
            '0' => __('No'),
        ],
        'ignore_unknown',
        '',
        '',
        '',
        '',
        true,
        false,
        false,
        'w100p'
    )
);

            echo '<form method="post" class="max_floating_element_size" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_modules" id="form_edit">';
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

        $("tr#delete_table-11, " +
            "tr#delete_table-12, " +
            "tr#delete_table-13, " +
            "tr#delete_table-14, " +
            "tr#delete_table-15, " +
            "tr#delete_table-16, " +
            "tr#delete_table-17, " +
            "tr#delete_table-18, " +
            "tr#delete_table-19, " +
            "tr#delete_table-23, " +
            "tr#delete_table-24, " +
            "tr#delete_table-25, " +
            "tr#delete_table-26, " +
            "tr#delete_table-27, " +
            "tr#delete_table-28, " +
            "tr#delete_table-29, " +
            "tr#delete_table-30, " +
            "tr#delete_table-31, " +
            "tr#delete_table-32, " +
            "tr#delete_table-33, " +
            "tr#delete_table-34, " +
            "tr#delete_table-35, " +
            "tr#delete_table-36, " +
            "tr#delete_table-37, " +
            "tr#delete_table-38, " +
            "tr#delete_table-39, " + 
            "tr#delete_table-made_enabled, " + 
            "tr#delete_table-40").hide();

        var params = {
            "page" : "operation/agentes/ver_agente",
            "get_agent_modules_json" : 1,
            "truncate_module_names": 1,
            "get_distinct_name" : 1,
            "indexed" : 0,
            "safe_name" : 1,
            "exclude_policy_modules" : 1
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
        $("#form_edit input[type=text]").attr ("value", "");
        $("#form_edit input[type=checkbox]").not ("#checkbox-recursion").removeAttr ("checked");
        $("tr#delete_table-11, " +
            "tr#delete_table-12, " +
            "tr#delete_table-13, " +
            "tr#delete_table-14, " +
            "tr#delete_table-15, " +
            "tr#delete_table-16, " +
            "tr#delete_table-17, " +
            "tr#delete_table-18, " +
            "tr#delete_table-19, " +
            "tr#delete_table-23, " +
            "tr#delete_table-24, " +
            "tr#delete_table-25, " +
            "tr#delete_table-26, " +
            "tr#delete_table-27, " +
            "tr#delete_table-28, " +
            "tr#delete_table-29, " +
            "tr#delete_table-30, " +
            "tr#delete_table-31, " +
            "tr#delete_table-32, " +
            "tr#delete_table-33, " +
            "tr#delete_table-34, " +
            "tr#delete_table-35, " +
            "tr#delete_table-36, " +
            "tr#delete_table-37, " +
            "tr#delete_table-38, " +
            "tr#delete_table-39, " + 
            "tr#delete_table-made_enabled, " + 
            "tr#delete_table-40").show ();

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
        $("tr#delete_table-4, " +
            "tr#delete_table-5, " +
            "tr#delete_table-6, " +
            "tr#delete_table-edit6, " +
            "tr#delete_table-7, " +
            "tr#delete_table-8, " +
            "tr#delete_table-9, " +
            "tr#delete_table-10, " +
            "tr#delete_table-11, " +
            "tr#delete_table-12, " +
            "tr#delete_table-13, " +
            "tr#delete_table-14, " +
            "tr#delete_table-15, " +
            "tr#delete_table-16, " +
            "tr#delete_table-17, " +
            "tr#delete_table-18, " +
            "tr#delete_table-19, " +
            "tr#delete_table-20, " +
            "tr#delete_table-21, " +
            "tr#delete_table-22, " +
            "tr#delete_table-23, " +
            "tr#delete_table-24, " +
            "tr#delete_table-25, " +
            "tr#delete_table-26, " +
            "tr#delete_table-27, " +
            "tr#delete_table-28, " +
            "tr#delete_table-29, " +
            "tr#delete_table-30, " +
            "tr#delete_table-31, " +
            "tr#delete_table-32, " +
            "tr#delete_table-33, " +
            "tr#delete_table-34, " +
            "tr#delete_table-35, " +
            "tr#delete_table-36, " +
            "tr#delete_table-37, " +
            "tr#delete_table-38, " +
            "tr#delete_table-39, " + 
            "tr#delete_table-made_enabled, " + 
            "tr#delete_table-40").hide ();
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
                    $("tr#delete_table-11, " +
                        "tr#delete_table-12, " +
                        "tr#delete_table-13, " +
                        "tr#delete_table-14, " +
                        "tr#delete_table-15, " +
                        "tr#delete_table-16, " +
                        "tr#delete_table-17, " +
                        "tr#delete_table-18, " +
                        "tr#delete_table-19, " +
                        "tr#delete_table-23, " +
                        "tr#delete_table-24, " +
                        "tr#delete_table-25, " +
                        "tr#delete_table-26, " +
                        "tr#delete_table-27, " +
                        "tr#delete_table-28, " +
                        "tr#delete_table-29, " +
                        "tr#delete_table-30, " +
                        "tr#delete_table-31, " +
                        "tr#delete_table-32, " +
                        "tr#delete_table-33, " +
                        "tr#delete_table-34, " +
                        "tr#delete_table-35, " +
                        "tr#delete_table-36, " +
                        "tr#delete_table-37, " +
                        "tr#delete_table-38, " +
                        "tr#delete_table-39, " + 
                        "tr#delete_table-made_enabled, " + 
                        "tr#delete_table-40").show();
                }
                else {
                    $(".select_modules_row_2").css('display', '');
                    if ($('#module_name option:selected').val() == undefined) {
                        $("tr#delete_table-11, " +
                            "tr#delete_table-12, " +
                            "tr#delete_table-13, " +
                            "tr#delete_table-14, " +
                            "tr#delete_table-15, " +
                            "tr#delete_table-16, " +
                            "tr#delete_table-17, " +
                            "tr#delete_table-18, " +
                            "tr#delete_table-19, " +
                            "tr#delete_table-23, " +
                            "tr#delete_table-24, " +
                            "tr#delete_table-25, " +
                            "tr#delete_table-26, " +
                            "tr#delete_table-27, " +
                            "tr#delete_table-28, " +
                            "tr#delete_table-29, " +
                            "tr#delete_table-30, " +
                            "tr#delete_table-31, " +
                            "tr#delete_table-32, " +
                            "tr#delete_table-33, " +
                            "tr#delete_table-34, " +
                            "tr#delete_table-35, " +
                            "tr#delete_table-36, " +
                            "tr#delete_table-37, " +
                            "tr#delete_table-38, " +
                            "tr#delete_table-39, " + 
                            "tr#delete_table-made_enabled, " + 
                            "tr#delete_table-40").hide();
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
            else if (this.id == "checkbox-made_enabled") {
                return; //Do none
            }
            else {
                if (this.id == "checkbox-force_group") {
                    $("#checkbox-recursion").prop("checked", false);
                }

                if (this.checked) {
                    $(".select_agents_row_2").css('display', 'none');
                    $("tr#delete_table-11, " +
                        "tr#delete_table-12, " +
                        "tr#delete_table-13, " +
                        "tr#delete_table-14, " +
                        "tr#delete_table-15, " +
                        "tr#delete_table-16, " +
                        "tr#delete_table-17, " +
                        "tr#delete_table-18, " +
                        "tr#delete_table-19, " +
                        "tr#delete_table-23, " +
                        "tr#delete_table-24, " +
                        "tr#delete_table-25, " +
                        "tr#delete_table-26, " +
                        "tr#delete_table-27, " +
                        "tr#delete_table-28, " +
                        "tr#delete_table-29, " +
                        "tr#delete_table-30, " +
                        "tr#delete_table-31, " +
                        "tr#delete_table-32, " +
                        "tr#delete_table-33, " +
                        "tr#delete_table-34, " +
                        "tr#delete_table-35, " +
                        "tr#delete_table-36, " +
                        "tr#delete_table-37, " +
                        "tr#delete_table-38, " +
                        "tr#delete_table-39, " + 
                        "tr#delete_table-40").show ();
                }
                else {
                    $(".select_agents_row_2").css('display', '');
                    if ($('#id_agents option:selected').val() == undefined) {
                        $("tr#delete_table-11, " +
                            "tr#delete_table-12, " +
                            "tr#delete_table-13, " +
                            "tr#delete_table-14, " +
                            "tr#delete_table-15, " +
                            "tr#delete_table-16, " +
                            "tr#delete_table-17, " +
                            "tr#delete_table-18, " +
                            "tr#delete_table-19, " +
                            "tr#delete_table-23, " +
                            "tr#delete_table-24, " +
                            "tr#delete_table-25, " +
                            "tr#delete_table-26, " +
                            "tr#delete_table-27, " +
                            "tr#delete_table-28, " +
                            "tr#delete_table-29, " +
                            "tr#delete_table-30, " +
                            "tr#delete_table-31, " +
                            "tr#delete_table-32, " +
                            "tr#delete_table-33, " +
                            "tr#delete_table-34, " +
                            "tr#delete_table-35, " +
                            "tr#delete_table-36, " +
                            "tr#delete_table-37, " +
                            "tr#delete_table-38, " +
                            "tr#delete_table-39, " + 
                            "tr#delete_table-made_enabled, " + 
                            "tr#delete_table-40").hide();
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
            $("tr#delete_table-20, tr#delete_table-21, tr#delete_table-22").show();
        }
        else {
            $("tr#delete_table-20, tr#delete_table-21, tr#delete_table-22").hide();
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

            $("tr#delete_table-11, " +
                "tr#delete_table-12, " +
                "tr#delete_table-13, " +
                "tr#delete_table-14, " +
                "tr#delete_table-15, " +
                "tr#delete_table-16, " +
                "tr#delete_table-17, " +
                "tr#delete_table-18, " +
                "tr#delete_table-19, " +
                "tr#delete_table-23, " +
                "tr#delete_table-24, " +
                "tr#delete_table-25, " +
                "tr#delete_table-26, " +
                "tr#delete_table-27, " +
                "tr#delete_table-28, " +
                "tr#delete_table-29, " +
                "tr#delete_table-30, " +
                "tr#delete_table-31, " +
                "tr#delete_table-32, " +
                "tr#delete_table-33, " +
                "tr#delete_table-34, " +
                "tr#delete_table-35, " +
                "tr#delete_table-36, " +
                "tr#delete_table-37, " +
                "tr#delete_table-38, " +
                "tr#delete_table-39, " + 
                "tr#delete_table-made_enabled, " + 
                "tr#delete_table-40").hide();

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
                            $("#delete_table-40").after("<tr class='macro_field' id='delete_table-edit"+(80+parseInt(i))+"'><td class='w100p' colspan='2'><div><label>"+macro['desc']+"</label><input type='hidden' name='desc"+macro['macro']+"' value='"+macro['desc']+"'><input type='text' name='"+macro['macro']+"'></div></td></tr>");
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
</script>
<?php
/**
 * Process_manage_edit
 *
 * @param mixed $module_name    Module_name.
 * @param mixed $agents_select  Agents_select.
 * @param mixed $module_status  Module_status.
 * @param mixed $selection_mode Selection_mode.
 *
 * @return boolen True/False.
 */
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
        'made_enabled',
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
        'ignore_unknown',
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
        'warning_time',
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
                [
                    'id_agente'        => $agents_select,
                    'id_policy_module' => 0,
                    'policy_linked'    => 0,
                ],
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

        if (modules_made_compatible($module['id_tipo_modulo']) === false) {
            $values['made_enabled'] = 0;
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
