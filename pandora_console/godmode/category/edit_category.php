<?php
/**
 * Edit Category.
 *
 * @category   Category
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

check_login();

enterprise_hook('open_meta_frame');

// Include functions code.
require_once $config['homedir'].'/include/functions_categories.php';

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Edit Category'
    );
    include 'general/noaccess.php';

    return;
}

// Get parameters
$action = (string) get_parameter('action', '');
$id_category = (int) get_parameter('id_category', 0);
$update_category = (int) get_parameter('update_category', 0);
$create_category = (int) get_parameter('create_category', 0);
$name_category = (string) get_parameter('name_category', '');
$tab = (string) get_parameter('tab', 'list');

if (is_metaconsole() === true) {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=advanced&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure'].'">'.html_print_image(
                'images/list.png',
                true,
                [
                    'title' => __('List categories'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ],
    ];
} else {
    $buttons = [
        'list' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gmodules&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure'].'">'.html_print_image(
                'images/list.png',
                true,
                [
                    'title' => __('List categories'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ],
    ];
}

$buttons[$tab]['active'] = false;

// Header.
if (is_metaconsole() === true) {
    ui_meta_print_header(__('Categories configuration'), __('Editor'), $buttons);
} else {
    ui_print_page_header(__('Categories configuration'), 'images/gm_modules.png', false, '', true, $buttons);
}


// Two actions can performed in this page: update and create categories
// Update category: update an existing category
if ($update_category && $id_category != 0) {
    $values = [];
    $values['name'] = $name_category;

    $result = false;
    if ($values['name'] != '') {
        $result = db_process_sql_update('tcategory', $values, ['id' => $id_category]);
    }

    if ($result === false) {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Fail try to update category #'.$id_category
        );
        ui_print_error_message(__('Error updating category'));
    } else {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Update category #'.$id_category
        );
        ui_print_success_message(__('Successfully updated category'));
    }
}

// Create category: creates a new category.
if ($create_category) {
    $return_create = true;

    $values = [];
    $values['name'] = $name_category;

    // DB insert.
    $return_create = false;
    if ($values['name'] != '') {
        $return_create = db_process_sql_insert('tcategory', $values);
    }

    if ($return_create === false) {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Fail try to create category'
        );
        ui_print_error_message(__('Error creating category'));
        $action = 'new';
        // If create action ends successfully then current action is update.
    } else {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Create category #'.$return_create
        );
        ui_print_success_message(__('Successfully created category'));
        $id_category = $return_create;
        $action = 'update';
    }
}

// Form fields are filled here
// Get results when update action is performed.
if ($action == 'update' && $id_category != 0) {
    $result_category = db_get_row_filter('tcategory', ['id' => $id_category]);
    $name_category = $result_category['name'];
} //end if
else {
    $name_category = '';
}


// Create/Update category form
echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/category/edit_category&action='.$action.'&id_category='.$id_category.'&pure='.(int) $config['pure'].'" enctype="multipart/form-data">';

if (!defined('METACONSOLE')) {
    echo '<div align=left  class="pandora_form w100p">';
} else {
    echo '<div align=left  class="pandora_form w100p">';
}

echo "<table border=0 cellpadding=4 cellspacing=4 class='databox filters' width=100%>";

if (defined('METACONSOLE')) {
    if ($action == 'update') {
        echo '<thead>
					<tr>
						<th align=center colspan=5>'.__('Update category').'</th>
					</tr>
				</thead>';
    }

    if ($action == 'new') {
        echo '<thead>
					<tr>
						<th align=center colspan=5>'.__('Create category').'</th>
					</tr>
				</thead>';
    }
}

    echo '<tr>';
        echo "<td class='bolder'>";

        html_print_label(__('Name'), 'name');
        echo '</td>';
        echo '<td>';
        html_print_input_text('name_category', $name_category);
        echo '</td>';
    echo '</tr>';

echo '</table>';

if ($action === 'update') {
    html_print_input_hidden('update_category', 1);
    $buttonCaption = __('Update');
    $buttonName = 'update_button';
    $buttonIcon = 'update';
} else if ($action === 'new') {
    html_print_input_hidden('create_category', 1);
    $buttonCaption = __('Create');
    $buttonName = 'create_button';
    $buttonIcon = 'next';
}

html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            $buttonCaption,
            $buttonName,
            false,
            [ 'icon' => $buttonIcon ],
            true
        ),
    ]
);

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');
