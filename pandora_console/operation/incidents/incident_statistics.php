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

require_once $config['homedir'].'/include/functions_graph.php';

check_login();

ui_print_standard_header(
    __('Statistics'),
    'images/book_edit.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Issues'),
        ],
        [
            'link'  => '',
            'label' => __('Statistics'),
        ],
    ]
);

if (!$config['integria_enabled']) {
    ui_print_error_message(__('In order to access ticket management system, integration with Integria IMS must be enabled and properly configured'));
    exit;
}

echo '<div class="info_box">';
echo '<table width="90%">
	<tr><td valign="top" style="width:50%;"><h3>'.__('Incidents by status').'</h3>';
echo graph_incidents_status();

echo '<td valign="top" style="width:50%;"><h3>'.__('Incidents by priority').'</h3>';
echo grafico_incidente_prioridad();

echo '<tr><td style="width:50%;"><h3>'.__('Incidents by group').'</h3>';
echo graphic_incident_group();

echo '<td style="width:50%;"><h3>'.__('Incidents by user').'</h3>';
echo graphic_incident_user();

echo '</table>';
echo '</div>';
