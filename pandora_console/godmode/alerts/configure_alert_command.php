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
// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_alerts.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

$update_command = (bool) get_parameter('update_command');
$id = (int) get_parameter('id');
$pure = get_parameter('pure', 0);
$alert = [];

// Header.
if (defined('METACONSOLE')) {
    alerts_meta_print_header();
} else {
    ui_print_page_header(
        __('Alerts').' &raquo; '.__('Configure alert command'),
        'images/gm_alerts.png',
        false,
        'alerts_config_command_tab',
        true
    );
}


if ($update_command) {
    $id = (int) get_parameter('id');
    $alert = alerts_get_alert_command($id);
    if ($alert['internal']) {
        db_pandora_audit('ACL Violation', 'Trying to access Alert Management');
        include 'general/noaccess.php';
        exit;
    }

    $name = (string) get_parameter('name');
    $command = (string) get_parameter('command');
    $description = (string) get_parameter('description');
    $id_group = (string) get_parameter('id_group', 0);

    $fields_descriptions = [];
    $fields_values = [];
    $fields_hidden = [];
    $info_fields = '';
    $values = [];
    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        $fields_descriptions[] = (string) get_parameter('field'.$i.'_description');
        $fields_values[] = (string) get_parameter('field'.$i.'_values');
        $fields_hidden[] = get_parameter('field'.$i.'_hide');
        $info_fields .= ' Field'.$i.': '.$fields_values[($i - 1)];
    }

    $values['fields_values'] = io_json_mb_encode($fields_values);
    $values['fields_descriptions'] = io_json_mb_encode($fields_descriptions);
    $values['fields_hidden'] = io_json_mb_encode($fields_hidden);

    $values['name'] = $name;
    $values['command'] = $command;
    $values['description'] = $description;
    $values['id_group'] = $id_group;

    // Check it the new name is used in the other command.
    $id_check = db_get_value('id', 'talert_commands', 'name', $name);
    if (($id_check != $id) && (!empty($id_check))) {
        $result = '';
    } else {
        $result = alerts_update_alert_command($id, $values);
        if ($result) {
            $info = '{"Name":"'.$name.'","Command":"'.$command.'","Description":"'.$description.' '.$info_fields.'"}';
            $alert['fields_values'] = io_json_mb_encode($fields_values);
            $alert['fields_descriptions'] = io_json_mb_encode($fields_descriptions);
            $alert['name'] = $name;
            $alert['command'] = $command;
            $alert['description'] = $description;
            $alert['id_group'] = $id_group;
        }
    }

    if ($result) {
        db_pandora_audit('Command management', 'Update alert command #'.$id, false, false, $info);
    } else {
        db_pandora_audit('Command management', 'Fail to update alert command #'.$id, false, false);
    }

    ui_print_result_message(
        $result,
        __('Successfully updated'),
        __('Could not be updated')
    );
}


$name = '';
$command = '';
$description = '';
$fields_descriptions = '';
$fields_values = '';
$id_group = 0;
if ($id) {
    if (!$result) {
        $alert = alerts_get_alert_command($id);
    }

    $name = $alert['name'];
    $command = $alert['command'];
    $description = $alert['description'];
    $id_group = $alert['id_group'];
    $fields_descriptions = $alert['fields_descriptions'];
    $fields_values = $alert['fields_values'];
    $fields_hidden = $alert['fields_hidden'];
}

if (!empty($fields_descriptions)) {
    $fields_descriptions = json_decode($fields_descriptions, true);
}

if (!empty($fields_values)) {
    $fields_values = json_decode($fields_values, true);
}

if (!empty($fields_hidden)) {
    $fields_hidden = json_decode($fields_hidden, true);
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

if (defined('METACONSOLE')) {
    $table->head[0] = ($id) ? __('Update Command') : __('Create Command');
    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
}

$table->style = [];
if (!defined('METACONSOLE')) {
    $table->style[0] = 'font-weight: bold';
    $table->style[2] = 'font-weight: bold';
    $table->style[4] = 'font-weight: bold';
}

$table->size = [];
$table->size[0] = '20%';
$table->data = [];

$table->colspan['name'][1] = 3;
$table->data['name'][0] = __('Name');
$table->data['name'][2] = html_print_input_text('name', $name, '', 35, 255, true);

$table->colspan['command'][1] = 3;
$table->data['command'][0] = __('Command');
$table->data['command'][1] = html_print_textarea('command', 8, 30, $command, '', true);

$table->colspan['group'][1] = 3;
$table->data['group'][0] = __('Group');
$table->data['group'][1] = html_print_select_groups(
    false,
    'LM',
    true,
    'id_group',
    $id_group,
    false,
    '',
    0,
    true
);

$table->colspan['description'][1] = 3;
$table->data['description'][0] = __('Description');
$table->data['description'][1] = html_print_textarea('description', 10, 30, $description, '', true);


for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
    $table->data['field'.$i][0] = sprintf(__('Field %s description'), $i);

    if (!empty($fields_descriptions)) {
        $field_description = $fields_descriptions[($i - 1)];
    } else {
        $field_description = '';
    }

    $table->data['field'.$i][1] = html_print_input_text('field'.$i.'_description', $field_description, '', 30, 255, true);

    $table->data['field'.$i][2] = sprintf(__('Field %s values'), $i);

    if (!empty($fields_values)) {
        $field_values = $fields_values[($i - 1)];
    } else {
        $field_values = '';
    }

    if (!empty($fields_hidden)) {
        $selected = (bool) $fields_hidden[($i - 1)];
    } else {
        $selected = false;
    }

    $table->data['field'.$i][3] = html_print_input_text('field'.$i.'_values', $field_values, '', 55, 255, true, false, false, '', 'field_value');

    $table->data['field'.$i][4] = __('Hide');

    $table->data['field'.$i][5] = html_print_checkbox_extended('field'.$i.'_hide', 1, $selected, false, 'cursor: \'pointer\'', 'class="hide_inputs"', true);
}

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_commands&pure='.$pure.'">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('id', $id);
    html_print_input_hidden('update_command', 1);
    html_print_submit_button(__('Update'), 'create', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create_command', 1);
    html_print_submit_button(__('Create'), 'create', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');
?>

<script type="text/javascript">
$(document).ready (function () {

    $(".hide_inputs").each(function(index) {
        var $input_in_row = $(this).closest('tr').find('.field_value');
        if($(this).is(':checked')) {  
            $input_in_row.prop('style', '-webkit-text-security: disc;');
        } else {  
            $input_in_row.prop('style', '');
        }
    });

    $(".hide_inputs").click(function() {
        var $input_in_row = $(this).closest('tr').find('.field_value');
        if($(this).is(':checked')) {  
            $input_in_row.prop('style', '-webkit-text-security: disc;');
        } else {  
            $input_in_row.prop('style', '');
        }  
    });
});
</script>
