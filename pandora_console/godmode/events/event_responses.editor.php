<?php
/**
 * Event responses editor view.
 *
 * @category   Events
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
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

    // ACL check for event response edition.
    if (!check_acl_restricted_all($config['id_user'], $event_response['id_group'], 'PM')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Group Management'
        );
        include 'general/noaccess.php';
        return;
    }
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
    $event_response['command_timeout'] = 90;
}

$table = new stdClass();
$table->styleTable = 'margin: 10px 10px 10px';
$table->class = 'databox filters';
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data = [];

$data = [];

$data[0] = html_print_label_input_block(
    __('Name'),
    '<div class="w100p margin-top-10">'.html_print_input_text(
        'name',
        $event_response['name'],
        '',
        false,
        255,
        true,
        false,
        true,
        '',
        'w100p'
    ).html_print_input_hidden('id_response', $event_response['id'], true).'</div>'
);

$return_all_group = false;

if (users_can_manage_group_all('PM') === true) {
    $return_all_group = true;
}


$data[1] = html_print_label_input_block(
    __('Group'),
    '<div class="w100p margin-top-10">'.html_print_select_groups(
        false,
        'PM',
        $return_all_group,
        'id_group',
        $event_response['id_group'],
        '',
        '',
        '',
        true,
        false,
        false,
        'w100p'
    ).'</div>'
);
$table->data[0] = $data;

$data = [];
$table->colspan[1][0] = 2;
$data[0] = html_print_label_input_block(
    __('Description'),
    '<div class="w100p margin-top-10">'.html_print_textarea(
        'description',
        5,
        1,
        $event_response['description'],
        'class="'.$class_description.' w100p"',
        true,
        'w100p'
    ).'</div>'
);
$table->data[1] = $data;

$data = [];
$locations = [
    __('Modal window'),
    __('New window'),
];
$data[0] = html_print_label_input_block(
    __('Location').ui_print_help_tip(__('For Command type Modal Window mode is enforced'), true),
    '<div class="w100p margin-top-10">'.html_print_select(
        $locations,
        'new_window',
        $event_response['new_window'],
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$data[1] = '<span class="size">'.__('Size').'</span>';
if ($event_response['modal_width'] == 0) {
    $event_response['modal_width'] = 620;
}

if ($event_response['modal_height'] == 0) {
    $event_response['modal_height'] = 500;
}

$data[1] = '<div class="flex flex-space-around">';
$data[1] .= html_print_label_input_block(
    __('Width').' (px) ',
    '<div class="w100p margin-top-10">'.html_print_input_text(
        'modal_width',
        $event_response['modal_width'],
        '',
        4,
        5,
        true
    ).'</div>',
    ['div_class' => 'mgn_tp_0_imp']
);
$data[1] .= html_print_label_input_block(
    __('Height').' (px) ',
    '<div class="w100p margin-top-10">'.html_print_input_text(
        'modal_height',
        $event_response['modal_height'],
        '',
        4,
        5,
        true
    ).'</div>'
);
$data[1] .= '</div>';
$table->data[2] = $data;

$data = [];
$data[0] = html_print_label_input_block(
    __('Parameters'),
    '<div class="w100p margin-top-10">'.html_print_input_text(
        'params',
        $event_response['params'],
        '',
        50,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$types = [
    'url'     => __('URL'),
    'command' => __('Command'),
];

$data[1] = html_print_label_input_block(
    __('Type'),
    '<div class="w100p margin-top-10">'.html_print_select(
        $types,
        'type',
        $event_response['type'],
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%'
    ).'</div>'
);
$table->data[3] = $data;

$data = [];
$table->colspan[4][0] = 2;
$data[0] = html_print_label_input_block(
    __('Command').'<span id="url_label" class="labels invisible">'.__('URL').'</span>'.ui_print_help_icon('response_macros', true),
    '<div class="w100p margin-top-10">'.html_print_textarea(
        'target',
        3,
        1,
        $event_response['target'],
        'class="mh_initial w100p"',
        true
    ).'</div>'
);
$table->data[4] = $data;

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

$data = [];
$data[0] = html_print_label_input_block(
    '<div id="server_to_exec_label" class="labels invisible">'.__('Server to execute command').'</div>',
    '<div id="server_to_exec_value" class="invisible" >'.html_print_select(
        $servers_to_exec,
        'server_to_exec',
        $event_response['server_to_exec'],
        '',
        '',
        '',
        true
    ).'</div>'
);

$data[1] = html_print_label_input_block(
    '<div id="command_timeout_label" class="labels invisible">'.__('Command timeout (s)'),
    '<div id="command_timeout_value" class="invisible">'.html_print_input_text(
        'command_timeout',
        $event_response['command_timeout'],
        '',
        4,
        5,
        true
    )
);

$table->data[5] = $data;

$data = [];
$data[0] = html_print_label_input_block(
    __('Display command').ui_print_help_tip(__('If enabled the command will be displayed to any user that can execute this event response'), true),
    '<div class="w100p margin-top-10">'.html_print_checkbox_switch(
        'display_command',
        1,
        $event_response['display_command'],
        true
    ).'</div>'
);

$table->data[6] = $data;

if ((int) $event_response_id === 0) {
    $actionUrl = 'index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=list&action=create_response&amp;pure='.$config['pure'];
    $buttonCaption = __('Create');
    $buttonName = 'create_response_button';
} else {
    $actionUrl = 'index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=list&action=update_response&amp;pure='.$config['pure'];
    $buttonCaption = __('Update');
    $buttonName = 'update_response_button';
}

echo '<form method="POST" action="'.$actionUrl.'">';
html_print_table($table);
html_print_action_buttons(
    html_print_submit_button(
        $buttonCaption,
        $buttonName,
        false,
        ['icon' => 'wand'],
        true
    ),
    [ 'type' => 'form_action']
);
echo '</form>';
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
            $('#command_timeout_label').css('display','');
            $('#command_timeout_value').css('display','');

            break;
        case 'url':
            $('#new_window').removeAttr('disabled');
            $('#server_to_exec_label').css('display','none');
            $('#server_to_exec_value').css('display','none');
            $('#command_timeout_label').css('display','none');
            $('#command_timeout_value').css('display','none');

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
