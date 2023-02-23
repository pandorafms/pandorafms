<?php
/**
 * Agent Status View.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Monitoring.
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
global $config;

ob_start();

require_once 'include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_modules.php';
enterprise_include_once('include/functions_config_agents.php');

ui_require_css_file('tables');

check_login();

if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent main list view'
    );
    include 'general/noaccess.php';

    return;
}

if (is_ajax() === true) {
    ob_get_clean();

    $get_agent_module_last_value = (bool) get_parameter('get_agent_module_last_value');
    $get_actions_alert_template = (bool) get_parameter('get_actions_alert_template');

    if ($get_actions_alert_template === true) {
        $id_template = get_parameter('id_template');

        $own_info = get_user_info($config['id_user']);
        $usr_groups = [];
        $usr_groups = users_get_groups($config['id_user'], 'LW', true);

        $filter_groups = '';
        $filter_groups = implode(',', array_keys($usr_groups));

        $sql = sprintf(
            "SELECT t1.id, t1.name,
                (SELECT COUNT(t2.id) 
                    FROM talert_templates t2 
                    WHERE t2.id =  %d 
                        AND t2.id_alert_action = t1.id) as 'sort_order'
            FROM talert_actions t1
            WHERE id_group IN (%s) 
            ORDER BY sort_order DESC",
            $id_template,
            $filter_groups
        );

        $rows = db_get_all_rows_sql($sql);


        if ($rows !== false) {
            echo json_encode($rows);
        } else {
            echo 'false';
        }

        return;
    }

    if ($get_agent_module_last_value === true) {
        $id_module = (int) get_parameter('id_agent_module');
        $id_agent = (int) modules_get_agentmodule_agent((int) $id_module);
        if (! check_acl_one_of_groups($config['id_user'], agents_get_all_groups_agent($id_agent), 'AR')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access agent main list view'
            );
            echo json_encode(false);
            return;
        }

        $module_value = modules_get_last_value($id_module);
        if (is_numeric($row['value']) && !modules_is_string_type($module['id_tipo_modulo'])) {
            $value = $module_value;
        } else {
            $module = modules_get_agentmodule($id_module);

            $is_snapshot = is_snapshot_data($module_value);
            $is_large_image = is_text_to_black_string($module_value);
            if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
                $link = ui_get_snapshot_link(
                    [
                        'id_module'   => $module['id_agente_modulo'],
                        'interval'    => $module['current_interval'],
                        'module_name' => $module['nombre'],
                    ]
                );
                $value = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
            } else {
                $value = ui_print_module_string_value(
                    $module_value,
                    $module['id_agente_modulo'],
                    $module['current_interval'],
                    $module['module_name']
                );
            }
        }

        echo json_encode($value);
        return;
    }

    return;
}

ob_end_clean();


// Take some parameters (GET).
$group_id = (int) get_parameter('group_id', 0);
$search = trim(get_parameter('search', ''));
$search_custom = trim(get_parameter('search_custom', ''));
$offset = (int) get_parameter('offset', 0);
$refr = get_parameter('refr', 0);
$recursion = get_parameter('recursion', 0);
$status = (int) get_parameter('status', -1);

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);
$agent_a = (bool) check_acl($config['id_user'], 0, 'AR');
$agent_w = (bool) check_acl($config['id_user'], 0, 'AW');
$access = ($agent_a === true) ? 'AR' : (($agent_w === true) ? 'AW' : 'AR');

$onheader = [];

if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
    // Prepare the tab system to the future.
    $tab = 'setup';
    // Options.
    $setuptab['godmode'] = true;
    $setuptab['active'] = false;
    // Setup tab.
    $setuptab['text'] = html_print_anchor(
        [
            'href'    => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'),
            'content' => html_print_image(
                'images/configuration@svg.svg',
                true,
                [
                    'title' => __('Setup'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ),
        ],
        true
    );
    // Header button.
    $onheader = ['setup' => $setuptab];
}

// Header.
ui_print_standard_header(
    __('Agent detail'),
    'images/agent.png',
    false,
    '',
    false,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('Views'),
        ],
    ]
);

if ((bool) $strict_user === false) {
    if (tags_has_user_acl_tags() === true) {
        ui_print_tags_warning();
    }
}

// User is deleting agent.
if (isset($result_delete) === true) {
    ui_print_result_message(
        $result_delete,
        __('Sucessfully deleted agent'),
        __('There was an error message deleting the agent')
    );
}

$groups = users_get_groups(false, $access);

$fields = [];
$fields[AGENT_STATUS_NORMAL] = __('Normal');
$fields[AGENT_STATUS_WARNING] = __('Warning');
$fields[AGENT_STATUS_CRITICAL] = __('Critical');
$fields[AGENT_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$fields[AGENT_STATUS_NOT_INIT] = __('Not init');

$searchForm = '';
$searchForm .= '<form method="post" action="?sec=view&sec2=operation/agentes/estado_agente&group_id='.$group_id.'">';

$table = new stdClass();
$table->width = '100%';
$table->class = 'nueva-clase';

$table->data['group'][0] = '<div>';
$table->data['group'][0] .= '<label>'.__('Group').'</label>';
$table->data['group'][0] .= html_print_select_groups(
    false,
    $access,
    true,
    'group_id',
    $group_id,
    'this.form.submit()',
    '',
    '',
    true,
    false,
    true,
    '',
    false
);
$table->data['group'][0] .= '</div>';

$table->data['group'][1] = '<div>';
$table->data['group'][1] .= '<label>'.__('Recursion').'</label>';
$table->data['group'][1] .= html_print_checkbox_switch(
    'recursion',
    1,
    $recursion,
    true
);
$table->data['group'][1] .= '</div>';

$table->data['group'][2] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $fields,
        'status',
        $status,
        'this.form.submit()',
        __('All'),
        AGENT_STATUS_ALL,
        true,
        false,
        true,
        '',
        false
    )
);

$table->data['search_fields'][0] = html_print_label_input_block(
    __('Search'),
    html_print_input_text(
        'search',
        $search,
        '',
        35,
        255,
        true
    )
);

$table->data['search_fields'][1] = html_print_label_input_block(
    __('Search in custom fields'),
    html_print_input_text(
        'search_custom',
        $search_custom,
        '',
        35,
        255,
        true
    )
);

$searchForm .= html_print_table($table, true);
$searchForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Search'),
            'srcbutton',
            false,
            [
                'icon' => 'search',
                'mode' => 'secondary mini',
            ],
            true
        ),
    ],
    true
);
$searchForm .= '</form>';

ui_toggle(
    $searchForm,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

if (empty($search) === false) {
    $filter = ['string' => '%'.$search.'%'];
} else {
    $filter = [];
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

$selected = true;
$selectNameUp = false;
$selectNameDown = false;
$selectOsUp = false;
$selectOsDown = false;
$selectIntervalUp = false;
$selectIntervalDown = false;
$selectGroupUp = false;
$selectGroupDown = false;
$selectDescriptionUp = false;
$selectDescriptionDown = false;
$selectLastContactUp = false;
$selectLastContactDown = false;
$selectRemoteUp = false;
$selectRemoteDown = false;
$order = null;

switch ($sortField) {
    case 'remote':
        switch ($sort) {
            case 'up':
                $selectRemoteUp = $selected;
                $order = [
                    'field'  => 'remote',
                    'field2' => 'nombre',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectRemoteDown = $selected;
                $order = [
                    'field'  => 'remote',
                    'field2' => 'nombre',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'name':
        switch ($sort) {
            case 'up':
                $selectNameUp = $selected;
                $order = [
                    'field'  => 'alias',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field'  => 'alias',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'os':
        switch ($sort) {
            case 'up':
                $selectOsUp = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectOsDown = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'interval':
        switch ($sort) {
            case 'up':
                $selectIntervalUp = $selected;
                $order = [
                    'field'  => 'intervalo',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectIntervalDown = $selected;
                $order = [
                    'field'  => 'intervalo',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'group':
        switch ($sort) {
            case 'up':
                $selectGroupUp = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectGroupDown = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'last_contact':
        switch ($sort) {
            case 'up':
                $selectLastContactUp = $selected;
                $order = [
                    'field'  => 'ultimo_contacto',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            case 'down':
                $selectLastContactDown = $selected;
                $order = [
                    'field'  => 'ultimo_contacto',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'description':
        switch ($sort) {
            case 'up':
                $selectDescriptionUp = $selected;
                $order = [
                    'field'  => 'comentarios',
                    'field2' => 'alias',
                    'order'  => 'DESC',
                ];
            break;

            case 'down':
                $selectDescriptionDown = $selected;
                $order = [
                    'field'  => 'comentarios',
                    'field2' => 'alias',
                    'order'  => 'ASC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    default:
        $selectNameUp = $selected;
        $selectNameDown = false;
        $selectOsUp = false;
        $selectOsDown = false;
        $selectIntervalUp = false;
        $selectIntervalDown = false;
        $selectGroupUp = false;
        $selectGroupDown = false;
        $selectDescriptionUp = false;
        $selectDescriptionDown = false;
        $selectLastContactUp = false;
        $selectLastContactDown = false;
        $order = [
            'field'  => 'alias',
            'field2' => 'alias',
            'order'  => 'ASC',
        ];
    break;
}

$search_sql = '';
if ($search != '') {
    $sql = sprintf(
        'SELECT DISTINCT taddress_agent.id_agent FROM taddress
	     INNER JOIN taddress_agent ON
	     taddress.id_a = taddress_agent.id_a
	     WHERE taddress.ip LIKE "%%%s%%"',
        $search
    );

    $id = db_get_all_rows_sql($sql);
    if (empty($id) === false) {
        $aux = $id[0]['id_agent'];
        $search_sql = sprintf(
            ' AND ( `nombre` LIKE "%%%s%%" OR tagente.id_agente = %d',
            $search,
            $aux
        );
        $nagent_count = count($id);
        if ($nagent_count >= 2) {
            for ($i = 1; $i < $nagent_count; $i++) {
                $aux = $id[$i]['id_agent'];
                $search_sql .= sprintf(
                    ' OR tagente.id_agente = %d',
                    $aux
                );
            }
        }

        $search_sql .= ')';
    } else {
        $search_sql = sprintf(
            ' AND ( nombre 
			 LIKE "%%%s%%" OR alias 
			 LIKE "%%%s%%" OR comentarios LIKE "%%%s%%" 
			 OR EXISTS (SELECT * FROM tagent_custom_data WHERE id_agent = id_agente AND description LIKE "%%%s%%"))',
            $search,
            $search,
            $search,
            $search
        );
    }
}

if (empty($search_custom) === false) {
    $search_sql_custom = " AND EXISTS (SELECT * FROM tagent_custom_data 
		WHERE id_agent = id_agente AND description LIKE '%$search_custom%')";
} else {
    $search_sql_custom = '';
}

// Show only selected groups.
if ($group_id > 0) {
    $groups = [$group_id];
    if ($recursion) {
        $groups = groups_get_children_ids($group_id, true);
    }
} else {
    $groups = [];
    $user_groups = users_get_groups($config['id_user'], $access);
    $groups = array_keys($user_groups);
}


if ($strict_user) {
    $count_filter = [
        // 'order' => 'tagente.nombre ASC',
        'order'    => 'tagente.nombre ASC',
        'disabled' => 0,
        'status'   => $status,
        'search'   => $search,
    ];
    $filter = [
        // 'order' => 'tagente.nombre ASC',
        'order'    => 'tagente.nombre ASC',
        'disabled' => 0,
        'status'   => $status,
        'search'   => $search,
        'offset'   => (int) get_parameter('offset'),
        'limit'    => (int) $config['block_size'],
    ];

    if ($group_id > 0) {
        $groups = [$group_id];
        if ($recursion) {
            $groups = groups_get_children_ids($group_id, true);
        }

        $filter['id_group'] = implode(',', $groups);
        $count_filter['id_group'] = $filter['id_group'];
    }

    $fields = [
        'tagente.id_agente',
        'tagente.id_grupo',
        'tagente.id_os',
        'tagente.ultimo_contacto',
        'tagente.intervalo',
        'tagente.comentarios description',
        'tagente.quiet',
        'tagente.normal_count',
        'tagente.warning_count',
        'tagente.critical_count',
        'tagente.unknown_count',
        'tagente.notinit_count',
        'tagente.total_count',
        'tagente.fired_count',
        'tagente.nombre',
        'tagente.alias',
    ];

    $acltags = tags_get_user_groups_and_tags($config['id_user'], $access, $strict_user);

    $total_agents = tags_get_all_user_agents(false, $config['id_user'], $acltags, $count_filter, $fields, false, $strict_user, true);
    $total_agents = count($total_agents);

    $agents = tags_get_all_user_agents(false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
} else {
    $total_agents = agents_count_agents_filter(
        [
            'disabled'      => 0,
            'id_grupo'      => $groups,
            'search'        => $search_sql,
            'search_custom' => $search_sql_custom,
            'status'        => $status,
        ],
        $access
    );

    $agents = agents_get_agents(
        [
            'order'         => 'nombre ASC',
            'id_grupo'      => $groups,
            'disabled'      => 0,
            'status'        => $status,
            'search_custom' => $search_sql_custom,
            'search'        => $search_sql,
            'offset'        => (int) get_parameter('offset'),
            'limit'         => (int) $config['block_size'],
        ],
        [
            'id_agente',
            'id_grupo',
            'nombre',
            'alias',
            'id_os',
            'ultimo_contacto',
            'intervalo',
            'comentarios description',
            'quiet',
            'normal_count',
            'warning_count',
            'critical_count',
            'unknown_count',
            'notinit_count',
            'total_count',
            'fired_count',
            'ultimo_contacto_remoto',
            'remote',
            'agent_version',
        ],
        $access,
        $order
    );
}

if (empty($agents)) {
    $agents = [];
}

if ($config['language'] === 'ja'
    || $config['language'] === 'zh_CN'
    || $own_info['language'] === 'ja'
    || $own_info['language'] === 'zh_CN'
) {
    // Adds a custom font size for Japanese and Chinese language.
    $custom_font_size = 'custom_font_size';
} else {
    $custom_font_size = '';
}

// Urls to sort the table.
$url_up_agente = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=name&amp;sort=up';
$url_down_agente = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=name&amp;sort=down';
$url_up_description = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=description&amp;sort=up';
$url_down_description = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=description&amp;sort=down';
$url_up_remote = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=remote&amp;sort=up';
$url_down_remote = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=remote&amp;sort=down';
$url_up_os = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=os&amp;sort=up';
$url_down_os = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=os&amp;sort=down';
$url_up_interval = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=interval&amp;sort=up';
$url_down_interval = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=interval&amp;sort=down';
$url_up_group = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=group&amp;sort=up';
$url_down_group = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=group&amp;sort=down';
$url_up_last = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=last_contact&amp;sort=up';
$url_down_last = 'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr='.$refr.'&amp;offset='.$offset.'&amp;group_id='.$group_id.'&amp;recursion='.$recursion.'&amp;search='.$search.'&amp;status='.$status.'&amp;sort_field=last_contact&amp;sort=down';

// Show data.
$tableAgents = new stdClass();
$tableAgents->cellpadding = 0;
$tableAgents->cellspacing = 0;
$tableAgents->id = 'agent_list';
$tableAgents->class = 'info_table tactical_table';

$tableAgents->head = [];
$tableAgents->head[0] = '<span>'.__('Agent').'</span>';
$tableAgents->head[0] .= ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectNameUp, $selectNameDown);
$tableAgents->size[0] = '12%';

$tableAgents->head[1] = '<span>'.__('Description').'</span>';
$tableAgents->head[0] .= ui_get_sorting_arrows($url_up_description, $url_down_description, $selectDescriptionUp, $selectDescriptionDown);
$tableAgents->size[1] = '16%';

$tableAgents->head[10] = '<span>'.__('Remote').'</span>';
$tableAgents->head[10] .= ui_get_sorting_arrows($url_up_remote, $url_down_remote, $selectRemoteUp, $selectRemoteDown);
$tableAgents->size[10] = '9%';

$tableAgents->head[2] = '<span>'.__('OS').'</span>';
$tableAgents->head[2] .= ui_get_sorting_arrows($url_up_os, $url_down_os, $selectOsUp, $selectOsDown);
$tableAgents->size[2] = '8%';

$tableAgents->head[3] = '<span>'.__('Interval').'</span>';
$tableAgents->head[3] .= ui_get_sorting_arrows($url_up_interval, $url_down_interval, $selectIntervalUp, $selectIntervalDown);
$tableAgents->size[3] = '10%';

$tableAgents->head[4] = '<span>'.__('Group').'</span>';
$tableAgents->head[4] .= ui_get_sorting_arrows($url_up_group, $url_down_group, $selectGroupUp, $selectGroupDown);
$tableAgents->size[4] = '8%';

$tableAgents->head[5] = '<span>'.__('Type').'</span>';
$tableAgents->size[5] = '8%';

$tableAgents->head[6] = '<span>'.__('Modules').'</span>';
$tableAgents->size[6] = '10%';

$tableAgents->head[7] = '<span>'.__('Status').'</span>';
$tableAgents->size[7] = '4%';

$tableAgents->head[8] = '<span>'.__('Alerts').'</span>';
$tableAgents->size[8] = '4%';

$tableAgents->head[9] = '<span>'.__('Last contact').'</span>';
$tableAgents->head[9] .= ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastContactUp, $selectLastContactDown);
$tableAgents->size[9] = '15%';

$tableAgents->align = [];

$tableAgents->align[2] = 'left';
$tableAgents->align[3] = 'left';
$tableAgents->align[4] = 'left';
$tableAgents->align[5] = 'left';
$tableAgents->align[6] = 'left';
$tableAgents->align[7] = 'left';
$tableAgents->align[8] = 'left';
$tableAgents->align[9] = 'left';

$tableAgents->style = [];
$tableAgents->data = [];

$rowPair = true;
$iterator = 0;
foreach ($agents as $agent) {
    $cluster = db_get_row_sql('select id from tcluster where id_agent = '.$agent['id_agente']);

    if ($rowPair) {
        $tableAgents->rowclass[$iterator] = 'rowPair';
    } else {
        $tableAgents->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $alert_img = agents_tree_view_alert_img($agent['fired_count']);

    $status_img = agents_tree_view_status_img(
        $agent['critical_count'],
        $agent['warning_count'],
        $agent['unknown_count'],
        $agent['total_count'],
        $agent['notinit_count']
    );

    $in_planned_downtime = db_get_sql(
        'SELECT executed FROM tplanned_downtime 
		INNER JOIN tplanned_downtime_agents 
		ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
		WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1'
    );

    $data = [];

    $data[0] = '<div class="left_'.$agent['id_agente'].'">';

    $data[0] .= html_print_anchor(
        [
            'href'    => ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente']),
            'content' => ui_print_truncate_text($agent['alias'], 'agent_medium', false, true, true),
        ],
        true
    );

    if ($agent['quiet']) {
        $data[0] .= '&nbsp;';
        $data[0] .= html_print_image(
            'images/dot_blue.png',
            true,
            [
                'border' => '0',
                'title'  => __('Quiet'),
                'alt'    => '',
            ]
        );
    }

    if ($in_planned_downtime) {
        $data[0] .= ui_print_help_tip(
            __('Agent in scheduled downtime'),
            true,
            'images/minireloj-16.png'
        );
        $data[0] .= '</em>';
    }

    $data[0] .= '<div class="agentleft_'.$agent['id_agente'].'" style="visibility: hidden; clear: left;">';

    if ($agent['id_os'] == CLUSTER_OS_ID) {
        $cluster = PandoraFMS\Cluster::loadFromAgentId(
            $agent['id_agente']
        );
        $url = 'index.php?sec=reporting&sec2=';
        $url .= 'operation/cluster/cluster';
        $url = ui_get_full_url(
            $url.'&op=view&id='.$cluster->id()
        );
        $data[0] .= '<a href="'.$url.'">'.__('View').'</a>';
    } else {
        $data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'">'.__('View').'</a>';
    }

    if (check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
        $data[0] .= ' | ';

        if ($agent['id_os'] == CLUSTER_OS_ID) {
            $cluster = PandoraFMS\Cluster::loadFromAgentId(
                $agent['id_agente']
            );
            $url = 'index.php?sec=reporting&sec2=';
            $url .= 'operation/cluster/cluster';
            $url = ui_get_full_url(
                $url.'&op=update&id='.$cluster->id()
            );
            $data[0] .= '<a href="'.$url.'">'.__('Edit').'</a>';
        } else {
                $data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent['id_agente'].'">'.__('Edit').'</a>';
        }
    }

    $data[0] .= '</div></div>';

    $data[1] = '<span class="'.$custom_font_size.'">'.ui_print_truncate_text($agent['description'], 'description', false, true, true, '[&hellip;]').'</span>';

    $data[10] = '';

    if (enterprise_installed()) {
        enterprise_include_once('include/functions_config_agents.php');
        if (enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']])) {
            $data[10] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=remote_configuration&id_agente='.$agent['id_agente'].'&disk_conf=1">'.html_print_image(
                'images/remote-configuration@svg.svg',
                true,
                [
                    'align' => 'middle',
                    'title' => __('Remote config'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ).'</a>';
        }
    }

    $data[2] = html_print_div(
        [
            'class'   => 'invert_filter main_menu_icon',
            'content' => ui_print_os_icon($agent['id_os'], false, true),
        ],
        true
    );

    $data[3] = '<span>'.human_time_description_raw(
        $agent['intervalo']
    ).'</span>';
    $data[4] = '<a href="'.$config['homeurl'].'index.php?sec=view&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$agent['id_grupo'].'">';
    $data[4] .= ui_print_group_icon(
        $agent['id_grupo'],
        true,
        'groups_small',
        '',
        false,
        false,
        false,
        'invert_filter main_menu_icon'
    );
    $data[4] .= '</a>';
    $agent['not_init_count'] = $agent['notinit_count'];

    $data[5] = ui_print_type_agent_icon(
        $agent['id_os'],
        $agent['ultimo_contacto_remoto'],
        $agent['ultimo_contacto'],
        $agent['remote'],
        $agent['agent_version']
    );

    $data[6] = reporting_tiny_stats($agent, true, 'modules', ':', $strict_user);

    $data[7] = $status_img;

    $data[8] = $alert_img;

    $data[9] = agents_get_interval_status($agent);

    // This old code was returning "never" on agents without modules, BAD !!
    // And does not print outdated agents in red. WRONG !!!!
    // $data[7] = ui_print_timestamp ($agent_info["last_contact"], true);
    array_push($tableAgents->data, $data);
}

if (empty($tableAgents->data) === false) {
    html_print_table($tableAgents);

    $tablePagination = ui_pagination(
        $total_agents,
        ui_get_url_refresh(
            [
                'group_id'   => $group_id,
                'search'     => $search,
                'sort_field' => $sortField,
                'sort'       => $sort,
                'status'     => $status,
            ]
        ),
        0,
        0,
        true,
        'offset',
        false,
        'dataTables_paginate paging_simple_numbers'
    );

    unset($table);
} else {
    ui_print_info_message([ 'no_close' => true, 'message' => __('There are no defined agents') ]);
    $tablePagination = '';
}

if ((bool) check_acl($config['id_user'], 0, 'AW') === true || (bool) check_acl($config['id_user'], 0, 'AM') === true) {
    echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';
    html_print_action_buttons(
        html_print_submit_button(
            __('Create agent'),
            'crt',
            false,
            [ 'icon' => 'next' ],
            true
        ),
        [
            'type'          => 'data_table',
            'class'         => 'fixed_action_buttons',
            'right_content' => $tablePagination,
        ]
    );
    echo '</form>';
}

?>

<script type="text/javascript">
$(document).ready (function () {
    $("[class^='left']").mouseenter (function () {
        $(".agent"+$(this)[0].className).css('visibility', '');
    }).mouseleave(function () {
        $(".agent"+$(this)[0].className).css('visibility', 'hidden');
    });
});
</script>
