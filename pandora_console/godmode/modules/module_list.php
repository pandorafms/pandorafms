<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access module management');
    include 'general/noaccess.php';
    exit;
}

// Header
ui_print_page_header(__('Module management').' &raquo; '.__('Defined modules'), 'images/gm_modules.png', false, '', true);

$update_module = (bool) get_parameter_post('update_module');

// Update
if ($update_module) {
    $name = get_parameter_post('name');
    $id_type = get_parameter_post('id_type');
    $description = get_parameter_post('description');
    $icon = get_parameter_post('icon');
    $category = get_parameter_post('category');

    $values = [
        'descripcion' => $description,
        'categoria'   => $category,
        'nombre'      => $name,
        'icon'        => $icon,
    ];

    $result = db_process_sql_update('ttipo_modulo', $values, ['id_tipo' => $id_type]);

    if (! $result) {
        ui_print_error_message(__('Problem modifying module'));
    } else {
        ui_print_success_message(__('Module updated successfully'));
    }
}


echo "<table cellpadding='0' cellspacing='0' width='100%' class='info_table'>";
echo '<thead>';
echo '<th>'.__('Icon').'</th>';
echo '<th>'.__('ID').'</th>';
echo '<th>'.__('Name').'</th>';
echo '<th>'.__('Description').'</th>';
echo '</thead';

$rows = db_get_all_rows_sql('SELECT * FROM ttipo_modulo ORDER BY nombre');
if ($rows === false) {
    $rows = [];
}

$color = 0;
foreach ($rows as $row) {
    if ($color == 1) {
        $tdcolor = 'datos';
        $color = 0;
    } else {
        $tdcolor = 'datos2';
        $color = 1;
    }

    echo "
	<tr>
		<td class='$tdcolor' align=''>".html_print_image('images/'.$row['icon'], true, ['border' => '0'])."</td>
		<td class='$tdcolor'>
		<b>".$row['id_tipo']."
		</b></td>
		<td class='$tdcolor'>
		<b>".$row['nombre']."
		</b></td>
		<td class='$tdcolor'>
		".$row['descripcion'].'
		</td>
	</tr>';
}

echo '</table>';
