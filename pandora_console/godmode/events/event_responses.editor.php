<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

$meta = false;
if (enterprise_installed() && defined('METACONSOLE')) {
    $meta = true;
}

$class_description = 'response_description';
if ($meta) {
    $class_description = 'response_description_metaconsole';
}


$event_response_id = get_parameter('id_response', 0);

if ($event_response_id > 0) {
    $event_response = db_get_row('tevent_response', 'id', $event_response_id);
} else {
    $event_response = [];
    $event_response['name'] = '';
    $event_response['description'] = '';
    $event_response['id_group'] = 0;
    $event_response['type'] = '';
    $event_response['target'] = '';
    $event_response['id'] = 0;
    $event_response['new_window'] = 1;
    $event_response['modal_width'] = 0;
    $event_response['modal_height'] = 0;
    $event_response['params'] = '';
    $event_response['server_to_exec'] = '';
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

if (is_metaconsole()) {
    $table->head[0] = __('Edit event responses');
    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

$table->size = [];

$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold;';

$table->data = [];

$data = [];
$data[0] = __('Name');
$data[1] = html_print_input_text(
    'name',
    $event_response['name'],
    '',
    50,
    255,
    true
);
$data[1] .= html_print_input_hidden('id_response', $event_response['id'], true);

$data[2] = __('Group');
$data[3] = html_print_select_groups(false, 'PM', true, 'id_group', $event_response['id_group'], '', '', '', true);
$table->data[0] = $data;

$data = [];
$table->colspan[1][1] = 3;
$data[0] = __('Description');
$data[1] = html_print_textarea(
    'description',
    5,
    40,
    $event_response['description'],
    'class="'.$class_description.'"',
    true
);
$table->data[1] = $data;

$data = [];
$data[0] = __('Location').ui_print_help_tip(__('For Command type Modal Window mode is enforced'), true);
$locations = [
    __('Modal window'),
    __('New window'),
];
$data[1] = html_print_select($locations, 'new_window', $event_response['new_window'], '', '', '', true);

$data[2] = '<span class="size">'.__('Size').'</span>';
if ($event_response['modal_width'] == 0) {
    $event_response['modal_width'] = 620;
}

if ($event_response['modal_height'] == 0) {
    $event_response['modal_height'] = 500;
}

$data[3] = '<span class="size">'.__('Width').' (px) </span>';
$data[3] .= '<span class="size">'.html_print_input_text('modal_width', $event_response['modal_width'], '', 4, 5, true).'</span>';
$data[3] .= '<span class="size">'.__('Height').' (px) </span>';
$data[3] .= '<span class="size">'.html_print_input_text('modal_height', $event_response['modal_height'], '', 4, 5, true).'</span>';
$table->data[2] = $data;

$data = [];
$data[0] = __('Parameters');
$data[1] = html_print_input_text(
    'params',
    $event_response['params'],
    '',
    50,
    255,
    true
);
$types = [
    'url'     => __('URL'),
    'command' => __('Command'),
];
$data[2] = __('Type');
$data[3] = html_print_select($types, 'type', $event_response['type'], '', '', '', true);
$table->data[3] = $data;

$data = [];
$data[0] = '<span id="command_label" class="labels">'.__('Command').'</span><span id="url_label" style="display:none;" class="labels">'.__('URL').'</span>'.ui_print_help_icon('response_macros', true);
$data[1] = html_print_textarea(
    'target',
    3,
    1,
    $event_response['target'],
    'style="min-height:initial;"',
    true
);

$servers_to_exec = [];
$servers_to_exec[0] = __('Local console');

if (enterprise_installed()) {
    enterprise_include_once('include/functions_satellite.php');

    $rows = get_proxy_servers();
    foreach ($rows as $row) {
        if ($row['server_type'] != 13) {
            $s_type = ' (Standard)';
        } else {
            $s_type = ' (Satellite)';
        }

        $servers_to_exec[$row['id_server']] = $row['name'].$s_type;
    }
}

$data[2] = '<div id="server_to_exec_label" style="display:none;" class="labels">'.__('Server to execute command').'</div>';
$data[3] = '<div id="server_to_exec_value" style="display:none;">'.html_print_select($servers_to_exec, 'server_to_exec', $event_response['server_to_exec'], '', '', '', true).'</div>';

$table->data[4] = $data;

if ($event_response_id == 0) {
    echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=list&action=create_response&amp;pure='.$config['pure'].'">';
    html_print_table($table);
    if (!defined('METACONSOLE')) {
        echo '<div style="width:100%;text-align:right;">';
    } else {
        echo '<div style="width:100%;text-align:right;">';
    }

    html_print_submit_button(__('Create'), 'create_response_button', false, ['class' => 'sub next']);
    echo '</div>';
    echo '</form>';
} else {
    echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=list&action=update_response&amp;pure='.$config['pure'].'">';
    html_print_table($table);
    if (!defined('METACONSOLE')) {
        echo '<div style="width:100%;text-align:right;">';
    } else {
        echo '<div style="width:100%;text-align:right;">';
    }

    html_print_submit_button(__('Update'), 'update_response_button', false, ['class' => 'sub next']);
    echo '</div>';
    echo '</form>';
}
?>

<script language="javascript" type="text/javascript">
$('#type').change(function() {
    $('.labels').hide();
    $('#'+$(this).val()+'_label').show();
    
    switch ($(this).val()) {
        case 'command':
            $('#new_window option[value="0"]')
                .prop('selected', true);
            $('#new_window').attr('disabled','disabled');
            $('#server_to_exec_label').css('display','');
            $('#server_to_exec_value').css('display','');
            break;
        case 'url':
            $('#new_window').removeAttr('disabled');
            $('#server_to_exec_label').css('display','none');
            $('#server_to_exec_value').css('display','none');
            break;
    }
});

$('#new_window').change(function() {
    switch ($(this).val()) {
        case '0':
            $('.size').css('visibility','visible');
            break;
        case '1':
            $('.size').css('visibility','hidden');
            break;
    }
});


function update_form() {
    $('#type').trigger('change');
    $('#new_window').trigger('change');
}

update_form();
</script>
