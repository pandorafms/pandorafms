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
if (is_metaconsole() === true) {
    $sec = 'advanced';
    enterprise_include_once('meta/include/functions_components_meta.php');
    components_meta_print_header();
} else {
    $sec = 'gmodules';
    ui_print_standard_header(
        __('Module management'),
        'images/op_inventory.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Configuration'),
            ],
            [
                'link'  => '',
                'label' => __('Inventory modules'),
            ],
        ]
    );
}

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
$table->class = 'databox filter-table-adv';
$table->style = [];
$table->style[0] = 'width: 50%';
$table->style[1] = 'width: 50%';
$table->data = [];

$table->data[0][] = html_print_label_input_block(
    __('Name'),
    html_print_input_text(
        'name',
        $name,
        '',
        45,
        100,
        true,
        $disabled
    )
);

$table->data[0][] = html_print_label_input_block(
    __('Description'),
    html_print_input_text(
        'description',
        $description,
        '',
        60,
        500,
        true
    )
);

$table->data[1][] = html_print_label_input_block(
    __('OS'),
    html_print_select_from_sql(
        'SELECT id_os, name FROM tconfig_os ORDER BY name',
        'id_os',
        $id_os,
        '',
        '',
        '',
        $return = true
    )
);

$table->data[1][] = html_print_label_input_block(
    __('Interpreter'),
    html_print_input_text(
        'interpreter',
        $interpreter,
        '',
        25,
        100,
        true
    ).ui_print_input_placeholder(
        __('Left blank for the LOCAL inventory modules'),
        true
    )
);

$table->data[2][] = html_print_label_input_block(
    __('Format'),
    html_print_input_text(
        'format',
        $data_format,
        '',
        50,
        100,
        true
    ).ui_print_input_placeholder(
        __('separate fields with ').SEPARATOR_COLUMN,
        true
    )
);

$table->data[2][] = html_print_label_input_block(
    __('Block Mode'),
    html_print_checkbox_switch(
        'block_mode',
        1,
        $block_mode,
        true
    )
);

$radioButtons = [];
$radioButtons[] = html_print_radio_button('script_mode', 1, __('Script mode'), $script_mode, true);
$radioButtons[] = html_print_radio_button('script_mode', 2, __('Use inline code'), $script_mode, true);

$table->data[3][] = html_print_label_input_block(
    __('Script mode'),
    html_print_div(
        [
            'class'   => 'switch_radio_button',
            'content' => implode('', $radioButtons),
        ],
        true
    )
);

$table->colspan[4][0] = 2;

$table->data[4][0] = html_print_label_input_block(
    __('Script path'),
    html_print_input_text(
        'script_path',
        $script_path,
        '',
        50,
        1000,
        true
    ),
    ['div_class' => 'script_path_inventory_modules']
);

$table->data[4][0] .= html_print_label_input_block(
    __('Code'),
    html_print_textarea(
        'code',
        25,
        80,
        base64_decode($code),
        '',
        true
    ).ui_print_input_placeholder(
        __("Here is placed the script for the REMOTE inventory modules Local inventory modules don't use this field").SEPARATOR_COLUMN,
        true
    ),
    ['div_class' => 'code_inventory_modules']
);

echo '<form name="inventorymodule" id="inventorymodule_form" class="max_floating_element_size" method="post" 
	action="index.php?sec='.$sec.'&sec2=godmode/modules/manage_inventory_modules">';

html_print_table($table);
if ($id_module_inventory) {
    html_print_input_hidden('update_module_inventory', 1);
    html_print_input_hidden('id_module_inventory', $id_module_inventory);
    $buttonCaption = __('Update');
    $buttonIcon = 'update';
} else {
    html_print_input_hidden('create_module_inventory', 1);
    $buttonCaption = __('Create');
    $buttonIcon = 'wand';
}

$actionButtons = '';
$actionButtons = html_print_submit_button(
    $buttonCaption,
    'submit',
    false,
    ['icon' => $buttonIcon],
    true
);
$actionButtons .= html_print_go_back_button(
    'index.php?sec=gmodules&sec2=godmode/modules/manage_inventory_modules',
    ['button_class' => ''],
    true
);

html_print_action_buttons($actionButtons);
echo '</form>';

?>

<script type="text/javascript">
    $(document).ready (function () {
        var mode = <?php echo $script_mode; ?>;

        if (mode == 1) {
            $('.script_path_inventory_modules').show();
            $('.code_inventory_modules').hide();
        } else {
            $('.code_inventory_modules').show();
            $('.script_path_inventory_modules').hide();
        }

        $('input[type=radio][name=script_mode]').change(function() {
            if (this.value == 1) {
                $('.script_path_inventory_modules').show();
                $('.code_inventory_modules').hide();
            }
            else if (this.value == 2) {
                $('.code_inventory_modules').show();
                $('.script_path_inventory_modules').hide();
            }
        });
    });
</script>
