<?php
/**
 * Edit Fields manager.
 *
 * @category   Resources.
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

$id_field = (int) get_parameter('id_field', 0);
$name = (string) get_parameter('name', '');
$display_on_front = (bool) get_parameter('display_on_front', 0);
$is_password_type = (bool) get_parameter('is_password_type', 0);
$is_combo_enable = (bool) get_parameter('is_combo_enable', 0);
$combo_values = (string) get_parameter('combo_values', '');
$is_link_enabled = (bool) get_parameter('is_link_enabled', 0);

// Header.
if ($id_field) {
    $field = db_get_row_filter('tagent_custom_fields', ['id_field' => $id_field]);
    $name = $field['name'];
    $display_on_front = $field['display_on_front'];
    $is_password_type = $field['is_password_type'];
    $combo_values = $field['combo_values'] ? $field['combo_values'] : '';
    $is_combo_enable = $config['is_combo_enable'];
    $is_link_enabled = $field['is_link_enabled'];
    $header_title = __('Update agent custom field');
} else {
    $header_title = __('Create agent custom field');
}

ui_print_standard_header(
    $header_title,
    'images/custom_field.png',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => 'index.php?sec=gagente&sec2=godmode/agentes/fields_manager',
            'label' => __('Resources'),
        ],
        [
            'link'  => 'index.php?sec=gagente&sec2=godmode/agentes/fields_manager',
            'label' => __('Custom field'),
        ],
        [
            'link'  => '',
            'label' => __('Edit'),
        ],
    ]
);

echo "<div id='message_set_password'  title='".__('Agent Custom Fields Information')."' class='invisible'>";
echo "<p class='center bolder'>".__('You cannot set the Password type until you clear the combo values and click on update button.').'</p>';
echo '</div>';

echo "<div id='message_set_combo'  title='".__('Agent Custom Fields Information')."' class='invisible'>";
echo "<p class='center bolder'>".__('You cannot unset the enable combo until you clear the combo values and click on update.').'</p>';
echo '</div>';

echo "<div id='message_no_set_password'  title='".__('Agent Custom Fields Information')."' class='invisible'>";
echo "<p class='center bolder'>".__('If you select Enabled combo the Password type will be disabled.').'</p>';
echo '</div>';

echo "<div id='message_no_set_combo'  title='".__('Agent Custom Fields Information')."' class='invisible'>";
echo "<p class='center bolder'>".__('If you select Passord type the Enabled combo will be disabled.').'</p>';
echo '</div>';

$table = new stdClass();
$table->class = 'databox filter-table-adv';
$table->id = 'configure_field';
$table->width = '100%';
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data = [];

$table->data[0][0] = html_print_label_input_block(
    __('Name'),
    html_print_input_text(
        'name',
        $name,
        '',
        35,
        100,
        true
    )
);

$table->data[0][1] = html_print_label_input_block(
    __('Display on front').ui_print_help_tip(
        __('The fields with display on front enabled will be displayed into the agent details'),
        true
    ),
    html_print_checkbox_switch(
        'display_on_front',
        1,
        $display_on_front,
        true
    )
);

$table->data[1][0] = html_print_label_input_block(
    __('Link type'),
    html_print_checkbox_switch_extended(
        'is_link_enabled',
        1,
        $is_link_enabled,
        false,
        '',
        '',
        true
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Pass type').ui_print_help_tip(
        __('The fields with pass type enabled will be displayed like html input type pass in html'),
        true
    ),
    html_print_checkbox_switch(
        'is_password_type',
        1,
        $is_password_type,
        true
    )
);

$table->data[2][1] = html_print_label_input_block(
    __('Enabled combo'),
    html_print_checkbox_switch_extended(
        'is_combo_enable',
        0,
        $config['is_combo_enable'],
        false,
        '',
        '',
        true
    )
);

$table->data[3][0] = html_print_label_input_block(
    __('Combo values').ui_print_help_tip(
        __('Set values separated by comma'),
        true
    ),
    html_print_textarea(
        'combo_values',
        3,
        65,
        io_safe_output($combo_values),
        '',
        true
    )
);

echo '<form class="max_floating_element_size" name="field" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/fields_manager">';
html_print_table($table);

if ($id_field > 0) {
    html_print_input_hidden('update_field', 1);
    html_print_input_hidden('id_field', $id_field);
    $buttonCaption = __('Update');
    $buttonName = 'updbutton';
} else {
    html_print_input_hidden('create_field', 1);
    $buttonCaption = __('Create');
    $buttonName = 'crtbutton';
}

$actionButtons = [];
$actionButtons[] = html_print_submit_button(
    $buttonCaption,
    $buttonName,
    false,
    [ 'icon' => 'wand' ],
    true
);
$actionButtons[] = html_print_go_back_button(
    'index.php?sec=gagente&sec2=godmode/agentes/fields_manager',
    ['button_class' => ''],
    true
);

html_print_action_buttons(
    implode('', $actionButtons),
    ['type' => 'form_action'],
);

echo '</form>';
?>

<script>
$(document).ready (function () {
    if($('input[type=hidden][name=update_field]').val() == 1 && $('#textarea_combo_values').val() != ''){
        $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
        $('#configure_field-3').show();

        $('input[type=checkbox][name=is_password_type]').change(function (e) {
            dialog_message("#message_set_password");
            $('input[type=checkbox][name=is_password_type]').prop('checked', false);
            $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
            $('#configure_field-3').show();
            e.preventDefault();
        });

        $('input[type=checkbox][name=is_combo_enable]').change(function (e) {
            if($('#textarea_combo_values').val() != '' &&  $('input[type=checkbox][name=is_combo_enable]').prop('checked', true)){
                dialog_message("#message_set_combo");
                $('input[type=checkbox][name=is_combo_enable]').prop('checked', true);
                $('#configure_field-3').show();
                e.preventDefault();
            }
        });
    } else {
        $('#configure_field-3').hide();
    }
   
    if ($('input[type=checkbox][name=is_link_enabled]').is(":checked") === true) {
        $('#configure_field-2').hide();
    } else {
        $('#configure_field-2').show();
    }

    $('input[type=checkbox][name=is_link_enabled]').change(function () {
        if( $('input[type=checkbox][name=is_link_enabled]').prop('checked') ){
            $('#configure_field-2').hide();
            $('#configure_field-3').hide();
        } else{
            $('#configure_field-2').show();
            if($('input[type=checkbox][name=is_combo_enable]').prop('checked') === true) {
                $('#configure_field-3').show();
            }
        }
    });
    
    $('input[type=checkbox][name=is_combo_enable]').change(function () {
        if( $('input[type=checkbox][name=is_combo_enable]').prop('checked') ){
          $('#configure_field-3').show();
          dialog_message("#message_no_set_password");
          $('#configure_field-1').hide();
        }
        else{
          $('#configure_field-3').hide();
          $('#configure_field-1').show();
        }
    });
    $('input[type=checkbox][name=is_password_type]').change(function () {
        if( $('input[type=checkbox][name=is_password_type]').prop('checked')){
            dialog_message("#message_no_set_combo");
            $('#configure_field-3').hide();
        }
        else{
            if($('input[type=checkbox][name=is_combo_enable]').prop('checked') === true) {
                $('#configure_field-3').show();
            }
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
