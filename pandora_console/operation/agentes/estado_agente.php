<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

ob_start();

require_once 'include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_modules.php';
enterprise_include_once('include/functions_config_agents.php');

check_login();

if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit('ACL Violation', 'Trying to access agent main list view');
    include 'general/noaccess.php';

    return;
}

if (is_ajax()) {
    ob_get_clean();

    $get_agent_module_last_value = (bool) get_parameter('get_agent_module_last_value');
    $get_actions_alert_template = (bool) get_parameter('get_actions_alert_template');

    if ($get_actions_alert_template) {
        $id_template = get_parameter('id_template');

        $own_info = get_user_info($config['id_user']);
        $usr_groups = [];
        $usr_groups = users_get_groups($config['id_user'], 'LW', true);

        $filter_groups = '';
        $filter_groups = implode(',', array_keys($usr_groups));

        switch ($config['dbtype']) {
            case 'mysql':
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
            break;

            case 'oracle':
                $sql = sprintf(
                    'SELECT t1.id, t1.name,
						(SELECT COUNT(t2.id) 
							FROM talert_templates t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as sort_order
					FROM talert_actions t1
					WHERE id_group IN (%s) 
					ORDER BY sort_order DESC',
                    $id_template,
                    $filter_groups
                );
            break;

            case 'postgresql':
                $sql = sprintf(
                    'SELECT t1.id, t1.name,
						(SELECT COUNT(t2.id) 
							FROM talert_templates t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as sort_order
					FROM talert_actions t1
					WHERE id_group IN (%s) 
					ORDER BY sort_order DESC',
                    $id_template,
                    $filter_groups
                );
            break;
        }

        $rows = db_get_all_rows_sql($sql);


        if ($rows !== false) {
            echo json_encode($rows);
        } else {
            echo 'false';
        }

        return;
    }

    if ($get_agent_module_last_value) {
        $id_module = (int) get_parameter('id_agent_module');
        $id_agent = (int) modules_get_agentmodule_agent((int) $id_module);
        if (! check_acl_one_of_groups($config['id_user'], agents_get_all_groups_agent($id_agent), 'AR')) {
            db_pandora_audit(
                'ACL Violation',
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


// Take some parameters (GET)
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

if (check_acl($config['id_user'], 0, 'AW')) {
    // Prepare the tab system to the future
    $tab = 'setup';

    // Setup tab
    $setuptab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente">'.html_print_image('images/setup.png', true, ['title' => __('Setup')]).'</a>';

    $setuptab['godmode'] = true;

    $setuptab['active'] = false;

    $onheader = ['setup' => $setuptab];
}

ui_print_page_header(__('Agent detail'), 'images/agent_mc.png', false, 'agent_status', false, $onheader);

if (!$strict_user) {
    if (tags_has_user_acl_tags()) {
        ui_print_tags_warning();
    }
}

// User is deleting agent
if (isset($result_delete)) {
    if ($result_delete) {
        ui_print_success_message(__('Sucessfully deleted agent'));
    } else {
        ui_print_error_message(__('There was an error message deleting the agent'));
    }
}

echo '<form method="post" action="?sec=view&sec2=operation/agentes/estado_agente&group_id='.$group_id.'">';

echo '<table cellpadding="4" cellspacing="4" class="databox filters" width="100%" style="font-weight: bold; margin-bottom: 10px;">';

echo '<tr><td style="white-space:nowrap;">';

echo __('Group').'&nbsp;';

$groups = users_get_groups(false, $access);

html_print_select_groups(false, $access, true, 'group_id', $group_id, 'this.form.submit()', '', '', false, false, true, '', false);

echo '</td><td style="white-space:nowrap;">';

echo __('Recursion').'&nbsp;';
html_print_checkbox('recursion', 1, $recursion, false, false, 'this.form.submit()');

echo '</td><td style="white-space:nowrap;">';

echo __('Search').'&nbsp;';
html_print_input_text('search', $search, '', 15);

echo '</td><td style="white-space:nowrap;">';

$fields = [];
$fields[AGENT_STATUS_NORMAL] = __('Normal');
$fields[AGENT_STATUS_WARNING] = __('Warning');
$fields[AGENT_STATUS_CRITICAL] = __('Critical');
$fields[AGENT_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$fields[AGENT_STATUS_NOT_INIT] = __('Not init');

echo __('Status').'&nbsp;';
html_print_select($fields, 'status', $status, 'this.form.submit()', __('All'), AGENT_STATUS_ALL, false, false, true, '', false, 'width: 90px;');

echo '</td><td style="white-space:nowrap;">';

echo __('Search in custom fields').'&nbsp;';
html_print_input_text('search_custom', $search_custom, '', 15);

echo '</td><td style="white-space:nowrap;">';

html_print_submit_button(
    __('Search'),
    'srcbutton',
    '',
    ['class' => 'sub search']
);

echo '</td><td style="width:5%;">&nbsp;</td>';

echo '</tr></table></form>';

if ($search != '') {
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
$order = null;


$order_collation = '';
switch ($config['dbtype']) {
    case 'mysql':
        // $order_collation = " COLLATE utf8_general_ci";
        $order_collation = '';
    break;

    case 'postgresql':
    case 'oracle':
        $order_collation = '';
    break;
}


switch ($sortField) {
    case 'remote':
        switch ($sort) {
            case 'up':
                $selectRemoteUp = $selected;
                $order = [
                    'field'  => 'remote'.$order_collation,
                    'field2' => 'nombre'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectRemoteDown = $selected;
                $order = [
                    'field'  => 'remote'.$order_collation,
                    'field2' => 'nombre'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;
        }
    break;

    case 'name':
        switch ($sort) {
            case 'up':
                $selectNameUp = $selected;
                $order = [
                    'field'  => 'alias'.$order_collation,
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field'  => 'alias'.$order_collation,
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;
        }
    break;

    case 'os':
        switch ($sort) {
            case 'up':
                $selectOsUp = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectOsDown = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;
        }
    break;

    case 'interval':
        switch ($sort) {
            case 'up':
                $selectIntervalUp = $selected;
                $order = [
                    'field'  => 'intervalo',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectIntervalDown = $selected;
                $order = [
                    'field'  => 'intervalo',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;
        }
    break;

    case 'group':
        switch ($sort) {
            case 'up':
                $selectGroupUp = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectGroupDown = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;
        }
    break;

    case 'last_contact':
        switch ($sort) {
            case 'up':
                $selectLastContactUp = $selected;
                $order = [
                    'field'  => 'ultimo_contacto',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;

            case 'down':
                $selectLastContactDown = $selected;
                $order = [
                    'field'  => 'ultimo_contacto',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
            break;
        }
    break;

    case 'description':
        switch ($sort) {
            case 'up':
                $selectDescriptionUp = $selected;
                $order = [
                    'field'  => 'comentarios',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'DESC',
                ];
            break;

            case 'down':
                $selectDescriptionDown = $selected;
                $order = [
                    'field'  => 'comentarios',
                    'field2' => 'alias'.$order_collation,
                    'order'  => 'ASC',
                ];
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
            'field'  => 'alias'.$order_collation,
            'field2' => 'alias'.$order_collation,
            'order'  => 'ASC',
        ];
    break;
}

$search_sql = '';
if ($search != '') {
    $sql = "SELECT DISTINCT taddress_agent.id_agent FROM taddress
	INNER JOIN taddress_agent ON
	taddress.id_a = taddress_agent.id_a
	WHERE taddress.ip LIKE '%$search%'";

    $id = db_get_all_rows_sql($sql);
    if ($id != '') {
        $aux = $id[0]['id_agent'];
        $search_sql = ' AND ( nombre '.$order_collation."
			COLLATE utf8_general_ci LIKE '%$search%' OR alias ".$order_collation." COLLATE utf8_general_ci LIKE '%$search%' 
			OR tagente.id_agente = $aux";
        if (count($id) >= 2) {
            for ($i = 1; $i < count($id); $i++) {
                $aux = $id[$i]['id_agent'];
                $search_sql .= " OR tagente.id_agente = $aux";
            }
        }

        $search_sql .= ')';
    } else {
        $search_sql = ' AND ( nombre '.$order_collation."
			COLLATE utf8_general_ci LIKE '%$search%' OR alias ".$order_collation." COLLATE utf8_general_ci LIKE '%$search%') ";
    }
}


if (!empty($search_custom)) {
    $search_sql_custom = " AND EXISTS (SELECT * FROM tagent_custom_data 
		WHERE id_agent = id_agente AND description LIKE '%$search_custom%')";
} else {
    $search_sql_custom = '';
}

// Show only selected groups
if ($group_id > 0) {
    $groups = [$group_id];
    if ($recursion) {
        $groups = groups_get_id_recursive($group_id, true);
    }
} else {
    $groups = [];
    $user_groups = users_get_groups($config['id_user'], $access);
    $groups = array_keys($user_groups);
}


if ($strict_user) {
    $count_filter = [
        // 'order' => 'tagente.nombre COLLATE utf8_general_ci ASC',
        'order'    => 'tagente.nombre ASC',
        'disabled' => 0,
        'status'   => $status,
        'search'   => $search,
    ];
    $filter = [
        // 'order' => 'tagente.nombre COLLATE utf8_general_ci ASC',
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
            $groups = groups_get_id_recursive($group_id, true);
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
            'order'         => 'nombre '.$order_collation.' ASC',
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

$agent_font_size = 'font-size:  7px';
$description_font_size = 'font-size: 6.5px';
if ($config['language'] == 'ja' || $config['language'] == 'zh_CN' || $own_info['language'] == 'ja' || $own_info['language'] == 'zh_CN') {
    $agent_font_size = 'font-size: 15px';
    $description_font_size = 'font-size: 11px';
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


// Prepare pagination
ui_pagination(
    $total_agents,
    ui_get_url_refresh(['group_id' => $group_id, 'recursion' => $recursion, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort, 'status' => $status])
);

// Show data.
$table = new stdClass();
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = '100%';
$table->class = 'info_table';

$table->head = [];
$table->head[0] = __('Agent').ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectNameUp, $selectNameDown);
$table->size[0] = '10%';

$table->head[1] = __('Description').ui_get_sorting_arrows($url_up_description, $url_down_description, $selectDescriptionUp, $selectDescriptionDown);
$table->size[1] = '16%';

$table->head[10] = __('Remote').ui_get_sorting_arrows($url_up_remote, $url_down_remote, $selectRemoteUp, $selectRemoteDown);
$table->size[10] = '9%';

$table->head[2] = __('OS').ui_get_sorting_arrows($url_up_os, $url_down_os, $selectOsUp, $selectOsDown);
$table->size[2] = '8%';

$table->head[3] = __('Interval').ui_get_sorting_arrows($url_up_interval, $url_down_interval, $selectIntervalUp, $selectIntervalDown);
$table->size[3] = '10%';

$table->head[4] = __('Group').ui_get_sorting_arrows($url_up_group, $url_down_group, $selectGroupUp, $selectGroupDown);
$table->size[4] = '8%';

$table->head[5] = __('Type');
$table->size[5] = '8%';

$table->head[6] = __('Modules');
$table->size[6] = '10%';

$table->head[7] = __('Status');
$table->size[7] = '4%';

$table->head[8] = __('Alerts');
$table->size[8] = '4%';

$table->head[9] = __('Last contact').ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastContactUp, $selectLastContactDown);
$table->size[9] = '15%';

$table->align = [];

$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';
$table->align[5] = 'left';
$table->align[6] = 'left';
$table->align[7] = 'left';
$table->align[8] = 'left';
$table->align[9] = 'left';

$table->style = [];

$table->data = [];

$rowPair = true;
$iterator = 0;
foreach ($agents as $agent) {
    $cluster = db_get_row_sql('select id from tcluster where id_agent = '.$agent['id_agente']);

    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
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
    $data[0] .= '<span>';

    $data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'"> <span style="'.$agent_font_size.';font-weight:bold" title ="'.$agent['nombre'].'">'.$agent['alias'].'</span></a>';
    $data[0] .= '</span>';

    if ($agent['quiet']) {
        $data[0] .= '&nbsp;';
        $data[0] .= html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']);
    }

    if ($in_planned_downtime) {
        $data[0] .= ui_print_help_tip(__('Agent in planned downtime'), true, 'images/minireloj-16.png');
        $data[0] .= '</em>';
    }

    $data[0] .= '<div class="agentleft_'.$agent['id_agente'].'" style="visibility: hidden; clear: left;">';

    if ($agent['id_os'] == 100) {
        $data[0] .= '<a href="index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_view&id='.$cluster['id'].'">'.__('View').'</a>';
    } else {
        $data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'">'.__('View').'</a>';
    }

    if (check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
        $data[0] .= ' | ';

        if ($agent['id_os'] == 100) {
                $data[0] .= '<a href="index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$cluster['id'].'&step=1&update=1">'.__('Edit').'</a>';
        } else {
                $data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent['id_agente'].'">'.__('Edit').'</a>';
        }
    }

    $data[0] .= '</div></div>';

    $data[1] = ui_print_truncate_text($agent['description'], 'description', false, true, true, '[&hellip;]', $description_font_size);

    $data[10] = '';

    if (enterprise_installed()) {
        enterprise_include_once('include/functions_config_agents.php');
        if (enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']])) {
            $data[10] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=remote_configuration&id_agente='.$agent['id_agente'].'&disk_conf=1">'.html_print_image('images/application_edit.png', true, ['align' => 'middle', 'title' => __('Remote config')]).'</a>';
        }
    }

    $data[2] = ui_print_os_icon($agent['id_os'], false, true);

    $data[3] = '<span style="font-size:6.5pt;">'.human_time_description_raw($agent['intervalo']).'</span>';

    $data[4] = ui_print_group_icon($agent['id_grupo'], true);
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
    array_push($table->data, $data);
}

if (!empty($table->data)) {
    html_print_table($table);

    ui_pagination(
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
        false,
        'offset',
        true,
        'pagination-bottom'
    );

    if (check_acl($config['id_user'], 0, 'AW') || check_acl($config['id_user'], 0, 'AM')) {
        echo '<div style="text-align: right; float: right;">';
        echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';
            html_print_input_hidden('new_agent', 1);
            html_print_submit_button(__('Create agent'), 'crt', false, 'class="sub next"');
        echo '</form>';
        echo '</div>';
    }

    unset($table);
} else {
    ui_print_info_message([ 'no_close' => true, 'message' => __('There are no defined agents') ]);
    echo '<div style="text-align: right; float: right;">';
    echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';
        html_print_input_hidden('new_agent', 1);
        html_print_submit_button(__('Create agent'), 'crt', false, 'class="sub next"');
    echo '</form>';
    echo '</div>';
}
?>

<script type="text/javascript">
$(document).ready (function () {
    $("[class^='left']").mouseenter (function () {
        console.log($(this));
        $(".agent"+$(this)[0].className).css('visibility', '');
    }).mouseleave(function () {
        $(".agent"+$(this)[0].className).css('visibility', 'hidden');
    });
});
</script>