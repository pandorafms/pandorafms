<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Load the header.
require $config['homedir'].'/operation/users/user_edit_header.php';

// Update user info.
if (isset($_GET['modified']) && !$view_mode) {
    if (html_print_csrf_error()) {
        return;
    }

    $upd_info = [];
    $upd_info['fullname'] = get_parameter_post('fullname', $user_info['fullname']);
    $upd_info['firstname'] = get_parameter_post('firstname', $user_info['firstname']);
    $upd_info['lastname'] = get_parameter_post('lastname', $user_info['lastname']);
    $password_new = get_parameter_post('password_new', '');
    $password_confirm = get_parameter_post('password_conf', '');
    $upd_info['email'] = get_parameter_post('email', '');
    $upd_info['phone'] = get_parameter_post('phone', '');
    $upd_info['comments'] = get_parameter_post('comments', '');
    $upd_info['language'] = get_parameter_post('language', $user_info['language']);
    $upd_info['timezone'] = get_parameter_post('timezone', '');
    $upd_info['id_skin'] = get_parameter('skin', $user_info['id_skin']);
    $upd_info['default_event_filter'] = get_parameter('event_filter', null);
    $upd_info['block_size'] = get_parameter('block_size', $config['block_size']);
    $upd_info['firstname'] = get_parameter('newsletter_reminder', $user_info['first_name']);
    $default_block_size = get_parameter('default_block_size', 0);
    if ($default_block_size) {
        $upd_info['block_size'] = 0;
    }

    $upd_info['section'] = get_parameter('section', $user_info['section']);
    $upd_info['data_section'] = get_parameter('data_section', '');
    $dashboard = get_parameter('dashboard', '');
    $visual_console = get_parameter('visual_console', '');

    // Save autorefresh list.
    $autorefresh_list = get_parameter_post('autorefresh_list');
    if (($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')) {
        $upd_info['autorefresh_white_list'] = '';
    } else {
        $upd_info['autorefresh_white_list'] = json_encode($autorefresh_list);
    }

    $upd_info['time_autorefresh'] = (int) get_parameter('time_autorefresh', 0);

    $is_admin = db_get_value('is_admin', 'tusuario', 'id_user', $id);

    $section = io_safe_output($upd_info['section']);

    if (($section == 'Event list') || ($section == 'Group view')
        || ($section == 'Alert detail') || ($section == 'Tactical view')
        || ($section == 'Default')
    ) {
        $upd_info['data_section'] = '';
    } else if ($section == 'Dashboard') {
        $upd_info['data_section'] = $dashboard;
    } else if ($section == 'Visual console') {
        $upd_info['data_section'] = $visual_console;
    }

    if (!empty($password_new)) {
        if ($config['user_can_update_password'] && $password_confirm == $password_new) {
            if ((!$is_admin || $config['enable_pass_policy_admin'])
                && $config['enable_pass_policy']
            ) {
                $pass_ok = login_validate_pass($password_new, $id, true);
                if ($pass_ok != 1) {
                    ui_print_error_message($pass_ok);
                } else {
                    $return = update_user_password($id, $password_new);
                    if ($return) {
                        $return2 = save_pass_history($id, $password_new);
                    }
                }
            } else {
                $return = update_user_password($id, $password_new);
            }
        } else if ($password_new !== 'NON-INIT') {
            $error_msg = __('Passwords didn\'t match or other problem encountered while updating passwords');
        }
    } else if (empty($password_new) && empty($password_confirm)) {
        $return = true;
    } else if (empty($password_new) || empty($password_confirm)) {
        $return = false;
    }

    // No need to display "error" here, because when no update is needed
    // (no changes in data) SQL function returns 0 (FALSE), but is not an error,
    // just no change. Previous error message could be confussing to the user.
    if ($return) {
        if (!empty($password_new) && !empty($password_confirm)) {
            $success_msg = __('Password successfully updated');
        }

        // If info is valid then proceed with update.
        if ((filter_var($upd_info['email'], FILTER_VALIDATE_EMAIL) || $upd_info['email'] == '')
            && (preg_match('/^[0-9- ]+$/D', $upd_info['phone']) || $upd_info['phone'] == '')
        ) {
            $return_update_user = update_user($id, $upd_info);

            if ($return_update_user === false) {
                $error_msg = __('Error updating user info');
            } else if ($return_update_user == true) {
                $success_msg = __('User info successfully updated');
            } else {
                if (!empty($password_new) && !empty($password_confirm)) {
                    $success_msg = __('Password successfully updated');
                } else {
                    $return = false;
                    $error_msg = __('No changes have been made');
                }
            }

            ui_print_result_message(
                $return,
                $success_msg,
                $error_msg,
                $user_auth_error
            );
        } else if (!filter_var($upd_info['email'], FILTER_VALIDATE_EMAIL)) {
            ui_print_error_message(__('Please enter a valid email'));
        } else if (!preg_match('/^[0-9- ]+$/D', $upd_info['phone'])) {
            ui_print_error_message(__('Please enter a valid phone number'));
        }

        $user_info = $upd_info;
    } else {
        if (!$error_msg) {
            $error_msg = __('Error updating passwords: ');
        }

        $user_auth_error = $config['auth_error'];

        ui_print_result_message(
            $return,
            $success_msg,
            $error_msg,
            $user_auth_error
        );
    }
}

// Prints action status for current message.
if ($status != -1) {
    ui_print_result_message(
        $status,
        __('User info successfully updated'),
        __('Error updating user info')
    );
}

/*
    $jump = '&nbsp;&nbsp;';
    $table = new stdClass();
    $table->id = 'user_form';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'databox filters';
*/

if (defined('METACONSOLE')) {
    $table->head[0] = __('Edit my User');
    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

/*
    $table->style[0] = 'min-width: 320px;width: 320px;margin-right:0px;padding-right:0px;';
    $table->style[1] = 'min-width: 280px;width: 280px;margin-right:0px;padding-right:0px;';
    $table->style[2] = 'min-width: 150px;width: 150px;margin-right:0px;margin-left:0px;padding-left:0px;padding-right:0px;';

    $data = [];
*/

$user_id = '<div><span class="edit_user_labels">'.__('User ID').': </span>';
$user_id .= '<span>'.$id.'</span></div>';
// $full_name = '<div><span class="edit_user_labels">'.__('Full (display) name').'</span>';
// $full_name .= '<span>';
$full_name = '<div>'.html_print_input_text_extended(
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
).'</div>';

// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.
if (is_user_admin($id)) {
    $avatar = html_print_image('images/people_1.png', true, ['class' => 'user_avatar']);
} else {
    $avatar = html_print_image('images/people_2.png', true, ['class' => 'user_avatar']);
}

if ($view_mode === false) {
    $table->rowspan[0][2] = 3;
} else {
    $table->rowspan[0][2] = 2;
}

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;

    $data = [];
*/

// $email = '<div><span class="edit_user_labels">'.__('E-mail').'</span>';
$email = '<div>'.html_print_input_text_extended('email', $user_info['email'], 'email', '', '25', '100', $view_mode, '', ['class' => 'input', 'placeholder' => __('E-mail')], true).'</div>';
// $phone = '<div><span class="edit_user_labels">'.__('Phone number').'</span>';
$phone = '<div>'.html_print_input_text_extended('phone', $user_info['phone'], 'phone', '', '20', '30', $view_mode, '', ['class' => 'input', 'placeholder' => __('Phone number')], true).'</div>';

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;
*/

if ($view_mode === false) {
    if ($config['user_can_update_password']) {
        // $data = [];
        // $new_pass = '<div><span class="edit_user_labels">'.__('New Password').'</span>';
        $new_pass = '<div><span>'.html_print_input_text_extended('password_new', '', 'password_new', '', '25', '45', $view_mode, '', ['class' => 'input', 'placeholder' => __('New Password')], true, true).'</span></div>';
        // $new_pass_confirm = '<div><span class="edit_user_labels">'.__('Password confirmation').'</span>';
        $new_pass_confirm = '<div><span>'.html_print_input_text_extended('password_conf', '', 'password_conf', '', '20', '45', $view_mode, '', ['class' => 'input', 'placeholder' => __('Password confirmation')], true, true).'</span></div>';

        /*
            $table->rowclass[] = '';
            $table->rowstyle[] = 'font-weight: bold;';
            $table->data[] = $data;
        */
    } else {
        // $data = [];
        $new_pass = '<i>'.__('You cannot change your password under the current authentication scheme').'</i>';
        $new_pass_confirm = '';

        /*
            $table->rowclass[] = '';
            $table->rowstyle[] = '';
            $table->colspan[count($table - data)][0] = 2;
            $table->data[] = $data;
        */
    }
}

// $data = [];
$size_pagination = '<div><span class="edit_user_labels">'.__('Block size for pagination').ui_print_help_tip(__('If checkbox is clicked then block size global configuration is used'), true).'</span>';
if ($user_info['block_size'] == 0) {
    $block_size = $config['global_block_size'];
} else {
    $block_size = $user_info['block_size'];
}

$size_pagination .= $jump.'<span>'.html_print_input_text('block_size', $block_size, '', 5, 5, true).'</span>';
$size_pagination .= $jump.'<span>'.html_print_checkbox('default_block_size', 1, $user_info['block_size'] == 0, true).'</span>';
$size_pagination .= __('Default').' ('.$config['global_block_size'].')</div>';

$values = [
    -1 => __('Default'),
    1  => __('Yes'),
    0  => __('No'),
];


$language = '<div><span class="edit_user_labels">'.__('Language').': </span></div>';
$language .= '<div>'.html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true,
    '',
    '',
    '',
    '',
    '',
    10
).'</div>';

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;
*/

$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
$id_usr = $config['id_user'];


// $data = [];
if (!$meta) {
    $home_screen = '<div><span class="edit_user_labels">'.__('Home screen').ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type sec=estado&sec2=operation/agentes/estado_agente to show agent detail view'), true).'</span></div>';
    $values = [
        'Default'        => __('Default'),
        'Visual console' => __('Visual console'),
        'Event list'     => __('Event list'),
        'Group view'     => __('Group view'),
        'Tactical view'  => __('Tactical view'),
        'Alert detail'   => __('Alert detail'),
        'Other'          => __('Other'),
    ];
    if (enterprise_installed()) {
        $values['Dashboard'] = __('Dashboard');
    }

    $home_screen .= '<div><span>'.html_print_select($values, 'section', io_safe_output($user_info['section']), 'show_data_section();', '', -1, true, false, false).'</span></div>';

    if (enterprise_installed()) {
        $dashboards = get_user_dashboards($config['id_user']);

        $dashboards_aux = [];
        if ($dashboards === false) {
            $dashboards = ['None' => 'None'];
        } else {
            foreach ($dashboards as $key => $dashboard) {
                $dashboards_aux[$dashboard['name']] = $dashboard['name'];
            }
        }

        $home_screen .= html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true);
    }

    $layouts = visual_map_get_user_layouts($config['id_user'], true);
    $layouts_aux = [];
    if ($layouts === false) {
        $layouts_aux = ['None' => 'None'];
    } else {
        foreach ($layouts as $layout) {
            $layouts_aux[$layout] = $layout;
        }
    }

    $home_screen .= html_print_select($layouts_aux, 'visual_console', $user_info['data_section'], '', '', '', true);
    $home_screen .= html_print_input_text('data_section', $user_info['data_section'], '', 60, 255, true, false);



    // User only can change skins if has more than one group.
    $skin = '';
    if (function_exists('skins_print_select')) {
        if (count($usr_groups) > 1) {
            $skin = '<div><span class="edit_user_labels">'.__('Skin').'</span></div>';
            $skin .= $jump.'<div><span>'.skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true).'</span></div>';
        }
    }
} else {
    $home_screen = '';
    $skin = '';
}

$timezone = '<span class="edit_user_labels">'.__('Timezone').'</span>';
$timezone .= $jump.html_print_timezone_select('timezone', $user_info['timezone']);

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;
*/

// Double auth.
$double_auth_enabled = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $config['id_user']);
// $data = [];
if ($config['double_auth_enabled']) {
    $double_authentication = '<div><span class="edit_user_labels">'.__('Double authentication').'</span>';
    $double_authentication .= '<span>'.html_print_checkbox('double_auth', 1, $double_auth_enabled, true).'</span>';
}

if ($double_auth_enabled) {
    $double_authentication .= html_print_button(__('Show information'), 'show_info', false, 'javascript:show_double_auth_info();', '', true);
}

// Dialog.
$double_authentication .= '<div id="dialog-double_auth" style="display:none"><div id="dialog-double_auth-container"></div></div></div>';

if (check_acl($config['id_user'], 0, 'ER')) {
    $event_filter = '<div><span class="edit_user_labels">'.__('Event filter').'</span></div>';
    $event_filter .= '<div><span>'.html_print_select_from_sql(
        'SELECT id_filter, id_name FROM tevent_filter',
        'event_filter',
        $user_info['default_event_filter'],
        '',
        __('None'),
        null,
        true
    ).'</span></div>';
} else if (license_free()) {
    $newsletter = __('Newsletter Subscribed').':';
    if ($user_info['middlename']) {
        $newsletter .= $jump.'<span>'.__('Already subscribed to %s newsletter', get_product_name()).'</span>';
    } else {
        $newsletter .= $jump.'<span><a href="javascript: force_run_newsletter();">'.__('Subscribe to our newsletter').'</a></span>';
    }

    $newsletter_reminder = __('Newsletter Reminder').' ';
    if ($user_info['firstname'] != 0) {
        $user_info['firstname'] = 1;
    }

    $newsletter_reminder .= html_print_checkbox('newsletter_reminder', 1, $user_info['firstname'], true);
} else {
    // $table->colspan[count($table->data)][0] = 3;
}

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;
    $data = [];
*/

$autorefresh_list_out = [];
if (is_metaconsole()) {
    $autorefresh_list_out['monitoring/tactical'] = 'tactical';
    $autorefresh_list_out['monitoring/group_view'] = 'group_view';
} else {
    $autorefresh_list_out['operation/agentes/tactical'] = 'tactical';
    $autorefresh_list_out['operation/agentes/group_view'] = 'group_view';
}

$autorefresh_list_out['operation/agentes/estado_agente'] = 'agent_status';
$autorefresh_list_out['operation/agentes/alerts_status'] = 'alerts_status';
$autorefresh_list_out['operation/agentes/status_monitor'] = 'status_monitor';
$autorefresh_list_out['enterprise/operation/services/services'] = 'services';
$autorefresh_list_out['enterprise/dashboard/main_dashboard'] = 'main_dashboard';
$autorefresh_list_out['operation/reporting/graph_viewer'] = 'graph_viewer';
$autorefresh_list_out['operation/snmpconsole/snmp_view'] = 'snmp_view';
$autorefresh_list_out['operation/agentes/pandora_networkmap'] = 'networkmap';
$autorefresh_list_out['operation/visual_console/render_view'] = 'render_view';
$autorefresh_list_out['operation/events/events'] = 'events';
$autorefresh_list_out['enterprise/godmode/reporting/cluster_view'] = 'cluster_view';

if (!isset($autorefresh_list)) {
    $select = db_process_sql("SELECT autorefresh_white_list FROM tusuario WHERE id_user = '".$config['id_user']."'");
    $autorefresh_list = json_decode($select[0]['autorefresh_white_list']);
    if ($autorefresh_list === null) {
        $autorefresh_list[0] = __('None');
    } else {
        $aux = [];
        $count_autorefresh_list = count($autorefresh_list);
        for ($i = 0; $i < $count_autorefresh_list; $i++) {
            $aux[$autorefresh_list[$i]] = $autorefresh_list_out[$autorefresh_list[$i]];
            unset($autorefresh_list_out[$autorefresh_list[$i]]);
            $autorefresh_list[$i] = $aux;
        }

        $autorefresh_list = $aux;
    }
} else {
    if (($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')) {
        $autorefresh_list[0] = __('None');
    } else {
        $aux = [];
        $count_autorefresh_list = count($autorefresh_list);
        for ($i = 0; $i < $count_autorefresh_list; $i++) {
            $aux[$autorefresh_list[$i]] = $autorefresh_list_out[$autorefresh_list[$i]];
            unset($autorefresh_list_out[$autorefresh_list[$i]]);
            $autorefresh_list[$i] = $aux;
        }

        $autorefresh_list = $aux;
    }
}

$autorefresh_show = '<div><span class="edit_user_labels">'._('Autorefresh').ui_print_help_tip(
    __('This will activate autorefresh in selected pages'),
    true
).'</span></div>';
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
    'width:200px'
);
$arrows = ' ';
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
    'width:200px'
);

$table_ichanges = '<div class="autorefresh_select">
		<div class="1">
            <span class="autorefresh_select_text">'.__('Full list of pages').': </span>
            <div>'.$select_out.'</div>
		</div>
		<div class="2">
            <a href="javascript:">'.html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'right_autorefreshlist',
        'alt'   => __('Push selected pages into autorefresh list'),
        'title' => __('Push selected pages into autorefresh list'),
    ]
).'</a>
            <br/>
            <a href="javascript:">'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'left_autorefreshlist',
        'alt'   => __('Pop selected pages out of autorefresh list'),
        'title' => __('Pop selected pages out of autorefresh list'),
    ]
).'</a>
        </div>    
        <div class="3">    
			<span class="autorefresh_select_text">'.__('List of pages with autorefresh').': </span>   
			<div>'.$select_in.'</div>
		</div>
	</div>';
$autorefresh_show .= $table_ichanges;

// Time autorefresh.
$time_autorefresh = get_refresh_time_array();
$time_autorefresh = '<div><span class="edit_user_labels">'.__('Time autorefresh');
$time_autorefresh .= ui_print_help_tip(
    __('Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first'),
    true
).'</span></div>';
$time_autorefresh .= '<div><span>';
$time_autorefresh .= html_print_select(
    $times,
    'time_autorefresh',
    $user_info['time_autorefresh'],
    '',
    '',
    '',
    true,
    false,
    false
).'</span></div>';

/*
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;vertical-align: top';
    $table->data[] = $data;

    $data = [];
*/

$comments = '<span class="edit_user_labels">'.__('Comments').'</span>';

/*
    $table->colspan[count($table->data)][0] = 3;
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data[] = $data;

    $data = [];
*/

$comments .= html_print_textarea(
    'comments',
    2,
    60,
    $user_info['comments'],
    (($view_mode) ? 'readonly="readonly"' : ''),
    true
);
$comments .= html_print_input_hidden('quick_language_change', 1, true);

/*
    $table->colspan[count($table->data)][0] = 3;
    $table->rowclass[] = '';
    $table->rowstyle[] = '';
    $table->data[] = $data;
*/

echo '<form name="user_mod" method="post" action="'.ui_get_full_url().'&amp;modified=1&amp;id='.$id.'&amp;pure='.$config['pure'].'">';

echo '<div id="user_form">
    <div class="user_edit_first_row">
        <div class="edit_user_info">
            <div class="edit_user_info_left">'.$avatar.$user_id.'</div>
            <div class="edit_user_info_right">'.$full_name.$email.$phone.$new_pass.$new_pass_confirm.'</div>
        </div>  
        <div class="edit_user_autorefresh">'.$autorefresh_show.$time_autorefresh.'</div>
    </div> 
    <div class="user_edit_second_row">
        <div class="edit_user_options">'.$language.$size_pagination.$skin.$event_filter.$home_screen.$double_authentication.$newsletter.$newsletter_reminder.'</div>
        <div class="edit_user_timezone">'.$timezone.'</div>
    </div> 
    <div class="user_edit_third_row">
        <div class="edit_user_comments">'.$comments.'</div>
    </div>    
</div>';

/*
    Borrar: html_print_table($table);
*/

echo '<div class="edit_user_button">';
if (!$config['user_can_update_info']) {
    echo '<i>'.__('You can not change your user info under the current authentication scheme').'</i>';
} else {
    html_print_csrf_hidden();
    html_print_submit_button(__('Update'), 'uptbutton', $view_mode, 'class="sub upd"');
}

echo '</div></form>';

/*
    Borrar: unset($table);
*/

if (!defined('METACONSOLE')) {
    echo '<h4>'.__('Profiles/Groups assigned to this user').'</h4>';
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox data';
if (defined('METACONSOLE')) {
    $table->width = '100%';
    $table->class = 'databox data';
    $table->title = __('Profiles/Groups assigned to this user');
    $table->head_colspan[0] = 0;
    $table->headstyle[] = 'background-color: #82B93C';
    $table->headstyle[] = 'background-color: #82B93C';
    $table->headstyle[] = 'background-color: #82B93C';
}

$table->data = [];
$table->head = [];
$table->align = [];
$table->style = [];

if (!defined('METACONSOLE')) {
    $table->style[0] = 'font-weight: bold';
    $table->style[1] = 'font-weight: bold';
}

$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Tags');
$table->align = [];
$table->align[1] = 'left';

$table->data = [];

$result = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $id);
if ($result === false) {
    $result = [];
}

foreach ($result as $profile) {
    $data[0] = '<b>'.profile_get_name($profile['id_perfil']).'</b>';
    if ($config['show_group_name']) {
        $data[1] = ui_print_group_icon(
            $profile['id_grupo'],
            true
        ).'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">&nbsp;</a>';
    } else {
        $data[1] = ui_print_group_icon(
            $profile['id_grupo'],
            true
        ).'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">&nbsp;'.ui_print_truncate_text(groups_get_name($profile['id_grupo'], true), GENERIC_SIZE_TEXT).'</a>';
    }

    $tags_ids = explode(',', $profile['tags']);
    $tags = tags_get_tags($tags_ids);

    $data[2] = tags_get_tags_formatted($tags);

    array_push($table->data, $data);
}

if (!empty($table->data)) {
    html_print_table($table);
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('This user doesn\'t have any assigned profile/group.') ]);
}

enterprise_hook('close_meta_frame');

?>

<script language="javascript" type="text/javascript">
$(document).ready (function () {

    $("#right_autorefreshlist").click (function () {
        jQuery.each($("select[name='autorefresh_list_out[]'] option:selected"), function (key, value) {
            imodule_name = $(value).html();
            if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                id_imodule = $(value).attr('value');
                $("select[name='autorefresh_list[]']").append($("<option></option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                $("#autorefresh_list_out").find("option[value='" + id_imodule + "']").remove();
                $("#autorefresh_list").find("option[value='']").remove();
                $("#autorefresh_list").find("option[value='0']").remove();
                if($("#autorefresh_list_out option").length == 0) {
                    $("select[name='autorefresh_list_out[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                }
            }
        });
    });

    $("#left_autorefreshlist").click (function () {
        jQuery.each($("select[name='autorefresh_list[]'] option:selected"), function (key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("#autorefresh_list").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list_out").find("option[value='']").remove();
                    $("select[name='autorefresh_list_out[]']").append($("<option><option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                    $("#autorefresh_list_out option").last().remove();
                    if($("#autorefresh_list option").length == 0) {
                        $("select[name='autorefresh_list[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
        });
    });

    $("#submit-uptbutton").click (function () {
        if($("#autorefresh_list option").length > 0) {
            $('#autorefresh_list option').prop('selected', true);
        }
    });

    check_default_block_size()
    $("#checkbox-default_block_size").change(function() {
        check_default_block_size();
    });
    
    function check_default_block_size() {
        if ($("#checkbox-default_block_size").is(':checked')) {
            $("#text-block_size").attr('disabled', true);
        }
        else {
            $("#text-block_size").removeAttr('disabled');
        }
    }

    $("input#checkbox-double_auth").change(function (e) {
        e.preventDefault();

        if (this.checked) {
            show_double_auth_activation();
        }
        else {
            show_double_auth_deactivation();
        }
    });
    
    show_data_section();
});

function show_data_section () {
    section = $("#section").val();
    
    switch (section) {
        case <?php echo "'".'Dashboard'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Visual console'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "");
            break;
        case <?php echo "'".'Event list'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Group view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Tactical view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Alert detail'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Other'."'"; ?>:
            $("#text-data_section").css("display", "");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Default'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
    }
}

function show_double_auth_info () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    $dialogContainer.html($loadingSpinner);

    // Load the info page
    var request = $.ajax({
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        type: 'POST',
        dataType: 'html',
        data: {
            page: 'include/ajax/double_auth.ajax',
            id_user: userID,
            get_double_auth_data_page: 1,
            containerID: $dialogContainer.prop('id')
        },
        complete: function(xhr, textStatus) {
            
        },
        success: function(data, textStatus, xhr) {
            // isNaN = is not a number
            if (isNaN(data)) {
                $dialogContainer.html(data);
            }
            // data is a number, convert it to integer to do the compare
            else if (Number(data) === -1) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
            }
            else {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
        }
    });

    $("div#dialog-double_auth")
        .css('display','block')
        .append($dialogContainer)
        .dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double autentication information'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 400,
            height: 375,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();
            }
        })
        .show();

}

function show_double_auth_activation () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    $dialogContainer.html($loadingSpinner);

    // Load the info page
    var request = $.ajax({
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        type: 'POST',
        dataType: 'html',
        data: {
            page: 'include/ajax/double_auth.ajax',
            id_user: userID,
            get_double_auth_info_page: 1,
            containerID: $dialogContainer.prop('id')
        },
        complete: function(xhr, textStatus) {
            
        },
        success: function(data, textStatus, xhr) {
            // isNaN = is not a number
            if (isNaN(data)) {
                $dialogContainer.html(data);
            }
            // data is a number, convert it to integer to do the compare
            else if (Number(data) === -1) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
            }
            else {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
        }
    });

    $("div#dialog-double_auth").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double autentication activation'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 500,
            height: 400,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();

                document.location.reload();
            }
        })
        .show();
}

function show_double_auth_deactivation () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    var message = "<p><?php echo __('Are you sure?').'<br>'.__('The double authentication will be deactivated'); ?></p>";
    var $button = $("<input type=\"button\" value=\"<?php echo __('Deactivate'); ?>\" />");

    $dialogContainer
        .empty()
        .append(message)
        .append($button);

    var request;

    $button.click(function(e) {
        e.preventDefault();

        $dialogContainer.html($loadingSpinner);

        // Deactivate the double auth
        request = $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: userID,
                deactivate_double_auth: 1
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                if (data === -1) {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else if (data) {
                    $dialogContainer.html("<?php echo '<b><div class=\"green\">'.__('The double autentication was deactivated successfully').'</div></b>'; ?>");
                }
                else {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error deactivating the double autentication').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error deactivating the double autentication').'</div></b>'; ?>");
            }
        });
    });
    

    $("div#dialog-double_auth").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double autentication activation'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 300,
            height: 150,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();

                document.location.reload();
            }
        })
        .show();
}
</script>
