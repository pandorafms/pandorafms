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
check_login();

// Take some parameters (GET).
$offset = (int) get_parameter('offset');
$group_id = (int) get_parameter('group_id');
$ag_group = get_parameter('ag_group_refresh', -1);
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$recursion = (bool) get_parameter('recursion', false);
$disabled = get_parameter('disabled', 0);
$os = get_parameter('os', 0);

if ($ag_group == -1) {
    $ag_group = (int) get_parameter('ag_group', -1);
}

if (($ag_group == -1) && ($group_id != 0)) {
    $ag_group = $group_id;
}

if (! check_acl($config['id_user'], 0, 'AW') && ! check_acl($config['id_user'], 0, 'AD')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access agent manager'
    );
    include 'general/noaccess.php';
    exit;
}

enterprise_include_once('include/functions_policies.php');
require_once 'include/functions_agents.php';
require_once 'include/functions_users.php';

$search = get_parameter('search', '');

// Prepare the tab system to the future.
$tab = 'view';

// Setup tab.
$viewtab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente">'.html_print_image('images/operation.png', true, ['title' => __('View')]).'</a>';

$viewtab['operation'] = true;

$viewtab['active'] = false;

$onheader = ['view' => $viewtab];

// Header.
ui_print_page_header(__('Agents defined in %s', get_product_name()), 'images/agent_mc.png', false, '', true, $onheader);

// Perform actions.
$agent_to_delete = (int) get_parameter('borrar_agente');
$enable_agent = (int) get_parameter('enable_agent');
$disable_agent = (int) get_parameter('disable_agent');

if ($disable_agent != 0) {
    $server_name = db_get_row_sql('select server_name from tagente where id_agente = '.$disable_agent);
} else if ($enable_agent != 0) {
    $server_name = db_get_row_sql('select server_name from tagente where id_agente = '.$enable_agent);
}


$result = null;

if ($agent_to_delete) {
    $id_agente = $agent_to_delete;
    if (check_acl_one_of_groups(
        $config['id_user'],
        agents_get_all_groups_agent($id_agente),
        'AW'
    )
    ) {
        $id_agentes[0] = $id_agente;
        $result = agents_delete_agent($id_agentes);
    } else {
        // NO permissions.
        db_pandora_audit(
            'ACL Violation',
            "Trying to delete agent \'".agents_get_name($id_agente)."\'"
        );
        include 'general/noaccess.php';
        exit;
    }

    ui_print_result_message($result, __('Success deleted agent.'), __('Could not be deleted.'));

    if (enterprise_installed()) {
        // Check if the remote config file still exist.
        if (isset($config['remote_config'])) {
            enterprise_include_once('include/functions_config_agents.php');
            if (enterprise_hook('config_agents_has_remote_configuration', [$id_agente])) {
                ui_print_error_message(__('Maybe the files conf or md5 could not be deleted'));
            }
        }
    }
}

if ($enable_agent) {
    $result = db_process_sql_update('tagente', ['disabled' => 0], ['id_agente' => $enable_agent]);
    $alias = agents_get_alias($enable_agent);

    if ($result) {
        // Update the agent from the metaconsole cache.
        enterprise_include_once('include/functions_agents.php');
        $values = ['disabled' => 0];
        enterprise_hook('agent_update_from_cache', [$enable_agent, $values, $server_name]);
        config_agents_update_config_token($enable_agent, 'standby', 0);
        db_pandora_audit('Agent management', 'Enable  '.$alias);
    } else {
        db_pandora_audit('Agent management', 'Fail to enable '.$alias);
    }

    ui_print_result_message(
        $result,
        __('Successfully enabled'),
        __('Could not be enabled')
    );
}

if ($disable_agent) {
    $result = db_process_sql_update('tagente', ['disabled' => 1], ['id_agente' => $disable_agent]);
    $alias = agents_get_alias($disable_agent);

    if ($result) {
        // Update the agent from the metaconsole cache.
        enterprise_include_once('include/functions_agents.php');
        $values = ['disabled' => 1];
        enterprise_hook('agent_update_from_cache', [$disable_agent, $values, $server_name]);
        config_agents_update_config_token($disable_agent, 'standby', 1);

        db_pandora_audit('Agent management', 'Disable  '.$alias);
    } else {
        db_pandora_audit('Agent management', 'Fail to disable '.$alias);
    }

    ui_print_result_message(
        $result,
        __('Successfully disabled'),
        __('Could not be disabled')
    );
}

echo "<table cellpadding='4' cellspacing='4' class='databox filters' width='100%' style='font-weight: bold; margin-bottom: 10px;'>
	<tr>";
echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'>";

echo '<td>';

echo __('Group').'&nbsp;';
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AR') && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

html_print_select_groups(false, 'AR', $return_all_group, 'ag_group', $ag_group, 'this.form.submit();', '', 0, false, false, true, '', false);

echo '<td>';
echo __('Show Agents').'&nbsp;';
$fields = [
    2 => __('Everyone'),
    1 => __('Only disabled'),
    0 => __('Only enabled'),
];
html_print_select($fields, 'disabled', $disabled, 'this.form.submit()');

echo '</td>';

echo '<td>';
echo __('Operative System').'&nbsp;';

$pre_fields = db_get_all_rows_sql('select distinct(tagente.id_os),tconfig_os.name from tagente,tconfig_os where tagente.id_os = tconfig_os.id_os');
$fields = [];

foreach ($pre_fields as $key => $value) {
        $fields[$value['id_os']] = $value['name'];
}

html_print_select($fields, 'os', $os, 'this.form.submit()', 'All', 0);

echo '</td>';

echo '<td>';
echo __('Recursion').'&nbsp;';
html_print_checkbox('recursion', 1, $recursion, false, false, 'this.form.submit()');

echo '</td><td>';
echo __('Search').'&nbsp;';
html_print_input_text('search', $search, '', 12);

echo ui_print_help_tip(__('Search filter by alias, name, description, IP address or custom fields content'), true);

echo '</td><td>';
echo "<input name='srcbutton' type='submit' class='sub search' value='".__('Search')."'>";
echo '</form>';
echo '<td>';
echo '</tr></table>';

$order_collation = '';
switch ($config['dbtype']) {
    case 'mysql':
        $order_collation = '';
        $order_collation = 'COLLATE utf8_general_ci';
    break;

    case 'postgresql':
    case 'oracle':
        $order_collation = '';
    break;

    default:
        // Default.
    break;
}

$selected = true;
$selectNameUp = false;
$selectNameDown = false;
$selectOsUp = false;
$selectOsDown = false;
$selectGroupUp = false;
$selectGroupDown = false;
switch ($sortField) {
    case 'remote':
        switch ($sort) {
            case 'up':
                $selectRemoteUp = $selected;
                $order = [
                    'field'  => 'remote ',
                    'field2' => 'nombre '.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectRemoteDown = $selected;
                $order = [
                    'field'  => 'remote ',
                    'field2' => 'nombre '.$order_collation,
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
                    'field'  => 'alias '.$order_collation,
                    'field2' => 'alias '.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field'  => 'alias '.$order_collation,
                    'field2' => 'alias '.$order_collation,
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
                    'field2' => 'alias '.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectOsDown = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias '.$order_collation,
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
                    'field2' => 'alias '.$order_collation,
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectGroupDown = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias '.$order_collation,
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    default:
        $selectNameUp = $selected;
        $selectNameDown = '';
        $selectOsUp = '';
        $selectOsDown = '';
        $selectGroupUp = '';
        $selectGroupDown = '';
        $order = [
            'field'  => 'alias '.$order_collation,
            'field2' => 'alias '.$order_collation,
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
        $search_sql = ' AND ( LOWER(nombre) '.$order_collation."
			LIKE LOWER('%$search%') OR tagente.id_agente = $aux";
        if (count($id) >= 2) {
            for ($i = 1; $i < count($id); $i++) {
                $aux = $id[$i]['id_agent'];
                $search_sql .= " OR tagente.id_agente = $aux";
            }
        }

        $search_sql .= ')';
    } else {
        $search_sql = ' AND ( nombre '.$order_collation."
			LIKE LOWER('%$search%') OR alias ".$order_collation."
			LIKE LOWER('%$search%') OR comentarios ".$order_collation." LIKE LOWER('%$search%') 
			OR EXISTS (SELECT * FROM tagent_custom_data 
			WHERE id_agent = id_agente AND description LIKE '%$search%'))";
    }
}

if ($disabled == 1) {
    $search_sql .= ' AND disabled = '.$disabled.$search_sql;
} else {
    if ($disabled == 0) {
        $search_sql .= ' AND disabled = 0'.$search_sql;
    }
}

if ($os != 0) {
    $search_sql .= ' AND id_os = '.$os;
}

$user_groups_to_sql = '';
// Show only selected groups.
if ($ag_group > 0) {
    $ag_groups = [];
    $ag_groups = (array) $ag_group;
    if ($recursion) {
        $ag_groups = groups_get_id_recursive($ag_group, true);
    }

    $user_groups_to_sql = implode(',', $ag_groups);
} else {
    // Concatenate AW and AD permisions to get all the possible groups where the user can manage.
    $user_groupsAW = users_get_groups($config['id_user'], 'AW');
    $user_groupsAD = users_get_groups($config['id_user'], 'AD');

    $user_groups = ($user_groupsAW + $user_groupsAD);
    $user_groups_to_sql = implode(',', array_keys($user_groups));
}

$sql = sprintf(
    'SELECT COUNT(DISTINCT(tagente.id_agente))
	FROM tagente LEFT JOIN tagent_secondary_group tasg
		ON tagente.id_agente = tasg.id_agent
	WHERE (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))
		%s',
    $user_groups_to_sql,
    $user_groups_to_sql,
    $search_sql
);

$total_agents = db_get_sql($sql);

$sql = sprintf(
    'SELECT *
	FROM tagente LEFT JOIN tagent_secondary_group tasg
		ON tagente.id_agente = tasg.id_agent
	WHERE (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))
		%s
	GROUP BY tagente.id_agente
	ORDER BY %s %s, %s %s
	LIMIT %d, %d',
    $user_groups_to_sql,
    $user_groups_to_sql,
    $search_sql,
    $order['field'],
    $order['order'],
    $order['field2'],
    $order['order'],
    $offset,
    $config['block_size']
);

$agents = db_get_all_rows_sql($sql);

// Delete rnum row generated by oracle_recode_query() function.
if (($config['dbtype'] == 'oracle') && ($agents !== false)) {
    for ($i = 0; $i < count($agents); $i++) {
        unset($agents[$i]['rnum']);
    }
}

// Prepare pagination.
ui_pagination($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&recursion=$recursion&search=$search&sort_field=$sortField&sort=$sort&disabled=$disabled&os=$os", $offset);

if ($agents !== false) {
    // Urls to sort the table.
    if ($config['language'] == 'ja'
        || $config['language'] == 'zh_CN'
        || $own_info['language'] == 'ja'
        || $own_info['language'] == 'zh_CN'
    ) {
        // Adds a custom font size for Japanese and Chinese language.
        $custom_font_size = 'custom_font_size';
    }

    $url_up_agente = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=name&sort=up&disabled=$disabled';
    $url_down_agente = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=name&sort=down&disabled=$disabled';
    $url_up_remote = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=remote&sort=up&disabled=$disabled';
    $url_down_remote = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=remote&sort=down&disabled=$disabled';
    $url_up_os = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=os&sort=up&disabled=$disabled';
    $url_down_os = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=os&sort=down&disabled=$disabled';
    $url_up_group = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=group&sort=up&disabled=$disabled';
    $url_down_group = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=group&sort=down&disabled=$disabled';


    echo "<table cellpadding='0' id='agent_list' cellspacing='0' width='100%' class='info_table'>";
    echo '<thead><tr>';
    echo '<th>'.__('Agent name').ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectNameUp, $selectNameDown).'</th>';
    echo "<th title='".__('Remote agent configuration')."'>".__('R').ui_get_sorting_arrows($url_up_remote, $url_down_remote, $selectRemoteUp, $selectRemoteDown).'</th>';
    echo '<th>'.__('OS').ui_get_sorting_arrows($url_up_os, $url_down_os, $selectOsUp, $selectOsDown).'</th>';
    echo '<th>'.__('Type').'</th>';
    echo '<th>'.__('Group').ui_get_sorting_arrows($url_up_group, $url_down_group, $selectGroupUp, $selectGroupDown).'</th>';
    echo '<th>'.__('Description').'</th>';
    echo "<th style='text-align:left'>".__('Actions').'</th>';
    echo '</tr></thead>';
    $color = 1;

    $rowPair = true;
    $iterator = 0;
    foreach ($agents as $agent) {
        // Begin Update tagente.remote 0/1 with remote agent function return.
        if (enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']])) {
            db_process_sql_update('tagente', ['remote' => 1], 'id_agente = '.$agent['id_agente'].'');
        } else {
            db_process_sql_update('tagente', ['remote' => 0], 'id_agente = '.$agent['id_agente'].'');
        }

            // End Update tagente.remote 0/1 with remote agent function return.
        $all_groups = agents_get_all_groups_agent($agent['id_agente'], $agent['id_grupo']);
        $check_aw = check_acl_one_of_groups($config['id_user'], $all_groups, 'AW');
        $check_ad = check_acl_one_of_groups($config['id_user'], $all_groups, 'AD');

        $cluster = db_get_row_sql('select id from tcluster where id_agent = '.$agent['id_agente']);

        // Do not show the agent if there is not enough permissions.
        if (!$check_aw && !$check_ad) {
            continue;
        }

        if ($color == 1) {
            $tdcolor = 'datos';
            $color = 0;
        } else {
            $tdcolor = 'datos2';
            $color = 1;
        }


        if ($rowPair) {
            $rowclass = 'rowPair';
        } else {
            $rowclass = 'rowOdd';
        }

        $rowPair = !$rowPair;
        $iterator++;
        // Agent name.
        echo "<tr class='$rowclass'><td class='$tdcolor' width='40%'>";
        if ($agent['disabled']) {
            echo '<em>';
        }

        echo '<span class="left">';
        echo '<strong>';

        if ($check_aw) {
            $main_tab = 'main';
        } else {
            $main_tab = 'module';
        }

        if ($agent['alias'] == '') {
            $agent['alias'] = $agent['nombre'];
        }

        if ($agent['id_os'] == 100) {
            $cluster = db_get_row_sql('select id from tcluster where id_agent = '.$agent['id_agente']);
            echo '<a href="index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$cluster['id'].'&step=1&update=1">'.$agent['alias'].'</a>';
        } else {
            echo '<a alt ='.$agent['nombre']." href='index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=$main_tab&
			id_agente=".$agent['id_agente']."'>".'<span class="'.$custom_font_size.' title ="'.$agent['nombre'].'">'.$agent['alias'].'</span>'.'</a>';
        }

        echo '</strong>';

        $in_planned_downtime = db_get_sql(
            'SELECT executed FROM tplanned_downtime 
			INNER JOIN tplanned_downtime_agents ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
			WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1'
        );

        if ($agent['disabled']) {
            ui_print_help_tip(__('Disabled'));

            if (!$in_planned_downtime) {
                echo '</em>';
            }
        }

        if ($agent['quiet']) {
            echo '&nbsp;';
            html_print_image('images/dot_blue.png', false, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']);
        }

        if ($in_planned_downtime) {
            ui_print_help_tip(__('Agent in planned downtime'), false, 'images/minireloj-16.png');

            echo '</em>';
        }

        echo '</span><div class="left actions" style="visibility: hidden; clear: left">';
        if ($check_aw) {
            if ($agent['id_os'] == 100) {
                $cluster = db_get_row_sql('select id from tcluster where id_agent = '.$agent['id_agente']);
                echo '<a href="index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_builder&id_cluster='.$cluster['id'].'&step=1&update=1">'.__('Edit').'</a>';
                echo ' | ';
            } else {
                echo '<a href="index.php?sec=gagente&
				sec2=godmode/agentes/configurar_agente&tab=main&
				id_agente='.$agent['id_agente'].'">'.__('Edit').'</a>';
                echo ' | ';
            }
        }

        if ($agent['id_os'] != 100) {
            echo '<a href="index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=module&
			id_agente='.$agent['id_agente'].'">'.__('Modules').'</a>';
            echo ' | ';
        }

        echo '<a href="index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=alert&
			id_agente='.$agent['id_agente'].'">'.__('Alerts').'</a>';
        echo ' | ';

        if ($agent['id_os'] == 100) {
            echo '<a href="index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_view&id='.$cluster['id'].'">'.__('View').'</a>';
        } else {
            echo '<a href="index.php?sec=estado
			&sec2=operation/agentes/ver_agente
			&id_agente='.$agent['id_agente'].'">'.__('View').'</a>';
        }

        echo '</div>';
        echo '</td>';

        echo "<td align='left' class='$tdcolor'>";
        // Has remote configuration ?
        if (enterprise_installed()) {
            enterprise_include_once('include/functions_config_agents.php');
            if (enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']])) {
                echo "<a href='index.php?".'sec=gagente&'.'sec2=godmode/agentes/configurar_agente&'.'tab=remote_configuration&'.'id_agente='.$agent['id_agente']."&disk_conf=1'>";
                echo html_print_image('images/application_edit.png', true, ['align' => 'middle', 'title' => __('Edit remote config')]);
                echo '</a>';
            }
        }

        echo '</td>';

        // Operating System icon.
        echo "<td class='$tdcolor' align='left' valign='middle'>";
        ui_print_os_icon($agent['id_os'], false);
        echo '</td>';

        // Type agent (Networt, Software or Satellite).
        echo "<td class='$tdcolor' align='left' valign='middle'>";
        echo ui_print_type_agent_icon(
            $agent['id_os'],
            $agent['ultimo_contacto_remoto'],
            $agent['ultimo_contacto'],
            $agent['remote'],
            $agent['agent_version']
        );
        echo '</td>';


        // Group icon and name.
        echo "<td class='$tdcolor' align='left' valign='middle'>".ui_print_group_icon($agent['id_grupo'], true).'</td>';

        // Description.
        echo "<td class='".$tdcolor."f9'><span class='".$custom_font_size."'>".ui_print_truncate_text($agent['comentarios'], 'description', true, true, true, '[&hellip;]').'</span></td>';

        // Action
        // When there is only one element in page it's necesary go back page.
        if ((count($agents) == 1) && ($offset >= $config['block_size'])) {
            $offsetArg = ($offset - $config['block_size']);
        } else {
            $offsetArg = $offset;
        }

        echo "<td class='$tdcolor action_buttons' align='left' style='width:7%' valign='middle'>";

        if ($agent['disabled']) {
            echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
			enable_agent=".$agent['id_agente']."&group_id=$ag_group&recursion=$recursion&search=$search&offset=$offsetArg&sort_field=$sortField&sort=$sort&disabled=$disabled'";

            if ($agent['id_os'] != 100) {
                echo '>';
            } else {
                echo ' onClick="if (!confirm(\' '.__('You are going to enable a cluster agent. Are you sure?').'\')) return false;">';
            }

            echo html_print_image('images/lightbulb_off.png', true, ['alt' => __('Enable agent'), 'title' => __('Enable agent')]).'</a>';
        } else {
            echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
			disable_agent=".$agent['id_agente']."&group_id=$ag_group&recursion=$recursion&search=$search&offset=$offsetArg&sort_field=$sortField&sort=$sort&disabled=$disabled'";
            if ($agent['id_os'] != 100) {
                echo '>';
            } else {
                echo ' onClick="if (!confirm(\' '.__('You are going to disable a cluster agent. Are you sure?').'\')) return false;">';
            }

            echo html_print_image('images/lightbulb.png', true, ['alt' => __('Disable agent'), 'title' => __('Disable agent')]).'</a>';
        }

        if ($check_aw) {
            echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
			borrar_agente=".$agent['id_agente']."&group_id=$ag_group&recursion=$recursion&search=$search&offset=$offsetArg&sort_field=$sortField&sort=$sort&disabled=$disabled'";

            if ($agent['id_os'] != 100) {
                echo ' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
            } else {
                echo ' onClick="if (!confirm(\' '.__('WARNING! - You are going to delete a cluster agent. Are you sure?').'\')) return false;">';
            }

            echo html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
        }

        echo '</td>';
    }

    echo '</table>';
    ui_pagination($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&recursion=$recursion&search=$search&sort_field=$sortField&sort=$sort&disabled=$disabled&os=$os", $offset);
    echo "<table width='100%'><tr><td align='right'>";
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined agents') ]);
}

if (check_acl($config['id_user'], 0, 'AW')) {
    // Create agent button.
    echo '<div style="text-align: right;">';
    echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
    html_print_input_hidden('new_agent', 1);
    html_print_submit_button(
        __('Create agent'),
        'crt-2',
        false,
        'class="sub next"'
    );
    echo '</form>';
    echo '</div>';
}

echo '</td></tr></table>';
?>

<script type="text/javascript">
    $(document).ready (function () {
        $("table#agent_list tr").hover (function () {
                $(".actions", this).css ("visibility", "");
            },
            function () {
                $(".actions", this).css ("visibility", "hidden");
        });
        
        $("#ag_group").click (
            function () {
                $(this).css ("width", "auto");
                $(this).css ("min-width", "100px");
            });
            
        $("#ag_group").blur (function () {
            $(this).css ("width", "100px");
        });
        
    });
</script>
