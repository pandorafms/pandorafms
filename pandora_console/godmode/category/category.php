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

// Check login and ACLs
check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Categories Management');
    include 'general/noaccess.php';
    return;
}

// Include functions code
require_once $config['homedir'].'/include/functions_categories.php';

// Get parameters
$delete = (int) get_parameter('delete_category', 0);
$search = (int) get_parameter('search_category', 0);
$category_name = (string) get_parameter('category_name', '');
$tab = (string) get_parameter('tab', 'list');

if (defined('METACONSOLE')) {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=advanced&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure'].'">'.html_print_image('images/list.png', true, ['title' => __('List categories')]).'</a>',
        ],
    ];
} else {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=galertas&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure'].'">'.html_print_image('images/list.png', true, ['title' => __('List categories')]).'</a>',
        ],
    ];
}

$buttons[$tab]['active'] = true;

// Header
if (defined('METACONSOLE')) {
    ui_meta_print_header(__('Categories configuration'), __('List'), $buttons);
} else {
    ui_print_page_header(__('Categories configuration'), 'images/gm_modules.png', false, '', true, $buttons);
}


// Two actions can performed in this page: search and delete categories
// Delete action: This will delete a category
if ($delete != 0) {
    $return_delete = categories_delete_category($delete);
    if (!$return_delete) {
        db_pandora_audit('Category management', "Fail try to delete category #$delete");
        ui_print_error_message(__('Error deleting category'));
    } else {
        db_pandora_audit('Category management', "Delete category #$delete");
        ui_print_success_message(__('Successfully deleted category'));
    }
}

// statements for pagination
$url = ui_get_url_refresh();
$total_categories = categories_get_category_count();

$filter['offset'] = (int) get_parameter('offset');
$filter['limit'] = (int) $config['block_size'];
// Search action: This will filter the display category view
$result = false;

$result = db_get_all_rows_filter(
    'tcategory',
    [
        'limit'  => $filter['limit'],
        'offset' => $filter['offset'],
    ]
);

// Display categories previously filtered or not
$rowPair = true;
$iterator = 0;

if (!empty($result)) {
    // Prepare pagination
    ui_pagination($total_categories, $url);

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';

    $table->data = [];
    $table->head = [];
    $table->align = [];
    $table->style = [];
    $table->style[0] = 'font-weight: bold; text-align:left';
    $table->style[1] = 'text-align:center; width: 100px;';
    $table->head[0] = __('Category name');
    $table->head[1] = __('Actions');

    foreach ($result as $category) {
        if ($rowPair) {
            $table->rowclass[$iterator] = 'rowPair';
        } else {
            $table->rowclass[$iterator] = 'rowOdd';
        }

        $rowPair = !$rowPair;
        $iterator++;

        $data = [];

        if (defined('METACONSOLE')) {
            $data[0] = "<a href='index.php?sec=advanced&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".$category['name'].'</a>';
            $data[1] = "<a href='index.php?sec=advanced&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".html_print_image('images/config.png', true, ['title' => 'Edit']).'</a>&nbsp;&nbsp;';
            $data[1] .= '<a  href="index.php?sec=advanced&sec2=godmode/category/category&delete_category='.$category['id'].'&pure='.(int) $config['pure'].'"onclick="if (! confirm (\''.__('Are you sure?').'\')) return false">'.html_print_image('images/cross.png', true, ['title' => 'Delete']).'</a>';
        } else {
            $data[0] = "<a href='index.php?sec=gmodules&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".$category['name'].'</a>';
            $table->cellclass[][1] = 'action_buttons';
            $data[1] = "<a href='index.php?sec=gmodules&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".html_print_image('images/config.png', true, ['title' => 'Edit']).'</a>';
            $data[1] .= '<a  href="index.php?sec=gmodules&sec2=godmode/category/category&delete_category='.$category['id'].'&pure='.(int) $config['pure'].'"onclick="if (! confirm (\''.__('Are you sure?').'\')) return false">'.html_print_image('images/cross.png', true, ['title' => 'Delete']).'</a>';
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    ui_pagination($total_categories, $url, $offset, 0, false, 'offset', true, 'pagination-bottom');
} else {
    // No categories available or selected
    ui_print_info_message(['no_close' => true, 'message' => __('No categories found') ]);
}

// Form to add new categories or search categories
echo "<div style='width=100%; float:right;'>";
if (defined('METACONSOLE')) {
    echo '<form method="post" action="index.php?sec=advanced&sec2=godmode/category/edit_category&action=new&pure='.(int) $config['pure'].'">';
} else {
    echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/category/edit_category&action=new&pure='.(int) $config['pure'].'">';
}

    html_print_input_hidden('create_category', '1', true);
    html_print_submit_button(__('Create category'), 'create_button', false, 'class="sub next"');
    echo '</form>';
echo '</div>';

enterprise_hook('close_meta_frame');
