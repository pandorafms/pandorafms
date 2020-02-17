<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

enterprise_hook('open_meta_frame');

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access User Management'
    );
    include 'general/noaccess.php';
    exit;
}

enterprise_include_once('meta/include/functions_users_meta.php');

$tab = get_parameter('tab', 'profile');
$pure = get_parameter('pure', 0);

// Header
if (!defined('METACONSOLE')) {
    $buttons = [
        'user'    => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image('images/gm_users.png', true, ['title' => __('User management')]).'</a>',
        ],
        'profile' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">'.html_print_image('images/profiles.png', true, ['title' => __('Profile management')]).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;

    ui_print_page_header(
        __('User management').' &raquo; '.__('Profiles defined on %s', get_product_name()),
        'images/gm_users.png',
        false,
        'profile_tab',
        true,
        $buttons
    );
    $sec = 'gusuarios';
} else {
    user_meta_print_header();
    $sec = 'advanced';
}


$delete_profile = (bool) get_parameter('delete_profile');
$create_profile = (bool) get_parameter('create_profile');
$update_profile = (bool) get_parameter('update_profile');
$id_profile = (int) get_parameter('id');

// Profile deletion
if ($delete_profile) {
    // Delete profile
    $profile = db_get_row('tperfil', 'id_perfil', $id_profile);
    $ret = profile_delete_profile_and_clean_users($id_profile);
    if ($ret === false) {
        ui_print_error_message(__('There was a problem deleting the profile'));
    } else {
        db_pandora_audit(
            'Profile management',
            'Delete profile '.$profile['name']
        );
        ui_print_success_message(__('Successfully deleted'));
    }

    $id_profile = 0;
}

// Store the variables when create or update
if ($create_profile || $update_profile) {
    $name = get_parameter('name');

    // Incidents
    $incident_view = (bool) get_parameter('incident_view');
    $incident_edit = (bool) get_parameter('incident_edit');
    $incident_management = (bool) get_parameter('incident_management');

    // Agents
    $agent_view = (bool) get_parameter('agent_view');
    $agent_edit = (bool) get_parameter('agent_edit');
    $agent_disable = (bool) get_parameter('agent_disable');

    // Alerts
    $alert_edit = (bool) get_parameter('alert_edit');
    $alert_management = (bool) get_parameter('alert_management');

    // Users
    $user_management = (bool) get_parameter('user_management');

    // DB
    $db_management = (bool) get_parameter('db_management');

    // Pandora
    $pandora_management = (bool) get_parameter('pandora_management');

    // Events
    $event_view = (bool) get_parameter('event_view');
    $event_edit = (bool) get_parameter('event_edit');
    $event_management = (bool) get_parameter('event_management');

    // Reports
    $report_view = (bool) get_parameter('report_view');
    $report_edit = (bool) get_parameter('report_edit');
    $report_management = (bool) get_parameter('report_management');

    // Network maps
    $map_view = (bool) get_parameter('map_view');
    $map_edit = (bool) get_parameter('map_edit');
    $map_management = (bool) get_parameter('map_management');

    // Visual console
    $vconsole_view = (bool) get_parameter('vconsole_view');
    $vconsole_edit = (bool) get_parameter('vconsole_edit');
    $vconsole_management = (bool) get_parameter('vconsole_management');

    $values = [
        'name'                => $name,
        'incident_view'       => $incident_view,
        'incident_edit'       => $incident_edit,
        'incident_management' => $incident_management,
        'agent_view'          => $agent_view,
        'agent_edit'          => $agent_edit,
        'agent_disable'       => $agent_disable,
        'alert_edit'          => $alert_edit,
        'alert_management'    => $alert_management,
        'user_management'     => $user_management,
        'db_management'       => $db_management,
        'event_view'          => $event_view,
        'event_edit'          => $event_edit,
        'event_management'    => $event_management,
        'report_view'         => $report_view,
        'report_edit'         => $report_edit,
        'report_management'   => $report_management,
        'map_view'            => $map_view,
        'map_edit'            => $map_edit,
        'map_management'      => $map_management,
        'vconsole_view'       => $vconsole_view,
        'vconsole_edit'       => $vconsole_edit,
        'vconsole_management' => $vconsole_management,
        'pandora_management'  => $pandora_management,
    ];
}

// Update profile
if ($update_profile) {
    if ($name) {
        $ret = db_process_sql_update('tperfil', $values, ['id_perfil' => $id_profile]);
        if ($ret !== false) {
            $info = '{"Name":"'.$incident_view.'",
				"Incident view":"'.$incident_view.'",
				"Incident edit":"'.$incident_edit.'",
				"Incident management":"'.$incident_management.'",
				"Agent view":"'.$agent_view.'",
				"Agent edit":"'.$agent_edit.'",
				"Agent disable":"'.$agent_disable.'",
				"Alert edit":"'.$alert_edit.'",
				"Alert management":"'.$alert_management.'",
				"User management":"'.$user_management.'",
				"DB management":"'.$db_management.'",
				"Event view":"'.$event_view.'",
				"Event edit":"'.$event_edit.'",
				"Event management":"'.$event_management.'",
				"Report view":"'.$report_view.'",
				"Report edit":"'.$report_edit.'",
				"Report management":"'.$report_management.'",
				"Network map view":"'.$map_view.'",
				"Network map edit":"'.$map_edit.'",
				"Network map management":"'.$map_management.'",
				"Visual console view":"'.$vconsole_view.'",
				"Visual console edit":"'.$vconsole_edit.'",
				"Visual console management":"'.$vconsole_management.'",
				"'.get_product_name().' Management":"'.$pandora_management.'"}';

            db_pandora_audit(
                'User management',
                'Update profile '.$name,
                false,
                false,
                $info
            );

            ui_print_success_message(__('Successfully updated'));
        } else {
            ui_print_error_message(__('There was a problem updating this profile'));
        }
    } else {
        ui_print_error_message(__('Profile name cannot be empty'));
    }

    $id_profile = 0;
}

// Create profile
if ($create_profile) {
    if ($name) {
        $ret = db_process_sql_insert('tperfil', $values);

        if ($ret !== false) {
            ui_print_success_message(__('Successfully created'));
            $info = '{"Name":"'.$incident_view.'",
				"Incident view":"'.$incident_view.'",
				"Incident edit":"'.$incident_edit.'",
				"Incident management":"'.$incident_management.'",
				"Agent view":"'.$agent_view.'",
				"Agent edit":"'.$agent_edit.'",
				"Agent disable":"'.$agent_disable.'",
				"Alert edit":"'.$alert_edit.'",
				"Alert management":"'.$alert_management.'",
				"User management":"'.$user_management.'",
				"DB management":"'.$db_management.'",
				"Event view":"'.$event_view.'",
				"Event edit":"'.$event_edit.'",
				"Event management":"'.$event_management.'",
				"Report view":"'.$report_view.'",
				"Report edit":"'.$report_edit.'",
				"Report management":"'.$report_management.'",
				"Network map view":"'.$map_view.'",
				"Network map edit":"'.$map_edit.'",
				"Network map management":"'.$map_management.'",
				"Visual console view":"'.$vconsole_view.'",
				"Visual console edit":"'.$vconsole_edit.'",
				"Visual console management":"'.$vconsole_management.'",
				"'.get_product_name().' Management":"'.$pandora_management.'"}';

            db_pandora_audit(
                'User management',
                'Created profile '.$name,
                false,
                false,
                $info
            );
        } else {
            ui_print_error_message(__('There was a problem creating this profile'));
        }
    } else {
        ui_print_error_message(__('There was a problem creating this profile'));
    }

    $id_profile = 0;
}

$table = new stdClass();
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->class = 'info_table profile_list';
$table->width = '100%';

$table->head = [];
$table->data = [];
$table->size = [];
$table->align = [];

$table->head['profiles'] = __('Profiles');

$table->head['IR'] = 'IR'.ui_print_help_tip(__('System incidents reading'), true);
$table->head['IW'] = 'IW'.ui_print_help_tip(__('System incidents writing'), true);
$table->head['IM'] = 'IM'.ui_print_help_tip(__('System incidents management'), true);
$table->head['AR'] = 'AR'.ui_print_help_tip(__('Agents reading'), true);
$table->head['AW'] = 'AW'.ui_print_help_tip(__('Agents management'), true);
$table->head['AD'] = 'AD'.ui_print_help_tip(__('Agents disable'), true);
$table->head['LW'] = 'LW'.ui_print_help_tip(__('Alerts editing'), true);
$table->head['LM'] = 'LM'.ui_print_help_tip(__('Alerts management'), true);
$table->head['UM'] = 'UM'.ui_print_help_tip(__('Users management'), true);
$table->head['DM'] = 'DM'.ui_print_help_tip(__('Database management'), true);
$table->head['ER'] = 'ER'.ui_print_help_tip(__('Events reading'), true);
$table->head['EW'] = 'EW'.ui_print_help_tip(__('Events writing'), true);
$table->head['EM'] = 'EM'.ui_print_help_tip(__('Events management'), true);
$table->head['RR'] = 'RR'.ui_print_help_tip(__('Reports reading'), true);
$table->head['RW'] = 'RW'.ui_print_help_tip(__('Reports writing'), true);
$table->head['RM'] = 'RM'.ui_print_help_tip(__('Reports management'), true);
$table->head['MR'] = 'MR'.ui_print_help_tip(__('Network maps reading'), true);
$table->head['MW'] = 'MW'.ui_print_help_tip(__('Network maps writing'), true);
$table->head['MM'] = 'MM'.ui_print_help_tip(__('Network maps management'), true);
$table->head['VR'] = 'VR'.ui_print_help_tip(__('Visual console reading'), true);
$table->head['VW'] = 'VW'.ui_print_help_tip(__('Visual console writing'), true);
$table->head['VM'] = 'VM'.ui_print_help_tip(__('Visual console management'), true);
$table->head['PM'] = 'PM'.ui_print_help_tip(__('Systems management'), true);
$table->head['operations'] = '<span title="Operations">'.__('Op.').'</span>';

$table->align = array_fill(1, 11, 'center');

$table->size['profiles'] = '200px';
$table->size['IR'] = '10px';
$table->size['IW'] = '10px';
$table->size['IM'] = '10px';
$table->size['AR'] = '10px';
$table->size['AW'] = '10px';
$table->size['AD'] = '10px';
$table->size['LW'] = '10px';
$table->size['LM'] = '10px';
$table->size['UM'] = '10px';
$table->size['DM'] = '10px';
$table->size['ER'] = '10px';
$table->size['EW'] = '10px';
$table->size['EM'] = '10px';
$table->size['RR'] = '10px';
$table->size['RW'] = '10px';
$table->size['RM'] = '10px';
$table->size['MR'] = '10px';
$table->size['MW'] = '10px';
$table->size['MM'] = '10px';
$table->size['VR'] = '10px';
$table->size['VW'] = '10px';
$table->size['VM'] = '10px';
$table->size['PM'] = '10px';
$table->size['operations'] = '5%';

$profiles = db_get_all_rows_in_table('tperfil');
if ($profiles === false) {
    $profiles = [];
}

$img = html_print_image('images/ok.png', true, ['border' => 0]);

foreach ($profiles as $profile) {
    $data['profiles'] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'&pure='.$pure.'">'.$profile['name'].'</a>';
    $data['IR'] = ($profile['incident_view'] ? $img : '');
    $data['IW'] = ($profile['incident_edit'] ? $img : '');
    $data['IM'] = ($profile['incident_management'] ? $img : '');
    $data['AR'] = ($profile['agent_view'] ? $img : '');
    $data['AW'] = ($profile['agent_edit'] ? $img : '');
    $data['AD'] = ($profile['agent_disable'] ? $img : '');
    $data['LW'] = ($profile['alert_edit'] ? $img : '');
    $data['LM'] = ($profile['alert_management'] ? $img : '');
    $data['UM'] = ($profile['user_management'] ? $img : '');
    $data['DM'] = ($profile['db_management'] ? $img : '');
    $data['ER'] = ($profile['event_view'] ? $img : '');
    $data['EW'] = ($profile['event_edit'] ? $img : '');
    $data['EM'] = ($profile['event_management'] ? $img : '');
    $data['RR'] = ($profile['report_view'] ? $img : '');
    $data['RW'] = ($profile['report_edit'] ? $img : '');
    $data['RM'] = ($profile['report_management'] ? $img : '');
    $data['MR'] = ($profile['map_view'] ? $img : '');
    $data['MW'] = ($profile['map_edit'] ? $img : '');
    $data['MM'] = ($profile['map_management'] ? $img : '');
    $data['VR'] = ($profile['vconsole_view'] ? $img : '');
    $data['VW'] = ($profile['vconsole_edit'] ? $img : '');
    $data['VM'] = ($profile['vconsole_management'] ? $img : '');
    $data['PM'] = ($profile['pandora_management'] ? $img : '');
    $table->cellclass[]['operations'] = 'action_buttons';
    $data['operations'] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'&pure='.$pure.'">'.html_print_image('images/config.png', true, ['title' => __('Edit')]).'</a>';
    if (check_acl($config['id_user'], 0, 'PM') || users_is_admin()) {
        $data['operations'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/users/profile_list&delete_profile=1&id='.$profile['id_perfil'].'&pure='.$pure.'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true).'</a>';
    }

    array_push($table->data, $data);
}

if (isset($data)) {
    html_print_table($table);
} else {
    echo "<div class='nf'>".__('There are no defined profiles').'</div>';
}

echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/users/configure_profile&pure='.$pure.'">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden('new_profile', 1);
html_print_submit_button(__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';
unset($table);

enterprise_hook('close_meta_frame');
