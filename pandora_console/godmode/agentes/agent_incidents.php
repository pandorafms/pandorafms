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
global $config;

require_once 'include/functions_incidents.php';

check_login();

if (!$config['integria_enabled']) {
    ui_print_error_message(__('In order to access ticket management system, integration with Integria IMS must be enabled and properly configured'));
    return;
}

$group = $id_grupo;

if (! check_acl($config['id_user'], $group, 'AW', $id_agente)) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent manager'
    );
    include 'general/noaccess.php';
    return;
}

$offset = (int) get_parameter('offset', 0);


// See if id_agente is set (either POST or GET, otherwise -1
$id_agent = (int) get_parameter('id_agente');
$groups = users_get_groups($config['id_user'], 'AR');
$filter = ' AND id_agent = '.$id_agent;
$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=incident&id_agente='.$id_agent;

$params = [
    '',
    '-10',
    '1',
    '-1',
    '0',
    '',
    '',
    '',
    agents_get_name($id_agent),
];

$result = integria_api_call(null, null, null, null, 'get_incidents', $params, false, 'json', ',');

$result = json_decode($result, true);

if (empty($result) === true) {
    $result = [];
    $count = 0;
    echo '<div class="nf">'.__('No incidents associated to this agent').'</div><br />';
    return;
} else {
    $count = count($result);
    $result = array_slice($result, $offset, $config['block_size']);
}

// Show pagination.
ui_pagination($count, $url, $offset, 0, false, 'offset');
// ($count + $offset) it's real count of incidents because it's use LIMIT $offset in query.
echo '<br />';

// Show headers.
$table->width = '100%';
$table->class = 'databox';
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = [];
$table->data = [];
$table->size = [];
$table->align = [];

$table->head[0] = __('ID');
$table->head[1] = __('Status');
$table->head[2] = __('Incident');
$table->head[3] = __('Priority');
$table->head[4] = __('Group');
$table->head[5] = __('Updated');

$table->size[0] = 43;
$table->size[7] = 50;

$table->align[1] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';

$rowPair = true;
$iterator = 0;
foreach ($result as $row) {
    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $data = [];

    $data[0] = '<a href="index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$row['id_incidencia'].'">'.$row['id_incidencia'].'</a>';
    $attach = incidents_get_attach($row['id_incidencia']);

    if (!empty($attach)) {
        $data[0] .= '&nbsp;&nbsp;'.html_print_image('images/attachment.png', true, ['style' => 'align:middle;']);
    }

    $data[1] = incidents_print_status_img($row['estado'], true);
    $data[2] = '<a href="index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$row['id_incidencia'].'">'.substr(io_safe_output($row['titulo']), 0, 45).'</a>';
    $data[3] = incidents_print_priority_img($row['prioridad'], true);
    $data[4] = $row['id_grupo'];
    $data[5] = ui_print_timestamp($row['actualizacion'], true);

    array_push($table->data, $data);
}

html_print_table($table);

echo '</div>';
unset($table);
echo '<br><br>';

echo '<div id="both">&nbsp;</div>';
