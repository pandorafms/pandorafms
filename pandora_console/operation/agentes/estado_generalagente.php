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
global $config;

require_once 'include/functions_agents.php';

require_once $config['homedir'].'/include/functions_graph.php';
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

if (empty($agent['server_name']) === true) {
    ui_print_error_message(
        __('The agent has not assigned server. Maybe agent does not run fine.')
    );
}

if ($agent === false) {
    ui_print_error_message(__('There was a problem loading agent'));
    return;
}

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') === false
    && check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent General Information'
    );
    include_once 'general/noaccess.php';
    return;
}

$alive_animation = agents_get_starmap($id_agente, 200, 50);

/*
 * START: TABLE AGENT BUILD.
 */
$agentCaptionAddedMessage = [];
$agentCaption = '<span class="subsection_header_title">'.ucfirst(agents_get_alias($agent['id_agente'])).'</span>';
$in_planned_downtime = (bool) db_get_sql(
    'SELECT executed FROM tplanned_downtime 
	INNER JOIN tplanned_downtime_agents 
	ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
	WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1'
);

if ((bool) $agent['disabled'] === true) {
    $agentCaptionAddedMessage[] = __('Disabled');
} else if ((bool) $agent['quiet'] === true) {
    $agentCaptionAddedMessage[] = __('Quiet');
}

if ($in_planned_downtime === true) {
    $agentCaptionAddedMessage[] = __('In scheduled downtime');
}

if (empty($agentCaptionAddedMessage) === false) {
    $agentCaption .= '&nbsp;<span class="result_info_text">('.implode(' - ', $agentCaptionAddedMessage).')</span>';
}

$agentIconGroup = ((bool) $config['show_group_name'] === false) ? ui_print_group_icon(
    $agent['id_grupo'],
    true,
    '',
    'padding-right: 6px;',
    true,
    false,
    false,
    '',
    true
) : '';

$agentIconStatus = agents_detail_view_status_img(
    $agent['critical_count'],
    $agent['warning_count'],
    $agent['unknown_count'],
    $agent['total_count'],
    $agent['notinit_count']
);

$agent_details_agent_caption = html_print_div(
    [
        'class'   => 'agent_details_agent_caption',
        'content' => $agentCaption,
    ],
    true
);

$agent_details_agent_data = html_print_div(
    [
        'class'   => 'agent_details_agent_data',
        'content' => $agentIconGroup,
    ],
    true
);

$agent_details_agent_status_image = html_print_div(
    [
        'class'   => 'icono_right',
        'content' => $agentIconStatus,
    ],
    true
);

$agentStatusHeader = html_print_div(
    [
        'class'   => 'agent_details_header',
        'content' => $agent_details_agent_caption.$agent_details_agent_data.$agent_details_agent_status_image,
    ],
    true
);

// Fixed width non interactive charts.
$status_chart_width = 150;
$graph_width = 150;

$table_status = new stdClass();
$table_status->id = 'agent_status_main';
$table_status->width = '100%';
$table_status->cellspacing = 0;
$table_status->cellpadding = 0;
$table_status->class = 'floating_form';
$table_status->style[0] = 'height: 32px; width: 30%; padding-right: 5px; text-align: end; vertical-align: top';
$table_status->style[1] = 'height: 32px; width: 70%; padding-left: 5px; font-weight: lighter; vertical-align: top';

$agentStatusGraph = html_print_div(
    [
        'id'      => 'status_pie',
        'style'   => 'width: '.$graph_width.'px;',
        'content' => graph_agent_status(
            $id_agente,
            $graph_width,
            $graph_width,
            true,
            false,
            false,
            true
        ),
    ],
    true
);

/*
    $table_agent_graph = '<div id="status_pie" style="width: '.$graph_width.'px;">';
    $table_agent_graph .= graph_agent_status(
    $id_agente,
    $graph_width,
    $graph_width,
    true,
    false,
    false,
    true
    );
$table_agent_graph .= '</div>';*/

/*
    $table_agent_os = '<p>'.ui_print_os_icon(
    $agent['id_os'],
    false,
    true,
    true,
    false,
    false,
    false,
    [
        'title' => get_os_name($agent['id_os']),
        'width' => '20px;',
    ]
    );
*/

$table_status->data['agent_os'][0] = __('OS');
$agentOS = [];
$agentOS[] = html_print_div([ 'content' => (empty($agent['os_version']) === true) ? get_os_name((int) $agent['id_os']) : $agent['os_version']], true);
$agentOS[] = html_print_div([ 'style' => 'width: 16px;padding-left: 5px', 'content' => ui_print_os_icon($agent['id_os'], false, true, true, false, false, false, ['width' => '16px'])], true);
$table_status->data['agent_os'][1] = html_print_div(['class' => 'agent_details_agent_data', 'content' => implode('', $agentOS)], true);

// $table_agent_os .= (empty($agent['os_version']) === true) ? get_os_name((int) $agent['id_os']) : $agent['os_version'].'</p>';
$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);

foreach ($addresses as $k => $add) {
    if ($add == $address) {
        unset($addresses[$k]);
    }
}

if (empty($address) === false) {
    $table_status->data['ip_address'][0] = __('IP address');
    $table_status->data['ip_address'][1] = (empty($address) === true) ? '<em>'.__('N/A').'</em>' : $address;
    /*
        $table_agent_ip = '<p>'.html_print_image(
        'images/world.png',
        true,
        [
            'title' => __('IP address'),
            'class' => 'invert_filter',
        ]
        );
        $table_agent_ip .= '<span class="align-top inline">';
        $table_agent_ip .= (empty($address) === true) ? '<em>'.__('N/A').'</em>' : $address;
        $table_agent_ip .= '</span></p>';
    */
}

$table_status->data['agent_version'][0] = __('Agent Version');
$table_status->data['agent_version'][1] = (empty($agent['agent_version']) === true) ? '<i>'.__('N/A').'</i>' : $agent['agent_version'];

$table_status->data['description'][0] = __('Description');
$table_status->data['description'][1] = (empty($agent['comentarios']) === true) ? '<em>'.__('N/A').'</em>' : $agent['comentarios'];

/*
    $table_agent_version = '<p>'.html_print_image(
    'images/version.png',
    true,
    [
        'title' => __('Agent Version'),
        'class' => 'invert_filter',
    ]
    );
    $table_agent_version .= '<span class="align-top inline">';
    $table_agent_version .= (empty($agent['agent_version']) === true) ? '<i>'.__('N/A').'</i>' : $agent['agent_version'];
    $table_agent_version .= '</span></p>';

    $table_agent_description = '<p>'.html_print_image(
    'images/list.png',
    true,
    [
        'title' => __('Description'),
        'class' => 'invert_filter',
    ]
    );
    $table_agent_description .= '<span class="align-top inline">';
    $table_agent_description .= (empty($agent['comentarios']) === true) ? '<em>'.__('N/A').'</em>' : $agent['comentarios'];
    $table_agent_description .= '</span></p>';
*/

/*
    $table_agent_count_modules = reporting_tiny_stats(
    $agent,
    true,
    'agent',
    // Useless.
    ':',
    true
);*/

$agentCountModules = html_print_div(
    [
        'class'   => 'agent_details_bullets',
        'content' => reporting_tiny_stats(
            $agent,
            true,
            'agent',
            // Useless.
            ':',
            true
        ),
    ],
    true
);

$has_remote_conf = enterprise_hook(
    'config_agents_has_remote_configuration',
    [$agent['id_agente']]
);

if ((bool) $has_remote_conf) {
    $table_status->data['remote_config'][0] = __('Remote configuration');
    $table_status->data['remote_config'][1] = __('Enabled');

    $satellite_server = (int) db_get_value_filter(
        'satellite_server',
        'tagente',
        ['id_agente' => $id_agente]
    );

    if (empty($satellite_server) === false) {
        $satellite_name = db_get_value_filter(
            'name',
            'tserver',
            ['id_server' => $satellite_server]
        );

        $table_status->data['remote_config'][0] = __('Satellite server');
        $table_status->data['remote_config'][1] = $satellite_name;
    }
}


$table_agent = $agentStatusHeader.'
    <div class="agent_details_content">
        <div class="agent_details_graph">
            '.$agentStatusGraph.$agentCountModules.'
        </div>
        <div class="agent_details_info">
            '.$alive_animation.html_print_table($table_status, true).'
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
$table_contact->class = 'floating_form';
$table_contact->style[0] = 'height: 32px; width: 30%; padding-right: 5px; text-align: end; vertical-align: top';
$table_contact->style[1] = 'height: 32px; width: 70%; padding-left: 5px; font-weight: lighter; vertical-align: top';

$agentContactCaption = html_print_div(
    [
        'class'   => 'agent_details_agent_caption',
        'content' => '<span class="subsection_header_title">'.__('Agent contact').'</span>',
    ],
    true
);

$buttonsRefreshAgent = html_print_button(
    __('Refresh data'),
    'refresh_data',
    false,
    'window.location.assign("index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60")',
    [ 'mode' => 'link' ],
    true
);

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') === true) {
    $buttonsRefreshAgent .= html_print_button(
        __('Force checks'),
        'force_checks',
        false,
        'window.location.assign("index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'")',
        [ 'mode' => 'link' ],
        true
    );
}

$buttons_refresh_agent_view = html_print_div(
    [
        'class'   => 'buttons_agent_view',
        'content' => $buttonsRefreshAgent,
    ],
    true
);

// Data for agent contact.
$intervalHumanTime = human_time_description_raw($agent['intervalo']);
$lastContactDate = ui_print_timestamp($agent['ultimo_contacto'], true);
$remoteContactDate = ($agent['ultimo_contacto_remoto'] === '01-01-1970 00:00:00') ? __('Never') : date_w_fixed_tz($agent['ultimo_contacto_remoto']);
$lastAndRemoteContact = sprintf('%s / %s', $lastContactDate, $remoteContactDate);
$progress = agents_get_next_contact($id_agente);
$tempTimeToShow = ($agent['intervalo'] - (strtotime('now') - strtotime($agent['ultimo_contacto'])));
$progressCaption = ($tempTimeToShow >= 0) ? sprintf('%d s', $tempTimeToShow) : __('Out of bounds');
$ajaxNextContactInterval = (empty($agent['intervalo']) === true) ? 0 : (100 / $agent['intervalo']);
$secondary_groups = enterprise_hook('agents_get_secondary_groups', [$id_agente]);
$secondaryLinks = [];
if (empty($secondary_groups['for_select']) === true) {
    $secondaryLinks[] = '<em>'.__('N/A').'</em>';
} else {
    foreach ($secondary_groups['for_select'] as $id => $name) {
        $secondaryLinks[] = html_print_anchor(
            [
                'href'    => 'index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id,
                'content' => $name,
            ],
            true
        );
    }
}

$last_status_change_agent = agents_get_last_status_change($agent['id_agente']);
$time_elapsed = (empty($last_status_change_agent) === false) ? human_time_comparation($last_status_change_agent) : '<em>'.__('N/A').'</em>';

// Agent Interval.
$data = [];
$data[0] = __('Interval');
$data[1] = $intervalHumanTime;
$table_contact->data[] = $data;

// Last & Remote contact.
$data = [];
$data[0] = __('Last contact').' / '.__('Remote');
$data[1] = $lastAndRemoteContact;
$table_contact->data[] = $data;

// Next contact progress.
$data = [];
$data[0] = __('Next contact');
$data[1] = ui_progress(
    $progress,
    '80%',
    '1.2',
    '#ececec',
    true,
    $progressCaption,
    [
        'page'     => 'operation/agentes/ver_agente',
        'interval' => $ajaxNextContactInterval,
        'data'     => [
            'id_agente'       => $id_agente,
            'refresh_contact' => 1,
        ],

    ]
);
$table_contact->data[] = $data;

// Group line.
$data = [];
$data[0] = '<b>'.__('Group').'</b>';
$data[1] = html_print_anchor(
    [
        'href'    => 'index.php?sec=gagente&sec2=godmode/groups/tactical&id_group='.$agent['id_grupo'],
        'content' => groups_get_name($agent['id_grupo']),
    ],
    true
);
$table_contact->data[] = $data;

// Secondary groups.
$data = [];
$data[0] = '<b>'.__('Secondary groups').'</b>';
$data[1] = implode(', ', $secondaryLinks);
$table_contact->data[] = $data;

// Parent agent line.
if (enterprise_installed() === true) {
    $data = [];
    $data[0] = '<b>'.__('Parent').'</b>';
    if ((int) $agent['id_parent'] === 0) {
        $data[1] = '<em>'.__('N/A').'</em>';
    } else {
        $data[1] = html_print_anchor(
            [
                'href'    => 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent['id_parent'],
                'content' => agents_get_alias($agent['id_parent']),
            ],
            true
        );
    }

    $table_contact->data[] = $data;
}

// Last status change line.
$data = [];
$data[0] = '<b>'.__('Last status change').'</b>';
$data[1] = $time_elapsed;
$table_contact->data[] = $data;

/*
 * END: TABLE CONTACT BUILD
 */

/*
 * START: TABLE DATA BUILD
 */

$data_opcional = new stdClass();
$data_opcional->id = 'agent_data_main';
$data_opcional->width = '100%';
$data_opcional->class = 'floating_form';
// Gis and url address.
$agentAdditionalContent = '';
// Position Information.
if ((bool) $config['activate_gis'] === true) {
    $dataPositionAgent = gis_get_data_last_position_agent(
        $agent['id_agente']
    );
    if (is_array($dataPositionAgent) === true && $dataPositionAgent['stored_longitude'] !== '' && $dataPositionAgent['stored_longitude'] !== '') {
        $data_opcional->data['agent_position'][0] = __('Position (Long, Lat)');

        $dataOptionalOutput = html_print_anchor(
            [
                'href'    => 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente,
                'content' => $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'],
            ],
            true
        );

        if (empty($dataPositionAgent['description']) === false) {
            $dataOptionalOutput .= ' ('.$dataPositionAgent['description'].')';
        }

        $data_opcional->data['agent_position'][1] = $dataOptionalOutput;
    }
}

// If the url description is set.
if (empty($agent['url_address']) === false) {
    $data_opcional->data['url_address'][0] = __('Url address');
    $data_opcional->data['url_address'][1] = html_print_anchor(
        [
            'href'    => $agent['url_address'],
            'content' => $agent['url_address'],
        ],
        true
    );
}


// Other IP address and timezone offset.
if (empty($addresses) === false) {
    $data_opcional->data['other_ip_address'][0] = __('Other IP addresses');
    $data_opcional->data['other_ip_address'][1] = html_print_div(
        [
            'class'   => 'overflow-y mx_height50px',
            'content' => implode('<br>', $addresses),
        ],
        true
    );
}

// Timezone Offset.
if ((int) $agent['timezone_offset'] !== 0) {
    $data_opcional->data['timezone_offset'][0] = __('Timezone Offset');
    $data_opcional->data['timezone_offset'][1] = $agent['timezone_offset'];
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
        if ($custom_value[0]['is_password_type']) {
                $data[1] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
        } else if ($field['is_link_enabled'] === '1') {
            list($link_text, $link_url) = json_decode($custom_value[0]['description'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $link_text = '';
                $link_url = '';
            }

            if ($link_text === '') {
                $link_text = $link_url;
            }

            $data[1] = '<a href="'.$link_url.'">'.$link_text.'</a>';
        } else {
            $custom_value[0]['description'] = ui_bbcode_to_html($custom_value[0]['description']);
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

    if (is_array($second_column) === true) {
        $columns = array_merge($first_column, $second_column);
    } else {
        $columns = $first_column;
        if ($data_opcional->data !== null) {
            $filas = count($data_opcional->data);
        }

        $data_opcional->colspan[$filas][1] = 3;
    }

    $data_opcional->data[] = $columns;

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

if ((bool) $config['agentaccess'] === true && $access_agent > 0) {
    $agentAccessRateHeader = html_print_div(
        [
            'class'   => 'agent_details_header',
            'content' => '<span class="subsection_header_title">'.__('Agent access rate (Last 24h)').'</span>',
        ],
        true
    );

    $agentAccessRateContent = html_print_div(
        [
            'class'   => 'white-table-graph-content',
            'content' => graphic_agentaccess(
                $id_agente,
                SECONDS_1DAY,
                true,
                true
            ),
        ],
        true
    );

    $agentAccessRate = html_print_div(
        [
            'class'   => 'box-flat agent_details_col mrgn_lft_20px w50p',
            'id'      => 'table_access_rate',
            'content' => $agentAccessRateHeader.$agentAccessRateContent,
        ],
        true
    );
} else {
    $agentAccessRate = '';
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
    $table_incident = new stdClass();
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
if (empty($network_interfaces_by_agents) === false && empty($network_interfaces_by_agents[$id_agente]) === false) {
    $network_interfaces = $network_interfaces_by_agents[$id_agente]['interfaces'];
}

if (empty($network_interfaces) === false) {
    $table_interface = new stdClass();
    $table_interface->id = 'agent_interface_info';
    $table_interface->class = 'info_table';
    $table_interface->width = '100%';
    $table_interface->style = [];
    $table_interface->style['interface_event_graph'] = 'width: 35%;';

    $table_interface->head = [];
    $options = [
        'class' => 'closed',
        'style' => 'cursor:pointer;',
    ];
    $table_interface->data = [];
    $event_text_cont = 0;

    foreach ($network_interfaces as $interface_name => $interface) {
        if (empty($interface['traffic']) === false) {
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
                $graph_link .= $win_handle."', 800, 480)\">";
                $graph_link .= html_print_image(
                    'images/chart.png',
                    true,
                    [
                        'title' => __('Interface traffic'),
                        'class' => 'invert_filter',
                    ]
                ).'</a>';
            } else {
                $graph_link = '';
            }
        } else {
            $graph_link = '';
        }

        $content = [
            'id_agent_module' => $interface['status_module_id'],
            'id_group'        => $id_group,
            'period'          => SECONDS_1DAY,
            'time_from'       => '00:00:00',
            'time_to'         => '00:00:00',
            'sizeForTicks'    => 250,
            'height_graph'    => 40,
            [
                ['id_agent_module' => $interface['status_module_id']],
            ]
        ];

        $e_graph = \reporting_module_histogram_graph(
            ['datetime' => time()],
            $content
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

        $data = [];
        $data['interface_name'] = '<strong>'.$interface_name.'</strong>';
        $data['interface_status'] = $interface['status_image'];
        $data['interface_graph'] = $graph_link;
        $data['interface_ip'] = $interface['ip'];
        $data['interface_mac'] = $interface['mac'];
        $data['last_contact'] = __('Last contact: ').$last_contact;
        $data['interface_event_graph'] = $e_graph['chart'];

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
$agent_contact = html_print_div(
    [
        'class'   => 'agent_details_header',
        'content' => $agentContactCaption.$buttons_refresh_agent_view,
    ],
    true
);

$agent_contact .= html_print_table($table_contact, true);

$agentDetails = html_print_div(
    [
        'class'   => 'box-flat agent_details_col',
        'content' => $table_agent,
    ],
    true
);

$agentContact = html_print_div(
    [
        'class'   => 'box-flat agent_details_col mrgn_lft_20px',
        'content' => $agent_contact,
    ],
    true
);

$agentEventsHeader = html_print_div(
    [
        'class'   => 'agent_details_header',
        'content' => '<span class="subsection_header_title">'.__('Events (Last 24h)').'</span>',
    ],
    true
);

$agentEventsGraph = html_print_div(
    [
        'class'   => 'white-table-graph-content',
        'content' => graph_graphic_agentevents(
            $id_agente,
            95,
            70,
            SECONDS_1DAY,
            '',
            true,
            true,
            500
        ),
    ],
    true
);

$agentEvents = html_print_div(
    [
        'class'   => 'box-flat agent_details_col w50p',
        'content' => $agentEventsHeader.$agentEventsGraph,
    ],
    true
);

/*
 * EVENTS TABLE END.
 */
if (isset($data_opcional) === false || isset($data_opcional->data) === false || empty($data_opcional->data) === true) {
    $agentAdditionalInfo = '';
} else {
    $agentAdditionalInfo = ui_toggle(
        html_print_table($data_opcional, true),
        '<span class="subsection_header_title">'.__('Agent data').'</span>',
        'status_monitor_agent',
        false,
        false,
        true,
        '',
        'white-box-content',
        'box-flat white_table_graph w100p'
    );
}

$agentIncidents = (isset($table_incident) === false) ? '' : html_print_table($table_incident, true);

html_print_div(
    [
        'class'   => 'agent_details_first_row agent_details_line',
        'content' => $agentDetails.$agentContact,
    ]
);

html_print_div(
    [
        'class'   => 'agent_details_line',
        'content' => $agentEvents.$agentAccessRate,
    ]
);

if (empty($agentAdditionalInfo) === false) {
    html_print_div(
        [
            'class'   => 'agent_details_line',
            'content' => $agentAdditionalInfo,
        ]
    );
}

if (empty($agentIncidents) === false) {
    html_print_div(
        [
            'class'   => 'agent_details_line',
            'content' => $agentIncidents,
        ]
    );
}



/*
    // Show both graphs, events and access rate.
    if ($table_access_rate) {
    echo '<div class="agent_access_rate_events agent_details_line">'.$table_access_rate.$table_events.'</div>';
    } else {
    echo '<div class="w100p">'.$table_events.'</div>';
    }

    echo $agent_incidents;
*/

if (isset($table_interface) === true) {
    ui_toggle(
        html_print_table($table_interface, true),
        '<b>'.__('Interface information (SNMP)').'</b>',
        '',
        'interface-table-status-agent',
        true
    );
}
