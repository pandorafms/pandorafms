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
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access Group Management2');
    include 'general/noaccess.php';
    return;
}

$id_field = (int) get_parameter('id_field', 0);
$name = (string) get_parameter('name', '');
$display_on_front = (bool) get_parameter('display_on_front', 0);
$is_password_type = (bool) get_parameter('is_password_type', 0);
$is_combo_enable = (bool) get_parameter('is_combo_enable', 0);
$combo_values = (string) get_parameter('combo_values', '');
// Header.
if ($id_field) {
    $field = db_get_row_filter('tagent_custom_fields', ['id_field' => $id_field]);
    $name = $field['name'];
    $display_on_front = $field['display_on_front'];
    $is_password_type = $field['is_password_type'];
    $combo_values = $field['combo_values'];
    $is_combo_enable = $config['is_combo_enable'];
    ui_print_page_header(__('Update agent custom field'), 'images/custom_field.png', false, '', true, '');
} else {
    ui_print_page_header(__('Create agent custom field'), 'images/custom_field.png', false, '', true, '');
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->id = 'configure_field';
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->style[4] = 'font-weight: bold';
$table->style[6] = 'font-weight: bold';

echo "<div id='message_set_password'  title='".__('Agent Custom Fields Information')."' style='display:none;'>";
echo "<p style='text-align: center;font-weight: bold;'>".__('You cannot set the Password type until you clear the combo values and click on update button.').'</p>';
echo '</div>';

echo "<div id='message_set_combo'  title='".__('Agent Custom Fields Information')."' style='display:none;'>";
echo "<p style='text-align: center;font-weight: bold;'>".__('You cannot unset the enable combo until you clear the combo values and click on update.').'</p>';
echo '</div>';

echo "<div id='message_no_set_password'  title='".__('Agent Custom Fields Information')."' style='display:none;'>";
echo "<p style='text-align: center;font-weight: bold;'>".__('If you select Enabled combo the Password type will be disabled.').'</p>';
echo '</div>';

echo "<div id='message_no_set_combo'  title='".__('Agent Custom Fields Information')."' style='display:none;'>";
echo "<p style='text-align: center;font-weight: bold;'>".__('If you select Passord type the Enabled combo will be disabled.').'</p>';
echo '</div>';


$table->data = [];

$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text(
    'name',
    $name,
    '',
    35,
    100,
    true
);

$table->data[1][0] = __('Pass type').ui_print_help_tip(
    __('The fields with pass type enabled will be displayed like html input type pass in html'),
    true
);
$table->data[1][1] = html_print_checkbox_switch(
    'is_password_type',
    1,
    $is_password_type,
    true
);

$table->data[2][0] = __('Display on front').ui_print_help_tip(
    __('The fields with display on front enabled will be displayed into the agent details'),
    true
);
$table->data[2][1] = html_print_checkbox_switch(
    'display_on_front',
    1,
    $display_on_front,
    true
);

$table->data[3][0] = __('Enabled combo');
$table->data[3][1] = html_print_checkbox_switch_extended(
    'is_combo_enable',
    0,
    $config['is_combo_enable'],
    false,
    '',
    '',
    true
);

$table->rowstyle[4] = 'display: none;';
$table->data[4][0] = __('Combo values').ui_print_help_tip(
    __('Set values separated by comma'),
    true
);
$table->data[4][1] = html_print_input_text(
    'combo_values',
    io_safe_output($combo_values),
    '',
    35,
    200,
    true
);

echo '<form name="field" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/fields_manager">';
html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

if ($id_field) {
    html_print_input_hidden('update_field', 1);
    html_print_input_hidden('id_field', $id_field);
    html_print_submit_button(__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create_field', 1);
    html_print_submit_button(__('Create'), 'crtbutton', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';
?>

<script>
$(document).ready (function () {
    if($('input[type=hidden][name=update_field]').val() == 1 && $('input[type=text][name=combo_values]').val() != ''){
        $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
        $('#configure_field-4').show();
        $('input[type=checkbox][name=is_password_type]').change(function (e) {
            dialog_message("#message_set_password");
            $('input[type=checkbox][name=is_password_type]').prop('checked', false);
            $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
            $('#configure_field-4').show();
            e.preventDefault();
    });
    $('input[type=checkbox][name=is_combo_enable]').change(function (e) {
        if($('input[type=text][name=combo_values]').val() != '' &&  $('input[type=checkbox][name=is_combo_enable]').prop('checked', true)){
            dialog_message("#message_set_combo");
            $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
            $('#configure_field-4').show();
            e.preventDefault();
        }
    });
    }
   
   
    $('input[type=checkbox][name=is_combo_enable]').change(function () {
        if( $(this).is(":checked") ){
          $('#configure_field-4').show();
          dialog_message("#message_no_set_password");
          $('#configure_field-1').hide();
        }
        else{
          $('#configure_field-4').hide();
          $('#configure_field-1').show();
        }
    });
    $('input[type=checkbox][name=is_password_type]').change(function () {
        if( $(this).is(":checked")){
            dialog_message("#message_no_set_combo");
            $('#configure_field-3').hide();
        }
        else{
            $('#configure_field-3').show();
        }
    });
});

function dialog_message(message_id) {
  $(message_id)
    .css("display", "inline")
    .dialog({
      modal: true,
      show: "blind",
      hide: "blind",
      width: "400px",
      buttons: {
        Close: function() {
          $(this).dialog("close");
        }
      }
    });
}

</script>
