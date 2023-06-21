<?php
/**
 * Setup of Visual Styles.
 *
 * @category   Setup
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

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Visual Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

// FIX: this constant is declared to in godmode/reporting/reporting_builder.phps
// Constant with fonts directory.
define('_MPDF_TTFONTPATH', $config['homedir'].'/include/fonts/');

require_once 'include/functions_post_process.php';

// Load enterprise extensions.
enterprise_include('godmode/setup/setup_visuals.php');

// Load needed resources.
ui_require_css_file('setup.multicolumn');

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
echo '<form id="form_setup" class="max_floating_element_size" method="post">';
html_print_input_hidden('update_config', 1);

$performance_variables_control = (array) json_decode(io_safe_output($config['performance_variables_control']));

echo "<div id='dialog-legacy-vc' title='".__('Legacy visual console Information')."' class='invisible'>";
echo "<p class='center'>".__('Visual console in legacy mode will no longer be supported as of LTS 772').'</p>';
echo '</div>';

// ----------------------------------------------------------------------
// BEHAVIOUR CONFIGURATION
// ----------------------------------------------------------------------
$values = [];
$values[5] = human_time_description_raw(5);
$values[30] = human_time_description_raw(30);
$values[SECONDS_1MINUTE] = human_time_description_raw(SECONDS_1MINUTE);
$values[SECONDS_2MINUTES] = human_time_description_raw(SECONDS_2MINUTES);
$values[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
$values[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
$values[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);

$table_behaviour = new stdClass();
$table_behaviour->width = '100%';
$table_behaviour->class = 'filter-table-adv';
$table_behaviour->size[0] = '50%';
$table_behaviour->data = [];

$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Block size for pagination'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['block_size']->max,
            'name'   => 'block_size',
            'value'  => $config['global_block_size'],
            'return' => true,
            'min'    => $performance_variables_control['block_size']->min,
            'style'  => 'width:50px',
        ]
    )
);

$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Click to display lateral menus'),
    html_print_checkbox_switch(
        'click_display',
        1,
        $config['click_display'],
        true
    )
);

$row++;

$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Paginated module view'),
    html_print_checkbox_switch(
        'paginate_module',
        1,
        $config['paginate_module'],
        true
    )
);
$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Display data of proc modules in other format'),
    html_print_checkbox_switch(
        'render_proc',
        1,
        $config['render_proc'],
        true
    )
);
$row++;

$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Display text proc modules have state is ok'),
    html_print_input_text('render_proc_ok', $config['render_proc_ok'], '', 25, 25, true)
);
$table_behaviour->data[$row][] = html_print_label_input_block(
    __('Display text when proc modules have state critical'),
    html_print_input_text('render_proc_fail', $config['render_proc_fail'], '', 25, 25, true)
);
$row++;

if (enterprise_installed() === true) {
    $row++;
    $table_behaviour->data[$row][] = html_print_label_input_block(
        __('Service label font size'),
        html_print_input_text('service_label_font_size', $config['service_label_font_size'], '', 5, 5, true)
    );
    $table_behaviour->data[$row][] = html_print_label_input_block(
        __('Space between items in Service maps'),
        html_print_input_text('service_item_padding_size', $config['service_item_padding_size'], '', 5, 5, true, false, false, 'onChange="change_servicetree_nodes_padding()"')
    );
}


// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// STYLE CONFIGURATION
// ----------------------------------------------------------------------


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


$iconsets['default'] = __('Colors');
$iconsets['faces'] = __('Faces');
$iconsets['color_text'] = __('Colors and text');

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
$backgrounds_list_jpg = list_files('images/backgrounds', 'jpg', 1, 0);
$backgrounds_list_gif = list_files('images/backgrounds', 'gif', 1, 0);
$backgrounds_list_png = list_files('images/backgrounds', 'png', 1, 0);
$backgrounds_list = array_merge($backgrounds_list_jpg, $backgrounds_list_png);
$backgrounds_list = array_merge($backgrounds_list, $backgrounds_list_gif);
asort($backgrounds_list);

$open = false;
if (enterprise_installed() === false) {
    $open = true;
}

if (enterprise_installed() === true) {
    // Get all the custom logos.
    $filesCustomLogos = list_files('enterprise/images/custom_general_logos', 'png', 1, 0);

    $ent_files = list_files('enterprise/images/custom_logo', 'png', 1, 0);
    $open_files = list_files('images/custom_logo', 'png', 1, 0);

    $entOpenFilesInput = html_print_select(
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

    $customLogoLoginInput = html_print_select(
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
    $entOpenFilesInput = html_print_select(
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

    $customLogoLoginInput = html_print_select(
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

// Custom favicon.
$filesFavicon = list_files('images/custom_favicon', 'ico', 1, 0);

$table_styles = new stdClass();
$table_styles->width = '100%';
$table_styles->class = 'filter-table-adv';
$table_styles->size[0] = '50%';
$table_styles->data = [];

$table_styles->data[$row][] = html_print_label_input_block(
    __('Style template'),
    html_print_select(
        themes_get_css(),
        'style',
        $config['style'].'.css',
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Status icon set'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => html_print_select(
                $iconsets,
                'status_images_set',
                $config['status_images_set'],
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false,
                'width: 240px'
            ).html_print_button(
                __('View'),
                'status_set_preview',
                false,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true
            ),
        ],
        true
    )
);

$row++;
$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom favicon'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => html_print_select(
                $filesFavicon,
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
            ).html_print_image(
                ui_get_favicon(),
                true,
                [
                    'id'    => 'favicon_preview',
                    'style' => 'margin-left: 10px',
                ]
            ),
        ],
        true
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom background login'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => html_print_select(
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
            ).html_print_button(
                __('View'),
                'login_background_preview',
                false,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true
            ),
        ],
        true
    )
);
$row++;

$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom logo (menu)'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => logo_custom_enterprise('custom_logo', $config['custom_logo']).html_print_button(
                __('View'),
                'custom_logo_preview',
                $open,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true,
                false,
                $open,
                'visualmodal'
            ),
        ],
        true
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom logo collapsed (menu)'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => logo_custom_enterprise('custom_logo_collapsed', $config['custom_logo_collapsed']).html_print_button(
                __('View'),
                'custom_logo_collapsed_preview',
                $open,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true,
                false,
                $open,
                'visualmodal'
            ),
        ],
        true
    )
);
$row++;

$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom logo (header white background)'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => $entOpenFilesInput.html_print_button(
                __('View'),
                'custom_logo_white_bg_preview',
                $open,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true,
                false,
                $open,
                'visualmodal'
            ),
        ],
        true
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Custom logo (login)'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => $customLogoLoginInput.html_print_button(
                __('View'),
                'custom_logo_login_preview',
                $open,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true,
                false,
                $open,
                'visualmodal'
            ),
        ],
        true
    )
);
$row++;

// Splash login.
if (enterprise_installed() === true) {
    $table_styles->data[$row][] = html_print_label_input_block(
        __('Custom Splash (login)'),
        html_print_div(
            [
                'class'   => 'select-with-sibling',
                'content' => html_print_select(
                    list_files('enterprise/images/custom_splash_login', 'png', 1, 0),
                    'custom_splash_login',
                    $config['custom_splash_login'],
                    '',
                    __('Default'),
                    'default',
                    true,
                    false,
                    true,
                    '',
                    $open,
                    'width:240px'
                ).html_print_button(
                    __('View'),
                    'custom_splash_login_preview',
                    $open,
                    '',
                    [
                        'icon'  => 'camera',
                        'mode'  => 'link',
                        'class' => 'logo_preview',
                    ],
                    true,
                    false,
                    $open,
                    'visualmodal'
                ),
            ],
            true
        )
    );

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Custom documentation logo'),
        html_print_div(
            [
                'class'   => 'select-with-sibling',
                'content' => html_print_select(
                    $filesCustomLogos,
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
                ).html_print_button(
                    __('View'),
                    'custom_docs_logo_preview',
                    $open,
                    '',
                    [
                        'icon'  => 'camera',
                        'mode'  => 'link',
                        'class' => 'logo_preview',
                    ],
                    true,
                    false,
                    $open,
                    'visualmodal'
                ),
            ],
            true
        )
    );
    $row++;

    // Custom support icon.
    $table_styles->data[$row][] = html_print_label_input_block(
        __('Custom support logo'),
        html_print_div(
            [
                'class'   => 'select-with-sibling',
                'content' => html_print_select(
                    $filesCustomLogos,
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
                ).html_print_button(
                    __('View'),
                    'custom_support_logo_preview',
                    $open,
                    '',
                    [
                        'icon'  => 'camera',
                        'mode'  => 'link',
                        'class' => 'logo_preview',
                    ],
                    true,
                    false,
                    $open,
                    'visualmodal'
                ),
            ],
            true
        )
    );

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Custom networkmap center logo'),
        html_print_div(
            [
                'class'   => 'select-with-sibling',
                'content' => html_print_select(
                    $filesCustomLogos,
                    'custom_network_center_logo',
                    $config['custom_network_center_logo'],
                    '',
                    __('Default'),
                    'bola_pandora_network_maps.png',
                    true,
                    false,
                    true,
                    '',
                    false,
                    'width:240px'
                ).html_print_button(
                    __('View'),
                    'custom_network_center_logo_preview',
                    $open,
                    '',
                    [
                        'icon'  => 'camera',
                        'mode'  => 'link',
                        'class' => 'logo_preview',
                    ],
                    true,
                    false,
                    $open,
                    'visualmodal'
                ),
            ],
            true
        )
    );
    $row++;

    // Custom center mobile console icon.
    $table_styles->data[$row][] = html_print_label_input_block(
        __('Custom mobile console icon'),
        html_print_div(
            [
                'class'   => 'select-with-sibling',
                'content' => html_print_select(
                    $filesCustomLogos,
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
                ).html_print_button(
                    __('View'),
                    'custom_mobile_console_logo_preview',
                    $open,
                    '',
                    [
                        'icon'  => 'camera',
                        'mode'  => 'link',
                        'class' => 'logo_preview',
                    ],
                    true,
                    false,
                    $open,
                    'visualmodal'
                ),
            ],
            true
        )
    );
}

$row++;

// Title Header.
$table_styles->data[$row][] = html_print_label_input_block(
    __('Title (header)'),
    html_print_input_text('custom_title_header', $config['custom_title_header'], '', 50, 40, true)
);

// Subtitle Header.
$table_styles->data[$row][] = html_print_label_input_block(
    __('Subtitle (header)'),
    html_print_input_text('custom_subtitle_header', $config['custom_subtitle_header'], '', 50, 40, true)
);
$row++;

if (enterprise_installed() === true) {
    // Login title1.
    $table_styles->data[$row][] = html_print_label_input_block(
        __('Title 1 (login)'),
        html_print_input_text('custom_title1_login', $config['custom_title1_login'], '', 50, 50, true)
    );
    // Login text2.
    $table_styles->data[$row][] = html_print_label_input_block(
        __('Title 2 (login)'),
        html_print_input_text('custom_title2_login', $config['custom_title2_login'], '', 50, 50, true)
    );
    $row++;

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Docs URL (login)'),
        html_print_input_text('custom_docs_url', $config['custom_docs_url'], '', 50, 50, true)
    );

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Support URL (login)'),
        html_print_input_text('custom_support_url', $config['custom_support_url'], '', 50, 50, true)
    );
    $row++;

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Product name'),
        html_print_input_text('rb_product_name', get_product_name(), '', 30, 255, true)
    );

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Copyright notice'),
        html_print_input_text('rb_copyright_notice', get_copyright_notice(), '', 30, 255, true)
    );
    $row++;

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Background opacity % (login)'),
        "<input type='number' value=".$config['background_opacity']." size='5' name='background_opacity' min='0' max='99'>"
    );

    $table_styles->data[$row][] = html_print_label_input_block(
        __('Disable logo in graphs'),
        html_print_checkbox_switch(
            'fixed_graph',
            1,
            $config['fixed_graph'],
            true
        )
    );
    $row++;
}

/*
    Hello there! :)
    We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
    You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
*/

$table_styles->data[$row][] = html_print_label_input_block(
    __('Disable helps'),
    html_print_checkbox_switch(
        'disable_help',
        1,
        $config['disable_help'],
        true
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Fixed header'),
    html_print_checkbox_switch(
        'fixed_header',
        1,
        $config['fixed_header'],
        true
    )
);
$row++;

// For 5.1 Autohidden menu feature.
$table_styles->data[$row][] = html_print_label_input_block(
    __('Automatically hide submenu'),
    html_print_checkbox_switch(
        'autohidden_menu',
        1,
        $config['autohidden_menu'],
        true
    )
);

$table_styles->data[$row][] = html_print_label_input_block(
    __('Visual effects and animation'),
    html_print_checkbox_switch(
        'visual_animation',
        1,
        $config['visual_animation'],
        true
    )
);
$row++;

$table_styles->data[$row][] = html_print_label_input_block(
    __('Random background (login)'),
    html_print_checkbox_switch(
        'random_background',
        1,
        $config['random_background'],
        true
    )
);

// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// GIS CONFIGURATION
// ----------------------------------------------------------------------
$listIcons = gis_get_array_list_icons();
$arraySelectIcon = [];
foreach ($listIcons as $index => $value) {
    $arraySelectIcon[$index] = $index;
}

$table_gis = new stdClass();
$table_gis->width = '100%';
$table_gis->class = 'filter-table-adv';
$table_gis->size[0] = '50%';
$table_gis->data = [];

$table_gis->data[$row][] = html_print_label_input_block(
    __('GIS Labels'),
    html_print_checkbox_switch(
        'gis_label',
        1,
        $config['gis_label'],
        true
    )
);

$table_gis->data[$row][] = html_print_label_input_block(
    __('Default icon in GIS'),
    html_print_div(
        [
            'class'   => 'select-with-sibling',
            'content' => html_print_select(
                $arraySelectIcon,
                'gis_default_icon',
                $config['gis_default_icon'],
                '',
                __('Agent icon group'),
                '',
                true
            ).html_print_button(
                __('View'),
                'gis_icon_preview',
                false,
                '',
                [
                    'icon'  => 'camera',
                    'mode'  => 'link',
                    'class' => 'logo_preview',
                ],
                true
            ),
        ],
        true
    )
);
$row++;

// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// FONT AND TEXT CONFIGURATION
// ----------------------------------------------------------------------
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

$table_font = new stdClass();
$table_font->width = '100%';
$table_font->class = 'filter-table-adv';
$table_font->size[0] = '50%';
$table_font->data = [];

$table_font->data[$row][] = html_print_label_input_block(
    __('Graphs font size'),
    html_print_select(
        $font_size_array,
        'font_size',
        $config['font_size'],
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    )
);


$table_font->data[$row][] = html_print_label_input_block(
    __('Show unit along with value in reports'),
    html_print_checkbox_switch(
        'simple_module_value',
        1,
        $config['simple_module_value'],
        true
    )
);
$row++;

$table_font->data[$row][] = html_print_label_input_block(
    __('Agent size text'),
    html_print_div(
        [
            'class'   => 'filter-table-adv-manual',
            'content' => html_print_div(
                [
                    'class'   => 'w50p',
                    'content' => __('Small').html_print_input_text('agent_size_text_small', $config['agent_size_text_small'], '', 10, 3, true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => 'w50p',
                    'content' => __('Normal').html_print_input_text('agent_size_text_medium', $config['agent_size_text_medium'], '', 10, 3, true),
                ],
                true
            ),
        ],
        true
    )
);
$table_font->data[$row][] = html_print_label_input_block(
    __('Module size text'),
    html_print_div(
        [
            'class'   => 'filter-table-adv-manual',
            'content' => html_print_div(
                [
                    'class'   => 'w50p',
                    'content' => __('Small').html_print_input_text('module_size_text_small', $config['module_size_text_small'], '', 10, 3, true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => 'w50p',
                    'content' => __('Normal').html_print_input_text('module_size_text_medium', $config['module_size_text_medium'], '', 10, 3, true),
                ],
                true
            ),
        ],
        true
    )
);
$row++;

$table_font->data[$row][] = html_print_label_input_block(
    __('Description size text'),
    html_print_input_text(
        'description_size_text',
        $config['description_size_text'],
        '',
        3,
        3,
        true
    )
);
$table_font->data[$row][] = html_print_label_input_block(
    __('Item title size text'),
    html_print_input_text(
        'item_title_size_text',
        $config['item_title_size_text'],
        '',
        3,
        3,
        true
    )
);
$row++;


// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// CHARS CONFIGURATION
// ----------------------------------------------------------------------
$disabled_graph_precision = false;
if (enterprise_installed() === false) {
    $disabled_graph_precision = true;
}

if (isset($config['short_module_graph_data']) === false) {
    $config['short_module_graph_data'] = true;
}

$options_full_escale    = [];
$options_full_escale[0] = __('None');
$options_full_escale[1] = __('All');
$options_full_escale[2] = __('On Boolean graphs');

$options_soft_graphs    = [];
$options_soft_graphs[0] = __('Show only average by default');
$options_soft_graphs[1] = __('Show MAX/AVG/MIN by default');

$options_zoom_graphs    = [];
$options_zoom_graphs[1] = 'x1';
$options_zoom_graphs[2] = 'x2';
$options_zoom_graphs[3] = 'x3';
$options_zoom_graphs[4] = 'x4';
$options_zoom_graphs[5] = 'x5';

$graphColorAmount = 10;
$table_chars = new stdClass();
$table_chars->width = '100%';
$table_chars->class = 'filter-table-adv';
$table_chars->size[0] = '50%';
$table_chars->size[1] = '50%';
$table_chars->data = [];

for ($i = 1; $i <= $graphColorAmount; $i++) {
    $table_chars->data[$row][] = html_print_label_input_block(
        __('Graph color #'.$i),
        html_print_input_color(
            'graph_color'.$i,
            $config['graph_color'.$i],
            'graph_color'.$i,
            'w50p',
            true
        )
    );

    $row = ($i % 2 === 0) ? ($row + 1) : $row;
}

$table_chars->data[$row][] = html_print_label_input_block(
    __('Data precision'),
    html_print_input(
        [
            'type'                                        => 'number',
            'size'                                        => 5,
            'max'                                         => $performance_variables_control['graph_precision']->max,
            'name'                                        => 'graph_precision',
            'value'                                       => $config['graph_precision'],
            'return'                                      => true,
            'min'                                         => $performance_variables_control['graph_precision']->min,
            'style'                                       => 'width:50%',
            ($disabled_graph_precision) ? 'readonly' : '' => 'readonly',
            'onchange'                                    => 'change_precision()',
        ]
    )
);

$table_chars->data[$row][] = html_print_label_input_block(
    __('Data precision in graphs'),
    html_print_input(
        [
            'type'                                        => 'number',
            'size'                                        => 5,
            'max'                                         => $performance_variables_control['short_module_graph_data']->max,
            'name'                                        => 'short_module_graph_data',
            'value'                                       => $config['short_module_graph_data'],
            'return'                                      => true,
            'min'                                         => $performance_variables_control['short_module_graph_data']->min,
            'style'                                       => 'width:50%',
            ($disabled_graph_precision) ? 'readonly' : '' => 'readonly',
            'onchange'                                    => 'change_precision()',
        ]
    )
);
$row++;

$table_chars->data[$row][] = html_print_label_input_block(
    __('Value to interface graphics'),
    html_print_input_text(
        'interface_unit',
        $config['interface_unit'],
        '',
        20,
        20,
        true
    )
);

$table_chars->data[$row][] = html_print_label_input_block(
    __('Default line thickness for the Custom Graph.'),
    html_print_input_text(
        'custom_graph_width',
        $config['custom_graph_width'],
        '',
        5,
        5,
        true
    )
);
$row++;

$table_chars->data[$row][] = html_print_label_input_block(
    __('Number of elements in Custom Graph'),
    html_print_input_text(
        'items_combined_charts',
        $config['items_combined_charts'],
        '',
        5,
        5,
        true,
        false,
        false,
        ''
    )
);
$table_chars->data[$row][] = html_print_label_input_block(
    __('Use round corners'),
    html_print_checkbox_switch(
        'round_corner',
        1,
        $config['round_corner'],
        true
    )
);
$row++;

$table_chars->data[$row][] = html_print_label_input_block(
    __('Chart fit to content'),
    html_print_checkbox_switch(
        'maximum_y_axis',
        1,
        $config['maximum_y_axis'],
        true
    )
);

$table_chars->data[$row][] = html_print_label_input_block(
    __('Type of module charts'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Area').'&nbsp;'.html_print_radio_button(
                        'type_module_charts',
                        'area',
                        '',
                        $config['type_module_charts'] == 'area',
                        true
                    ),
                ],
                true
            ).html_print_div(
                [
                    'class'   => '',
                    'content' => __('Line').'&nbsp;'.html_print_radio_button(
                        'type_module_charts',
                        'line',
                        '',
                        $config['type_module_charts'] != 'area',
                        true
                    ),
                ],
                true
            ),
        ],
        true
    )
);
$row++;

$table_chars->data[$row][] = html_print_label_input_block(
    __('Percentile'),
    html_print_input_text(
        'percentil',
        $config['percentil'],
        '',
        20,
        20,
        true
    )
);

$table_chars->data[$row][] = html_print_label_input_block(
    __('Graph TIP view'),
    html_print_select(
        $options_full_escale,
        'full_scale_option',
        (isset($config['full_scale_option']) === true) ? $config['full_scale_option'] : 0,
        '',
        '',
        0,
        true,
        false,
        false
    )
);
$row++;

$table_chars->data[$row][] = html_print_label_input_block(
    __('Graph mode'),
    html_print_select(
        $options_soft_graphs,
        'type_mode_graph',
        (isset($config['type_mode_graph']) === true) ? $config['type_mode_graph'] : 0,
        '',
        '',
        0,
        true,
        false,
        false
    )
);

$table_chars->data[$row][] = html_print_label_input_block(
    __('Zoom graphs'),
    html_print_select(
        $options_zoom_graphs,
        'zoom_graph',
        $config['zoom_graph'],
        '',
        '',
        0,
        true,
        false,
        false
    )
);
$row++;

// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// Visual Consoles
// ----------------------------------------------------------------------
$intervals = [
    10   => '10 '.__('seconds'),
    30   => '30 '.__('seconds'),
    60   => '1 '.__('minutes'),
    300  => '5 '.__('minutes'),
    900  => '15 '.__('minutes'),
    1800 => '30 '.__('minutes'),
    3600 => '1 '.__('hour'),
];

$vc_favourite_view_array[0] = __('Classic view');
$vc_favourite_view_array[1] = __('View of favorites');

$table_vc = new stdClass();
$table_vc->width = '100%';
$table_vc->class = 'filter-table-adv';
$table_vc->style[0] = 'font-weight: bold';
$table_vc->size[0] = '50%';
$table_vc->data = [];

// Remove when the new view reaches rock solid stability.
$table_vc->data[$row][] = html_print_label_input_block(
    __('Legacy Visual Console View'),
    html_print_checkbox_switch(
        'legacy_vc',
        1,
        (bool) $config['legacy_vc'],
        true
    )
);

$table_vc->data[$row][] = html_print_label_input_block(
    __('Default cache expiration'),
    html_print_extended_select_for_time(
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
    )
);
$row++;

$table_vc->data[$row][] = html_print_label_input_block(
    __('Default interval for refresh on Visual Console'),
    html_print_select(
        $values,
        'vc_refr',
        (int) $config['vc_refr'],
        '',
        'N/A',
        0,
        true,
        false,
        false
    )
);

$table_vc->data[$row][] = html_print_label_input_block(
    __('Type of view of visual consoles'),
    html_print_select(
        $vc_favourite_view_array,
        'vc_favourite_view',
        $config['vc_favourite_view'],
        '',
        '',
        0,
        true
    )
);
$row++;

$table_vc->data[$row][] = html_print_label_input_block(
    __('Number of favorite visual consoles to show in the menu'),
    "<input ' value=".$config['vc_menu_items']." size='5' name='vc_menu_items' min='0' max='25'>"
);

$table_vc->data[$row][] = html_print_label_input_block(
    __('Default line thickness for the Visual Console'),
    html_print_input_text(
        'vc_line_thickness',
        (int) $config['vc_line_thickness'],
        '',
        5,
        5,
        true
    )
);
$row++;

$table_vc->data[$row][] = html_print_label_input_block(
    __('Mobile view not allow visual console orientation'),
    html_print_checkbox_switch(
        'mobile_view_orientation_vc',
        1,
        (bool) $config['mobile_view_orientation_vc'],
        true
    )
);

$table_vc->data[$row][] = html_print_label_input_block(
    __('Display item frame on alert triggered'),
    html_print_checkbox_switch(
        'display_item_frame',
        1,
        (bool) $config['display_item_frame'],
        true
    )
);

$row++;


// ----------------------------------------------------------------------
// Services
// ----------------------------------------------------------------------
$table_ser = new stdClass();
$table_ser->width = '100%';
$table_ser->class = 'filter-table-adv';
$table_ser->size[0] = '50%';
$table_ser->data = [];

$table_ser->data[0][] = html_print_label_input_block(
    __('Number of favorite services to show in the menu'),
    "<input ' value=".$config['ser_menu_items']." size='5' name='ser_menu_items' min='0' max='25'>"
);

// ----------------------------------------------------------------------
// Reports
// ----------------------------------------------------------------------
$interval_description = [
    'large' => 'Long',
    'tiny'  => 'Short',
];

$dirItems = scandir($config['homedir'].'/images/custom_logo');
$customLogos = [];
foreach ($dirItems as $entryDir) {
    if (strstr($entryDir, '.jpg') !== false || strstr($entryDir, '.png') !== false) {
        $customLogos['images/custom_logo/'.$entryDir] = $entryDir;
    }
}

if (empty($config['custom_report_front_logo'])) {
    $config['custom_report_front_logo'] = 'images/pandora_logo_white.jpg';
}

// Do not remove io_safe_output in textarea. TinyMCE avoids XSS injection.
if ($config['custom_report_front']) {
    $firstpage_content = $config['custom_report_front_firstpage'];
} else {
    $firstpage_content = io_safe_output($config['custom_report_front_firstpage']);
}

$custom_report_front_firstpage = str_replace(
    '(_URLIMAGE_)',
    ui_get_full_url(false, true, false, false),
    io_safe_output($firstpage_content)
);

$table_report = new stdClass();
$table_report->width = '100%';
$table_report->class = 'filter-table-adv';
$table_report->size[0] = '50%';

$table_report->data = [];

$table_report->data[$row][] = html_print_label_input_block(
    __('Show report info with description'),
    html_print_checkbox_switch(
        'custom_report_info',
        1,
        $config['custom_report_info'],
        true
    )
);

$table_report->data[$row][] = html_print_label_input_block(
    __('Custom report front page'),
    html_print_checkbox_switch(
        'custom_report_front',
        1,
        $config['custom_report_front'],
        true
    )
);
$row++;

$table_report->data[$row][] = html_print_label_input_block(
    __('PDF font size (px)'),
    "<input ' value=".$config['global_font_size_report']." name='global_font_size_report' min='1' max='50' step='1'>"
);
$table_report->data[$row][] = html_print_label_input_block(
    __('HTML font size for SLA (em)'),
    "<input ' value=".$config['font_size_item_report']." name='font_size_item_report' min='1' max='9' step='0.1'>"
);
$row++;

$table_report->data[$row][] = html_print_label_input_block(
    __('Graph image height for HTML reports'),
    html_print_input_text('graph_image_height', $config['graph_image_height'], '', 20, 20, true)
);
$table_report->data[$row][] = html_print_label_input_block(
    __('Interval description'),
    html_print_select(
        $interval_description,
        'interval_description',
        (isset($config['interval_description']) === true) ? $config['interval_description'] : 'large',
        '',
        '',
        '',
        true,
        false,
        false
    )
);
$row++;

// Logo.
$table_report->data['custom_report_front-logo'][] = html_print_label_input_block(
    __('Custom report front').' - '.__('Custom logo').ui_print_help_tip(
        __("The dir of custom logos is in your www Console in 'images/custom_logo'. You can upload more files (ONLY JPEG AND PNG) in upload tool in console."),
        true
    ),
    html_print_select(
        $customLogos,
        'custom_report_front_logo',
        io_safe_output($config['custom_report_front_logo']),
        'showPreview()',
        __('Default'),
        '',
        true
    )
);
$table_report->data['custom_report_front-preview'][] = html_print_label_input_block(
    __('Custom report front').' - '.__('Preview'),
    '<span id="preview_image">'.html_print_image($config['custom_report_front_logo'], true).'</span>'
);

$table_report->colspan['custom_report_front-header'][] = 2;
$table_report->data['custom_report_front-header'][] = html_print_label_input_block(
    __('Custom report front').' - '.__('Header'),
    html_print_textarea(
        'custom_report_front_header',
        5,
        15,
        io_safe_output($config['custom_report_front_header']),
        'class="w90p height_300px"',
        true
    )
);

$table_report->colspan['custom_report_front-first_page'][] = 2;
$table_report->data['custom_report_front-first_page'][] = html_print_label_input_block(
    __('Custom report front').' - '.__('First page'),
    html_print_textarea(
        'custom_report_front_firstpage',
        15,
        15,
        $custom_report_front_firstpage,
        'class="w90p height_300px"',
        true
    )
);

// Do not remove io_safe_output in textarea. TinyMCE avoids XSS injection.
$table_report->colspan['custom_report_front-footer'][] = 2;
$table_report->data['custom_report_front-footer'][] = html_print_label_input_block(
    __('Custom report front').' - '.__('Footer'),
    html_print_textarea(
        'custom_report_front_footer',
        5,
        15,
        io_safe_output($config['custom_report_front_footer']),
        'class="w90p height_300px""',
        true
    )
);


// ----------------------------------------------------------------------
// OTHER CONFIGURATION
// ----------------------------------------------------------------------
$decimal_separators = [
    ',' => ',',
    '.' => '.',
];

$common_dividers = [
    ';' => ';',
    ',' => ',',
    '|' => '|',
];

$switchProminentTime = html_print_radio_button(
    'prominent_time',
    'comparation',
    __('Comparation in rollover'),
    ($config['prominent_time'] === 'comparation'),
    true
);
$switchProminentTime .= html_print_radio_button(
    'prominent_time',
    'timestamp',
    __('Timestamp in rollover'),
    ($config['prominent_time'] === 'timestamp'),
    true
);
$switchProminentTime .= html_print_radio_button(
    'prominent_time',
    'compact',
    __('Compact mode'),
    ($config['prominent_time'] === 'compact'),
    true
);

$csvDividerIconEdit = 'images/edit.svg';
$csvDividerIconFile = 'images/logs@svg.svg';

$isCommonDivider = (in_array($config['csv_divider'], $common_dividers) === true);
$csvDividerIcon = ($isCommonDivider === false) ? $csvDividerIconEdit : $csvDividerIconFile;

$csvDividerInputsSub = html_print_div(
    [
        'class'   => ($isCommonDivider === false) ? 'invisible' : '',
        'id'      => 'custom_divider_input',
        'content' => html_print_input_text(
            'csv_divider',
            $config['csv_divider'],
            '',
            20,
            255,
            true,
            false,
            false,
            '',
            '',
            '',
            'off',
            false,
            '',
            '',
            '',
            ($isCommonDivider === false)
        ),
    ],
    true
);

$csvDividerInputsSub .= html_print_div(
    [
        'class'   => ($isCommonDivider === true) ? 'invisible' : '',
        'id'      => 'common_divider_input',
        'content' => html_print_select(
            $common_dividers,
            'csv_divider',
            $config['csv_divider'],
            '',
            '',
            '',
            true,
            false,
            false,
            '',
            ($isCommonDivider === true),
        ),
    ],
    true
);

$csvDividerInputs = html_print_div(
    [
        'class'   => 'mrgn_right_10px',
        'content' => $csvDividerInputsSub,
    ],
    true
);

$csvDividerInputs .= html_print_image(
    $csvDividerIcon,
    true,
    [
        'id'    => 'select_csv_divider',
        'class' => 'main_menu_icon invert_filter',
    ]
);

$csvDividerBlock = html_print_div(
    [
        'class'   => 'flex-row-center',
        'content' => $csvDividerInputs,
    ],
    true
);

$options_data_multiplier = [];
$options_data_multiplier[0] = __('Use 1024 when module unit are bytes');
$options_data_multiplier[1] = __('Use always 1000');
$options_data_multiplier[2] = __('Use always 1024');

$table_other = new stdClass();
$table_other->width = '100%';
$table_other->class = 'filter-table-adv';
$table_other->size[0] = '50%';
$table_other->size[1] = '50%';
$table_other->data = [];

$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('Networkmap max width'),
    html_print_input_text(
        'networkmap_max_width',
        $config['networkmap_max_width'],
        '',
        10,
        20,
        true
    )
);

$table_other->data[$row][] = html_print_label_input_block(
    __('Show only the group name'),
    html_print_checkbox_switch(
        'show_group_name',
        1,
        $config['show_group_name'],
        true
    )
);
$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('Show empty groups in group view'),
    html_print_checkbox_switch(
        'show_empty_groups',
        1,
        $config['show_empty_groups'],
        true
    )
);

$table_other->data[$row][] = html_print_label_input_block(
    __('Date format string'),
    html_print_input_text(
        'date_format',
        $config['date_format'],
        '',
        30,
        100,
        true
    ).ui_print_input_placeholder(
        __('Example').': '.date($config['date_format']),
        true
    )
);
$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('Decimal separator'),
    html_print_select(
        $decimal_separators,
        'decimal_separator',
        $config['decimal_separator'],
        '',
        '',
        '',
        true,
        false,
        false
    )
);

$table_other->data[$row][] = html_print_label_input_block(
    __('Visible time of successful notifiations'),
    html_print_input_text(
        'notification_autoclose_time',
        $config['notification_autoclose_time'],
        '',
        10,
        10,
        true
    )
);
$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('Timestamp, time comparison, or compact mode'),
    html_print_div(
        [
            'class'   => 'switch_radio_button',
            'content' => $switchProminentTime,
        ],
        true
    )
);
$row++;
// ----------------------------------------------------------------------
// CUSTOM VALUES POST PROCESS
// ----------------------------------------------------------------------
$count_custom_postprocess = post_process_get_custom_values();
$table_other->data[$row][] = html_print_label_input_block(
    __('Custom values post process'),
    html_print_div(
        [
            'class'   => 'filter-table-adv-manual',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Value').':&nbsp;'.html_print_input_text('custom_value', '', '', 25, 50, true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => '',
                    'content' => __('Text').':&nbsp;'.html_print_input_text('custom_text', '', '', 15, 50, true),
                ],
                true
            ).html_print_button(
                __('Add'),
                'custom_value_add_btn',
                false,
                '',
                [
                    'icon'  => 'next',
                    'mode'  => 'link',
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                ],
                true
            ).html_print_input_hidden(
                'custom_value_add',
                '',
                true
            ),
        ],
        true
    ).html_print_div(
        [
            'class'   => '',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Delete custom values').html_print_select(
                        post_process_get_custom_values(),
                        'custom_values',
                        '',
                        '',
                        '',
                        '',
                        true,
                        false,
                        true,
                        '',
                        false,
                        'width: 100%'
                    ),
                ],
                true
            ).html_print_button(
                __('Delete'),
                'custom_values_del_btn',
                empty($count_custom_postprocess),
                '',
                [
                    'icon'  => 'delete',
                    'mode'  => 'link',
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                ],
                true
            ).html_print_input_hidden(
                'custom_value_to_delete',
                '',
                true
            ),
        ],
        true
    )
);

// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// CUSTOM INTERVAL VALUES
// ----------------------------------------------------------------------
$units = [
    1               => __('seconds'),
    SECONDS_1MINUTE => __('minutes'),
    SECONDS_1HOUR   => __('hours'),
    SECONDS_1DAY    => __('days'),
    SECONDS_1MONTH  => __('months'),
    SECONDS_1YEAR   => __('years'),
];
$table_other->data[$row][] = html_print_label_input_block(
    __('Interval values'),
    html_print_div(
        [
            'class'   => 'filter-table-adv-manual',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Value').html_print_input_text('interval_value', '', '', 5, 5, true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => '',
                    'content' => __('Interval').html_print_select($units, 'interval_unit', 1, '', '', '', true, false, false, '', false, 'width: 100%'),
                ],
                true
            ).html_print_button(
                __('Add'),
                'interval_add_btn',
                false,
                '',
                [
                    'mode'  => 'link',
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                ],
                true
            ).html_print_input_hidden(
                'interval_values',
                $config['interval_values'],
                true
            ),
        ],
        true
    ).html_print_div(
        [
            'class'   => empty($config['interval_values']) === true ? 'invisible' : '',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Delete interval values').html_print_select(
                        get_periods(
                            false,
                            false
                        ),
                        'intervals',
                        '',
                        '',
                        '',
                        '',
                        true,
                        false,
                        true,
                        '',
                        false,
                        'width: 100%'
                    ),
                ],
                true
            ).html_print_button(
                __('Delete'),
                'interval_del_btn',
                empty($config['interval_values']),
                '',
                [
                    'mode'  => 'link',
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                ],
                true
            ).html_print_input_hidden(
                'interval_to_delete',
                '',
                true
            ),
        ],
        true
    )
);
$row++;



$table_other->data[$row][] = html_print_label_input_block(
    __('Module units'),
    html_print_div(
        [
            'class'   => 'filter-table-adv-manual',
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Value').html_print_input_text('custom_module_unit', '', '', 15, 50, true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => '',
                    'content' => __('Interval').html_print_select($units, 'interval_unit', 1, '', '', '', true, false, false, '', false, 'width: 100%'),
                ],
                true
            ).html_print_button(
                __('Add'),
                'module_unit_add_btn',
                false,
                '',
                [
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                    'mode'  => 'link',
                ],
                true
            ),
        ],
        true
    ).html_print_div(
        [
            'class'   => (empty($count_custom_postprocess) === true ? 'invisible' : ''),
            'content' => html_print_div(
                [
                    'class'   => '',
                    'content' => __('Delete custom values').html_print_select(
                        get_custom_module_units(),
                        'module_units',
                        '',
                        '',
                        '',
                        '',
                        true,
                        false,
                        true,
                        '',
                        false,
                        'width: 100%'
                    ),
                ],
                true
            ).html_print_button(
                __('Delete'),
                'custom_module_unit_del_btn',
                empty($count_custom_postprocess),
                '',
                [
                    'style' => 'display: flex; justify-content: flex-end; width: 100%;',
                    'mode'  => 'link',
                ],
                true
            ).html_print_input_hidden(
                'custom_module_unit_to_delete',
                '',
                true
            ),
        ],
        true
    )
);
$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('CSV divider'),
    $csvDividerBlock
);

$table_other->data[$row][] = html_print_label_input_block(
    __('CSV decimal separator'),
    html_print_select($decimal_separators, 'csv_decimal_separator', $config['csv_decimal_separator'], '', '', '', true, false, false)
);
$row++;

$table_other->data[$row][] = html_print_label_input_block(
    __('Data multiplier to use in graphs/data'),
    html_print_select($options_data_multiplier, 'use_data_multiplier', $config['use_data_multiplier'], '', '', 1, true, false, false)
);
$row++;

/*
 *
 * PAINT HTML.
 *
 */

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Behaviour configuration').' '.ui_print_help_icon('behavoir_conf_tab', true).'</legend>';
html_print_table($table_behaviour);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('GIS configuration').' '.ui_print_help_icon('gis_conf_tab', true).'</legend>';
html_print_table($table_gis);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Style configuration').' '.ui_print_help_icon('style_conf_tab', true).'</legend>';
html_print_table($table_styles);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Charts configuration').' '.ui_print_help_icon('charts_conf_tab', true).'</legend>';
html_print_table($table_chars);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Font and Text configuration').' '.ui_print_help_icon('front_and_text_conf_tab', true).'</legend>';
html_print_table($table_font);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Visual consoles configuration').' '.ui_print_help_icon('visual_consoles_conf_tab', true).'</legend>';
html_print_table($table_vc);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Reports configuration ').ui_print_help_icon('reports_configuration_tab', true).'</legend>';
html_print_table($table_report);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Services configuration').' '.ui_print_help_icon('services_conf_tab', true).'</legend>';
html_print_table($table_ser);
echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Other configuration').' '.ui_print_help_icon('other_conf_tab', true).'</legend>';
html_print_table($table_other);
echo '</fieldset>';

html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'update_button',
        false,
        [ 'icon' => 'next' ],
        true
    )
);

echo '</form>';


ui_require_css_file('color-picker', 'include/styles/js/');
ui_require_jquery_file('colorpicker');
ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/');
ui_require_javascript_file('pandora');

?>
<script language="javascript" type="text/javascript">

$(document).ready(function(){
    var editIcon = "<?php echo $csvDividerIconEdit; ?>";
    var listIcon = "<?php echo $csvDividerIconFile; ?>";

    $("#select_csv_divider").click(function(){
        $("#custom_divider_input").toggleClass('invisible');
        $("#common_divider_input").toggleClass('invisible');
        let iconPath = $("#select_csv_divider").attr("src");
        if (iconPath.includes(editIcon)) {
            $("#select_csv_divider").attr("src", listIcon);
        } else {
            $("#select_csv_divider").attr("src", editIcon);
        }
    })

    defineTinyMCE('#textarea_custom_report_front_header');
    defineTinyMCE('#textarea_custom_report_front_footer');
    defineTinyMCE('#textarea_custom_report_front_firstpage');
})

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


$(document).ready (function () {

    var enterprise = '<?php echo enterprise_installed(); ?>';

    if (enterprise === '') {
        $('#text-custom_title_header').prop( "disabled", true );
        $('#text-custom_subtitle_header').prop( "disabled", true );
    }

    // Show the cache expiration conf or not.
    $("input[name=legacy_vc]").change(function (e) {
        if ($(this).prop("checked") === true) {
            $("select#vc_default_cache_expiration_select").closest("td").hide();
            $("#dialog-legacy-vc").dialog({
                modal: true,
                width: 500,
                buttons:[
                    {
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-next',
                        text: "<?php echo __('OK'); ?>",
                        click: function(){
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        } else {
            $("select#vc_default_cache_expiration_select").closest("td").show();
        }
    }).change();

    var comfort = 0;
/*
    if(comfort == 0){
        $(':input,:radio,:checkbox,:file').change(function(){
            $('#button-update_button').css({'position':'fixed','right':'80px','bottom':'55px'});
            var comfort = 1;
        });
        
        $("*").keydown(function(){
            $('#button-update_button').css({'position':'fixed','right':'80px','bottom':'55px'});
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

    */
    $("#form_setup #text-graph_color10").attachColorPicker();

    
    //------------------------------------------------------------------
    // CUSTOM VALUES POST PROCESS
    //------------------------------------------------------------------
    $("#button-custom_values_del_btn").click( function()  {
        var interval_selected = $('#custom_values').val();
        $('#hidden-custom_value_to_delete').val(interval_selected);
        
        $("input[name='custom_value']").val("");
        $("input[name='custom_text']").val("");
        
        $('#button-update_button').trigger('click');
    });
    
    $("#button-custom_value_add_btn").click( function() {
        $('#hidden-custom_value_add').val(1);
        $('#button-update_button').trigger('click');
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
        $('#hidden-interval_values').val(1);
        $('#button-update_button').trigger('click');
    });
    //------------------------------------------------------------------

    //------------------------------------------------------------------
    // CUSTOM MODULE UNITS
    //------------------------------------------------------------------
    $("#button-custom_module_unit_del_btn").click( function()  {
        var unit_selected = $('#module_units option:selected').val();
        $('#hidden-custom_module_unit_to_delete').val(unit_selected);
        $('#button-update_button').trigger('click');
    });

    $("#button-module_unit_add_btn").click( function() {
        $('#button-update_button').trigger('click');
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
    var elementToCheck = e.target.id;
    // Fill it seing the target has been clicked
    switch (elementToCheck) {
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
    var homeUrl = "<?php echo $config['homeurl']; ?>";
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
