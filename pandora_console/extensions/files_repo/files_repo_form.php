<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

$full_extensions_dir = $config['homedir'].'/'.EXTENSIONS_DIR.'/';
require_once $full_extensions_dir.'files_repo/functions_files_repo.php';

$file = [];
$file['name'] = '';
$file['description'] = '';
$file['hash'] = '';
$file['groups'] = [];
if (isset($file_id) && $file_id > 0) {
    $file = files_repo_get_files(['id' => $file_id]);
    if (empty($file)) {
        $file_id = 0;
    } else {
        $file = $file[$file_id];
    }
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters filter-table-adv';
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->data = [];

// GROUPS.
$groups = groups_get_all();
// Add the All group to the beginning to be always the first.
// Use this instead array_unshift to keep the array keys.
$groups = ([0 => __('All')] + $groups);
$groups_selected = [];
foreach ($groups as $id => $name) {
    if (in_array($id, $file['groups'])) {
        $groups_selected[] = $id;
    }
}

$row = [];
$row[0] = html_print_label_input_block(
    __('Groups'),
    html_print_select_groups(
        // Id_user.
        false,
        // Privilege.
        'AR',
        // ReturnAllGroup.
        true,
        // Name.
        'groups[]',
        // Selected.
        $groups_selected,
        // Script.
        '',
        // Nothing.
        '',
        // Nothing_value.
        0,
        // Return.
        true,
        // Multiple.
        true
    )
);

// DESCRIPTION.
$row[1] = html_print_label_input_block(
    __('Description').ui_print_help_tip(__('Only 200 characters are permitted'), true),
    html_print_textarea(
        'description',
        4,
        20,
        $file['description'],
        'class="file_repo_description" style="min-height: 60px; max-height: 60px;"',
        true
    )
);
$table->data[] = $row;

// FILE and SUBMIT BUTTON.
$row = [];
// Public checkbox.
$checkbox = html_print_checkbox('public', 1, (bool) !empty($file['hash']), true);
$style = 'class="inline padding-2-10"';

$row[0] = __('File');
if ($file_id > 0) {
    $submit_button = html_print_submit_button(
        __('Update'),
        'submit',
        false,
        ['icon' => 'wand'],
        true
    );

    $row[0] = html_print_label_input_block(
        __('File'),
        $file['name']
    );

    $row[1] = html_print_label_input_block(
        __('Public link'),
        $checkbox.html_print_input_hidden(
            'file_id',
            $file_id,
            true
        )
    );
} else {
    $submit_button = html_print_submit_button(
        __('Add'),
        'submit',
        false,
        ['icon' => 'wand'],
        true
    );

    $row[0] = html_print_label_input_block(
        __('File'),
        html_print_input_file(
            'upfile',
            true
        )
    );

    $row[1] = html_print_label_input_block(
        __('Public link'),
        $checkbox.html_print_input_hidden(
            'add_file',
            1,
            true
        )
    );
}



$table->data[] = $row;

$url = ui_get_full_url('index.php?sec=godmode/extensions&sec2=extensions/files_repo');
echo "<form method='post' action='$url' enctype='multipart/form-data'>";
html_print_table($table);
html_print_action_buttons($submit_button);
echo '</form>';

?>

<script language="javascript" type="text/javascript">

    $(document).ready (function () {

        var all_enabled = $(".chkb_all").prop("checked");
        if (all_enabled) {
            $(".chkb_group").prop("checked", false);
            $(".chkb_group").prop("disabled", true);
        }

        $(".chkb_all").click(function () {
            all_enabled = $(".chkb_all").prop("checked");
            if (all_enabled) {
                $(".chkb_group").prop("checked", false);
                $(".chkb_group").prop("disabled", true);
            } else {
                $(".chkb_group").prop("disabled", false);
            }
        });

    });

</script>