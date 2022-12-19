<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
// Load global variables
global $config;

// Check user credentials
check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Inventory Module Management'
    );
    include 'general/noaccess.php';
    return;
}


// Header
if (defined('METACONSOLE')) {
    $sec = 'advanced';
    enterprise_include_once('meta/include/functions_components_meta.php');
    enterprise_hook('open_meta_frame');
    components_meta_print_header();
} else {
    $sec = 'gmodules';
    ui_print_page_header(
        __('Module management').' » '.__('Inventory modules'),
        'images/op_inventory.png',
        false,
        '',
        true
    );
}

// Header
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
if ($is_windows) {
    ui_print_error_message(__('Not supported in Windows systems'));
}

// Initialize variables
$id_module_inventory = (int) get_parameter('id_module_inventory');

$script_mode = 1;

// Updating
if ($id_module_inventory) {
    $row = db_get_row(
        'tmodule_inventory',
        'id_module_inventory',
        $id_module_inventory
    );

    if (!empty($row)) {
        $name = $row['name'];
        $description = $row['description'];
        $id_os = $row['id_os'];
        $interpreter = $row['interpreter'];
        $code = $row['code'];
        $data_format = $row['data_format'];
        $block_mode = $row['block_mode'];
        $script_path = $row['script_path'];
        $script_mode = $row['script_mode'];
    } else {
        ui_print_error_message(__('Inventory module error'));
        include 'general/footer.php';
        return;
    }

    // New module
} else {
    $name = '';
    $description = '';
    $id_os = 1;
    $interpreter = '';
    $code = '';
    $data_format = '';
    $block_mode = 0;
}

if ($id_os == null) {
    $disabled = true;
} else {
    $disabled = false;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->data = [];
$table->data[0][0] = '<strong>'.__('Name').'</strong>';
$table->data[0][1] = html_print_input_text('name', $name, '', 45, 100, true, $disabled);
$table->data[1][0] = '<strong>'.__('Description').'</strong>';
$table->data[1][1] = html_print_input_text('description', $description, '', 60, 500, true);
$table->data[2][0] = '<strong>'.__('OS').'</strong>';
$table->data[2][1] = html_print_select_from_sql(
    'SELECT id_os, name FROM tconfig_os ORDER BY name',
    'id_os',
    $id_os,
    '',
    '',
    '',
    $return = true
);

$table->data[3][0] = '<strong>'.__('Interpreter').'</strong>';
$table->data[3][1] = html_print_input_text('interpreter', $interpreter, '', 25, 100, true);
$table->data[3][1] .= ui_print_help_tip(__('Left blank for the LOCAL inventory modules'), true);

$table->data['block_mode'][0] = '<strong>'.__('Block Mode').'</strong>';
$table->data['block_mode'][1] = html_print_checkbox('block_mode', 1, $block_mode, true);

$table->data[4][0] = '<strong>'.__('Format').'</strong>';
$table->data[4][0] .= ui_print_help_tip(__('separate fields with ').SEPARATOR_COLUMN, true);
$table->data[4][1] = html_print_input_text('format', $data_format, '', 50, 100, true);

$table->data[5][0] = '<strong>'.__('Script mode').'</strong>';
$table->data[5][0] .= ui_print_help_tip(__(''), true);
$table->data[5][1] = __('Use script');
$table->data[5][1] .= html_print_radio_button(
    'script_mode',
    1,
    '',
    $script_mode,
    true
).'&nbsp;&nbsp;';
$table->data[5][1] .= '&nbsp&nbsp&nbsp&nbsp'.__('Use inline code');
$table->data[5][1] .= html_print_radio_button(
    'script_mode',
    2,
    '',
    $script_mode,
    true
).'&nbsp;&nbsp;';

$table->data[6][0] = '<strong>'.__('Script path').'</strong>';
$table->data[6][1] = html_print_input_text('script_path', $script_path, '', 50, 1000, true);

$table->data[7][0] = '<strong>'.__('Code').'</strong>';
$table->data[7][0] .= ui_print_help_tip(__("Here is placed the script for the REMOTE inventory modules Local inventory modules don't use this field").SEPARATOR_COLUMN, true);

$table->data[7][1] = html_print_textarea('code', 25, 80, base64_decode($code), '', true);


echo '<form name="inventorymodule" id="inventorymodule_form" method="post" 
	action="index.php?sec='.$sec.'&sec2=godmode/modules/manage_inventory_modules">';

html_print_table($table);
if ($id_module_inventory) {
    html_print_input_hidden('update_module_inventory', 1);
    html_print_input_hidden('id_module_inventory', $id_module_inventory);
} else {
    html_print_input_hidden('create_module_inventory', 1);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_module_inventory) {
    html_print_submit_button(__('Update'), 'submit', false, 'class="sub next"');
} else {
    html_print_submit_button(__('Create'), 'submit', false, 'class="sub upd"');
}

echo '</div>';
echo '</form>';

if (defined('METACONSOLE')) {
    enterprise_hook('close_meta_frame');
}

?>

<script type="text/javascript">
    $(document).ready (function () {
        var mode = <?php echo $script_mode; ?>;

        if (mode == 1) {
            $('#table1-6').show();
            $('#table1-7').hide();
        } else {
            $('#table1-7').show();
            $('#table1-6').hide(); 
        }

        $('input[type=radio][name=script_mode]').change(function() {
            if (this.value == 1) {
                $('#table1-6').show();
                $('#table1-7').hide();
            }
            else if (this.value == 2) {
                $('#table1-7').show();
                $('#table1-6').hide();
            }
        });
    });
</script>
