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

// Custom Home Screen controls.
$customHomeScreenAddition = [];
// Home screen. Dashboard.
$customHomeScreenAddition[HOME_SCREEN_DASHBOARD] = html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true);
// Home screen. Visual consoles.
$customHomeScreenAddition[HOME_SCREEN_VISUAL_CONSOLE] = html_print_select($layouts_aux, 'visual_console', $user_info['data_section'], '', '', '', true);
// Home screen. External link and Other.
$customHomeScreenAddition[HOME_SCREEN_EXTERNAL_LINK] = html_print_input_text('data_section', $user_info['data_section'], '', 60, 255, true, false);
$customHomeScreenAddition[HOME_SCREEN_OTHER] = html_print_input_text('data_section', $user_info['data_section'], '', 60, 255, true, false);

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

    foreach ($timezones as $timezone_name => $tz) {
        if ($timezone_name === 'America/Montreal') {
            $timezone_name = 'America/Toronto';
        } else if ($timezone_name === 'Asia/Chongqing') {
            $timezone_name = 'Asia/Shanghai';
        }

        $area_data_timezone_polys .= '';
        foreach ($tz['polys'] as $coords) {
            $area_data_timezone_polys .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="poly" coords="'.implode(',', $coords).'" />';
        }

        $area_data_timezone_rects .= '';
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
$userManagementTable->class = 'w100p floating_form';
$userManagementTable->data = [];
$userManagementTable->style = [];
$userManagementTable->rowclass = [];
$userManagementTable->cellclass = [];
$userManagementTable->colspan = [];
$userManagementTable->rowspan = [];

// Title for Profile information.
$userManagementTable->rowclass['title_profile_information'] = 'w100p';
$userManagementTable->cellstyle['title_profile_information'][0] = 'width: 40px;';
$userManagementTable->cellstyle['title_profile_information'][1] = 'width: 100%;';
$userManagementTable->data['title_profile_information'][0] = html_print_div(
    [
        'class'   => 'section_table_title_line',
        'content' => '',
    ],
    true
);
$userManagementTable->data['title_profile_information'][1] = html_print_subtitle_table(__('Profile information'), [], true);

// Id user.
if ($new_user === true) {
    $userManagementTable->rowclass['captions_iduser'] = 'field_half_width pdd_t_10px';
    $userManagementTable->rowclass['fields_iduser'] = 'field_half_width';
    $userManagementTable->data['captions_iduser'][0] = __('User ID');
    $userManagementTable->data['fields_iduser'][0] = html_print_input_text_extended(
        'id_user',
        $id,
        '',
        '',
        20,
        255,
        !$new_user || $view_mode,
        '',
        [
            'class'       => 'input_line user_icon_input',
            'placeholder' => __('User ID'),
        ],
        true
    );
} else {
    // TODO. Show the user id with a label.
}

// User Full name.
$userManagementTable->rowclass['captions_fullname'] = 'field_half_width pdd_t_10px';
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
$userManagementTable->rowclass['captions_email'] = 'field_half_width pdd_t_10px';
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
        'class'       => 'input input_line email_icon_input',
        'placeholder' => __('E-mail'),
    ],
    true
);

// User phone number.
$userManagementTable->rowclass['captions_phone'] = 'field_half_width pdd_t_10px';
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
        'class'       => 'input input_line phone_icon_input',
        'placeholder' => __('Phone number'),
    ],
    true
);

// Password management.
$passwordManageTable = new stdClass();
$passwordManageTable->class = 'w100p';
$passwordManageTable->id = 'password_manage';
$passwordManageTable->style = [];
$passwordManageTable->rowclass = [];
$passwordManageTable->data = [];

$passwordManageTable->data['captions_newpassword'][0] = __('New password');
$passwordManageTable->rowclass['fields_newpassword'] = 'w540px';
$passwordManageTable->data['fields_newpassword'][0] = html_print_input_text_extended(
    'password_new',
    '',
    'password_new',
    '',
    '25',
    '45',
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Password'),
    ],
    true,
    true
);

$passwordManageTable->data['captions_repeatpassword'][0] = __('Repeat new password');
$passwordManageTable->rowclass['fields_repeatpassword'] = 'w540px';
$passwordManageTable->data['fields_repeatpassword'][0] = html_print_input_text_extended(
    'password_confirm',
    '',
    'password_conf',
    '',
    '20',
    '45',
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Password confirmation'),
    ],
    true,
    true
);

if ($new_user === false) {
    $passwordManageTable->data['captions_currentpassword'][0] = __('Current password');
    $passwordManageTable->rowclass['fields_currentpassword'] = 'w540px';
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
            'class'       => 'input',
            'placeholder' => __('Own password confirmation'),
        ],
        true,
        true
    );
}

// $userManagementTable->rowclass['captions_passwordManage'] = 'full_section pdd_t_10px';
$userManagementTable->rowclass['passwordManage_table'] = 'table_section full_section';
// $userManagementTable->data['captions_passwordManage'][0] = __('Password management');
$userManagementTable->data['passwordManage_table'] = html_print_table($passwordManageTable, true);

// Title for Autorefresh.
$userManagementTable->rowclass['title_autorefresh'] = 'w100p';
$userManagementTable->cellstyle['title_autorefresh'][0] = 'width: 40px;';
$userManagementTable->cellstyle['title_autorefresh'][1] = 'width: 100%;';
$userManagementTable->data['title_autorefresh'][0] = html_print_div(
    [
        'class'   => 'section_table_title_line',
        'content' => '',
    ],
    true
);
$userManagementTable->data['title_autorefresh'][1] = html_print_subtitle_table(__('Autorefresh'), [], true);

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
    'width:100%'
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
    'width:100%'
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
            'images/darrowright_green.png',
            true,
            [
                'id'    => 'right_autorefreshlist',
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
            'images/darrowleft_green.png',
            true,
            [
                'id'    => 'left_autorefreshlist',
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

// $userManagementTable->rowclass['captions_autorefreshList'] = 'field_half_width pdd_t_10px';
// $userManagementTable->rowclass['fields_autorefreshList'] = 'field_half_width';
$userManagementTable->data['captions_autorefreshList'] = __('Autorefresh pages');
$userManagementTable->data['fields_autorefreshList'] = $autorefreshTable;

$userManagementTable->rowclass['captions_autorefreshTime'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_autorefreshTime'] = 'field_half_width';
$userManagementTable->data['captions_autorefreshTime'][0] = __('Time for autorefresh');
$userManagementTable->data['captions_autorefreshTime'][0] .= ui_print_help_tip(
    __('Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first'),
    true
);
$userManagementTable->data['fields_autorefreshTime'][0] = html_print_select(
    get_refresh_time_array(),
    'time_autorefresh',
    $user_info['time_autorefresh'],
    '',
    '',
    '',
    true,
    false,
    false
);

// Title for Language and Appearance.
$userManagementTable->rowclass['title_lookAndFeel'] = 'w100p';
$userManagementTable->cellstyle['title_lookAndFeel'][0] = 'width: 40px;';
$userManagementTable->cellstyle['title_lookAndFeel'][1] = 'width: 100%;';
$userManagementTable->data['title_lookAndFeel'][0] = html_print_div(
    [
        'class'   => 'section_table_title_line',
        'content' => '',
    ],
    true
);
$userManagementTable->data['title_lookAndFeel'][1] = html_print_subtitle_table(__('Language and Appearance'), [], true);

$userManagementTable->rowclass['captions_lang_colorscheme'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_lang_colorscheme'] = 'field_half_width';
$userManagementTable->data['captions_lang_colorscheme'][0] = __('Language');
$userManagementTable->data['fields_lang_colorscheme'][0] = html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true
);

$userManagementTable->data['captions_lang_colorscheme'][1] = __('User color scheme');
$userManagementTable->data['fields_lang_colorscheme'][1] = skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true);

$userManagementTable->rowclass['captions_blocksize_eventfilter'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_blocksize_eventfilter'] = 'field_half_width';
$userManagementTable->data['captions_blocksize_eventfilter'][0] = __('Block size for pagination');
$userManagementTable->data['fields_blocksize_eventfilter'][0] = html_print_input_text(
    'block_size',
    $user_info['block_size'],
    '',
    5,
    5,
    true
);

$userManagementTable->data['captions_blocksize_eventfilter'][1] = __('Event filter');
$userManagementTable->data['fields_blocksize_eventfilter'][1] = html_print_select(
    $event_filter,
    'default_event_filter',
    $user_info['default_event_filter'],
    '',
    '',
    __('None'),
    true,
    false,
    false
);

// Home screen table.
$homeScreenTable = new stdClass();
$homeScreenTable->class = 'w100p table_section';
$homeScreenTable->id = 'home_screen_table';
$homeScreenTable->style = [];
$homeScreenTable->rowclass = [];
$homeScreenTable->data = [];

// Home screen.
$homeScreenTable->data['captions_homescreen'][0] = __('Home screen');
$homeScreenTable->colspan['captions_homescreen'] = 2;
$homeScreenTable->rowclass['captions_homescreen'] = 'field_half_width pdd_t_10px';
$homeScreenTable->rowclass['fields_homescreen'] = 'field_half_width';
// $homeScreenTable->rowclass['fields_homescreen'] = 'w540px';
$homeScreenTable->data['fields_homescreen'][0] = html_print_select(
    $homeScreenValues,
    'section',
    io_safe_output($user_info['section']),
    'show_data_section();',
    '',
    -1,
    true,
    false,
    false
);
$homeScreenTable->data['fields_homescreen'][1] = $customHomeScreenDataField;

$userManagementTable->rowclass['homescreen_table'] = 'table_section';
$userManagementTable->data['homescreen_table'] = html_print_table($homeScreenTable, true);

// Timezone.
$userManagementTable->rowclass['captions_timezone'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_timezone'] = 'field_half_width';
$userManagementTable->colspan['captions_timezone'][0] = 2;
$userManagementTable->data['captions_timezone'][0] = __('Time zone');
$userManagementTable->data['fields_timezone'][0] = html_print_timezone_select('timezone', $user_info['timezone']);
$userManagementTable->data['fields_timezone'][0] .= ui_print_help_tip(
    __('The timezone must be that of the associated server.'),
    true
);

$userManagementTable->data['fields_timezone'][1] = html_print_div(
    [
        'id'      => 'timezone-picker',
        'content' => implode('', $timezoneContent),
    ],
    true
);


// Title for Language and Appearance.
$userManagementTable->rowclass['title_additionalSettings'] = 'w100p';
$userManagementTable->cellstyle['title_additionalSettings'][0] = 'width: 40px;';
$userManagementTable->cellstyle['title_additionalSettings'][1] = 'width: 100%;';
$userManagementTable->data['title_additionalSettings'][0] = html_print_div(
    [
        'class'   => 'section_table_title_line',
        'content' => '',
    ],
    true
);
$userManagementTable->data['title_additionalSettings'][1] = html_print_subtitle_table(__('Additional settings'), [], true);

$userManagementTable->rowclass['captions_addSettings'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_addSettings'] = 'field_half_width';
$userManagementTable->cellstyle['fields_addSettings'][1] = 'flex-wrap: wrap';
$userManagementTable->cellstyle['captions_addSettings'][1] = 'width: 35%';
$userManagementTable->cellstyle['captions_addSettings'][2] = 'width: 15%';
$userManagementTable->cellstyle['fields_addSettings'][1] = 'width: 35%';
$userManagementTable->cellstyle['fields_addSettings'][2] = 'width: 15%';
$userManagementTable->data['captions_addSettings'][0] = __('Comments');
$userManagementTable->data['fields_addSettings'][0] = html_print_textarea(
    'comments',
    2,
    65,
    $user_info['comments'],
    ($view_mode ? 'readonly="readonly"' : ''),
    true
);

$userManagementTable->data['captions_addSettings'][1] = __('Login allowed IP list');
$userManagementTable->data['captions_addSettings'][1] .= ui_print_help_tip(
    __('Add the source IPs that will allow console access. Each IP must be separated only by comma. * allows all.'),
    true
);
$userManagementTable->data['fields_addSettings'][1] = html_print_div(
    [
        'class'   => 'edit_user_allowed_ip',
        'content' => html_print_textarea(
            'allowed_ip_list',
            2,
            65,
            $user_info['allowed_ip_list'],
            (((bool) $view_mode === true) ? 'readonly="readonly"' : ''),
            true
        ),
    ],
    true
);

$userManagementTable->data['captions_addSettings'][2] = __('Allow all IPs');
$userManagementTable->data['fields_addSettings'][2] = html_print_div(
    [
        'class'   => 'margin-top-10',
        'content' => html_print_checkbox_switch(
            'allowed_ip_active',
            0,
            $user_info['allowed_ip_active'],
            true
        ),
    ],
    true
);


$userManagementTable->rowclass['captions_loginErrorUser'] = 'field_half_width pdd_t_10px';
$userManagementTable->rowclass['fields_loginErrorUser'] = 'field_half_width';
$userManagementTable->cellstyle['captions_loginErrorUser'][0] = 'width: 25%';
$userManagementTable->cellstyle['captions_loginErrorUser'][1] = 'width: 25%';
$userManagementTable->cellstyle['fields_loginErrorUser'][0] = 'width: 25%';
$userManagementTable->cellstyle['fields_loginErrorUser'][1] = 'width: 25%';
$userManagementTable->data['captions_loginErrorUser'][0] = __('Not Login');
$userManagementTable->data['captions_loginErrorUser'][0] .= ui_print_help_tip(
    __('The user with not login set only can access to API.'),
    true
);
$userManagementTable->data['fields_loginErrorUser'][0] = html_print_checkbox_switch(
    'not_login',
    1,
    $user_info['not_login'],
    true
);

$userManagementTable->data['captions_loginErrorUser'][1] = __('Local user');
$userManagementTable->data['captions_loginErrorUser'][1] .= ui_print_help_tip(
    __('The user with local authentication enabled will always use local authentication.'),
    true
);
$userManagementTable->data['fields_loginErrorUser'][1] = html_print_checkbox_switch(
    'local_user',
    1,
    $user_info['local_user'],
    true
);

$userManagementTable->data['captions_loginErrorUser'][2] = __('Session time');
$userManagementTable->data['captions_loginErrorUser'][2] .= ui_print_help_tip(
    __('This is defined in minutes, If you wish a permanent session should putting -1 in this field.'),
    true
);
$userManagementTable->data['fields_loginErrorUser'][2] = html_print_input_text(
    'session_time',
    $user_info['session_time'],
    '',
    5,
    5,
    true.false,
    false,
    '',
    'class="input_line_small"'
);

html_print_table($userManagementTable);

// User Profile definition table.
profile_print_profile_table($id, io_safe_output($json_profile), false, ($is_err === true && $new_user === true));
