<?php
/**
 * Agent status - general overview.
 *
 * @category   Agent view status.
 * @package    Pandora FMS
 * @subpackage Classic agent management view.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

require_once 'include/functions_agents.php';

require_once $config['homedir'].'/include/functions_graph.php';
include_graphs_dependencies();
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_incidents.php';
require_once $config['homedir'].'/include/functions_reporting_html.php';

require_once $config['homedir'].'/include/functions_clippy.php';

check_login();

$strict_user = (bool) db_get_value(
    'strict_acl',
    'tusuario',
    'id_user',
    $config['id_user']
);

$id_agente = get_parameter_get('id_agente', -1);

$agent = db_get_row('tagente', 'id_agente', $id_agente);

if (empty($agent['server_name'])) {
    ui_print_error_message(
        __('The agent has not assigned server. Maybe agent does not run fine.')
    );
}

if ($agent === false) {
    ui_print_error_message(__('There was a problem loading agent'));
    return;
}

$is_extra = enterprise_hook('policies_is_agent_extra_policy', [$id_agente]);

if ($is_extra === ENTERPRISE_NOT_HOOK) {
    $is_extra = false;
}

if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AR')
    && ! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')
    && !$is_extra
) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Agent General Information'
    );
    include_once 'general/noaccess.php';
    return;
}

$alive_animation = agents_get_status_animation(
    agents_get_interval_status($agent, false)
);

/*
 * START: TABLE AGENT BUILD.
 */

$agent_name = ui_print_agent_name(
    $agent['id_agente'],
    true,
    500,
    'font-size: medium;font-weight:bold',
    true
);
$in_planned_downtime = db_get_sql(
    'SELECT executed FROM tplanned_downtime 
	INNER JOIN tplanned_downtime_agents 
	ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
	WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1'
);

if ($agent['disabled']) {
    if ($in_planned_downtime) {
        $agent_name = '<em>'.$agent_name.ui_print_help_tip(__('Disabled'), true);
    } else {
        $agent_name = '<em>'.$agent_name.'</em>'.ui_print_help_tip(__('Disabled'), true);
    }
} else if ($agent['quiet']) {
    if ($in_planned_downtime) {
        $agent_name = "<em'>".$agent_name.'&nbsp;'.html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']);
    } else {
        $agent_name = "<em'>".$agent_name.'&nbsp;'.html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']).'</em>';
    }
} else {
    $agent_name = $agent_name;
}

if ($in_planned_downtime && !$agent['disabled'] && !$agent['quiet']) {
    $agent_name .= '<em>&nbsp;'.ui_print_help_tip(
        __('Agent in planned downtime'),
        true,
        'images/minireloj-16.png'
    ).'</em>';
} else if (($in_planned_downtime && !$agent['disabled'])
    || ($in_planned_downtime && !$agent['quiet'])
) {
    $agent_name .= '&nbsp;'.ui_print_help_tip(
        __('Agent in planned downtime'),
        true,
        'images/minireloj-16.png'
    ).'</em>';
}

$table_agent_header = '<div class="agent_details_agent_alias">';
$table_agent_header .= $agent_name;
$table_agent_header .= '</div>';
$table_agent_header .= '<div class="agent_details_agent_name">';
if (!$config['show_group_name']) {
    $table_agent_header .= ui_print_group_icon(
        $agent['id_grupo'],
        true,
        'groups_small',
        'padding-right: 6px;'
    );
}

$table_agent_header .= '</div>';

$status_img = agents_detail_view_status_img(
    $agent['critical_count'],
    $agent['warning_count'],
    $agent['unknown_count'],
    $agent['total_count'],
    $agent['notinit_count']
);

$table_agent_header .= '<div class="icono_right">'.$status_img.'</div>';

// Fixed width non interactive charts.
$status_chart_width = 180;
$graph_width = 180;

$table_agent_graph = '<div id="status_pie" style="width: '.$status_chart_width.'px;">';
$table_agent_graph .= graph_agent_status(
    $id_agente,
    $graph_width,
    $graph_width,
    true,
    false,
    false,
    true
);
$table_agent_graph .= '</div>';

$table_agent_os = '<p>'.ui_print_os_icon(
    $agent['id_os'],
    false,
    true,
    true,
    false,
    false,
    false,
    ['title' => __('OS').': '.get_os_name($agent['id_os'])]
);
$table_agent_os .= (empty($agent['os_version'])) ? get_os_name((int) $agent['id_os']) : $agent['os_version'].'</p>';

$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);

foreach ($addresses as $k => $add) {
    if ($add == $address) {
        unset($addresses[$k]);
    }
}

if (!empty($address)) {
    $table_agent_ip = '<p>'.html_print_image('images/world.png', true, ['title' => __('IP address')]);
    $table_agent_ip .= '<span style="vertical-align:top; display: inline-block;">';
    $table_agent_ip .= empty($address) ? '<em>'.__('N/A').'</em>' : $address;
    $table_agent_ip .= '</span></p>';
}

$table_agent_version = '<p>'.html_print_image('images/version.png', true, ['title' => __('Agent Version')]);
$table_agent_version .= '<span style="vertical-align:top; display: inline-block;">';
$table_agent_version .= empty($agent['agent_version']) ? '<i>'.__('N/A').'</i>' : $agent['agent_version'];
$table_agent_version .= '</span></p>';

$table_agent_description = '<p>'.html_print_image(
    'images/default_list.png',
    true,
    ['title' => __('Description')]
);
$table_agent_description .= '<span style="vertical-align:top; display: inline-block;">';
$table_agent_description .= empty($agent['comentarios']) ? '<em>'.__('N/A').'</em>' : $agent['comentarios'];
$table_agent_description .= '</span></p>';

$table_agent_count_modules = reporting_tiny_stats(
    $agent,
    true,
    'agent',
    // Useless.
    ':',
    true
);

$has_remote_conf = enterprise_hook(
    'config_agents_has_remote_configuration',
    [$agent['id_agente']]
);

if ($has_remote_conf) {
    $remote_cfg = '<p>'.html_print_image('images/remote_configuration.png', true);
    $remote_cfg .= __('Remote configuration enabled').'</p>';
} else {
    $remote_cfg = '';
}



// $table_agent_count_modules .= ui_print_help_tip(__('Agent statuses are re-calculated by the server, they are not  shown in real time.'), true);
$table_agent = '
    <div class="agent_details_header">
        '.$table_agent_header.'
    </div>
    <div class="agent_details_content">
        <div class="agent_details_graph">
            '.$table_agent_graph.'
            <div class="agent_details_bullets">
                '.$table_agent_count_modules.'
            </div>
        </div>
        <div class="agent_details_info">
            '.$alive_animation.$table_agent_os.$table_agent_ip.$table_agent_version.$table_agent_description.$remote_cfg.'
        </div>
    </div>';

/*
 * END: TABLE AGENT BUILD.
 */

/*
 *START: TABLE CONTACT BUILD.
 */

$table_contact = new stdClass();
$table_contact->id = 'agent_contact_main';
$table_contact->width = '100%';
$table_contact->cellspacing = 0;
$table_contact->cellpadding = 0;
$table_contact->class = 'white_table white_table_no_border';
$table_contact->style[0] = 'width: 30%;';
$table_contact->style[1] = 'width: 70%;';
$table_contact->headstyle[1] = 'padding-top:6px; padding-bottom:6px;padding-right: 10px;';

$table_contact->head[0] = ' <span>'.__('Agent contact').'</span>';

$buttons_refresh_agent_view = '<div class="buttons_agent_view">';
$buttons_refresh_agent_view .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">'.html_print_image('images/refresh.png', true, ['title' => __('Refresh data'), 'alt' => '']).'</a><br>';
if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
    $buttons_refresh_agent_view .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">'.html_print_image('images/target.png', true, ['title' => __('Force remote checks'), 'alt' => '']).'</a>';
}

$buttons_refresh_agent_view .= '</div>';

$table_contact->head[1] = $buttons_refresh_agent_view;

$data = [];
$data[0] = '<b>'.__('Interval').'</b>';
$data[1] = human_time_description_raw($agent['intervalo']);
$table_contact->data[] = $data;

$data = [];
$data[0] = '<b>'.__('Last contact').' / '.__('Remote').'</b>';
$data[1] = ui_print_timestamp($agent['ultimo_contacto'], true);
$data[1] .= ' / ';

if ($agent['ultimo_contacto_remoto'] == '01-01-1970 00:00:00') {
    $data[1] .= __('Never');
} else {
    $data[1] .= date_w_fixed_tz($agent['ultimo_contacto_remoto']);
}

$table_contact->data[] = $data;


$data = [];
$data[0] = '<b>'.__('Next contact').'</b>';
$progress = agents_get_next_contact($id_agente);
$data[1] = ui_progress(
    $progress,
    '100%',
    1.8,
    '#BBB',
    true,
    floor(($agent['intervalo'] * (100 - $progress) / 100)).' s',
    [
        'page'     => 'operation/agentes/ver_agente',
        'interval' => (100 / $agent['intervalo']),
        'data'     => [
            'id_agente'       => $id_agente,
            'refresh_contact' => 1,
        ],

    ]
);

if ($progress > 100) {
    $data[0] .= clippy_context_help('agent_out_of_limits');
}

$table_contact->data[] = $data;

$data = [];
$data[0] = '<b>'.__('Group').'</b>';
$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$agent['id_grupo'].'">'.groups_get_name($agent['id_grupo']).'</a>';
$table_contact->data[] = $data;

$data = [];
$data[0] = '<b>'.__('Secondary groups').'</b>';
$secondary_groups = enterprise_hook('agents_get_secondary_groups', [$id_agente]);
if (!$secondary_groups) {
    $data[1] = '<em>'.__('N/A').'</em>';
} else {
    $secondary_links = [];
    foreach ($secondary_groups['for_select'] as $id => $name) {
        $secondary_links[] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id.'">'.$name.'</a>';
    }

    $data[1] = implode(', ', $secondary_links);
}

$table_contact->data[] = $data;

if (enterprise_installed()) {
    $data = [];
    $data[0] = '<b>'.__('Parent').'</b>';
    if ($agent['id_parent'] == 0) {
        $data[1] = '<em>'.__('N/A').'</em>';
    } else {
        $data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent['id_parent'].'">'.agents_get_alias($agent['id_parent']).'</a>';
    }

    $table_contact->data[] = $data;
}

/*
 * END: TABLE CONTACT BUILD
 */

/*
 * START: TABLE DATA BUILD
 */

$table_data = new stdClass();
$table_data->id = 'agent_data_main';
$table_data->width = '100%';
$table_data->cellspacing = 0;
$table_data->cellpadding = 0;
$table_data->class = 'box-shadow white_table white_table_droppable align-top';
$table_data->style = array_fill(0, 3, 'width: 25%;');

$table_data->head[0] = html_print_image(
    'images/arrow_down_green.png',
    true,
    $options
);
$table_data->head[0] .= ' <span style="vertical-align: middle; font-weight:bold; padding-left:20px">'.__('Agent info').'</span>';
$table_data->head_colspan[0] = 4;

// Gis and url address.
$data_opcional = [];
// Position Information.
if ($config['activate_gis']) {
    $data_opcional[] = '<b>'.__('Position (Long, Lat)').'</b>';
        $dataPositionAgent = gis_get_data_last_position_agent(
            $agent['id_agente']
        );

    if ($dataPositionAgent === false) {
        $data_opcional[] = __('There is no GIS data.');
    } else {
        $data_opcional[] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
        if ($dataPositionAgent['description'] != '') {
            $data_opcional[] .= $dataPositionAgent['description'];
        } else {
            $data_opcional[] .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
        }

        $data_opcional[] .= '</a>';
    }

        array_push($data_opcional);
}

// If the url description is set.
if ($agent['url_address'] != '') {
    // $data_opcional = [];
    $data_opcional[] = '<b>'.__('Url address').'</b>';
    if ($agent['url_address'] != '') {
        $data_opcional[] = '<a href='.$agent['url_address'].'>'.$agent['url_address'].'</a>';
    }
}


// Other IP address and timezone offset.
if (!empty($addresses)) {
    // $data_opcional = [];
    $data_opcional[] = '<b>'.__('Other IP addresses').'</b>';
    if (!empty($addresses)) {
        $data_opcional[] = '<div style="overflow-y: scroll; max-height:50px;">'.implode('<br>', $addresses).'</div>';
    }
}

// Timezone Offset.
if ($agent['timezone_offset'] != 0) {
    $data_opcional[] = '<b>'.__('Timezone Offset').'</b>';
    if ($agent['timezone_offset'] != 0) {
        $data_opcional[] = $agent['timezone_offset'];
    }
}


$data_opcional = array_chunk($data_opcional, 4);
foreach ($data_opcional as $key => $value) {
    $table_data->data[] = $data_opcional[$key];
}

// Custom fields.
$fields = db_get_all_rows_filter(
    'tagent_custom_fields',
    ['display_on_front' => 1]
);
if ($fields === false) {
    $fields = [];
}

$custom_fields = [];
foreach ($fields as $field) {
    $custom_value = db_get_all_rows_sql(
        'select tagent_custom_data.description,tagent_custom_fields.is_password_type from tagent_custom_fields 
        INNER JOIN tagent_custom_data ON tagent_custom_fields.id_field = tagent_custom_data.id_field where tagent_custom_fields.id_field = '.$field['id_field'].' and tagent_custom_data.id_agent = '.$id_agente
    );

    if ($custom_value[0]['description'] !== false && $custom_value[0]['description'] != '') {
        $data = [];

        $data[0] = '<b>'.$field['name'].ui_print_help_tip(__('Custom field'), true).'</b>';
        $custom_value[0]['description'] = ui_bbcode_to_html($custom_value[0]['description']);

        if ($custom_value[0]['is_password_type']) {
                $data[1] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
        } else {
            $data[1] = $custom_value[0]['description'];
        }

        $custom_fields[] = $data;
    }
}

$custom_fields_count = count($custom_fields);
for ($i = 0; $i < $custom_fields_count; $i++) {
    $first_column = $custom_fields[$i];
    $j = ($i + 1);
    $second_column = $custom_fields[$j];

    if (is_array($second_column)) {
        $columns = array_merge($first_column, $second_column);
    } else {
        $columns = $first_column;
        $filas = count($table_data->data);
        $table_data->colspan[$filas][1] = 3;
    }

    $table_data->data[] = $columns;

    $i++;
}

/*
 * END: TABLE DATA BUILD
 */

/*
 * START: ACCESS RATE GRAPH
 */

$access_agent = db_get_value_sql(
    'SELECT COUNT(id_agent)
    FROM tagent_access
    WHERE id_agent = '.$id_agente
);

if ($config['agentaccess'] && $access_agent > 0) {
    $table_access_rate = '<div class="white_table_graph" id="table_access_rate">
                            <div class="white_table_graph_header">'.html_print_image(
        'images/arrow_down_green.png',
        true
    ).'<span>'.__('Agent access rate (24h)').'</span></div>
    <div class="white_table_graph_content h80p">
'.graphic_agentaccess(
        $id_agente,
        '95%',
        100,
        SECONDS_1DAY,
        true
    ).'</div>
</div>';
}

/*
 * END: ACCESS RATE GRAPH
 */

/*
 * START: TABLE INCIDENTS
 */

$last_incident = db_get_row_sql(
    sprintf(
        'SELECT * FROM tincidencia
	     WHERE estado IN (0,1)
		 AND id_agent = %d
        ORDER BY actualizacion DESC',
        $id_agente
    )
);

if ($last_incident != false) {
    $table_incident->id = 'agent_incident_main';
    $table_incident->width = '100%';
    $table_incident->cellspacing = 0;
    $table_incident->cellpadding = 0;
    $table_incident->class = 'white_table';
    $table_incident->style = array_fill(0, 3, 'width: 25%;');

    $table_incident->head[0] = ' <span><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident['id_incidencia'].'">'.__('Active incident on this agent').'</a></span>';
    $table_incident->head_colspan[0] = 4;

    $data = [];
    $data[0] = '<b>'.__('Author').'</b>';
    $data[1] = $last_incident['id_creator'];
    $data[2] = '<b>'.__('Timestamp').'</b>';
    $data[3] = $last_incident['inicio'];
    $table_incident->data[] = $data;

    $data = [];
    $data[0] = '<b>'.__('Title').'</b>';
    $data[1] = '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident['id_incidencia'].'">'.$last_incident['titulo'].'</a>';
    $data[2] = '<b>'.__('Priority').'</b>';
    $data[3] = incidents_print_priority_img($last_incident['prioridad'], true);
    $table_incident->data[] = $data;
}

/*
 * END: TABLE INCIDENTS
 */

/*
 * START: TABLE INTERFACES
 */

$network_interfaces_by_agents = agents_get_network_interfaces([$agent]);

$network_interfaces = [];
if (!empty($network_interfaces_by_agents) && !empty($network_interfaces_by_agents[$id_agente])) {
    $network_interfaces = $network_interfaces_by_agents[$id_agente]['interfaces'];
}

if (!empty($network_interfaces)) {
    $table_interface = new stdClass();
    $table_interface->id = 'agent_interface_info';
    $table_interface->class = 'info_table';
    $table_interface->width = '100%';
    $table_interface->style = [];
    $table_interface->style['interface_status'] = 'width: 30px;padding-top:0px;padding-bottom:0px;';
    $table_interface->style['interface_graph'] = 'width: 20px;padding-top:0px;padding-bottom:0px;';
    $table_interface->style['interface_event_graph'] = 'width: 100%;padding-top:0px;padding-bottom:0px;';
    $table_interface->align['interface_event_graph'] = 'right';
    // $table_interface->style['interface_event_graph'] = 'width: 5%;padding-top:0px;padding-bottom:0px;';
    $table_interface->align['interface_event_graph_text'] = 'left';
    $table_interface->style['interface_name'] = 'width: 10%;padding-top:0px;padding-bottom:0px;';
    $table_interface->align['interface_name'] = 'left';
    $table_interface->align['interface_ip'] = 'left';
    $table_interface->align['last_contact'] = 'left';
    $table_interface->style['last_contact'] = 'width: 40%;padding-top:0px;padding-bottom:0px;';
    $table_interface->style['interface_ip'] = 'width: 8%;padding-top:0px;padding-bottom:0px;';
    $table_interface->style['interface_mac'] = 'width: 12%;padding-top:0px;padding-bottom:0px;';

    $table_interface->head = [];
    $options = [
        'class' => 'closed',
        'style' => 'cursor:pointer;',
    ];
    $table_interface->data = [];
    $event_text_cont = 0;

    foreach ($network_interfaces as $interface_name => $interface) {
        if (!empty($interface['traffic'])) {
            $permission = check_acl_one_of_groups($config['id_user'], $all_groups, 'RR');

            if ($permission) {
                $params = [
                    'interface_name'     => $interface_name,
                    'agent_id'           => $id_agente,
                    'traffic_module_in'  => $interface['traffic']['in'],
                    'traffic_module_out' => $interface['traffic']['out'],
                ];
                $params_json = json_encode($params);
                $params_encoded = base64_encode($params_json);
                $win_handle = dechex(crc32($interface['status_module_id'].$interface_name));
                $graph_link = "<a href=\"javascript:winopeng_var('operation/agentes/interface_traffic_graph_win.php?params=";
                $graph_link .= $params_encoded."','";
                $graph_link .= $win_handle."', 1000, 650)\">";
                $graph_link .= html_print_image(
                    'images/chart_curve.png',
                    true,
                    ['title' => __('Interface traffic')]
                ).'</a>';
            } else {
                $graph_link = '';
            }
        } else {
            $graph_link = '';
        }

        $events_limit = 5000;
        $user_groups = users_get_groups($config['id_user'], 'ER');
        $user_groups_ids = array_keys($user_groups);
        if (empty($user_groups)) {
            $groups_condition = ' 1 = 0 ';
        } else {
            $groups_condition = ' id_grupo IN ('.implode(',', $user_groups_ids).') ';
        }

        if (!check_acl($config['id_user'], 0, 'PM')) {
            $groups_condition .= ' AND id_grupo != 0';
        }

        $status_condition = ' AND (estado = 0 OR estado = 1) ';
        $unixtime = (get_system_time() - SECONDS_1DAY);
        // Last hour.
        $time_condition = 'AND (utimestamp > '.$unixtime.')';
        // Tags ACLs.
        if ($id_group > 0 && in_array(0, $user_groups_ids)) {
            $group_array = (array) $id_group;
        } else {
            $group_array = $user_groups_ids;
        }

        $acl_tags = tags_get_acl_tags(
            $config['id_user'],
            $group_array,
            'ER',
            'event_condition',
            'AND',
            '',
            true,
            [],
            true
        );

        $id_modules_array = [];
        $id_modules_array[] = $interface['status_module_id'];

        $unixtime = (get_system_time() - SECONDS_1DAY);
        // Last hour.
        $time_condition = 'WHERE (te.utimestamp > '.$unixtime.')';

        $sqlEvents = sprintf(
            'SELECT *
			FROM tevento te
			INNER JOIN tagente_estado tae
				ON te.id_agentmodule = tae.id_agente_modulo
					AND tae.id_agente_modulo IN (%s)
			%s',
            implode(',', $id_modules_array),
            $time_condition
        );

        $sqlLast_contact = sprintf(
            '
			SELECT timestamp
			FROM tagente_estado
			WHERE id_agente_modulo = '.$interface['status_module_id']
        );

        $last_contact = db_get_all_rows_sql($sqlLast_contact);
        $last_contact = array_shift($last_contact);
        $last_contact = array_shift($last_contact);

        $events = db_get_all_rows_sql($sqlEvents);
        $text_event_header = __('Events info (24hr.)');
        if (!$events) {
            $no_events = ['color' => ['criticity' => 2]];
            $e_graph = reporting_get_event_histogram($no_events, $text_event_header);
        } else {
            $e_graph = reporting_get_event_histogram($events, $text_event_header);
        }

        $data = [];
        $data['interface_name'] = '<strong>'.$interface_name.'</strong>';
        $data['interface_status'] = $interface['status_image'];
        $data['interface_graph'] = $graph_link;
        $data['interface_ip'] = $interface['ip'];
        $data['interface_mac'] = $interface['mac'];
        $data['last_contact'] = __('Last contact: ').$last_contact;
        $data['interface_event_graph'] = $e_graph;
        if ($event_text_cont == 0) {
            $data['interface_event_graph_text'] = ui_print_help_tip('Module events graph', true);
            $event_text_cont++;
        } else {
            $data['interface_event_graph_text'] = '';
        }

        $table_interface->data[] = $data;
    }
}

/*
 * END: TABLE INTERFACES
 */

    // This javascript piece of code is used to make expandible
    // the body of the table.
?>
    <script type="text/javascript">
        $(document).ready (function () {

            $("#agent_data_main").find("thead").click (function () {
                close_table('#agent_data_main');
            })
            .css('cursor', 'pointer');

            $("#table_events").find(".white_table_graph_header").click (function () {
                close_table_white('#table_events');
            })
            .css('cursor', 'pointer');

            $("#table_access_rate").find(".white_table_graph_header").click (function () {
                close_table_white('#table_access_rate');
            })
            .css('cursor', 'pointer');
 
            function close_table(id){
                var arrow = $(id).find("thead").find("img");
                if (arrow.hasClass("closed")) {
                    arrow.removeClass("closed");
                    arrow.prop("src", "images/arrow_down_green.png");
                    $(id).find("tbody").show();
                } else {
                    arrow.addClass("closed");
                    arrow.prop("src", "images/arrow_right_green.png");
                    $(id).find("tbody").hide();
                }
            }

            function close_table_white(id){
                var arrow = $(id).find(".white_table_graph_header").find("img");
                if (arrow.hasClass("closed")) {
                    arrow.removeClass("closed");
                    arrow.prop("src", "images/arrow_down_green.png");
                    $(id).find(".white_table_graph_content").show();
                } else {
                    arrow.addClass("closed");
                    arrow.prop("src", "images/arrow_right_green.png");
                    $(id).find(".white_table_graph_content").hide();
                }
            }
            
        });
    </script>
<?php
// EVENTS.
if ($config['agentaccess'] && $access_agent > 0) {
    $extra_class = 'h80p';
} else {
    $extra_class = '';
}

$table_events = '<div class="white_table_graph" id="table_events">
            <div class="white_table_graph_header">'.html_print_image(
    'images/arrow_down_green.png',
    true
).'<span>'.__('Events (24h)').'</span></div>
            <div class="white_table_graph_content '.$extra_class.'">
'.graph_graphic_agentevents(
    $id_agente,
    100,
    45,
    SECONDS_1DAY,
    '',
    true,
    true
).'</div>
        </div>';

/*
 * EVENTS TABLE END.
 */

$agent_contact = html_print_table($table_contact, true);

if (empty($table_data->data)) {
    $agent_info = '';
} else {
    if (count($table_data->data) === 1 && $config['activate_gis'] && $dataPositionAgent === false) {
        $agent_info = '';
    } else {
        $agent_info = html_print_table($table_data, true);
    }
}

$agent_incidents = !isset($table_incident) ? '' : html_print_table($table_incident, true);

echo '<div id="agent_details_first_row">
    <div class="box-shadow agent_details_col agent_details_col_left">'.$table_agent.'</div>
    <div class="box-shadow agent_details_col agent_details_col_right">'.$agent_contact.'</div>
    </div>'.$agent_info;

// Show both graphs, events and access rate.
if ($table_access_rate) {
    echo '<div class="agent_access_rate_events">'.$table_access_rate.$table_events.'</div>';
} else {
    echo '<div style="width: 100%">'.$table_events.'</div>';
}

echo $agent_incidents;

if (isset($table_interface)) {
    ui_toggle(
        html_print_table($table_interface, true),
        '<b>'.__('Interface information (SNMP)').'</b>'
    );
}
