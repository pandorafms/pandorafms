<?php
/**
 * Module library view.
 *
 * @category   Module library
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

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AR')) {
    // Doesn't have access to this page.
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Module Library View'
    );
    include 'general/noaccess.php';
    exit;
}


// Header.
if (check_acl($config['id_user'], 0, 'PM') && enterprise_installed()) {
    $buttons['setup'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=general&sec2=godmode/setup/setup&amp;section=module_library">'.html_print_image('images/configuration@svg.svg', true, ['title' => __('Setup'), 'class' => 'main_menu_icon invert_filter']).'</a>',
    ];
}

$buttons['categories'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories">'.html_print_image('images/logs@svg.svg', true, ['title' => __('Categories'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];

$buttons['view'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view">'.html_print_image('images/see-details@svg.svg', true, ['title' => __('View'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];


$tab = get_parameter('tab', 'view');
if ($tab !== 'search_module') {
    $buttons[$tab]['active'] = true;
}

$headerTitle = ($tab === 'categories') ? __('Categories') : __('Main view');

// Header.
ui_print_standard_header(
    $headerTitle,
    '',
    false,
    'module_library',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Module library'),
        ],
    ]
);

// Styles.
ui_require_css_file('module_library');


// Get params.
$page = get_parameter('page', '1');
$search = get_parameter('search', '');
$id_cat = get_parameter('id_cat', '');

// Show error messages.
echo '<div id="show_errors_library"></div>';

echo '<div id="module_library_main">';

$sidebar_library = '
<div class="sidebar_library">
    <h3>'.__('Search').'</h3>
        <input id="search_module" name="search_module" placeholder="Search module" type="text" class="search_input"/>
    <h3>'.__('Categories').'</h3>
    <div id="categories_sidebar"><ul></ul></div>
</div>
';

switch ($tab) {
    case 'search_module':
        echo '<div class="content_library">';
            echo '<div id="search_title_result"><h2>'.__('Search').': </h2></div>';
            echo '<div id="search_result" class="result_string-'.$search.'"></div>';
            echo '<div id="pagination_library" class="page-'.$page.'"></div>';
            echo '<div id="modal_library"></div>';
        echo '</div>';
        echo $sidebar_library;
    break;

    case 'categories':
        if ($id_cat != '') {
            echo '<div class="content_library">';
                echo '<div id="category_title_result"><h2>'.__('Category').': </h2></div>';
                echo '<div id="category_result" class="result_category-'.$id_cat.'"></div>';
                echo '<div id="pagination_library" class="page-'.$page.'"></div>';
                echo '<div id="modal_library"></div>';
            echo '</div>';
            echo $sidebar_library;
        } else {
            echo '<div id="categories_library">';
            echo '</div>';
        }
    break;

    default:
        echo '<div id="library_main">';
        echo '<span></span>';
        echo '<p></p>';
        echo '<div id="library_main_content">';
        // Show 9 categories.
        for ($i = 1; $i <= 9; $i++) {
            echo '<div class="library_main_category"></div>';
        }

        echo '</div>';
        echo '<button name="view_all" class="sub next">
              <a class="category_link"href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories">'.__('View all categories').'</a>
              </button>';
        echo '</div>';
        echo $sidebar_library;
    break;
}

echo '</div>';

?>
<script>
var more_details = '<?php echo __('More details'); ?>';
var total_modules_text = '<?php echo __('Total modules'); ?>';
var view_web = '<?php echo __('View in Module Library'); ?>';
var empty_result = '<?php echo __('No module found'); ?>';
var error_get_token = '<?php echo __('Problem with authentication. Check your internet connection'); ?>';
var invalid_user = '<?php echo __('Invalid username or password'); ?>';
var error_main = '<?php echo __('Error loading Module Library'); ?>';
var error_category = '<?php echo __('Error loading category'); ?>';
var error_categories = '<?php echo __('Error loading categories'); ?>';
var error_no_category = '<?php echo __('There is no such category'); ?>';
var error_search = '<?php echo __('Error loading results'); ?>';
var token = null;
</script>

<?php
if (check_acl($config['id_user'], 0, 'AW') && enterprise_installed()) {
    enterprise_include_once('include/functions_module_library.php');
}

ui_require_javascript_file('module_library');

