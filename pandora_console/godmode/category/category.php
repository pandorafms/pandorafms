<?php
/**
 * Category.
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

// Check login and ACLs.
check_login();

if (!check_acl($config['id_user'], 0, 'PM') && !is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Categories Management'
    );
    include 'general/noaccess.php';
    return;
}

// Include functions code.
require_once $config['homedir'].'/include/functions_categories.php';

// Get parameters.
$delete = (int) get_parameter('delete_category', 0);
$search = (int) get_parameter('search_category', 0);
$category_name = (string) get_parameter('category_name', '');
$tab = (string) get_parameter('tab', 'list');

$sec = (is_metaconsole() === true) ? 'advanced' : 'galertas';

$buttons = [
    'list' => [
        'active' => false,
        'text'   => '<a href="index.php?sec='.$sec.'&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure'].'">'.html_print_image(
            'images/logs@svg.svg',
            true,
            [
                'title' => __('List categories'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>',
    ],
];

$buttons[$tab]['active'] = true;

ui_print_standard_header(
    __('Categories configuration'),
    'images/gm_modules.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Resources'),
        ],
        [
            'link'  => '',
            'label' => __('Module categories'),
        ],
    ]
);

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/category/category&tab=list&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All categories information is read only. Go to %s to manage it.',
            $url
        )
    );
}

// Two actions can performed in this page: search and delete categories
// Delete action: This will delete a category.
if ($is_management_allowed === true && $delete != 0) {
    $return_delete = categories_delete_category($delete);
    if (!$return_delete) {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Fail try to delete category #'.$delete
        );
        ui_print_error_message(__('Error deleting category'));
    } else {
        db_pandora_audit(
            AUDIT_LOG_CATEGORY_MANAGEMENT,
            'Delete category #'.$delete
        );
        ui_print_success_message(__('Successfully deleted category'));
    }
}

// Statements for pagination.
$url = ui_get_url_refresh();
$total_categories = categories_get_category_count();

$filter['offset'] = (int) get_parameter('offset');
$filter['limit'] = (int) $config['block_size'];
// Search action: This will filter the display category view.
$result = false;

$result = db_get_all_rows_filter(
    'tcategory',
    [
        'limit'  => $filter['limit'],
        'offset' => $filter['offset'],
    ]
);

// Display categories previously filtered or not.
$rowPair = true;
$iterator = 0;

if (empty($result) === false) {
    $table = new stdClass();
    $table->class = 'info_table';

    $table->data = [];
    $table->head = [];
    $table->align = [];
    $table->style = [];
    $table->style[0] = 'font-weight: bold; text-align:left';
    $table->style[1] = 'text-align:center; width: 100px;';
    $table->head[0] = __('Category name');
    if ($is_management_allowed === true) {
        $table->head[1] = __('Actions');
    }

    foreach ($result as $category) {
        if ($rowPair) {
            $table->rowclass[$iterator] = 'rowPair';
        } else {
            $table->rowclass[$iterator] = 'rowOdd';
        }

        $rowPair = !$rowPair;
        $iterator++;

        $data = [];

        if (is_metaconsole() === true) {
            $data[0] = "<a href='index.php?sec=advanced&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".$category['name'].'</a>';
            $data[1] = "<a href='index.php?sec=advanced&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".html_print_image(
                'images/edit.svg',
                true,
                [
                    'title' => __('Edit'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>&nbsp;&nbsp;';
            $data[1] .= '<a  href="index.php?sec=advanced&sec2=godmode/category/category&delete_category='.$category['id'].'&pure='.(int) $config['pure'].'"onclick="if (! confirm (\''.__('Are you sure?').'\')) return false">'.html_print_image(
                'images/delet.svg',
                true,
                [
                    'title' => __('Delete'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>';
        } else {
            if ($is_management_allowed === true) {
                $data[0] = "<a href='index.php?sec=gmodules&sec2=godmode/category/edit_category&action=update&id_category=".$category['id'].'&pure='.(int) $config['pure']."'>".$category['name'].'</a>';
            } else {
                $data[0] = $category['name'];
            }

            if ($is_management_allowed === true) {
                $table->cellclass[][1] = 'table_action_buttons';
                $tableActionButtonsContent = [];
                $tableActionButtonsContent[] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=gmodules&sec2=godmode/category/edit_category&action=update&id_category='.$category['id'].'&pure='.(int) $config['pure'],
                        'content' => html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'title' => __('Edit'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        ),
                    ],
                    true
                );

                $tableActionButtonsContent[] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=gmodules&sec2=godmode/category/category&delete_category='.$category['id'].'&pure='.(int) $config['pure'],
                        'onClick' => 'if (! confirm (\''.__('Are you sure?').'\')) return false',
                        'content' => html_print_image(
                            'images/delete.svg',
                            true,
                            [
                                'title' => __('Delete'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        ),
                    ],
                    true
                );

                $data[1] = implode('', $tableActionButtonsContent);
            }
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    $tablePagination = ui_pagination($total_categories, $url, $offset, 0, true, 'offset', false);
} else {
    // No categories available or selected.
    ui_print_info_message(['no_close' => true, 'message' => __('No categories found') ]);
}

if ($is_management_allowed === true) {
    // Form to add new categories or search categories.
    $sec = (is_metaconsole() === true) ? 'advanced' : 'gmodules';

    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/category/edit_category&action=new&pure='.(int) $config['pure'].'">';

    html_print_input_hidden('create_category', '1', true);

    html_print_action_buttons(
        html_print_submit_button(
            __('Create category'),
            'create_button',
            false,
            [ 'icon' => 'next' ],
            true
        ),
        [ 'right_content' => $tablePagination ]
    );

    echo '</form>';
}
