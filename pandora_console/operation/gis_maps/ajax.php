<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
$working_dir = str_replace('\\', '/', getcwd());
// Windows compatibility
if (file_exists($working_dir.'/include/config.php')) {
    include_once 'include/config.php';
} else {
    include_once '../../include/config.php';
}

$hash = (string) get_parameter('hash', '');
if (!empty($hash)) {
    // It is a ajax call from PUBLIC_CONSOLE
    $idMap = (int) get_parameter('map_id');
    $id_user = get_parameter('id_user', '');

    $myhash = md5($config['dbpass'].$idMap.$id_user);

    // Check input hash
    if ($myhash == $hash) {
        $config['id_user'] = $id_user;
    }
} else {
    check_login();
}

global $config;

require_once $config['homedir'].'/include/functions_gis.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_events.php';
require_once $config['homedir'].'/include/functions_alerts.php';

$opt = get_parameter('opt');

switch ($opt) {
    case 'get_data_conexion':
        $returnJSON['correct'] = 1;
        $idConection = get_parameter('id_conection');

        $row = db_get_row_filter(
            'tgis_map_connection',
            ['id_tmap_connection' => $idConection]
        );

        $returnJSON['content'] = $row;

        echo json_encode($returnJSON);
    break;

    case 'get_new_positions':
        $id_features = get_parameter('id_features', '');
        $last_time_of_data = get_parameter('last_time_of_data');
        $layerId = get_parameter('layer_id');
        $agentView = get_parameter('agent_view');

        $returnJSON = [];
        $returnJSON['correct'] = 1;

        $idAgentsWithGIS = [];

        if ($agentView == 0) {
            $flagGroupAll = db_get_all_rows_sql(
                'SELECT tgrupo_id_grupo
				FROM tgis_map_layer
				WHERE id_tmap_layer = '.$layerId.' AND tgrupo_id_grupo = 0;'
            );
            // group 0 = all groups
            $defaultCoords = db_get_row_sql(
                'SELECT default_longitude, default_latitude
				FROM tgis_map
				WHERE id_tgis_map IN (SELECT tgis_map_id_tgis_map FROM tgis_map_layer WHERE id_tmap_layer = '.$layerId.')'
            );

            if ($flagGroupAll === false) {
                $idAgentsWithGISTemp = db_get_all_rows_sql(
                    'SELECT id_agente
					FROM tagente
					WHERE id_grupo IN
						(SELECT tgrupo_id_grupo
							FROM tgis_map_layer
							WHERE id_tmap_layer = '.$layerId.')
						OR id_agente IN
						(SELECT tagente_id_agente
							FROM tgis_map_layer_has_tagente
							WHERE tgis_map_layer_id_tmap_layer = '.$layerId.');'
                );
            } else {
                // All groups, all agents
                $idAgentsWithGISTemp = db_get_all_rows_sql(
                    'SELECT
						tagente_id_agente AS id_agente
					FROM tgis_data_status
					WHERE tagente_id_agente'
                );
            }

            if (empty($idAgentsWithGISTemp)) {
                $idAgentsWithGISTemp = [];
            }

            foreach ($idAgentsWithGISTemp as $idAgent) {
                $idAgentsWithGIS[] = $idAgent['id_agente'];
            }
        } else {
            // Extract the agent GIS status for one agent.
            $idAgentsWithGIS[] = $id_features;
        }

        switch ($config['dbtype']) {
            case 'mysql':
                if (empty($idAgentsWithGIS)) {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							IFNULL(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							IFNULL(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE 1 = 0'
                    );
                } else {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							IFNULL(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							IFNULL(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE id_agente IN ('.implode(',', $idAgentsWithGIS).')'
                    );
                }
            break;

            case 'postgresql':
                if (empty($idAgentsWithGIS)) {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							COALESCE(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							COALESCE(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE 1 = 0'
                    );
                } else {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							COALESCE(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							COALESCE(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE id_agente IN ('.implode(',', $idAgentsWithGIS).')'
                    );
                }
            break;

            case 'oracle':
                if (empty($idAgentsWithGIS)) {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							COALESCE(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							COALESCE(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE 1 = 0'
                    );
                } else {
                    $agentsGISStatus = db_get_all_rows_sql(
                        'SELECT t1.alias, id_parent, t1.id_agente AS tagente_id_agente,
							COALESCE(t2.stored_longitude, '.$defaultCoords['default_longitude'].') AS stored_longitude,
							COALESCE(t2.stored_latitude, '.$defaultCoords['default_latitude'].') AS stored_latitude
						FROM tagente t1
						LEFT JOIN tgis_data_status t2 ON t1.id_agente = t2.tagente_id_agente
							WHERE id_agente IN ('.implode(',', $idAgentsWithGIS).')'
                    );
                }
            break;
        }

        if ($agentsGISStatus === false) {
            $agentsGISStatus = [];
        }

        $agents = null;
        foreach ($agentsGISStatus as $row) {
            $status = agents_get_status($row['tagente_id_agente']);

            if (!$config['gis_label']) {
                $row['alias'] = '';
            }

            $icon = gis_get_agent_icon_map($row['tagente_id_agente'], true, $status);
            if ($icon[0] !== '/') {
                $icon_size = getimagesize($config['homedir'].'/'.$icon);
            } else {
                $icon_size = getimagesize($config['homedir'].$icon);
            }

            $icon_width = $icon_size[0];
            $icon_height = $icon_size[1];

            $agents[$row['tagente_id_agente']] = [
                'icon_path'        => $config['homeurl'].'/'.$icon,
                'icon_width'       => $icon_width,
                'icon_height'      => $icon_height,
                'name'             => io_safe_output($row['alias']),
                'status'           => $status,
                'stored_longitude' => $row['stored_longitude'],
                'stored_latitude'  => $row['stored_latitude'],
                'id_parent'        => $row['id_parent'],
            ];
        }

        $returnJSON['content'] = json_encode($agents);
        echo json_encode($returnJSON);
    break;

    case 'point_path_info':
        $id = get_parameter('id');
        $row = db_get_row_sql('SELECT * FROM tgis_data_history WHERE id_tgis_data = '.$id);

        $returnJSON = [];
        $returnJSON['correct'] = 1;
        $returnJSON['content'] = __('Agent').': <a class="bolder" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row['tagente_id_agente'].'">'.agents_get_alias($row['tagente_id_agente']).'</a><br />';
        $returnJSON['content'] .= __('Position (Lat, Long, Alt)').': ('.$row['latitude'].', '.$row['longitude'].', '.$row['altitude'].') <br />';
        $returnJSON['content'] .= __('Start contact').': '.$row['start_timestamp'].'<br />';
        $returnJSON['content'] .= __('Last contact').': '.$row['end_timestamp'].'<br />';
        $returnJSON['content'] .= __('Num reports').': '.$row['number_of_packages'].'<br />';
        if ($row['manual_placemen']) {
            $returnJSON['content'] .= '<br />'.__('Manual placement').'<br />';
        }

        echo json_encode($returnJSON);

    break;

    case 'point_agent_info':
        $id = get_parameter('id');
        $agent = db_get_row_sql('SELECT * FROM tagente WHERE id_agente = '.$id);
        $agentDataGIS = gis_get_data_last_position_agent($agent['id_agente']);

        $returnJSON = [];
        $returnJSON['correct'] = 1;
        $returnJSON['content'] = '';

        $content = '';

        $table = new StdClass();
        $table->class = 'blank';
        $table->style = [];
        $table->style[0] = 'font-weight: bold';
        $table->rowstyle = [];
        $table->data = [];

        // Agent name
        $row = [];
        $row[] = __('Agent');
        $row[] = '<a class="bolder" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'">'.$agent['alias'].'</a>';
        $table->data[] = $row;

        // Position
        $row = [];
        $row[] = __('Position (Lat, Long, Alt)');

        // it's positioned in default position of map.
        if ($agentDataGIS === false) {
            $row[] = __('Default position of map.');
        } else {
            $row[] = '('.$agentDataGIS['stored_latitude'].', '.$agentDataGIS['stored_longitude'].', '.$agentDataGIS['stored_altitude'].')';
        }

        $table->data[] = $row;

        // IP
        $agent_ip_address = agents_get_address($id);
        if ($agent_ip_address || $agent_ip_address != '') {
            $row = [];
            $row[] = __('IP Address');
            $row[] = agents_get_address($id);
            $table->data[] = $row;
        }

        // OS
        $row = [];
        $row[] = __('OS');
        $osversion_offset = strlen($agent['os_version']);
        if ($osversion_offset > 15) {
            $osversion_offset = ($osversion_offset - 15);
        } else {
            $osversion_offset = 0;
        }

        if ($agent['os_version'] != '') {
            $agent_os_version = '&nbsp;(<i><span title="'.$agent['os_version'].'">'.substr($agent['os_version'], $osversion_offset, 15).'</span></i>)';
        }

        $row[] = ui_print_os_icon($agent['id_os'], true, true).$agent_os_version;
        $table->data[] = $row;

        // URL
        $agent_url = $agent['url_address'];
        if (!empty($agent_url)) {
            $row = [];
            $row[] = __('URL');
            $row[] = "<a href=\"$agent_url\">".ui_print_truncate_text($agent_url, 20).'</a>';
            $table->data[] = $row;
        }

        // Description
        $agent_description = $agent['comentarios'];
        if ($agent_description || $agent_description != '') {
            $row = [];
            $row[] = __('Description');
            $row[] = $agent_description;
            $table->data[] = $row;
        }

        // Group
        $row = [];
        $row[] = __('Group');
        $row[] = groups_get_name($agent['id_grupo']);
        $table->data[] = $row;

        // Agent version
        $row = [];
        if (strtolower(get_os_name($agent['id_os'])) == 'satellite') {
            $row[] = __('Satellite Version');
        } else {
            $row[] = __('Agent Version');
        }

        $row[] = $agent['agent_version'];
        $table->data[] = $row;

        // Last contact
        $row = [];
        $row[] = __('Last contact');
        if ($agent['ultimo_contacto'] == '1970-01-01 00:00:00') {
            $row[] = __('Never');
        } else {
            $row[] = date_w_fixed_tz($agent['ultimo_contacto']);
        }

        $table->data[] = $row;

        // Last remote contact
        $row = [];
        $row[] = __('Remote');
        if ($agent['ultimo_contacto_remoto'] == '1970-01-01 00:00:00') {
            $row[] = __('Never');
        } else {
            $row[] = date_w_fixed_tz($agent['ultimo_contacto_remoto']);
        }

        $table->data[] = $row;

        // Critical && not validated events
        $filter = [
            'id_agente' => (int) $agent['id_agente'],
            'criticity' => EVENT_CRIT_CRITICAL,
            'estado'    => [
                EVENT_STATUS_NEW,
                EVENT_STATUS_INPROCESS,
            ],
        ];
        $result = events_get_events($filter, 'COUNT(*) as num');

        if (!empty($result)) {
            $number = (int) $result[0]['num'];

            if ($number > 0) {
                $row = [];
                $row[] = __('Number of non-validated critical events');
                $row[] = '<a href="?sec=estado&sec2=operation/events/events&status=3&severity='.EVENT_CRIT_CRITICAL.'&id_agent='.$agent['id_agente'].'">'.$number.'</a>';
                $table->data[] = $row;
            }
        }

        // Alerts fired
        $alerts_fired = alerts_get_alerts(0, '', 'fired', -1, $true, false, $agent['id_agente']);
        if (!empty($alerts_fired)) {
            $row = [];
            $row[] = __('Alert(s) fired');
            $alerts_detail = '';
            foreach ($alerts_fired as $alert) {
                $alerts_detail .= '<p>'.$alert['module_name'].' - '.$alert['template_name'].' - '.date($config['date_format'], $alert['last_fired']).'</p>';
            }

            $row[] = $alerts_detail;
            $table->data[] = $row;
        }

        // To remove the grey background color of the classes datos and datos2
        for ($i = 0; $i < count($table->data); $i++) {
            $table->rowstyle[] = 'background-color: inherit;';
        }

        // Save table
        $returnJSON['content'] = html_print_table($table, true);

        echo json_encode($returnJSON);
    break;

    case 'point_group_info':
        $agent_id = (int) get_parameter('id');
        $group_id = (int) get_parameter('id_parent');
        $group = db_get_row_sql('SELECT * FROM tgrupo WHERE id_grupo = '.$group_id);
        $agent = db_get_row_sql('SELECT * FROM tagente WHERE id_agente = '.$agent_id);
        $agentDataGIS = gis_get_data_last_position_agent($agent['id_agente']);

        $returnJSON = [];
        $returnJSON['correct'] = 1;
        $returnJSON['content'] = '';

        $content = '';

        $table = new StdClass();
        $table->class = 'blank';
        $table->style = [];
        $table->style[0] = 'font-weight: bold';
        $table->rowstyle = [];
        $table->data = [];

        // Group name
        $row = [];
        $row[] = __('Group');
        $row[] = '<a class="bolder" href="?sec=estado&sec2=operation/agentes/estado_agente&group_id='.$group_id.'">'.$group['nombre'].'</a>';
        $table->data[] = $row;

        // Position
        $row = [];
        $row[] = __('Position (Lat, Long, Alt)');

        // it's positioned in default position of map.
        if ($agentDataGIS === false) {
            $row[] = __('Default position of map.');
        } else {
            $row[] = '('.$agentDataGIS['stored_latitude'].', '.$agentDataGIS['stored_longitude'].', '.$agentDataGIS['stored_altitude'].')';
        }

        $table->data[] = $row;

        // Description
        $group_description = $group['description'];
        if ($group_description || $group_description != '') {
            $row = [];
            $row[] = __('Description');
            $row[] = $group_description;
            $table->data[] = $row;
        }

        // Last contact
        $row = [];
        $row[] = __('Last contact');
        if ($agent['ultimo_contacto'] == '01-01-1970 00:00:00') {
            $row[] = __('Never');
        } else {
            $row[] = date_w_fixed_tz($agent['ultimo_contacto']);
        }

        $table->data[] = $row;

        // Last remote contact
        $row = [];
        $row[] = __('Remote');
        if ($agent['ultimo_contacto_remoto'] == '01-01-1970 00:00:00') {
            $row[] = __('Never');
        } else {
            $row[] = date_w_fixed_tz($agent['ultimo_contacto_remoto']);
        }

        $table->data[] = $row;

        // Critical && not validated events
        $filter = [
            'id_grupo'  => $group_id,
            'criticity' => EVENT_CRIT_CRITICAL,
            'estado'    => [
                EVENT_STATUS_NEW,
                EVENT_STATUS_INPROCESS,
            ],
        ];
        $result = events_get_events($filter, 'COUNT(*) as num');

        if (!empty($result)) {
            $number = (int) $result[0]['num'];

            if ($number > 0) {
                $row = [];
                $row[] = __('Number of non-validated critical events');
                $row[] = '<a href="?sec=estado&sec2=operation/events/events&status=3&severity='.EVENT_CRIT_CRITICAL.'&id_group='.$group_id.'">'.$number.'</a>';
                $table->data[] = $row;
            }
        }

        // Alerts fired
        $alerts_fired = alerts_get_alerts($group_id, '', 'fired', -1, $true);
        if (!empty($alerts_fired)) {
            $row = [];
            $row[] = __('Alert(s) fired');
            $alerts_detail = '';
            foreach ($alerts_fired as $alert) {
                $alerts_detail .= '<p>'.$alert['agent_alias'].' - '.$alert['module_name'].' - '.$alert['template_name'].' - '.date($config['date_format'], $alert['last_fired']).'</p>';
            }

            $row[] = $alerts_detail;
            $table->data[] = $row;
        }

        // To remove the grey background color of the classes datos and datos2
        for ($i = 0; $i < count($table->data); $i++) {
            $table->rowstyle[] = 'background-color: inherit;';
        }

        // Save table
        $returnJSON['content'] = html_print_table($table, true);

        echo json_encode($returnJSON);
    break;

    case 'get_map_connection_data':
        $idConnection = get_parameter('id_connection');

        $returnJSON = [];

        $returnJSON['correct'] = 1;

        $returnJSON['content'] = db_get_row_sql('SELECT * FROM tgis_map_connection WHERE id_tmap_connection = '.$idConnection);

        echo json_encode($returnJSON);
    break;
}
