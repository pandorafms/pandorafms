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

require_once 'include/functions_agents.php';

check_login();

$id_agente = get_parameter_get('id_agente', -1);

if ($id_agente === -1) {
    ui_print_error_message(__('There was a problem loading agent'));
    return;
}

if (! check_acl($config['id_user'], $agent['id_grupo'], 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent General Information'
    );
    include_once 'general/noaccess.php';
    return;
}

ui_print_page_header(__('Agent custom fields'), 'images/custom_field.png', false, '', false);

echo '<table cellspacing="4" cellpadding="4" border="0" class="databox w450px">';
// Custom fields
$fields = db_get_all_rows_filter('tagent_custom_fields', ['display_on_front' => 1]);

foreach ($fields as $field) {
    echo '<tr><td class="datos"><b>'.$field['name'].ui_print_help_tip(__('Custom field'), true).'</b></td>';
    $custom_value = db_get_value_filter('description', 'tagent_custom_data', ['id_field' => $field['id_field'], 'id_agent' => $id_agente]);
    if ($custom_value === false || $custom_value == '') {
        $custom_value = '<i>-'.__('empty').'-</i>';
    } else {
        $custom_value = ui_bbcode_to_html($custom_value);
    }

    echo '<td class="datos f9" colspan="2">'.$custom_value.'</td></tr>';
}

// End of table
echo '</table>';
