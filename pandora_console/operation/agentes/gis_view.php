<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'MR') && ! check_acl($config['id_user'], 0, 'MW') && ! check_acl($config['id_user'], 0, 'MM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access GIS Agent view'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_gis.php';
require_once 'include/functions_html.php';
require_once $config['homedir'].'/include/functions_agents.php';

ui_require_javascript_file('openlayers.pandora');

// Get the parameters
$period = (int) get_parameter('period', SECONDS_1DAY);
$agentId = (int) get_parameter('id_agente');
$id_agente = $agentId;
$agent_name = agents_get_name($id_agente);
$agent_alias = agents_get_alias($id_agente);

// Avoid the agents with characters that fails the div.
$agent_name_original = $agent_name;
$agent_name = md5($agent_name);

$url = '';
// These variables come from index.php
foreach ($_GET as $key => $value) {
    $url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
}

echo "<div class='mrgn_btn_30px'></div>";

// Map with the current position
echo '<div id="'.$agent_name.'_agent_map" class="agent_map_position"></div>';

if (!gis_get_agent_map($id_agente, '500px', '100%', true, true, $period)) {
    ui_print_error_message(__('There is no default map. Please go to the setup for to set a default map.'));
    echo "<script type='text/javascript'>
		$(document).ready(function() {
			$('#".$agent_name."_agent_map').hide();
		});
		</script>";
}




switch ($config['dbtype']) {
    case 'mysql':
        $timestampLastOperation = db_get_value_sql(
            'SELECT UNIX_TIMESTAMP()'
        );
    break;

    case 'postgresql':
        $timestampLastOperation = db_get_value_sql(
            "SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP))"
        );
    break;

    case 'oracle':
        $timestampLastOperation = db_get_value_sql(
            "SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (".SECONDS_1DAY.')) from dual'
        );
    break;
}

gis_activate_ajax_refresh(null, $timestampLastOperation);
gis_activate_select_control();

echo '<br />';
echo "<form class='' action='index.php?".$url."' method='POST'>";
echo "<table width=100% class='databox filters'>";
echo '<tr><td>'.__('Period to show data as path');
echo '<td>';
html_print_extended_select_for_time('period', $period, '', '', '0', 10);
echo '<td>';
html_print_submit_button(__('Refresh path'), 'refresh', false, 'class="sub upd mrgn_top_0px"');
echo '</table></form>';

// Get the elements to present in this page
switch ($config['dbtype']) {
    case 'mysql':
        $sql = sprintf(
            '
			SELECT longitude, latitude, altitude, start_timestamp,
				end_timestamp, description, number_of_packages, manual_placement
			FROM tgis_data_history
			WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
			ORDER BY end_timestamp DESC
			LIMIT %d OFFSET %d',
            $agentId,
            (get_system_time() - $period),
            $config['block_size'],
            (int) get_parameter('offset')
        );
    break;

    case 'postgresql':
    case 'oracle':
        $set = [];
        $set['limit'] = $config['block_size'];
        $set['offset'] = (int) get_parameter('offset');
        $sql = sprintf(
            '
			SELECT longitude, latitude, altitude, start_timestamp,
				end_timestamp, description, number_of_packages, manual_placement
			FROM tgis_data_history
			WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
			ORDER BY end_timestamp DESC',
            $agentId,
            (get_system_time() - $period)
        );
        $sql = oracle_recode_query($sql, $set);
    break;
}

$result = db_get_all_rows_sql($sql, true);

$sql2 = sprintf(
    '
    SELECT current_longitude AS longitude, current_latitude AS latitude, current_altitude AS altitude, 
    start_timestamp, description, number_of_packages, manual_placement
    FROM tgis_data_status
    WHERE tagente_id_agente = %d
    ORDER BY start_timestamp DESC
    LIMIT %d OFFSET %d',
    $agentId,
    $config['block_size'],
    (int) get_parameter('offset')
);

    $result2 = db_get_all_rows_sql($sql2, true);

if ($result === false && $result2 === false) {
        ui_print_empty_data(__('This agent doesn\'t have any GIS data.'));
} else {
    if ($result === false) {
        $result = $result2;
    } else {
        $result2[0]['end_timestamp'] = date('Y-m-d H:i:s');
        array_unshift($result, $result2[0]);
    }
}


if ($result !== false) {
    echo '<h4>'.__('Positional data from the last').' '.human_time_description_raw($period).'</h4>';

    // Get the total elements for UI pagination
    $countData = count($result);

    if ($countData > 0) {
        ui_pagination($countData, false);
    }

    $table = new StdClass();
    $table->data = [];
    foreach ($result as $key => $row) {
        $distance = 0;
        if (isset($result[($key - 1)])) {
            $distance = gis_calculate_distance(
                $row['latitude'],
                $row['longitude'],
                $result[($key - 1)]['latitude'],
                $result[($key - 1)]['longitude']
            );
        } else {
            $dataLastPosition = gis_get_data_last_position_agent($agentId);
            if ($dataLastPosition !== false) {
                $distance = gis_calculate_distance(
                    $row['latitude'],
                    $row['longitude'],
                    $dataLastPosition['stored_latitude'],
                    $dataLastPosition['stored_longitude']
                );
            }
        }

        $rowdata = [
            $row['longitude'],
            $row['latitude'],
            (int) $row['altitude'].' m',
            is_numeric($row['start_timestamp']) ? date($config['date_format'], $row['start_timestamp']) : date_w_fixed_tz($row['start_timestamp']),
            is_numeric($row['end_timestamp']) ? date($config['date_format'], $row['end_timestamp']) : date_w_fixed_tz($row['end_timestamp']),
            $row['description'],
            sprintf(__('%s Km'), $distance),
            $row['number_of_packages'],
            $row['manual_placement'],
        ];
        array_push($table->data, $rowdata);
    }

    $table->head = [
        __('Longitude'),
        __('Latitude'),
        __('Altitude'),
        __('From'),
        __('To'),
        __('Description'),
        __('Distance'),
        __('# of Packages'),
        __('Manual placement'),
    ];
    $table->class = 'databox data';
    $table->id = $agent_name.'_position_data_table';
    $table->width = '100%';
    html_print_table($table);
    unset($table);

    if ($countData > 0) {
        ui_pagination($countData, false);
    }
}
