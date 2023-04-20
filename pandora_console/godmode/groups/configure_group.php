<?php
/**
 * Configure agent groups.
 *
 * @category   Agents group management.
 * @package    Pandora FMS
 * @subpackage User interface.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Group Management2'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('meta/include/functions_agents_meta.php');

// Default values.
$create_group = (bool) get_parameter('create_group');
$id_group = (int) get_parameter('id_group');
$acl_parent = true;
if ($id_group > 0) {
    $group = db_get_row('tgrupo', 'id_grupo', $id_group);
    if ($group !== false) {
        $icon = $group['icon'];
        $name = $group['nombre'];
        $id_parent = $group['parent'];
        $group_pass = io_safe_output($group['password']);
        $alerts_disabled = $group['disabled'];
        $custom_id = $group['custom_id'];
        $propagate = $group['propagate'];
        $skin = $group['id_skin'];
        $contact = $group['contact'];
        $other = $group['other'];
        $description = $group['description'];
        $max_agents = $group['max_agents'];
    } else {
        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            'There was a problem loading group in configure agent group.'
        );
        include 'general/noaccess.php';
        exit;
    }
} else {
    // Set default values.
    $icon = '';
    $name = '';
    $id_parent = 0;
    $group_pass = '';
    $alerts_disabled = 0;
    $custom_id = '';
    $propagate = 0;
    $skin = 0;
    $contact = '';
    $other = '';
    $description = '';
    $max_agents = 0;
}

// Header.
if (is_metaconsole() === true) {
    agents_meta_print_header();
    $sec = 'advanced';
} else {
    $title_in_header = ($id_group > 0) ? __('Update group') : __('Create group');

    // Header.
    ui_print_standard_header(
        $title_in_header,
        'images/group.png',
        false,
        '',
        false,
        [],
        [
            [
                'link'  => '',
                'label' => __('Profiles'),
            ],
            [
                'link'  => '',
                'label' => __('Manage agents group'),
            ],
        ]
    );
    $sec = 'gagente';
}


// Data before table.
$files = list_files(((is_metaconsole() === true) ? '../../' : '').'images/', '@groups.svg', 1, 0);

$files_old = list_files('images/groups_small/', 'png', 1, 0);
foreach ($files_old as $key => $f) {
    // Remove from the list the non-desired .png files.
    if (strpos($f, '.bad.png') !== false || strpos($f, '.default.png') !== false || strpos($f, '.ok.png') !== false || strpos($f, '.warning.png') !== false) {
        unset($files_old[$key]);
    }
}

$files = array_merge($files, $files_old);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filter-table-adv';
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->data = [];

$table->data[0][0] = html_print_label_input_block(
    __('Name'),
    html_print_input_text('name', $name, '', 35, 100, true)
);

$extension = pathinfo($icon, PATHINFO_EXTENSION);
if (empty($extension) === true) {
    $icon .= '.png';
}

$input_icon = html_print_select($files, 'icon', $icon, '', 'None', '', true, false, true, '', false, 'width: 100%;');
$input_icon .= ' <span id="icon_preview" class="mrgn_lft_05em">';
if (empty($icon) === false) {
    if (empty($extension) === true || $extension === 'png') {
        $input_icon .= html_print_image('images/groups_small/'.$icon, true);
    } else {
        $input_icon .= html_print_image('images/'.$icon, true);
    }
}

$input_icon .= '</span>';

$table->data[0][1] = html_print_label_input_block(
    __('Icon'),
    html_print_div(
        [
            'class'   => 'flex-content-left ',
            'content' => $input_icon,
        ],
        true
    )
);

if ($id_group > 0) {
    // The user can access to the parent, but she want to edit the group.
    if ((bool) check_acl($config['id_user'], $id_parent, 'AR') === false) {
        $acl_parent = false;

        $input_parent = __('You have not access to the parent.').html_print_input_hidden('id_parent', $id_parent, true);
    } else {
        $input_parent = '<div class="w250px inline">';
        $input_parent .= html_print_select_groups(
            false,
            'AR',
            false,
            'id_parent',
            $id_parent,
            '',
            __('None'),
            -1,
            true,
            false,
            true,
            '',
            false,
            false,
            false,
            $id_group
        );
        $input_parent .= '</div>';
    }
} else {
    $input_parent = '<div class="w250px inline">';
    $input_parent .= html_print_input(
        [
            'type'           => 'select_groups',
            'name'           => 'id_parent',
            'selected'       => $id_parent,
            'return'         => true,
            'returnAllGroup' => false,
            'nothing'        => __('None'),
            'nothing_value'  => -1,
        ]
    );
    $input_parent .= '</div>';
}

if ($acl_parent === true) {
    $input_parent .= ' <span id="parent_preview" class="mrgn_lft_05em">';
    $input_parent .= html_print_image('images/'.(($id_parent !== 0) ? groups_get_icon($id_parent) : 'unknown@groups.svg'), true);
    $input_parent .= '</span>';
}

$table->data[1][0] = html_print_label_input_block(
    __('Parent'),
    html_print_div(
        [
            'class'   => 'flex-content-left ',
            'content' => $input_parent,
        ],
        true
    )
);

if ((bool) $config['enterprise_installed'] === true) {
    $table->data[1][1] .= html_print_label_input_block(
        __('Group Password'),
        html_print_input_password('group_pass', $group_pass, '', 16, 255, true)
    );
}

$table->data[2][0] = html_print_label_input_block(
    __('Alerts').ui_print_help_tip(__('Enable alert use in this group.'), true),
    html_print_checkbox_switch('alerts_enabled', 1, ! $alerts_disabled, true)
);

$table->data[2][1] = html_print_label_input_block(
    __('Propagate ACL').ui_print_help_tip(__('Propagate the same ACL security into the child subgroups.'), true),
    html_print_checkbox_switch('propagate', 1, $propagate, true)
);

$table->data[3][0] = html_print_label_input_block(
    __('Custom ID'),
    html_print_input_text('custom_id', $custom_id, '', 16, 255, true)
);

$table->data[3][1] = html_print_label_input_block(
    __('Description'),
    html_print_input_text('description', $description, '', 60, 255, true)
);

$table->data[4][0] = html_print_label_input_block(
    __('Contact').ui_print_help_tip(__('Contact information accessible through the _groupcontact_ macro'), true),
    html_print_textarea('contact', 4, 40, $contact, "class='min-height-0px'", true)
);

$table->data[4][1] = html_print_label_input_block(
    __('Other').ui_print_help_tip(__('Information accessible through the _group_other_ macro'), true),
    html_print_textarea('other', 4, 40, $other, "class='min-height-0px'", true)
);

$table->data[5][0] = html_print_label_input_block(
    __('Max agents allowed').ui_print_help_tip(__('Set the maximum of agents allowed for this group. 0 is unlimited.'), true),
    html_print_input_text('max_agents', $max_agents, '', 10, 255, true)
);

$sec = (is_metaconsole() === true) ? 'advanced' : 'gagente';

echo '<form name="grupo" class="max_floating_element_size" method="post" action="index.php?sec='.$sec.'&sec2=godmode/groups/group_list&pure='.$config['pure'].'" >';
html_print_table($table);

$buttons = '';
if ($id_group) {
    $buttons .= html_print_input_hidden('update_group', 1, true);
    $buttons .= html_print_input_hidden('id_group', $id_group, true);
    $buttons .= html_print_submit_button(
        __('Update'),
        'updbutton',
        false,
        ['icon' => 'upd'],
        true
    );
} else {
    $buttons .= html_print_input_hidden('create_group', 1);
    $buttons .= html_print_submit_button(
        __('Create'),
        'crtbutton',
        false,
        ['icon' => 'next'],
        true
    );
}

$buttons .= html_print_button(
    __('Go back'),
    'button_back',
    false,
    '',
    [
        'icon' => 'back',
        'mode' => 'secondary',
    ],
    true
);

html_print_action_buttons(
    $buttons
);
echo '</form>';

?>
<script language="javascript" type="text/javascript">
function icon_changed () {
    var inputs = [];
    var data = this.value;
    var extension = data.split('.').pop();
    $('#icon_preview').fadeOut ('normal', function () {
        $('#icon_preview').empty ();
        if (data != "") {
            var params = [];
            params.push("get_image_path=1");
            if (extension === 'png') {
                params.push("img_src=images/groups_small/" + data);
            } else {
                params.push("img_src=images/" + data);
            }
            params.push("page=include/ajax/skins.ajax");
            params.push("only_src=1");
            jQuery.ajax ({
                data: params.join ("&"),
                type: 'POST',
                url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                success: function (result) {
                    $('#icon_preview').append ($('<img />').attr ('src', result));
                }
            });
        }
        $('#icon_preview').fadeIn ();
    });
}

function parent_changed () {
    var inputs = [];
    inputs.push ("get_group_json=1");
    inputs.push ("id_group=" + this.value);
    inputs.push ("page=godmode/groups/group_list");
    jQuery.ajax ({
        data: inputs.join ("&"),
        type: 'GET',
        url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        dataType: 'json',
        success: function (data) {
            var data_ = data;
            $('#parent_preview').fadeOut ('normal', function () {
                $('#parent_preview').empty ();
                if (data_ != null) {
                    if(data['icon'] == '') {
                        data['icon'] = 'without_group';
                    }
                    var params = [];
                    params.push("get_image_path=1");
                    params.push("img_src=images/" + data['icon']);
                    params.push("page=include/ajax/skins.ajax");
                    params.push("only_src=1");
                    jQuery.ajax ({
                        data: params.join ("&"),
                        type: 'POST',
                        url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                        success: function (result) {
                            $('#parent_preview').append ($('<img />').attr ('src', result));
                        }
                    });
                }
                $('#parent_preview').fadeIn ();
            });
        }
    });
}

$(document).ready (function () {
    $('#icon').change (icon_changed);
    $('#id_parent').change (parent_changed);
    $('#button-button_back').on('click', function(){
        window.location = '<?php echo ui_get_full_url('index.php?sec='.$sec.'&sec2=godmode/groups/group_list'); ?>';
    });
});
</script>
