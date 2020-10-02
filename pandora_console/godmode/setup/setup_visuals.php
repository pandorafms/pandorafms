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
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Visual Setup Management');
    include 'general/noaccess.php';
    return;
}

// FIX: this constant is declared to in godmode/reporting/reporting_builder.phps
// Constant with fonts directory
define('_MPDF_TTFONTPATH', $config['homedir'].'/include/fonts/');

require_once 'include/functions_post_process.php';

// Load enterprise extensions
enterprise_include('godmode/setup/setup_visuals.php');

/*
    NOTICE FOR DEVELOPERS:

    Update operation is done in config_process.php
    This is done in that way so the user can see the changes inmediatly.
    If you added a new token, please check config_update_config() in functions_config.php
    to add it there.
*/

require_once 'include/functions_themes.php';
require_once 'include/functions_gis.php';

$row = 0;
echo '<form id="form_setup" method="post">';
html_print_input_hidden('update_config', 1);

// ----------------------------------------------------------------------
// BEHAVIOUR CONFIGURATION
// ----------------------------------------------------------------------
$table_behaviour = new stdClass();
$table_behaviour->width = '100%';
$table_behaviour->class = 'databox filters';
$table_behaviour->style[0] = 'font-weight: bold;';
$table_behaviour->size[0] = '50%';
$table_behaviour->data = [];

$table_behaviour->data[$row][0] = __('Block size for pagination');
$table_behaviour->data[$row][1] = html_print_input_text('block_size', $config['global_block_size'], '', 5, 5, true);
$row++;

$values = [];
$values[5] = human_time_description_raw(5);
$values[30] = human_time_description_raw(30);
$values[SECONDS_1MINUTE] = human_time_description_raw(SECONDS_1MINUTE);
$values[SECONDS_2MINUTES] = human_time_description_raw(SECONDS_2MINUTES);
$values[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
$values[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
$values[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);

$table_behaviour->data[$row][0] = __('Paginated module view');
$table_behaviour->data[$row][1] = html_print_checkbox_switch(
    'paginate_module',
    1,
    $config['paginate_module'],
    true
);
$row++;

$table_behaviour->data[$row][0] = __('Display data of proc modules in other format');
$table_behaviour->data[$row][1] = html_print_checkbox_switch(
    'render_proc',
    1,
    $config['render_proc'],
    true
);
$row++;

$table_behaviour->data[$row][0] = __('Display text proc modules have state is ok');
$table_behaviour->data[$row][1] = html_print_input_text('render_proc_ok', $config['render_proc_ok'], '', 25, 25, true);
$row++;

$table_behaviour->data[$row][0] = __('Display text when proc modules have state critical');
$table_behaviour->data[$row][1] = html_print_input_text('render_proc_fail', $config['render_proc_fail'], '', 25, 25, true);
$row++;

// Daniel maya 02/06/2016 Display menu with click --INI
$table_behaviour->data[$row][0] = __('Click to display lateral menus');
$table_behaviour->data[$row][1] = html_print_checkbox_switch(
    'click_display',
    1,
    $config['click_display'],
    true
);
$row++;
// Daniel maya 02/06/2016 Display menu with click --END
if (enterprise_installed()) {
    $table_behaviour->data[$row][0] = __('Service label font size');
    $table_behaviour->data[$row][1] = html_print_input_text('service_label_font_size', $config['service_label_font_size'], '', 5, 5, true);
    $row++;

    $table_behaviour->data[$row][0] = __('Space between items in Service maps');
    $table_behaviour->data[$row][1] = html_print_input_text('service_item_padding_size', $config['service_item_padding_size'], '', 5, 5, true, false, false, 'onChange="change_servicetree_nodes_padding()"');
    $row++;
}

echo '<fieldset>';
echo '<legend>'.__('Behaviour configuration').' '.ui_print_help_icon('behavoir_conf_tab', true).'</legend>';
html_print_table($table_behaviour);
echo '</fieldset>';
// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// STYLE CONFIGURATION
// ----------------------------------------------------------------------
$table_styles = new stdClass();
$table_styles->width = '100%';
$table_styles->class = 'databox filters';
$table_styles->style[0] = 'font-weight: bold;';
$table_styles->size[0] = '50%';
$table_styles->data = [];

$table_styles->data[$row][0] = __('Style template');
$table_styles->data[$row][1] = html_print_select(
    themes_get_css(),
    'style',
    $config['style'].'.css',
    '',
    '',
    '',
    true
);
$row++;

$table_styles->data[$row][0] = __('Status icon set');
$iconsets['default'] = __('Colors');
$iconsets['faces'] = __('Faces');
$iconsets['color_text'] = __('Colors and text');
$table_styles->data[$row][1] = html_print_select(
    $iconsets,
    'status_images_set',
    $config['status_images_set'],
    '',
    '',
    '',
    true
);
$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'status_set_preview', false, '', 'class="sub camera logo_preview"', true);
$row++;

// Divs to show icon status Colours (Default).
$icon_unknown_ball = ui_print_status_image(STATUS_AGENT_UNKNOWN_BALL, '', true);
$icon_unknown = ui_print_status_image(STATUS_AGENT_UNKNOWN, '', true);
$icon_ok_ball = ui_print_status_image(STATUS_AGENT_OK_BALL, '', true);
$icon_ok = ui_print_status_image(STATUS_AGENT_OK, '', true);
$icon_warning_ball = ui_print_status_image(STATUS_AGENT_WARNING_BALL, '', true);
$icon_warning = ui_print_status_image(STATUS_AGENT_WARNING, '', true);
$icon_bad_ball = ui_print_status_image(STATUS_AGENT_CRITICAL_BALL, '', true);
$icon_bad = ui_print_status_image(STATUS_AGENT_CRITICAL, '', true);
// End - Divs to show icon status Colours (Default).
$table_styles->data[$row][0] = __('Login background');
$backgrounds_list_jpg = list_files('images/backgrounds', 'jpg', 1, 0);
$backgrounds_list_gif = list_files('images/backgrounds', 'gif', 1, 0);
$backgrounds_list_png = list_files('images/backgrounds', 'png', 1, 0);
$backgrounds_list = array_merge($backgrounds_list_jpg, $backgrounds_list_png);
$backgrounds_list = array_merge($backgrounds_list, $backgrounds_list_gif);
asort($backgrounds_list);

if (!enterprise_installed()) {
    $open = true;
}

// Custom favicon.
$files = list_files('images/custom_favicon', 'ico', 1, 0);
$table_styles->data[$row][0] = __('Custom favicon');
$table_styles->data[$row][1] = html_print_select(
    $files,
    'custom_favicon',
    $config['custom_favicon'],
    'setup_visuals_change_favicon();',
    __('Default'),
    '',
    true,
    false,
    true,
    '',
    false,
    'width:240px'
);
$table_styles->data[$row][1] .= '&nbsp;&nbsp;&nbsp;'.html_print_image(
    ui_get_favicon(),
    true,
    ['id' => 'favicon_preview']
);
$row++;

$table_styles->data[$row][0] = __('Custom background logo');
$table_styles->data[$row][1] = html_print_select(
    $backgrounds_list,
    'login_background',
    $config['login_background'],
    '',
    __('Default'),
    '',
    true,
    false,
    true,
    '',
    false,
    'width:240px'
);
$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'login_background_preview', false, '', 'class="sub camera logo_preview"', true);
$row++;


/**
 * Print a select for the custom logos.
 *
 * @param  string $name This is the name for the select
 * @param  string $logo This is the option in $config (path)
 * @return string Print the select
 */
function logo_custom_enterprise($name, $logo)
{
    if (enterprise_installed()) {
        $ent_files = list_files('enterprise/images/custom_logo', 'png', 1, 0);
        $open_files = list_files('images/custom_logo', 'png', 1, 0);

        $select = html_print_select(
            array_merge($ent_files, $open_files),
            $name,
            $logo,
            '',
            '',
            '',
            true,
            false,
            true,
            '',
            false,
            'width:240px'
        );
        return $select;
    } else {
        $select = html_print_select(
            list_files('images/custom_logo', 'png', 1, 0),
            $name,
            $logo,
            '',
            '',
            '',
            true,
            false,
            true,
            '',
            true,
            'width:240px'
        );
        return $select;
    }
}


$table_styles->data[$row][0] = __('Custom logo (menu)');
$table_styles->data[$row][1] = logo_custom_enterprise('custom_logo', $config['custom_logo']);
$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_logo_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
$row++;

$table_styles->data[$row][0] = __('Custom logo collapsed (menu)');
$table_styles->data[$row][1] = logo_custom_enterprise('custom_logo_collapsed', $config['custom_logo_collapsed']);
$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_logo_collapsed_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
$row++;

$table_styles->data[$row][0] = __('Custom logo (header white background)');
if (enterprise_installed()) {
    $ent_files = list_files('enterprise/images/custom_logo', 'png', 1, 0);
    $open_files = list_files('images/custom_logo', 'png', 1, 0);

    $table_styles->data[$row][1] = html_print_select(
        array_merge($open_files, $ent_files),
        'custom_logo_white_bg',
        $config['custom_logo_white_bg'],
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        $open,
        'width:240px'
    );
} else {
    $table_styles->data[$row][1] = html_print_select(
        list_files('images/custom_logo', 'png', 1, 0),
        'custom_logo_white_bg',
        $config['custom_logo_white_bg'],
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        $open,
        'width:240px'
    );
}

$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_logo_white_bg_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
$row++;

$table_styles->data[$row][0] = __('Custom logo (login)');

if (enterprise_installed()) {
    $table_styles->data[$row][1] = html_print_select(
        list_files('enterprise/images/custom_logo_login', 'png', 1, 0),
        'custom_logo_login',
        $config['custom_logo_login'],
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        $open,
        'width:240px'
    );
} else {
    $table_styles->data[$row][1] = html_print_select(
        '',
        'custom_logo_login',
        $config['custom_logo_login'],
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        $open,
        'width:240px'
    );
}

$table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_logo_login_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
$row++;

// Splash login
if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Custom Splash (login)');

    $table_styles->data[$row][1] = html_print_select(
        list_files('enterprise/images/custom_splash_login', 'png', 1, 0),
        'custom_splash_login',
        $config['custom_splash_login'],
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        $open,
        'width:240px'
    );

    $table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_splash_login_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
    $row++;
}

if (enterprise_installed()) {
    // Get all the custom logos.
    $files = list_files('enterprise/images/custom_general_logos', 'png', 1, 0);

    // Custom docs icon.
    $table_styles->data[$row][0] = __('Custom documentation logo');

    $table_styles->data[$row][1] = html_print_select(
        $files,
        'custom_docs_logo',
        $config['custom_docs_logo'],
        '',
        __('None'),
        '',
        true,
        false,
        true,
        '',
        false,
        'width:240px'
    );
    $table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_docs_logo_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
    $row++;

    // Custom support icon.
    $table_styles->data[$row][0] = __('Custom support logo');
    $table_styles->data[$row][1] = html_print_select(
        $files,
        'custom_support_logo',
        $config['custom_support_logo'],
        '',
        __('None'),
        '',
        true,
        false,
        true,
        '',
        false,
        'width:240px'
    );
    $table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_support_logo_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
    $row++;

    // Custom center networkmap icon.
    $table_styles->data[$row][0] = __('Custom networkmap center logo');
    $table_styles->data[$row][1] = html_print_select(
        $files,
        'custom_network_center_logo',
        $config['custom_network_center_logo'],
        '',
        __('Default'),
        '',
        true,
        false,
        true,
        '',
        false,
        'width:240px'
    );
    $table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_network_center_logo_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
    $row++;

    // Custom center mobile console icon.
    $table_styles->data[$row][0] = __('Custom mobile console icon');
    $table_styles->data[$row][1] = html_print_select(
        $files,
        'custom_mobile_console_logo',
        $config['custom_mobile_console_logo'],
        '',
        __('Default'),
        '',
        true,
        false,
        true,
        '',
        false,
        'width:240px'
    );
    $table_styles->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'custom_mobile_console_logo_preview', $open, '', 'class="sub camera logo_preview"', true, false, $open, 'visualmodal');
    $row++;
}

// Title Header
if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Title (header)');
    $table_styles->data[$row][1] = html_print_input_text('custom_title_header', $config['custom_title_header'], '', 50, 40, true);
    $row++;
}

// Subtitle Header
if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Subtitle (header)');
    $table_styles->data[$row][1] = html_print_input_text('custom_subtitle_header', $config['custom_subtitle_header'], '', 50, 40, true);
    $row++;
}

// login title1
if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Title 1 (login)');
    $table_styles->data[$row][1] = html_print_input_text('custom_title1_login', $config['custom_title1_login'], '', 50, 50, true);
    $row++;
}

// login text2
if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Title 2 (login)');
    $table_styles->data[$row][1] = html_print_input_text('custom_title2_login', $config['custom_title2_login'], '', 50, 50, true);
    $row++;
}

if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Docs URL (login)');
    $table_styles->data[$row][1] = html_print_input_text('custom_docs_url', $config['custom_docs_url'], '', 50, 50, true);
    $row++;
}

if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Support URL (login)');
    $table_styles->data[$row][1] = html_print_input_text('custom_support_url', $config['custom_support_url'], '', 50, 50, true);
    $row++;
}

if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Product name');
    $table_styles->data[$row][1] = html_print_input_text('rb_product_name', get_product_name(), '', 30, 255, true);
    $row++;
}

if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Copyright notice');
    $table_styles->data[$row][1] = html_print_input_text('rb_copyright_notice', get_copyright_notice(), '', 30, 255, true);
    $row++;
}

if (enterprise_installed()) {
    $table_styles->data[$row][0] = __('Disable logo in graphs');
    $table_styles->data[$row][1] = html_print_checkbox_switch(
        'fixed_graph',
        1,
        $config['fixed_graph'],
        true
    );
    $row++;
}

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */

$table_styles->data[$row][0] = __('Disable helps');
$table_styles->data[$row][1] = html_print_checkbox_switch(
    'disable_help',
    1,
    $config['disable_help'],
    true
);
$row++;

$table_styles->data[$row][0] = __('Fixed header');
$table_styles->data[$row][1] = html_print_checkbox_switch(
    'fixed_header',
    1,
    $config['fixed_header'],
    true
);
$row++;


    // For 5.1 Autohidden menu feature
    $table_styles->data['autohidden'][0] = __('Automatically hide submenu');
    $table_styles->data['autohidden'][1] = html_print_checkbox_switch(
        'autohidden_menu',
        1,
        $config['autohidden_menu'],
        true
    );

    $table_styles->data[$row][0] = __('Visual effects and animation');
    $table_styles->data[$row][1] = html_print_checkbox_switch(
        'visual_animation',
        1,
        $config['visual_animation'],
        true
    );


    echo '<fieldset>';
    echo '<legend>'.__('Style configuration').' '.ui_print_help_icon('style_conf_tab', true).'</legend>';
    html_print_table($table_styles);
    echo '</fieldset>';
    // ----------------------------------------------------------------------
    // ----------------------------------------------------------------------
    // GIS CONFIGURATION
    // ----------------------------------------------------------------------
    $table_gis = new stdClass();
    $table_gis->width = '100%';
    $table_gis->class = 'databox filters';
    $table_gis->style[0] = 'font-weight: bold;';
    $table_gis->size[0] = '50%';
    $table_gis->data = [];

    $table_gis->data[$row][0] = __('GIS Labels');
    $table_gis->data[$row][1] = html_print_checkbox_switch(
        'gis_label',
        1,
        $config['gis_label'],
        true
    );
    $row++;

    $listIcons = gis_get_array_list_icons();
    $arraySelectIcon = [];
    foreach ($listIcons as $index => $value) {
        $arraySelectIcon[$index] = $index;
    }

    $table_gis->data[$row][0] = __('Default icon in GIS');
    $table_gis->data[$row][1] = html_print_select(
        $arraySelectIcon,
        'gis_default_icon',
        $config['gis_default_icon'],
        '',
        __('Agent icon group'),
        '',
        true
    );
    $table_gis->data[$row][1] .= '&nbsp;'.html_print_button(__('View'), 'gis_icon_preview', false, '', 'class="sub camera logo_preview"', true);
    $row++;

    echo '<fieldset>';
    echo '<legend>'.__('GIS configuration').' '.ui_print_help_icon('gis_conf_tab', true).'</legend>';
    html_print_table($table_gis);
    echo '</fieldset>';
    // ----------------------------------------------------------------------
    // ----------------------------------------------------------------------
    // FONT AND TEXT CONFIGURATION
    // ----------------------------------------------------------------------
    $table_font = new stdClass();
    $table_font->width = '100%';
    $table_font->class = 'databox filters';
    $table_font->style[0] = 'font-weight: bold;';
    $table_font->size[0] = '50%';
    $table_font->data = [];

    $table_font->data[$row][0] = __('Graphs font family');

    // Load font families.
    $fonts = [];
    $dirFonts = scandir(_MPDF_TTFONTPATH);
    foreach ($dirFonts as $entryDir) {
        if (strstr($entryDir, '.ttf') !== false) {
            $explode = explode('-', $entryDir);
            if (count($explode) === 1) {
                $fonts[$entryDir] = substr($entryDir, 0, (strlen($entryDir) - 4));
            }

            if ($explode[1] === 'Regular.ttf') {
                $fonts[$explode[0].'.ttf'] = $explode[0];
            }
        }
    }

    $table_font->data[$row][1] = html_print_select(
        $fonts,
        'fontpath',
        io_safe_output($config['fontpath']),
        '',
        '',
        0,
        true
    );

    $row++;

    $table_font->data[$row][0] = __('Graphs font size');

    $font_size_array = [
        1  => 1,
        2  => 2,
        3  => 3,
        4  => 4,
        5  => 5,
        6  => 6,
        7  => 7,
        8  => 8,
        9  => 9,
        10 => 10,
        11 => 11,
        12 => 12,
        13 => 13,
        14 => 14,
        15 => 15,
    ];

    $table_font->data[$row][1] = html_print_select(
        $font_size_array,
        'font_size',
        $config['font_size'],
        '',
        '',
        0,
        true
    );
    $row++;

    $table_font->data[$row][0] = __('Agent size text');
    $table_font->data[$row][1] = __('Small:').html_print_input_text('agent_size_text_small', $config['agent_size_text_small'], '', 3, 3, true);
    $table_font->data[$row][1] .= ' '.__('Normal:').html_print_input_text('agent_size_text_medium', $config['agent_size_text_medium'], '', 3, 3, true);
    $row++;

    $table_font->data[$row][0] = __('Module size text');
    $table_font->data[$row][1] = __('Small:').html_print_input_text('module_size_text_small', $config['module_size_text_small'], '', 3, 3, true);
    $table_font->data[$row][1] .= ' '.__('Normal:').html_print_input_text('module_size_text_medium', $config['module_size_text_medium'], '', 3, 3, true);
    $row++;

    $table_font->data[$row][0] = __('Description size text');
    $table_font->data[$row][1] = html_print_input_text('description_size_text', $config['description_size_text'], '', 3, 3, true);
    $row++;

    $table_font->data[$row][0] = __('Item title size text');
    $table_font->data[$row][1] = html_print_input_text(
        'item_title_size_text',
        $config['item_title_size_text'],
        '',
        3,
        3,
        true
    );
    $row++;

    $table_font->data[$row][0] = __('Show unit along with value in reports');
    $table_font->data[$row][1] = html_print_checkbox_switch(
        'simple_module_value',
        1,
        $config['simple_module_value'],
        true
    );
    $row++;

    echo '<fieldset>';
    echo '<legend>'.__('Font and Text configuration').' '.ui_print_help_icon('front_and_text_conf_tab', true).'</legend>';
    html_print_table($table_font);
    echo '</fieldset>';
    // ----------------------------------------------------------------------
    // ----------------------------------------------------------------------
    // CHARS CONFIGURATION
    // ----------------------------------------------------------------------
    $table_chars = new stdClass();
    $table_chars->width = '100%';
    $table_chars->class = 'databox filters';
    $table_chars->style[0] = 'font-weight: bold;';
    $table_chars->size[0] = '50%';
    $table_chars->data = [];

    $table_chars->data[$row][0] = __('Graph color #1');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color1',
        $config['graph_color1'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #2');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color2',
        $config['graph_color2'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #3');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color3',
        $config['graph_color3'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #4');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color4',
        $config['graph_color4'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #5');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color5',
        $config['graph_color5'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #6');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color6',
        $config['graph_color6'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #7');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color7',
        $config['graph_color7'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #8');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color8',
        $config['graph_color8'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #9');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color9',
        $config['graph_color9'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph color #10');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_color10',
        $config['graph_color10'],
        '',
        8,
        8,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Value to interface graphics');
    $table_chars->data[$row][1] = html_print_input_text(
        'interface_unit',
        $config['interface_unit'],
        '',
        20,
        20,
        true
    );
    $row++;

    $disabled_graph_precision = false;
    if (enterprise_installed() === false) {
        $disabled_graph_precision = true;
    }

    $table_chars->data[$row][0] = __('Data precision');
    $table_chars->data[$row][1] = html_print_input_text(
        'graph_precision',
        $config['graph_precision'],
        '',
        5,
        5,
        true,
        $disabled_graph_precision,
        false,
        'onChange="change_precision()"'
    );
    $row++;

    if (isset($config['short_module_graph_data']) === false) {
        $config['short_module_graph_data'] = true;
    }

    $table_chars->data[$row][0] = __('Data precision in graphs');
    $table_chars->data[$row][1] = html_print_input_text(
        'short_module_graph_data',
        $config['short_module_graph_data'],
        '',
        5,
        5,
        true,
        $disabled_graph_precision,
        false,
        'onChange="change_precision()"'
    );
    $row++;

    $table_chars->data[$row][0] = __(
        'Default line thickness for the Custom Graph.'
    );
    $table_chars->data[$row][1] = html_print_input_text(
        'custom_graph_width',
        $config['custom_graph_width'],
        '',
        5,
        5,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Use round corners');
    $table_chars->data[$row][1] = html_print_checkbox_switch(
        'round_corner',
        1,
        $config['round_corner'],
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Type of module charts');
    $table_chars->data[$row][1] = __('Area').'&nbsp;'.html_print_radio_button(
        'type_module_charts',
        'area',
        '',
        $config['type_module_charts'] == 'area',
        true
    ).'&nbsp;&nbsp;';
    $table_chars->data[$row][1] .= __('Line').'&nbsp;'.html_print_radio_button(
        'type_module_charts',
        'line',
        '',
        $config['type_module_charts'] != 'area',
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Type of interface charts');
    $table_chars->data[$row][1] = __('Area').'&nbsp;'.html_print_radio_button(
        'type_interface_charts',
        'area',
        '',
        $config['type_interface_charts'] == 'area',
        true
    ).'&nbsp;&nbsp;';
    $table_chars->data[$row][1] .= __('Line').'&nbsp;'.html_print_radio_button(
        'type_interface_charts',
        'line',
        '',
        $config['type_interface_charts'] != 'area',
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Percentile');
    $table_chars->data[$row][1] = html_print_input_text(
        'percentil',
        $config['percentil'],
        '',
        20,
        20,
        true
    );
    $row++;

    $table_chars->data[$row][0] = __('Graph TIP view:');

    $options_full_escale    = [];
    $options_full_escale[0] = __('None');
    $options_full_escale[1] = __('All');
    $options_full_escale[2] = __('On Boolean graphs');

    $table_chars->data[$row][1] = html_print_select(
        $options_full_escale,
        'full_scale_option',
        $config['full_scale_option'],
        '',
        '',
        0,
        true,
        false,
        false
    );
    $row++;


    $table_chars->data[$row][0] = __('Graph mode');

    $options_soft_graphs    = [];
    $options_soft_graphs[0] = __('Show only average by default');
    $options_soft_graphs[1] = __('Show MAX/AVG/MIN by default');

    $table_chars->data[$row][1] = html_print_select(
        $options_soft_graphs,
        'type_mode_graph',
        $config['type_mode_graph'],
        '',
        '',
        0,
        true,
        false,
        false
    );
    $row++;

    $table_chars->data[$row][0] = __('Zoom graphs:');

    $options_zoom_graphs    = [];
    $options_zoom_graphs[1] = 'x1';
    $options_zoom_graphs[2] = 'x2';
    $options_zoom_graphs[3] = 'x3';
    $options_zoom_graphs[4] = 'x4';
    $options_zoom_graphs[5] = 'x5';

    $table_chars->data[$row][1] = html_print_select(
        $options_zoom_graphs,
        'zoom_graph',
        $config['zoom_graph'],
        '',
        '',
        0,
        true,
        false,
        false
    );
    $row++;

    echo '<fieldset>';
    echo '<legend>'.__('Charts configuration').' '.ui_print_help_icon('charts_conf_tab', true).'</legend>';
    html_print_table($table_chars);
    echo '</fieldset>';
    // ----------------------------------------------------------------------
    // ----------------------------------------------------------------------
    // Visual Consoles
    // ----------------------------------------------------------------------
    $table_vc = new stdClass();
    $table_vc->width = '100%';
    $table_vc->class = 'databox filters';
    $table_vc->style[0] = 'font-weight: bold';
    $table_vc->size[0] = '50%';
    $table_vc->data = [];

    // Remove when the new view reaches rock solid stability.
    $table_vc->data[$row][0] = __('Legacy Visual Console View');
    $table_vc->data[$row][1] = html_print_checkbox_switch(
        'legacy_vc',
        1,
        (bool) $config['legacy_vc'],
        true
    );
    $row++;

    $intervals = [
        10   => '10 '.__('seconds'),
        30   => '30 '.__('seconds'),
        60   => '1 '.__('minutes'),
        300  => '5 '.__('minutes'),
        900  => '15 '.__('minutes'),
        1800 => '30 '.__('minutes'),
        3600 => '1 '.__('hour'),
    ];
    $table_vc->data[$row][0] = __('Default cache expiration');
    $table_vc->data[$row][1] = html_print_extended_select_for_time(
        'vc_default_cache_expiration',
        $config['vc_default_cache_expiration'],
        '',
        __('No cache'),
        0,
        false,
        true,
        false,
        false,
        '',
        false,
        $intervals
    );
    $row++;

    $table_vc->data[$row][0] = __('Default interval for refresh on Visual Console');
    $table_vc->data[$row][1] = html_print_select($values, 'vc_refr', (int) $config['vc_refr'], '', 'N/A', 0, true, false, false);
    $row++;

    $vc_favourite_view_array[0] = __('Classic view');
    $vc_favourite_view_array[1] = __('View of favorites');
    $table_vc->data[$row][0] = __('Type of view of visual consoles');
    $table_vc->data[$row][1] = html_print_select($vc_favourite_view_array, 'vc_favourite_view', $config['vc_favourite_view'], '', '', 0, true);
    $row++;

    $table_vc->data[$row][0] = __('Number of favorite visual consoles to show in the menu');
    $table_vc->data[$row][1] = "<input type ='number' value=".$config['vc_menu_items']." size='5' name='vc_menu_items' min='0' max='25'>";
    $row++;

    $table_vc->data[$row][0] = __('Default line thickness for the Visual Console');
    $table_vc->data[$row][1] = html_print_input_text('vc_line_thickness', (int) $config['vc_line_thickness'], '', 5, 5, true);


    echo '<fieldset>';
    echo '<legend>'.__('Visual consoles configuration').' '.ui_print_help_icon('visual_consoles_conf_tab', true).'</legend>';
    html_print_table($table_vc);
    echo '</fieldset>';

    // ----------------------------------------------------------------------
    // Services
    // ----------------------------------------------------------------------
    $table_ser = new stdClass();
    $table_ser->width = '100%';
    $table_ser->class = 'databox filters';
    $table_ser->style[0] = 'font-weight: bold';
    $table_ser->size[0] = '50%';
    $table_ser->data = [];

    $table_ser->data['number'][0] = __('Number of favorite services to show in the menu');
    $table_ser->data['number'][1] = "<input type ='number' value=".$config['ser_menu_items']." size='5' name='ser_menu_items' min='0' max='25'>";

    echo '<fieldset>';
    echo '<legend>'.__('Services configuration').' '.ui_print_help_icon('services_conf_tab', true).'</legend>';
    html_print_table($table_ser);
    echo '</fieldset>';

    // ----------------------------------------------------------------------
    // Reports
    // ----------------------------------------------------------------------
    $table_report = new stdClass();
    $table_report->width = '100%';
    $table_report->class = 'databox filters';
    $table_report->style[0] = 'font-weight: bold;';
    $table_report->size[0] = '20%';

    $table_report->data = [];

    $table_report->data[$row][0] = __('Show report info with description');
    $table_report->data[$row][1] = html_print_checkbox_switch(
        'custom_report_info',
        1,
        $config['custom_report_info'],
        true
    );
    $row++;

    $table_report->data[$row][0] = __('Custom report front page');
    $table_report->data[$row][1] = html_print_checkbox_switch(
        'custom_report_front',
        1,
        $config['custom_report_front'],
        true
    );

    $row++;

    $table_report->data[$row][0] = __('PDF font size (px)');
    $table_report->data[$row][1] = "<input type ='number' value=".$config['global_font_size_report']." name='global_font_size_report' min='1' max='50' step='1'>";

    $row++;

    $table_report->data[$row][0] = __('HTML font size for SLA (em)');
    $table_report->data[$row][1] = "<input type ='number' value=".$config['font_size_item_report']." name='font_size_item_report' min='1' max='9' step='0.1'>";

    $row++;

    $table_report->data[$row][0] = __('PDF font family');
    $table_report->data[$row][1] = html_print_select(
        $fonts,
        'custom_report_front_font',
        $config['custom_report_front_font'],
        false,
        __('Default'),
        '',
        true
    );

    $row++;


    $table_report->data[$row][0] = __('Graph image height for HTML reports');
    $table_report->data[$row][1] = html_print_input_text('graph_image_height', $config['graph_image_height'], '', 20, 20, true);

    $row++;

    $interval_description = [
        'large' => 'Long',
        'tiny'  => 'Short',
    ];
    $table_report->data[$row][0] = __('Interval description');
    $table_report->data[$row][1] = html_print_select($interval_description, 'interval_description', $config['interval_description'], '', '', '', true, false, false);

    $row++;

    // ----------------------------------------------------------------------
    $dirItems = scandir($config['homedir'].'/images/custom_logo');
    foreach ($dirItems as $entryDir) {
        if (strstr($entryDir, '.jpg') !== false || strstr($entryDir, '.png') !== false) {
            $customLogos['images/custom_logo/'.$entryDir] = $entryDir;
        }
    }

    // Logo.
    $table_report->data['custom_report_front-logo'][0] = __('Custom report front').' - '.__('Custom logo').ui_print_help_tip(
        __("The dir of custom logos is in your www Console in 'images/custom_logo'. You can upload more files (ONLY JPEG AND PNG) in upload tool in console."),
        true
    );

    $table_report->data['custom_report_front-logo'][1] = html_print_select(
        $customLogos,
        'custom_report_front_logo',
        io_safe_output($config['custom_report_front_logo']),
        'showPreview()',
        __('Default'),
        '',
        true
    );
    // Preview.
    $table_report->data['custom_report_front-preview'][0] = __('Custom report front').' - '.'Preview';
    if (empty($config['custom_report_front_logo'])) {
        $config['custom_report_front_logo'] = 'images/pandora_logo_white.jpg';
    }

    $table_report->data['custom_report_front-preview'][1] = '<span id="preview_image">'.html_print_image($config['custom_report_front_logo'], true).'</span>';

    // Header.
    $table_report->data['custom_report_front-header'][0] = __('Custom report front').' - '.__('Header');

    // Do not remove io_safe_output in textarea. TinyMCE avoids XSS injection.
    $table_report->data['custom_report_front-header'][1] = html_print_textarea(
        'custom_report_front_header',
        5,
        15,
        io_safe_output($config['custom_report_front_header']),
        'style="width: 90%; height: 300px !important;"',
        true
    );

    // First page.
    // Do not remove io_safe_output in textarea. TinyMCE avoids XSS injection.
    if ($config['custom_report_front']) {
        $firstpage_content = $config['custom_report_front_firstpage'];
    } else {
        $firstpage_content = io_safe_output($config['custom_report_front_firstpage']);
    }

    $table_report->data['custom_report_front-first_page'][0] = __('Custom report front').' - '.__('First page');
    $custom_report_front_firstpage = str_replace(
        '(_URLIMAGE_)',
        ui_get_full_url(false, true, false, false),
        io_safe_output($firstpage_content)
    );
    $table_report->data['custom_report_front-first_page'][1] = html_print_textarea(
        'custom_report_front_firstpage',
        15,
        15,
        $custom_report_front_firstpage,
        'style="width: 90%; height: 300px !important;"',
        true
    );

    // Footer.
    $table_report->data['custom_report_front-footer'][0] = __('Custom report front').' - '.__('Footer');

    // Do not remove io_safe_output in textarea. TinyMCE avoids XSS injection.
    $table_report->data['custom_report_front-footer'][1] = html_print_textarea(
        'custom_report_front_footer',
        5,
        15,
        io_safe_output($config['custom_report_front_footer']),
        'style="width: 90%; height: 300px !important;"',
        true
    );

    echo '<fieldset>';
    echo '<legend>'.__('Reports configuration ').ui_print_help_icon('reports_configuration_tab', true).'</legend>';
    html_print_table($table_report);
    echo '</fieldset>';

    // ----------------------------------------------------------------------
    // OTHER CONFIGURATION
    // ----------------------------------------------------------------------
    $table_other = new stdClass();
    $table_other->width = '100%';
    $table_other->class = 'databox filters';
    $table_other->style[0] = 'font-weight: bold;';
    $table_other->size[0] = '50%';
    $table_other->size[1] = '26%';
    $table_other->size[2] = '12%';
    $table_other->size[3] = '12%';
    $table_other->data = [];

    $table_other->data[$row][0] = __('Custom graphviz directory');
    $table_other->data[$row][1] = html_print_input_text(
        'graphviz_bin_dir',
        $config['graphviz_bin_dir'],
        '',
        25,
        255,
        true
    );

    $row++;

    $table_other->data[$row][0] = __('Networkmap max width');
    $table_other->data[$row][1] = html_print_input_text(
        'networkmap_max_width',
        $config['networkmap_max_width'],
        '',
        10,
        20,
        true
    );
    $row++;

    $table_other->data[$row][0] = __('Show only the group name');
    $table_other->data[$row][1] = html_print_checkbox_switch(
        'show_group_name',
        1,
        $config['show_group_name'],
        true
    );
    $row++;

    $table_other->data[$row][0] = __('Date format string');
    $table_other->data[$row][1] = '<em>'.__('Example').'</em> '.date($config['date_format']);
    $table_other->data[$row][1] .= html_print_input_text('date_format', $config['date_format'], '', 30, 100, true);
    $row++;

    if ($config['prominent_time'] == 'comparation') {
        $timestamp = false;
        $comparation = true;
        $compact = false;
    } else if ($config['prominent_time'] == 'timestamp') {
        $timestamp = true;
        $comparation = false;
        $compact = false;
    } else if ($config['prominent_time'] == 'compact') {
        $timestamp = false;
        $comparation = false;
        $compact = true;
    }

    $table_other->data[$row][0] = __('Timestamp, time comparison, or compact mode');
    $table_other->data[$row][1] = '<div class="switch_radio_button">';
    $table_other->data[$row][1] .= html_print_radio_button('prominent_time', 'comparation', __('Comparation in rollover'), $comparation, true);
    $table_other->data[$row][1] .= html_print_radio_button('prominent_time', 'timestamp', __('Timestamp in rollover'), $timestamp, true);
    $table_other->data[$row][1] .= html_print_radio_button('prominent_time', 'compact', __('Compact mode'), $compact, true);
    $table_other->data[$row][1] .= '</div>';

    $row++;

    // ----------------------------------------------------------------------
    // CUSTOM VALUES POST PROCESS
    // ----------------------------------------------------------------------
    $table_other->data[$row][0] = __('Custom values post process');
    $table_other->data[$row][1] = __('Value').':&nbsp;'.html_print_input_text('custom_value', '', '', 25, 50, true);
    $table_other->data[$row][2] = __('Text').':&nbsp;'.html_print_input_text('custom_text', '', '', 15, 50, true);
    $table_other->data[$row][2] .= '&nbsp;';
    $table_other->data[$row][2] .= html_print_input_hidden(
        'custom_value_add',
        '',
        true
    );
    $table_other->data[$row][3] = html_print_button(
        __('Add'),
        'custom_value_add_btn',
        false,
        '',
        'class="sub next"',
        true
    );

    $row++;

    $table_other->data[$row][0] = '';
    $table_other->data[$row][1] = __('Delete custom values').': ';
    $table_other->data[$row][2] = html_print_select(
        post_process_get_custom_values(),
        'custom_values',
        '',
        '',
        '',
        '',
        true
    );
    $count_custom_postprocess = post_process_get_custom_values();
    $table_other->data[$row][3] = html_print_button(
        __('Delete'),
        'custom_values_del_btn',
        empty($count_custom_postprocess),
        '',
        'class="sub cancel"',
        true
    );
    // This hidden field will be filled from jQuery before submit
    $table_other->data[$row][1] .= html_print_input_hidden(
        'custom_value_to_delete',
        '',
        true
    );
    $table_other->data[$row][3] .= '<br><br>';

    // ----------------------------------------------------------------------
    // ----------------------------------------------------------------------
    // CUSTOM INTERVAL VALUES
    // ----------------------------------------------------------------------
    $row++;
    $table_other->data[$row][0] = __('Interval values');
    $units = [
        1               => __('seconds'),
        SECONDS_1MINUTE => __('minutes'),
        SECONDS_1HOUR   => __('hours'),
        SECONDS_1DAY    => __('days'),
        SECONDS_1MONTH  => __('months'),
        SECONDS_1YEAR   => __('years'),
    ];
    $table_other->data[$row][1] = __('Value').': ';
    $table_other->data[$row][1] .= html_print_input_text('interval_value', '', '', 5, 5, true);
    $table_other->data[$row][2] = html_print_select($units, 'interval_unit', 1, '', '', '', true, false, false);
    $table_other->data[$row][3] = html_print_button(__('Add'), 'interval_add_btn', false, '', 'class="sub next"', true);

    $row++;

    $table_other->data[$row][0] = '';
    $table_other->data[$row][1] = __('Delete interval').': ';
    $table_other->data[$row][2] = html_print_select(get_periods(false, false), 'intervals', '', '', '', '', true);
    $table_other->data[$row][3] = html_print_button(__('Delete'), 'interval_del_btn', empty($config['interval_values']), '', 'class="sub cancel"', true);

    $table_other->data[$row][1] .= html_print_input_hidden('interval_values', $config['interval_values'], true);
    // This hidden field will be filled from jQuery before submit
    $table_other->data[$row][1] .= html_print_input_hidden('interval_to_delete', '', true);
    $table_other->data[$row][3] .= '<br><br>';
    // ----------------------------------------------------------------------
    $row++;

    $table_other->data[$row][0] = __('Module units');
    $table_other->data[$row][1] = __('Value').': ';
    $table_other->data[$row][1] .= html_print_input_text('custom_module_unit', '', '', 15, 50, true);
    $table_other->data[$row][2] = '';
    $table_other->data[$row][3] = html_print_button(__('Add'), 'module_unit_add_btn', false, '', 'class="sub next"', true);

    $row++;
    $table_other->data[$row][0] = '';
    $table_other->data[$row][1] = __('Delete custom values').': ';
    $table_other->data[$row][2] = html_print_select(get_custom_module_units(), 'module_units', '', '', '', '', true, false, true, 'w100p');
    $table_other->data[$row][3] = html_print_button(
        __('Delete'),
        'custom_module_unit_del_btn',
        empty($count_custom_postprocess),
        '',
        'class="sub cancel"',
        true
    );

    $table_other->data[$row][3] .= html_print_input_hidden(
        'custom_module_unit_to_delete',
        '',
        true
    );

    $row++;

    $common_dividers = [
        ';' => ';',
        ',' => ',',
        '|' => '|',
    ];
    $table_other->data[$row][0] = __('CSV divider');
    if ($config['csv_divider'] != ';' && $config['csv_divider'] != ',' && $config['csv_divider'] != '|') {
        $table_other->data[$row][1] = html_print_input_text('csv_divider', $config['csv_divider'], '', 20, 255, true);
        $table_other->data[$row][1] .= '<a id="csv_divider_custom" onclick="javascript: edit_csv_divider();">'.html_print_image('images/default_list.png', true, ['id' => 'select']).'</a>';
    } else {
        $table_other->data[$row][1] = html_print_select($common_dividers, 'csv_divider', $config['csv_divider'], '', '', '', true, false, false);
        $table_other->data[$row][1] .= '<a id="csv_divider_custom" onclick="javascript: edit_csv_divider();">'.html_print_image('images/pencil.png', true, ['id' => 'pencil']).'</a>';
    }

    $row++;

    $decimal_separator = [
        '.' => '.',
        ',' => ',',
    ];
    $table_other->data[$row][0] = __('CSV decimal separator').ui_print_help_tip(__('Only for csv reports'), true);
    $table_other->data[$row][1] = html_print_select($decimal_separator, 'csv_decimal_separator', $config['csv_decimal_separator'], '', '', '', true, false, false);

    $row++;

    $table_other->data[$row][0] = __('Data multiplier to use in graphs/data');
    $options_data_multiplier = [];
    $options_data_multiplier[0] = __('Use 1024 when module unit are bytes');
    $options_data_multiplier[1] = __('Use always 1000');
    $options_data_multiplier[2] = __('Use always 1024');

    $table_other->data[$row][1] = html_print_select($options_data_multiplier, 'use_data_multiplier', $config['use_data_multiplier'], '', '', 1, true, false, false);


    echo '<fieldset>';
    echo '<legend>'.__('Other configuration').' '.ui_print_help_icon('other_conf_tab', true).'</legend>';
    html_print_table($table_other);
    echo '</fieldset>';


    echo '<div class="action-buttons" style="width: '.$table_other->width.'">';
    html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
    echo '</div>';
    echo '</form>';

    ui_require_css_file('color-picker', 'include/styles/js/');
    ui_require_jquery_file('colorpicker');


    function load_fonts()
    {
        global $config;

        $home = str_replace('\\', '/', $config['homedir']);
        $dir = scandir($home.'/include/fonts/');

        $fonts = [];

        foreach ($dir as $file) {
            if (strstr($file, '.ttf') !== false) {
                $fonts[$home.'/include/fonts/'.$file] = $file;
            }
        }

        return $fonts;
    }


    ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');
    ui_require_javascript_file('pandora');

    ?>
<script language="javascript" type="text/javascript">

function edit_csv_divider () {
    if ($("#csv_divider_custom img").attr("id") == "pencil") {
        $("#csv_divider_custom img").attr("src", "images/default_list.png");
        $("#csv_divider_custom img").attr("id", "select");
        var value = $("#csv_divider").val();
        $("#csv_divider").replaceWith("<input id='text-csv_divider' name='csv_divider' type='text'>");
        $("#text-csv_divider").val(value);
    }
    else {
        $("#csv_divider_custom img").attr("src", "images/pencil.png");
        $("#csv_divider_custom img").attr("id", "pencil");
        $("#text-csv_divider").replaceWith("<select id='csv_divider' name='csv_divider'>");
        var o = new Option(";", ";");
        var o1 = new Option(",", ",");
        var o2 = new Option("|", "|");
        $("#csv_divider").append(o);
        $("#csv_divider").append(o1);
        $("#csv_divider").append(o2);
    }
}

// Juanma (07/05/2014) New feature: Custom front page for reports  
function display_custom_report_front (show,table) {
    
    if (show == true) {
        $('tr#'+table+'-custom_report_front-logo').show();
        $('tr#'+table+'-custom_report_front-preview').show();
        $('tr#'+table+'-custom_report_front-header').show();
        $('tr#'+table+'-custom_report_front-first_page').show();
        $('tr#'+table+'-custom_report_front-footer').show();
    }
    else {
        $('tr#'+table+'-custom_report_front-logo').hide();
        $('tr#'+table+'-custom_report_front-preview').hide();
        $('tr#'+table+'-custom_report_front-header').hide();
        $('tr#'+table+'-custom_report_front-first_page').hide();
        $('tr#'+table+'-custom_report_front-footer').hide();
    }
    
}

function showPreview() {
    var img_value = $('#custom_report_front_logo').val();
    
    jQuery.post (
        <?php
        echo "'".ui_get_full_url(false, false, false, false)."'";
        ?>
   + "/ajax.php",
        {
            "page" : "<?php echo ENTERPRISE_DIR; ?>/godmode/reporting/reporting_builder.template_advanced",
            "get_image_path": "1",
            "img_src" : img_value
        },
        function (data, status) {
            $("#preview_image").html(data);
        }
    );
}

function change_precision() {
    var value = $("#text-graph_precision").val();
    if ((value < 0) || (value > 5)) {
        $("#text-graph_precision").val(1);
    }
}

function change_servicetree_nodes_padding () {
    var value = $("#text-service_item_padding_size").val();
    if (value < 80) {
        $("#text-service_item_padding_size").val(80);
    }
}

var added_config1 = {
        "elements":"textarea_custom_report_front_header, textarea_custom_report_front_footer",
    }

    defineTinyMCE(added_config1);

    var added_config2 = {
        "elements":"textarea_custom_report_front_firstpage",
    }

    defineTinyMCE(added_config2);

$(document).ready (function () {

    // Show the cache expiration conf or not.
    $("input[name=legacy_vc]").change(function (e) {
        if ($(this).prop("checked") === true) {
            $("select#vc_default_cache_expiration_select").closest("tr").hide();
        } else {
            $("select#vc_default_cache_expiration_select").closest("tr").show();
        }
    }).change();
    
    var comfort = 0;
    
    if(comfort == 0){
        $(':input,:radio,:checkbox,:file').change(function(){
            $('#submit-update_button').css({'position':'fixed','right':'80px','bottom':'55px'});
            var comfort = 1;
        });
        
        $("*").keydown(function(){
            $('#submit-update_button').css({'position':'fixed','right':'80px','bottom':'55px'});
            var comfort = 1;
        });
        
        $('#form_setup').after('<br>');    
        }
    
    $("#form_setup #text-graph_color1").attachColorPicker();
    $("#form_setup #text-graph_color2").attachColorPicker();
    $("#form_setup #text-graph_color3").attachColorPicker();
    $("#form_setup #text-graph_color4").attachColorPicker();
    $("#form_setup #text-graph_color5").attachColorPicker();
    $("#form_setup #text-graph_color6").attachColorPicker();
    $("#form_setup #text-graph_color7").attachColorPicker();
    $("#form_setup #text-graph_color8").attachColorPicker();
    $("#form_setup #text-graph_color9").attachColorPicker();
    $("#form_setup #text-graph_color10").attachColorPicker();
    
    
    //------------------------------------------------------------------
    // CUSTOM VALUES POST PROCESS
    //------------------------------------------------------------------
    $("#button-custom_values_del_btn").click( function()  {
        var interval_selected = $('#custom_values').val();
        $('#hidden-custom_value_to_delete').val(interval_selected);
        
        $("input[name='custom_value']").val("");
        $("input[name='custom_text']").val("");
        
        $('#submit-update_button').trigger('click');
    });
    
    $("#button-custom_value_add_btn").click( function() {
        $('#hidden-custom_value_add').val(1);
        
        $('#submit-update_button').trigger('click');
    });
    //------------------------------------------------------------------
    
    
    //------------------------------------------------------------------
    // CUSTOM INTERVAL VALUES
    //------------------------------------------------------------------
    $("#button-interval_del_btn").click( function()  {
        var interval_selected = $('#intervals option:selected').val();
        $('#hidden-interval_to_delete').val(interval_selected);
        $('#submit-update_button').trigger('click');
    });
    
    $("#button-interval_add_btn").click( function() {
        $('#submit-update_button').trigger('click');
    });
    //------------------------------------------------------------------
    
    //------------------------------------------------------------------
    // CUSTOM MODULE UNITS
    //------------------------------------------------------------------
    $("#button-custom_module_unit_del_btn").click( function()  {
        var unit_selected = $('#module_units option:selected').val();
        $('#hidden-custom_module_unit_to_delete').val(unit_selected);
        $('#submit-update_button').trigger('click');
    });

    $("#button-module_unit_add_btn").click( function() {
        $('#submit-update_button').trigger('click');
    });
    //------------------------------------------------------------------

    // Juanma (06/05/2014) New feature: Custom front page for reports  
    var custom_report = $('#checkbox-custom_report_front')
        .prop('checked');
    display_custom_report_front(custom_report,$('#checkbox-custom_report_front').parent().parent().parent().parent().parent().attr('id'));
    
    $("#checkbox-custom_report_front").change( function()  {
        var custom_report = $('#checkbox-custom_report_front')
            .prop('checked');
        display_custom_report_front(custom_report,$(this).parent().parent().parent().parent().parent().attr('id'));
    });
    $(".databox.filters").css('margin-bottom','0px');
});

// Change the favicon preview when is changed
function setup_visuals_change_favicon() {
    var icon_name = $("select#custom_favicon option:selected").val();
    var icon_path = (icon_name == "")
        ? "images/pandora.ico"
        : "images/custom_favicon/" + icon_name;
    $("#favicon_preview").attr("src", "<?php echo $config['homeurl']; ?>" + icon_path);
}

// Dialog loaders for the images previews
$(".logo_preview").click (function(e) {
    // Init the vars
    var icon_name = '';
    var icon_path = '';
    var options = {
        title: "<?php echo __('Logo preview'); ?>"
    };

    var homeUrl = "<?php echo $config['homeurl']; ?>";
    var homeUrlEnt = homeUrl + "<?php echo enterprise_installed() ? 'enterprise/' : ''; ?>";

    // Fill it seing the target has been clicked
    switch (e.target.id) {
        case 'button-custom_logo_preview':
            icon_name = $("select#custom_logo option:selected").val();
            icon_path = homeUrlEnt + "images/custom_logo/" + icon_name;
            options.grayed = true;
            break;
        case 'button-custom_logo_collapsed_preview':
            icon_name = $("select#custom_logo_collapsed option:selected").val();
            icon_path = homeUrlEnt + "images/custom_logo/" + icon_name;
            options.grayed = true;
            break;            
        case 'button-custom_logo_white_bg_preview':
            icon_name = $("select#custom_logo_white_bg option:selected").val();
            icon_path = homeUrlEnt + "images/custom_logo/" + icon_name;
            break;
        case 'button-custom_logo_login_preview':
            icon_name = $("select#custom_logo_login option:selected").val();
            icon_path = homeUrlEnt + "images/custom_logo_login/" + icon_name;
            options.grayed = true;
            break;
        case 'button-custom_splash_login_preview':
            icon_name = $("select#custom_splash_login option:selected").val();
            icon_path = homeUrlEnt + "images/custom_splash_login/" + icon_name;
            options.title = "<?php echo __('Splash Preview'); ?>";
            break;
        case 'button-custom_docs_logo_preview':
            icon_name = $("select#custom_docs_logo option:selected").val();
            icon_path = homeUrlEnt + "images/custom_general_logos/" + icon_name;
            options.grayed = true;
            break;
        case 'button-custom_support_logo_preview':
            icon_name = $("select#custom_support_logo option:selected").val();
            icon_path = homeUrlEnt + "images/custom_general_logos/" + icon_name;
            options.grayed = true;
            break;
        case 'button-custom_network_center_logo_preview':
            icon_name = $("select#custom_network_center_logo option:selected").val();
            icon_path = homeUrlEnt + "images/custom_general_logos/" + icon_name;
            break;
        case 'button-custom_mobile_console_logo_preview':
            icon_name = $("select#custom_mobile_console_logo option:selected").val();
            icon_path = homeUrlEnt + "images/custom_general_logos/" + icon_name;
            options.title = "<?php echo __('Mobile console logo preview'); ?>";
            break;
        case 'button-login_background_preview':
            icon_name = $("select#login_background option:selected").val();
            icon_path = homeUrl + "images/backgrounds/" + icon_name;
            options.title = "<?php echo __('Background preview'); ?>";
            break;
    }

    // Display the preview
    logo_preview (icon_name, icon_path, options);
});

$("#button-gis_icon_preview").click (function (e) {
    var icon_prefix = $("select#gis_default_icon option:selected").val();
    var icon_path = homeUrl + "images/gis_map/icons/" + icon_prefix;

    if (icon_prefix == "")
        return;

    $dialog = $("<div></div>");
    $icon_default = $("<img src=\"" + icon_path + ".default.png\">");
    $icon_ok = $("<img src=\"" + icon_path + ".ok.png\">");
    $icon_warning = $("<img src=\"" + icon_path + ".warning.png\">");
    $icon_bad = $("<img src=\"" + icon_path + ".bad.png\">");

    try {
        $dialog
            .hide()
            .empty()
            .append($icon_default)
            .append($icon_ok)
            .append($icon_warning)
            .append($icon_bad)
            .dialog({
                title: "<?php echo __('Gis icons preview'); ?>",
                resizable: true,
                draggable: true,
                modal: true,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                },
                minHeight: 1,
                close: function () {
                    $dialog
                        .empty()
                        .remove();
                }
            }).show();
    }
    catch (err) {
        // console.log(err);
    }
});

$("#button-status_set_preview").click (function (e) {
    var icon_dir = $("select#status_images_set option:selected").val();
    var icon_path = "<?php echo $config['homeurl']; ?>/images/status_sets/" + icon_dir + "/";

    if (icon_dir == "")
        return;

    $dialog = $("<div></div>");
    $icon_unknown_ball = $("<img src=\"" + icon_path + "agent_down_ball.png\">");
    $icon_unknown = $("<img src=\"" + icon_path + "agent_down.png\">");
    $icon_ok_ball = $("<img src=\"" + icon_path + "agent_ok_ball.png\">");
    $icon_ok = $("<img src=\"" + icon_path + "agent_ok.png\">");
    $icon_warning_ball = $("<img src=\"" + icon_path + "agent_warning_ball.png\">");
    $icon_warning = $("<img src=\"" + icon_path + "agent_warning.png\">");
    $icon_bad_ball = $("<img src=\"" + icon_path + "agent_critical_ball.png\">");
    $icon_bad = $("<img src=\"" + icon_path + "agent_critical.png\">");

    if(icon_dir == 'default'){
        $icon_unknown_ball = '<?php echo $icon_unknown_ball; ?>';
        $icon_unknown = '<?php echo $icon_unknown; ?>';
        $icon_ok_ball = '<?php echo $icon_ok_ball; ?>';
        $icon_ok = '<?php echo $icon_ok; ?>';
        $icon_warning_ball = '<?php echo $icon_warning_ball; ?>';
        $icon_warning = '<?php echo $icon_warning; ?>';
        $icon_bad_ball = '<?php echo $icon_bad_ball; ?>';
        $icon_bad = '<?php echo $icon_bad; ?>';
    }

    try {
        $dialog
            .hide()
            .empty()
            .append($icon_unknown_ball)
            .append($icon_unknown)
            .append('&nbsp;')
            .append($icon_ok_ball)
            .append($icon_ok)
            .append('&nbsp;')
            .append($icon_warning_ball)
            .append($icon_warning)
            .append('&nbsp;')
            .append($icon_bad_ball)
            .append($icon_bad)
            .css('vertical-align', 'middle')
            .dialog({
                title: "<?php echo __('Status set preview'); ?>",
                resizable: true,
                draggable: true,
                modal: true,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                },
                minHeight: 1,
                close: function () {
                    $dialog
                        .empty()
                        .remove();
                }
            }).show();
    }
    catch (err) {
        // console.log(err);
    }
});

</script>
