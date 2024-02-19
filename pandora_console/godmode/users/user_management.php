<?php
/**
 * User management.
 *
 * @category   Users
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\Dashboard\Manager;

// Load global vars.
global $config;

check_login();

// Bussiness Logic.
// Data for homescreen section.
$homeScreenValues = [
    HOME_SCREEN_DEFAULT        => __('Default'),
    HOME_SCREEN_VISUAL_CONSOLE => __('Visual console'),
    HOME_SCREEN_EVENT_LIST     => __('Event list'),
    HOME_SCREEN_GROUP_VIEW     => __('Group view'),
    HOME_SCREEN_TACTICAL_VIEW  => __('Tactical view'),
    HOME_SCREEN_ALERT_DETAIL   => __('Alert detail'),
    HOME_SCREEN_EXTERNAL_LINK  => __('External link'),
    HOME_SCREEN_OTHER          => __('Other'),
    HOME_SCREEN_DASHBOARD      => __('Dashboard'),
];

$dashboards = Manager::getDashboards(
    -1,
    -1,
    false,
    false,
    $id_usr
);

$dashboards_aux = [];
if ($dashboards === false) {
    $dashboards = ['None' => 'None'];
} else {
    foreach ($dashboards as $key => $dashboard) {
        $dashboards_aux[$dashboard['id']] = $dashboard['name'];
    }
}

// Custom Home Screen controls.
$customHomeScreenAddition = [];
// Home screen. Dashboard.
$customHomeScreenAddition[HOME_SCREEN_DASHBOARD] = html_print_select(
    $dashboards_aux,
    'dashboard',
    $user_info['data_section'],
    '',
    '',
    '',
    true,
    false,
    true,
    'w100p',
    false,
    'width: 100%'
);
// Home screen. Visual consoles.
$customHomeScreenAddition[HOME_SCREEN_VISUAL_CONSOLE] = html_print_select($layouts_aux, 'visual_console', $user_info['data_section'], '', '', '', true, false, true, 'w100p', false, 'width: 100%');
// Home screen. External link and Other.
$customHomeScreenAddition[HOME_SCREEN_EXTERNAL_LINK] = html_print_input_text('data_section', $user_info['data_section'], '', 60, 400, true);

$layouts = visual_map_get_user_layouts($config['id_user'], true);
$layouts_aux = [];
if ($layouts === false) {
    $layouts_aux = ['None' => 'None'];
} else {
    foreach ($layouts as $layout) {
        $layouts_aux[$layout] = $layout;
    }
}

// Home screen. Visual consoles.
$customHomeScreenAddition[HOME_SCREEN_VISUAL_CONSOLE] = html_print_select(
    $layouts_aux,
    'visual_console',
    $user_info['data_section'],
    '',
    '',
    '',
    true,
    false,
    true,
    'w100p',
    false,
    'width: 100%'
);
// Home screen. External link and Other.
$customHomeScreenAddition[HOME_SCREEN_EXTERNAL_LINK] = html_print_input_text(
    'data_section',
    $user_info['data_section'],
    '',
    60,
    999,
    true
);
$customHomeScreenDataField = '';
foreach ($customHomeScreenAddition as $key => $customField) {
    $customHomeScreenDataField .= html_print_div(
        [
            'id'      => sprintf('custom_home_screen_%s', $key),
            'content' => $customField,
        ],
        true
    );
}

// Timezone creation canvas.
$timezoneContent = [];
if (is_metaconsole() === false) {
    date_default_timezone_set('UTC');
    include_once 'include/javascript/timezonepicker/includes/parser.inc';

    // Read in options for map builder.
    $bases = [
        'gray'           => 'Gray',
        'blue-marble'    => 'Blue marble',
        'night-electric' => 'Night Electric',
        'living'         => 'Living Earth',
    ];

    $local_file = 'include/javascript/timezonepicker/images/gray-400.png';

    // Dimensions must always be exact since the imagemap does not scale.
    $array_size = getimagesize($local_file);

    $map_width = $array_size[0];
    $map_height = $array_size[1];

    $timezones = timezone_picker_parse_files(
        $map_width,
        $map_height,
        'include/javascript/timezonepicker/tz_world.txt',
        'include/javascript/timezonepicker/tz_islands.txt'
    );

    // Initial definition of vars.
    $area_data_timezone_polys = '';
    $area_data_timezone_rects = '';

    foreach ($timezones as $timezone_name => $tz) {
        if ($timezone_name === 'America/Montreal') {
            $timezone_name = 'America/Toronto';
        } else if ($timezone_name === 'Asia/Chongqing') {
            $timezone_name = 'Asia/Shanghai';
        }

        foreach ($tz['polys'] as $coords) {
            $area_data_timezone_polys .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="poly" coords="'.implode(',', $coords).'" />';
        }

        foreach ($tz['rects'] as $coords) {
            $area_data_timezone_rects .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="rect" coords="'.implode(',', $coords).'" />';
        }
    }

    $timezoneContent[] = '<img id="timezone-image" src="'.$local_file.'" width="'.$map_width.'" height="'.$map_height.'" usemap="#timezone-map" />';
    $timezoneContent[] = '<img class="timezone-pin" src="include/javascript/timezonepicker/images/pin.png" class="pdd_t_4px" />';
    $timezoneContent[] = '<map name="timezone-map" id="timezone-map">'.$area_data_timezone_polys.$area_data_timezone_rects.'</map>';
}

// Create the view.
$userManagementTable = new stdClass();
$userManagementTable->id = 'advanced';
$userManagementTable->width = '100%';
$userManagementTable->class = 'principal_table floating_form white_box';
$userManagementTable->data = [];
$userManagementTable->style = [];
$userManagementTable->rowclass = [];
$userManagementTable->cellclass = [];
$userManagementTable->colspan = [];
$userManagementTable->rowspan = [];

// Title for Profile information.
$sustitleTable = ($new_user === true) ? __('Profile information') : sprintf('%s [ %s ]', __('Profile information for'), $id);
$userManagementTable->data['title_profile_information'] = html_print_subtitle_table($sustitleTable);

// Id user.
if ($new_user === true) {
    $userManagementTable->rowclass['captions_iduser'] = 'field_half_width';
    $userManagementTable->rowclass['fields_iduser'] = 'field_half_width';
    $userManagementTable->data['captions_iduser'][0] = __('User ID');
    $userManagementTable->data['fields_iduser'][0] = html_print_input_text_extended(
        'id_user',
        '',
        '',
        '',
        20,
        255,
        !$new_user || $view_mode,
        '',
        [
            'class'       => 'input',
            'placeholder' => __('User ID'),
        ],
        true
    );
} else {
    $userManagementTable->data['fields_iduser'][0] = html_print_input_hidden('id', $id, false, false, false, 'id');
}

// User Full name.
$userManagementTable->rowclass['captions_fullname'] = 'field_half_width';
$userManagementTable->rowclass['fields_fullname'] = 'field_half_width';
$userManagementTable->data['captions_fullname'][0] = __('Full name');
$userManagementTable->data['fields_fullname'][0] = html_print_input_text_extended(
    'fullname',
    $user_info['fullname'],
    'fullname',
    '',
    20,
    100,
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Full (display) name'),
    ],
    true
);

// User Email.
$userManagementTable->rowclass['captions_email'] = 'field_half_width';
$userManagementTable->rowclass['fields_email'] = 'field_half_width';
$userManagementTable->data['captions_email'][0] = __('Email');
$userManagementTable->data['fields_email'][0] = html_print_input_text_extended(
    'email',
    $user_info['email'],
    'email',
    '',
    '25',
    '100',
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('E-mail'),
    ],
    true
);

// User phone number.
$userManagementTable->rowclass['captions_phone'] = 'field_half_width';
$userManagementTable->rowclass['fields_phone'] = 'field_half_width';
$userManagementTable->data['captions_phone'][0] = __('Phone number');
$userManagementTable->data['fields_phone'][0] = html_print_input_text_extended(
    'phone',
    $user_info['phone'],
    'phone',
    '',
    '20',
    '30',
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Phone number'),
    ],
    true
);

$fieldsAdminUserCount = 0;
$userManagementTable->rowclass['captions_fields_admin_user'] = 'field_half_width w50p';
$userManagementTable->cellclass['captions_fields_admin_user'][$fieldsAdminUserCount] = 'wrap';
if (empty($doubleAuthentication) === false) {
    $userManagementTable->data['captions_fields_admin_user'][$fieldsAdminUserCount] = $doubleAuthentication;
    $fieldsAdminUserCount++;
}

if (users_is_admin() === true) {
    $globalProfileContent = [];
    $globalProfileContent[] = '<span>'.__('Administrator user').'</span>';
    $globalProfileContent[] = html_print_checkbox_switch(
        'is_admin',
        0,
        $user_info['is_admin'],
        true
    );

    $userManagementTable->cellclass['captions_fields_admin_user'][$fieldsAdminUserCount] = 'wrap';
    $userManagementTable->data['captions_fields_admin_user'][$fieldsAdminUserCount] = html_print_div(
        [
            'class'   => 'margin-top-10',
            'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
            'content' => implode('', $globalProfileContent),
        ],
        true
    );
} else {
    // Insert in the latest row this hidden input avoiding create empty rows.
    $userManagementTable->data['fields_phone'][0] .= html_print_input_hidden(
        'is_admin_sent',
        0,
        true
    );
}

// Password management.
$passwordManageTable = new stdClass();
$passwordManageTable->class = 'full_section';
$passwordManageTable->id = 'password_manage';
$passwordManageTable->style = [];
$passwordManageTable->rowclass = [];
$passwordManageTable->data = [];

$passwordManageTable->data['captions_newpassword'][0] = __('New password');
$passwordManageTable->rowclass['fields_newpassword'] = 'w540px';
$passwordManageTable->data['fields_newpassword'][0] = '<div class="relative container-div-input-password ">'.html_print_input_text_extended(
    'password_new',
    '',
    'password_new',
    '',
    '25',
    '150',
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Password'),
        'style'       => 'width: 540px',
    ],
    true,
    true
).'</div>';

$passwordManageTable->data['captions_repeatpassword'][0] = __('Repeat new password');
$passwordManageTable->rowclass['fields_repeatpassword'] = 'w540px';
$passwordManageTable->data['fields_repeatpassword'][0] = '<div class="relative container-div-input-password ">'.html_print_input_text_extended(
    'password_confirm',
    '',
    'password_conf',
    '',
    '20',
    '150',
    $view_mode,
    '',
    [
        'class'       => 'input w100p',
        'placeholder' => __('Password confirmation'),
        'style'       => 'width: 540px',
    ],
    true,
    true
).'</div>';

if ($new_user === false && users_is_admin() === false) {
    $passwordManageTable->data['captions_currentpassword'][0] = __('Current password');
    $passwordManageTable->rowclass['fields_currentpassword'] = 'w540px';

    if ($user_info['id_user'] === $config['id_user']) {
        $passwordManageTable->data['fields_currentpassword'][0] = html_print_input_text_extended(
            'own_password_confirm',
            '',
            'own_password_confirm',
            '',
            '20',
            '45',
            $view_mode,
            '',
            [
                'class'       => 'input w100p',
                'placeholder' => __('Own password confirmation'),
            ],
            true,
            true
        );
    } else {
        $passwordManageTable->data['fields_currentpassword'][0] = html_print_input_text_extended(
            'own_password_confirm',
            '',
            'own_password_confirm',
            '',
            '20',
            '45',
            $view_mode,
            '',
            [
                'class'       => 'input w100p',
                'placeholder' => __('Third user password confirmation'),
            ],
            true,
            true
        );
    }
}

$userManagementTable->data['passwordManage_table'] = html_print_table($passwordManageTable, true);

if (users_is_admin() === true) {
    $userManagementTable->rowclass['captions_loginErrorUser'] = 'field_half_width w50p';
    $userManagementTable->cellclass['captions_loginErrorUser'][0] = 'wrap';
    $userManagementTable->cellclass['captions_loginErrorUser'][1] = 'wrap';
    $notLoginCheckContent = [];
    $notLoginCheckContent[] = '<span>'.__('Not Login').'</span>';
    $notLoginCheckContent[] = html_print_checkbox_switch(
        'not_login',
        1,
        $user_info['not_login'],
        true
    );

    $userManagementTable->data['captions_loginErrorUser'][0] = html_print_div(
        [
            'class'   => 'margin-top-10',
            'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
            'content' => implode('', $notLoginCheckContent),
        ],
        true
    );
    $userManagementTable->data['captions_loginErrorUser'][0] .= ui_print_input_placeholder(
        __('The user with not login set only can access to API.'),
        true
    );

    $localUserCheckContent = [];
    $localUserCheckContent[] = '<span>'.__('Local User').'</span>';
    $localUserCheckContent[] = html_print_checkbox_switch(
        'local_user',
        1,
        $user_info['local_user'],
        true
    );

    $userManagementTable->data['captions_loginErrorUser'][1] = html_print_div(
        [
            'class'   => 'margin-top-10',
            'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
            'content' => implode('', $localUserCheckContent),
        ],
        true
    );
    $userManagementTable->data['captions_loginErrorUser'][1] .= ui_print_input_placeholder(
        __('The user with local authentication enabled will always use local authentication.'),
        true
    );
}

$userManagementTable->data['show_tips_startup'][0] = html_print_checkbox_switch(
    'show_tips_startup',
    1,
    (isset($user_info['show_tips_startup']) === false) ? true : $user_info['show_tips_startup'],
    true
);

$userManagementTable->data['show_tips_startup'][1] = '<span>'.__('Show usage tips at startup').'</span>';

// Session time input.
$userManagementTable->rowclass['captions_userSessionTime'] = 'field_half_width';
$userManagementTable->rowclass['fields_userSessionTime'] = 'field_half_width';
$userManagementTable->cellclass['fields_userSessionTime'][0] = 'wrap';
$userManagementTable->data['captions_userSessionTime'][0] = __('Session time');
$userManagementTable->data['fields_userSessionTime'][0] = html_print_input_text(
    'session_time',
    $user_info['session_time'],
    '',
    5,
    5,
    true
);
$userManagementTable->data['fields_userSessionTime'][0] .= ui_print_input_placeholder(
    __('This is defined in minutes, If you wish a permanent session should putting -1 in this field.'),
    true
);

// Title for Autorefresh.
$userManagementTable->data['title_autorefresh'] = html_print_subtitle_table(__('Autorefresh'));

// Autorefresh selects.
$select_out = html_print_select(
    $autorefresh_list_out,
    'autorefresh_list_out[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:100%;'
);

$select_in = html_print_select(
    $autorefresh_list,
    'autorefresh_list[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:100%;'
);

// Full list pages generation.
$autorefreshFullListContent = [];
$autorefreshFullListContent[] = '<p class="autorefresh_select_text">'.__('Full list of pages').'</p>';
$autorefreshFullListContent[] = html_print_div(
    [
        'id'      => 'autorefreshAllPagesList',
        'content' => $select_out,
    ],
    true
);

// Selected pages generation.
$autorefreshSelectedListContent = [];
$autorefreshSelectedListContent[] = '<p class="autorefresh_select_text">'.__('Pages with autorefresh').'</p>';
$autorefreshSelectedListContent[] = html_print_div(
    [
        'id'      => 'autorefreshSelectedPagesList',
        'content' => $select_in,
    ],
    true
);

// Controls generation.
$autorefreshControlsContent = [];
$autorefreshControlsContent[] = html_print_anchor(
    [
        'id'      => 'addAutorefreshPage',
        'href'    => 'javascript:',
        'content' => html_print_image(
            'images/plus.svg',
            true,
            [
                'id'    => 'right_autorefreshlist',
                'style' => 'width: 24px; margin: 10px 10px 0;',
                'alt'   => __('Push selected pages into autorefresh list'),
                'title' => __('Push selected pages into autorefresh list'),
            ]
        ),
    ],
    true
);
$autorefreshControlsContent[] = html_print_anchor(
    [
        'id'      => 'removeAutorefreshPage',
        'href'    => 'javascript:',
        'content' => html_print_image(
            'images/minus.svg',
            true,
            [
                'id'    => 'left_autorefreshlist',
                'style' => 'width: 24px; margin: 10px 10px 0;',
                'alt'   => __('Pop selected pages out of autorefresh list'),
                'title' => __('Pop selected pages out of autorefresh list'),
            ]
        ),
    ],
    true
);

// Container with all pages list.
$autorefreshFullList = html_print_div(
    [
        'class'   => 'autorefresh_select_list_out',
        'content' => implode('', $autorefreshFullListContent),
    ],
    true
);

// Container with selected pages list.
$autorefreshSelectedList = html_print_div(
    [
        'class'   => 'autorefresh_select_list',
        'content' => implode('', $autorefreshSelectedListContent),
    ],
    true
);

// Container with controls.
$autorefreshControls = html_print_div(
    [
        'class'   => 'autorefresh_select_arrows',
        'content' => implode('', $autorefreshControlsContent),
    ],
    true
);

// Generate final control table.
$autorefreshTable = html_print_div(
    [
        'class'   => 'autorefresh_select',
        'content' => $autorefreshFullList.$autorefreshControls.$autorefreshSelectedList,
    ],
    true
);

$userManagementTable->rowclass['captions_autorefreshList'] = 'field_half_width';
$userManagementTable->rowclass['fields_autorefreshList'] = 'field_half_width';
$userManagementTable->cellstyle['fields_autorefreshList'][0] = 'width: 100%';
$userManagementTable->data['captions_autorefreshList'] = __('Autorefresh pages');
$userManagementTable->data['fields_autorefreshList'] = $autorefreshTable;

$userManagementTable->rowclass['captions_autorefreshTime'] = 'field_half_width';
$userManagementTable->rowclass['fields_autorefreshTime'] = 'field_half_width';
$userManagementTable->cellclass['fields_autorefreshTime'][0] = 'wrap';
$userManagementTable->data['captions_autorefreshTime'][0] = __('Time for autorefresh');
$userManagementTable->data['fields_autorefreshTime'][0] = html_print_select(
    get_refresh_time_array(),
    'time_autorefresh',
    ($user_info['time_autorefresh'] ?? 0),
    '',
    '',
    '',
    true,
    false,
    false
);
$userManagementTable->data['fields_autorefreshTime'][0] .= ui_print_input_placeholder(
    __('Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first'),
    true
);

// EHorus conf.
if (isset($config['ehorus_user_level_conf']) === true && (bool) $config['ehorus_user_level_conf'] === true) {
    $userManagementTable->data['captions_ehorus_user_level_enabled'][1] = __('Pandora RC user access enabled');
    $userManagementTable->data['fields_ehorus_user_level_enabled'][1] = html_print_checkbox_switch(
        'ehorus_user_level_enabled',
        1,
        $user_info['ehorus_user_level_enabled'],
        true
    );

    $userManagementTable->rowclass['captions_ehorus_user_level_user'] = 'field_half_width';
    $userManagementTable->rowclass['fields_ehorus_user_level_user'] = 'field_half_width';
    $userManagementTable->data['captions_ehorus_user_level_user'][1] = __('Pandora RC User');
    $userManagementTable->data['fields_ehorus_user_level_user'][1] = html_print_input_text_extended(
        'ehorus_user_level_user',
        $user_info['ehorus_user_level_user'],
        '',
        '',
        25,
        150,
        $view_mode,
        '',
        [
            'class'       => 'input',
            'placeholder' => __('Pandora RC User'),
        ],
        true
    );

    $userManagementTable->rowclass['captions_ehorus_user_level_pass'] = 'field_half_width';
    $userManagementTable->rowclass['fields_ehorus_user_level_pass'] = 'field_half_width';
    $userManagementTable->data['captions_ehorus_user_level_pass'][1] = __('Pandora RC Password');
    $userManagementTable->data['fields_ehorus_user_level_pass'][1] = html_print_input_text_extended(
        'ehorus_user_level_pass',
        io_output_password($user_info['ehorus_user_level_pass']),
        '',
        '',
        '25',
        '150',
        $view_mode,
        '',
        [
            'class'       => 'input w100p',
            'placeholder' => __('Pandora RC Password'),
        ],
        true,
        true
    );
}


// Title for Language and Appearance.
$userManagementTable->data['title_lookAndFeel'] = html_print_subtitle_table(__('Language and Appearance'));
// Language and color scheme.
$userManagementTable->rowclass['line1_looknfeel'] = 'field_half_width';
$userManagementTable->rowclass['line2_looknfeel'] = 'field_half_width';
$userManagementTable->data['line1_looknfeel'][0] = __('Language');
$userManagementTable->data['line2_looknfeel'][0] = html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true
);

// Hidden hint to change theme.
$hin_change_theme = ui_print_help_tip(__('When changing the theme, the login screen logo will be restricted to the default for that color scheme, if you have a custom logo, adjust it after changing the theme.'), true, '', '', 'display: none;');

if (is_metaconsole() === true) {
    if (users_is_admin() === true) {
        $userManagementTable->data['line1_looknfeel'][1] = $outputMetaAccess[0];
        $userManagementTable->data['line2_looknfeel'][1] = $outputMetaAccess[1];
    }
} else {
    if (function_exists('skins_print_select')) {
        $userManagementTable->data['line1_looknfeel'][1] = __('User color scheme').$hin_change_theme;
        $userManagementTable->data['line2_looknfeel'][1] = skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true);
    }
}

$performance_variables_control = (array) json_decode(io_safe_output($config['performance_variables_control']));

$userManagementTable->rowclass['captions_blocksize_eventfilter'] = 'field_half_width';
$userManagementTable->rowclass['fields_blocksize_eventfilter'] = 'field_half_width';
$userManagementTable->data['captions_blocksize_eventfilter'][0] = __('Block size for pagination');
$userManagementTable->data['fields_blocksize_eventfilter'][0] = html_print_input(
    [
        'type'   => 'number',
        'size'   => 5,
        'max'    => $performance_variables_control['block_size']->max,
        'name'   => 'block_size',
        'value'  => $user_info['block_size'],
        'return' => true,
    ]
);

if (is_metaconsole() === true && empty($user_info['metaconsole_default_event_filter']) !== true) {
    $user_info['default_event_filter'] = $user_info['metaconsole_default_event_filter'];
}

$userManagementTable->data['captions_blocksize_eventfilter'][1] = __('Event filter');
$userManagementTable->data['fields_blocksize_eventfilter'][1] = html_print_select(
    $event_filter,
    'default_event_filter',
    [$user_info['default_event_filter']],
    '',
    '',
    __('None'),
    true,
    false,
    false
);

// Home screen table.
$homeScreenTable = new stdClass();
$homeScreenTable->class = 'w100p full_section';
$homeScreenTable->id = 'home_screen_table';
$homeScreenTable->style = [];
$homeScreenTable->rowclass = [];
$homeScreenTable->data = [];
// Home screen.
if (is_metaconsole() === true && empty($user_info['metaconsole_data_section']) !== true) {
    $user_info['data_section'] = $user_info['metaconsole_data_section'];
    $user_info['section'] = $user_info['metaconsole_section'];
}

$homeScreenTable->data['captions_homescreen'][0] = __('Home screen');
$homeScreenTable->colspan['captions_homescreen'][0] = 2;
$homeScreenTable->rowclass['captions_homescreen'] = 'field_half_width';
$homeScreenTable->rowclass['fields_homescreen'] = 'field_half_width flex';
$homeScreenTable->data['fields_homescreen'][0] = html_print_select(
    $homeScreenValues,
    'section',
    $user_info['section'],
    'show_data_section();',
    '',
    -1,
    true,
    false,
    false
);
$homeScreenTable->data['fields_homescreen'][1] = html_print_div(
    [
        'class'   => 'w100p',
        'content' => $customHomeScreenDataField,
    ],
    true
);

$userManagementTable->rowclass['homescreen_table'] = 'w100p';
$userManagementTable->data['homescreen_table'] = html_print_table($homeScreenTable, true);

$homeScreenTable->data['fields_homescreen'][1] = html_print_div(
    [
        'class'   => 'w100p',
        'content' => $customHomeScreenDataField,
    ],
    true
);

$userManagementTable->rowclass['homescreen_table'] = 'w100p';
$userManagementTable->data['homescreen_table'] = html_print_table($homeScreenTable, true);


if (is_metaconsole() === true && users_is_admin() === true) {
    $userManagementTable->rowclass['search_custom1_looknfeel'] = 'field_half_width';
    $userManagementTable->rowclass['search_custom2_looknfeel'] = 'field_half_width flex-column';
    $userManagementTable->data['search_custom1_looknfeel'][0] = $searchCustomFieldView[0];
    $userManagementTable->data['search_custom2_looknfeel'][0] = $searchCustomFieldView[1];

    $userManagementTable->rowclass['agent_manager1_looknfeel'] = 'field_half_width';
    $userManagementTable->rowclass['agent_manager2_looknfeel'] = 'field_half_width flex-column';
    $userManagementTable->data['agent_manager1_looknfeel'][0] = $metaconsoleAgentManager[0];
    $userManagementTable->data['agent_manager1_looknfeel'][1] = $metaconsoleAgentManager[2];
    $userManagementTable->data['agent_manager2_looknfeel'][0] = $metaconsoleAgentManager[1];
    $userManagementTable->data['agent_manager2_looknfeel'][1] = $metaconsoleAgentManager[3];
}

// Timezone.
$userManagementTable->rowclass['captions_timezone'] = 'field_half_width';
$userManagementTable->rowclass['fields_timezone'] = 'field_half_width';
$userManagementTable->colspan['captions_timezone'][0] = 2;
$userManagementTable->cellstyle['fields_timezone'][0] = 'align-self: baseline;';
$userManagementTable->cellclass['fields_timezone'][0] = 'wrap';
$userManagementTable->data['captions_timezone'][0] = __('Time zone');
$userManagementTable->data['fields_timezone'][0] = html_print_timezone_select('timezone', $user_info['timezone']);
$userManagementTable->data['fields_timezone'][0] .= ui_print_input_placeholder(
    __('The timezone must be that of the associated server.'),
    true
);
if (is_metaconsole() === false) {
    $userManagementTable->data['fields_timezone'][1] = html_print_div(
        [
            'id'      => 'timezone-picker',
            'content' => implode('', $timezoneContent),
        ],
        true
    );
}

// Title for Language and Appearance.
$userManagementTable->data['title_additionalSettings'] = html_print_subtitle_table(__('Additional settings'));

$userManagementTable->rowclass['captions_addSettings'] = 'field_half_width';
$userManagementTable->rowclass['fields_addSettings'] = 'field_half_width';
$userManagementTable->cellstyle['fields_addSettings'][0] = 'align-self: baseline';
$userManagementTable->cellstyle['fields_addSettings'][1] = 'width: 50%;flex-direction: column;align-items: flex-start;';
$userManagementTable->data['captions_addSettings'][0] = __('Comments');
$userManagementTable->data['fields_addSettings'][0] = html_print_textarea(
    'comments',
    5,
    65,
    $user_info['comments'],
    ($view_mode) ? 'readonly="readonly"' : '',
    true,
    ''
);

if (users_is_admin($config['id_user']) === true || (bool) check_acl($config['id_user'], 0, 'PM') === true) {
    $allowAllIpsContent = [];
    $allowAllIpsContent[] = '<span>'.__('Enable IP allowlist').'</span>';
    $allowAllIpsContent[] = html_print_div(
        [
            'content' => html_print_checkbox_switch(
                'allowed_ip_active',
                0,
                ($user_info['allowed_ip_active'] ?? 0),
                true,
                false,
                'handleIpAllowlist(this)'
            ),
        ],
        true
    );

    $userManagementTable->data['captions_addSettings'][1] = html_print_div(
        [
            'class'   => 'margin-top-10',
            'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
            'content' => implode('', $allowAllIpsContent),
        ],
        true
    );

    $userManagementTable->data['fields_addSettings'][1] .= html_print_div(
        [
            'class'   => 'edit_user_allowed_ip '.(((int) $user_info['allowed_ip_active'] === 1) ? '' : 'invisible'),
            'content' => html_print_textarea(
                'allowed_ip_list',
                5,
                65,
                ($user_info['allowed_ip_list'] ?? ''),
                (((bool) $view_mode === true) ? 'readonly="readonly"' : ''),
                true
            ),
        ],
        true
    );

    $userManagementTable->data['fields_addSettings'][1] .= ui_print_input_placeholder(
        __('Add the source IPs that will allow console access. Each IP must be separated only by comma. * allows all.'),
        true,
        [
            'id'    => 'info_allowed_ip',
            'class' => ((int) $user_info['allowed_ip_active'] === 1) ? 'input_sub_placeholder' : 'input_sub_placeholder invisible',
        ]
    );
}

if ($config['ITSM_enabled'] && $config['ITSM_user_level_conf']) {
    // Pandora ITSM user remote login.
    $table_ITSM = new StdClass();
    $table_ITSM->data = [];
    $table_ITSM->width = '100%';
    $table_ITSM->id = 'ITSM-remote-setup';
    $table_ITSM->class = 'white_box';
    $table_ITSM->size['name'] = '30%';
    $table_ITSM->style['name'] = 'font-weight: bold';

    // Pandora ITSM user level authentication.
    // Title.
    $row = [];
    $row['control'] = '<p class="edit_user_labels">'.__('Pandora ITSM user configuration').': </p>';
    $table_ITSM->data['ITSM_user_level_conf'] = $row;

    // Pandora ITSM pass.
    $row = [];
    $row['name'] = __('Token');
    $row['control'] = html_print_input_password(
        'integria_user_level_pass',
        io_output_password($user_info['integria_user_level_pass']),
        '',
        100,
        100,
        true
    );
    $table_ITSM->data['integria_user_level_pass'] = $row;

    // Test.
    $ITSM_host = db_get_value('value', 'tconfig', 'token', 'ITSM_hostname');

    $row = [];
    $row['name'] = __('Test');
    $row['control'] = html_print_button(
        __('Start'),
        'ITSM',
        false,
        '',
        [
            'icon' => 'cog',
            'mode' => 'secondary mini',
        ],
        true
    );
    $row['control'] .= '&nbsp;<span id="ITSM-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $row['control'] .= '&nbsp;<span id="ITSM-success" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
    $row['control'] .= '&nbsp;<span id="ITSM-failure" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
    $row['control'] .= '<span id="ITSM-message" class="invisible"></span>';
    $table_ITSM->data['ITSM_test'] = $row;

    $userManagementTable->colspan['pandoraitsm'] = 2;
    $userManagementTable->data['pandoraitsm'] = html_print_table($table_ITSM, true);
}

if (isset($CodeQRTable) === true || isset($apiTokenContent) === true) {
    // QR Code and API Token advice.
    $titleQr = '<span class="font-title-font">'.__('Contact details (QR)').'</span>';
    $titleApi = '<span class="font-title-font margin-top-10">'.__('API Token credentials').'</span>';
    html_print_div(
        [
            'id'      => 'api_qrcode_display',
            'content' => $titleQr.$CodeQRTable.$titleApi.$apiTokenContent,
        ]
    );
}

html_print_table($userManagementTable);

$vcard_data = [];
$vcard_data['version'] = '3.0';
$vcard_data['firstName'] = io_safe_output($user_info['fullname']);
$vcard_data['lastName'] = '';
$vcard_data['middleName'] = '';
$vcard_data['workPhone'] = $user_info['phone'];
$vcard_data['email'] = $user_info['email'];
$vcard_data['organization'] = io_safe_output(get_product_name());
$vcard_data['url'] = ui_get_full_url('index.php');

$vcard_json = json_encode($vcard_data);

ui_require_javascript_file('ITSM');
?>

<script type="text/javascript">
$(document).ready(function () {
    paint_vcard(
        <?php echo $vcard_json; ?>,
        "#qr_code_agent_view",
        128,
        128
    );

    $('#button-ITSM').click(function() {
        var pass = $('input#password-integria_user_level_pass').val();
        var host = '<?php echo $ITSM_host; ?>';
        testConectionApi(pass, host);
    });

    //Hint to change theme.
    $('#skin1').on("change", () => {
        $('#advanced-line1_looknfeel-1 > a').css('display', 'block');
    })

    var ehorus_user_level_enabled = '<?php echo (isset($user_info['ehorus_user_level_enabled']) === true) ? $user_info['ehorus_user_level_enabled'] : 0; ?>';
    var chk_ehorus_user_level_enabled = ehorus_user_level_enabled;

    if (ehorus_user_level_enabled == 0) {
        $('#advanced-captions_ehorus_user_level_user').hide();
        $('#advanced-fields_ehorus_user_level_user').hide();
        $('#advanced-captions_ehorus_user_level_pass').hide();
        $('#advanced-fields_ehorus_user_level_pass').hide();
    } else {
        $('#advanced-captions_ehorus_user_level_user').show();
        $('#advanced-fields_ehorus_user_level_user').show();
        $('#advanced-captions_ehorus_user_level_pass').show();
        $('#advanced-fields_ehorus_user_level_pass').show();
    }

    $('#checkbox-ehorus_user_level_enabled1').on('change', () =>{

        if (chk_ehorus_user_level_enabled == 1) {
            $('#advanced-captions_ehorus_user_level_user').hide();
            $('#advanced-fields_ehorus_user_level_user').hide();
            $('#advanced-captions_ehorus_user_level_pass').hide();
            $('#advanced-fields_ehorus_user_level_pass').hide();
            chk_ehorus_user_level_enabled = 0;
        } else {
            $('#advanced-captions_ehorus_user_level_user').show();
            $('#advanced-fields_ehorus_user_level_user').show();
            $('#advanced-captions_ehorus_user_level_pass').show();
            $('#advanced-fields_ehorus_user_level_pass').show();
            chk_ehorus_user_level_enabled = 1;
        }
    })
});

function handleIpAllowlist(e){
    if(e.checked === true) {
        $('.edit_user_allowed_ip').show();
        $('#info_allowed_ip').show();
    } else {
        $('.edit_user_allowed_ip').hide();
        $('#info_allowed_ip').hide();
    }
}
</script>