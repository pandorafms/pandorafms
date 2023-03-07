<?php
/**
 * Profiles.
 *
 * @category   Profiles
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

// Load global vars.
global $config;

check_login();

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access User Management'
    );
    include 'general/noaccess.php';
    exit;
}

enterprise_include_once('meta/include/functions_users_meta.php');

$tab = get_parameter('tab', 'profile');
$pure = get_parameter('pure', 0);

// Header.
if (is_metaconsole() === false) {
    $buttons = [
        'user'    => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image(
                'images/user.svg',
                true,
                [
                    'title' => __('User management'),
                    'class' => 'invert_filter main_menu_user',
                ]
            ).'</a>',
        ],
        'profile' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">'.html_print_image(
                'images/suitcase@svg.svg',
                true,
                [
                    'title' => __('Profile management'),
                    'class' => 'invert_filter main_menu_user',
                ]
            ).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;

    // Header.
    ui_print_standard_header(
        __('User Profile management'),
        'images/user.svg',
        false,
        'profile_tab',
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Profiles'),
            ],
            [
                'link'  => '',
                'label' => __('Manage users'),
            ],
        ]
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

$is_management_allowed = true;
if (is_metaconsole() === false && is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/users_setup&tab=profile&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All profiles information is read only. Go to %s to manage it.',
            $url
        )
    );
}

// Profile deletion.
if ($is_management_allowed === true && $delete_profile === true) {
    // Delete profile.
    $profile = db_get_row('tperfil', 'id_perfil', $id_profile);
    $ret = profile_delete_profile_and_clean_users($id_profile);
    if ($ret === false) {
        ui_print_error_message(__('There was a problem deleting the profile'));
    } else {
        db_pandora_audit(
            AUDIT_LOG_USER_MANAGEMENT,
            'Delete profile '.io_safe_output($profile['name'])
        );
        ui_print_success_message(__('Successfully deleted'));
    }

    $id_profile = 0;
}

// Store the variables when create or update.
if ($is_management_allowed === true && ($create_profile === true || $update_profile === true)) {
    $name = get_parameter('name');

    // Agents.
    $agent_view = (bool) get_parameter('agent_view');
    $agent_edit = (bool) get_parameter('agent_edit');
    $agent_disable = (bool) get_parameter('agent_disable');

    // Alerts.
    $alert_edit = (bool) get_parameter('alert_edit');
    $alert_management = (bool) get_parameter('alert_management');

    // Users.
    $user_management = (bool) get_parameter('user_management');

    // DB.
    $db_management = (bool) get_parameter('db_management');

    // Pandora.
    $pandora_management = (bool) get_parameter('pandora_management');

    // Events.
    $event_view = (bool) get_parameter('event_view');
    $event_edit = (bool) get_parameter('event_edit');
    $event_management = (bool) get_parameter('event_management');

    // Reports.
    $report_view = (bool) get_parameter('report_view');
    $report_edit = (bool) get_parameter('report_edit');
    $report_management = (bool) get_parameter('report_management');

    // Network maps.
    $map_view = (bool) get_parameter('map_view');
    $map_edit = (bool) get_parameter('map_edit');
    $map_management = (bool) get_parameter('map_management');

    // Visual console.
    $vconsole_view = (bool) get_parameter('vconsole_view');
    $vconsole_edit = (bool) get_parameter('vconsole_edit');
    $vconsole_management = (bool) get_parameter('vconsole_management');

    // NCM.
    $network_config_view = (bool) get_parameter('network_config_view');
    $network_config_edit = (bool) get_parameter('network_config_edit');
    $network_config_management = (bool) get_parameter('network_config_management');

    $values = [
        'name'                      => $name,
        'agent_view'                => $agent_view,
        'agent_edit'                => $agent_edit,
        'agent_disable'             => $agent_disable,
        'alert_edit'                => $alert_edit,
        'alert_management'          => $alert_management,
        'user_management'           => $user_management,
        'db_management'             => $db_management,
        'event_view'                => $event_view,
        'event_edit'                => $event_edit,
        'event_management'          => $event_management,
        'report_view'               => $report_view,
        'report_edit'               => $report_edit,
        'report_management'         => $report_management,
        'map_view'                  => $map_view,
        'map_edit'                  => $map_edit,
        'map_management'            => $map_management,
        'vconsole_view'             => $vconsole_view,
        'vconsole_edit'             => $vconsole_edit,
        'vconsole_management'       => $vconsole_management,
        'network_config_view'       => $network_config_view,
        'network_config_edit'       => $network_config_edit,
        'network_config_management' => $network_config_management,
        'pandora_management'        => $pandora_management,
    ];
}

// Update profile.
if ($is_management_allowed === true && $update_profile === true) {
    if (empty($name) === false) {
        $ret = db_process_sql_update('tperfil', $values, ['id_perfil' => $id_profile]);
        if ($ret !== false) {
            $info = '{"Name":"'.$name.'",
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
                "NCM view":"'.$network_config_view.'",
				"NCM edit":"'.$network_config_edit.'",
				"NCM management":"'.$network_config_management.'",
				"'.get_product_name().' Management":"'.$pandora_management.'"}';

            db_pandora_audit(
                AUDIT_LOG_USER_MANAGEMENT,
                'Update profile '.io_safe_output($name),
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

// Create profile.
if ($is_management_allowed === true && $create_profile === true) {
    if (empty($name) === false) {
        $ret = db_process_sql_insert('tperfil', $values);

        if ($ret !== false) {
            ui_print_success_message(__('Successfully created'));
            $info = '{"Name":"'.$name.'",
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
                "NCM view":"'.$network_config_view.'",
				"NCM edit":"'.$network_config_edit.'",
				"NCM management":"'.$network_config_management.'",
				"'.get_product_name().' Management":"'.$pandora_management.'"}';

            db_pandora_audit(
                AUDIT_LOG_USER_MANAGEMENT,
                'Created profile '.io_safe_output($name),
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
$table->styleTable = 'margin: 10px';
$table->class = 'info_table profile_list';

$table->head = [];
$table->data = [];
$table->size = [];
$table->align = [];

$table->head['profiles'] = __('Profiles');

$table->head['AR'] = '<span title="'.__('View Agents').'">'.'AR'.'</span>';
$table->head['AW'] = '<span title="'.__('Edit Agents').'">'.'AW'.'</span>';
$table->head['AD'] = '<span title="'.__('Disable Agents').'">'.'AD'.'</span>';
$table->head['LW'] = '<span title="'.__('Edit Alerts').'">'.'LW'.'</span>';
$table->head['LM'] = '<span title="'.__('Manage Alerts').'">'.'LM'.'</span>';
$table->head['UM'] = '<span title="'.__('User Management').'">'.'UM'.'</span>';
$table->head['DM'] = '<span title="'.__('Database Management').'">'.'DM'.'</span>';
$table->head['ER'] = '<span title="'.__('View Events').'">'.'ER'.'</span>';
$table->head['EW'] = '<span title="'.__('Edit Events').'">'.'EW'.'</span>';
$table->head['EM'] = '<span title="'.__('Manage Events').'">'.'EM'.'</span>';
$table->head['RR'] = '<span title="'.__('View Reports').'">'.'RR'.'</span>';
$table->head['RW'] = '<span title="'.__('Edit Reports').'">'.'RW'.'</span>';
$table->head['RM'] = '<span title="'.__('Manage Reports').'">'.'RM'.'</span>';
$table->head['MR'] = '<span title="'.__('View Network Maps').'">'.'MR'.'</span>';
$table->head['MW'] = '<span title="'.__('Edit Network Maps').'">'.'MW'.'</span>';
$table->head['MM'] = '<span title="'.__('Manage Network Maps').'">'.'MM'.'</span>';
$table->head['VR'] = '<span title="'.__('View Visual Consoles').'">'.'VR'.'</span>';
$table->head['VW'] = '<span title="'.__('Edit Visual Consoles').'">'.'VW'.'</span>';
$table->head['VM'] = '<span title="'.__('Manage Visual Consoles').'">'.'VM'.'</span>';
$table->head['NR'] = '<span title="'.__('View NCM Data').'">'.'NR'.'</span>';
$table->head['NW'] = '<span title="'.__('Operate NCM').'">'.'NW'.'</span>';
$table->head['NM'] = '<span title="'.__('Manage NCM').'">'.'NM'.'</span>';
$table->head['PM'] = '<span title="'.__('Pandora Administration').'">'.'PM'.'</span>';

if ($is_management_allowed === true) {
    $table->head['operations'] = '<span title="Operations">'.__('Op.').'</span>';
}

$table->align = array_fill(1, 11, 'center');

$table->size['profiles'] = '150px';
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
$table->size['NR'] = '10px';
$table->size['NW'] = '10px';
$table->size['NM'] = '10px';
$table->size['PM'] = '10px';
if ($is_management_allowed === true) {
    $table->size['operations'] = '6%';
}

$profiles = db_get_all_rows_in_table('tperfil');
if ($profiles === false) {
    $profiles = [];
}

$img = html_print_image(
    'images/validate.svg',
    true,
    [
        'border' => 0,
        'class'  => 'invert_filter main_menu_icon',
    ]
);

foreach ($profiles as $profile) {
    if ($is_management_allowed === true) {
        $data['profiles'] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'&pure='.$pure.'">';
        $data['profiles'] .= $profile['name'];
        $data['profiles'] .= '</a>';
    } else {
        $data['profiles'] = $profile['name'];
    }

    $data['AR'] = (empty($profile['agent_view']) === false) ? $img : '';
    $data['AW'] = (empty($profile['agent_edit']) === false) ? $img : '';
    $data['AD'] = (empty($profile['agent_disable']) === false) ? $img : '';
    $data['LW'] = (empty($profile['alert_edit']) === false) ? $img : '';
    $data['LM'] = (empty($profile['alert_management']) === false) ? $img : '';
    $data['UM'] = (empty($profile['user_management']) === false) ? $img : '';
    $data['DM'] = (empty($profile['db_management']) === false) ? $img : '';
    $data['ER'] = (empty($profile['event_view']) === false) ? $img : '';
    $data['EW'] = (empty($profile['event_edit']) === false) ? $img : '';
    $data['EM'] = (empty($profile['event_management']) === false) ? $img : '';
    $data['RR'] = (empty($profile['report_view']) === false) ? $img : '';
    $data['RW'] = (empty($profile['report_edit']) === false) ? $img : '';
    $data['RM'] = (empty($profile['report_management']) === false) ? $img : '';
    $data['MR'] = (empty($profile['map_view']) === false) ? $img : '';
    $data['MW'] = (empty($profile['map_edit']) === false) ? $img : '';
    $data['MM'] = (empty($profile['map_management']) === false) ? $img : '';
    $data['VR'] = (empty($profile['vconsole_view']) === false) ? $img : '';
    $data['VW'] = (empty($profile['vconsole_edit']) === false) ? $img : '';
    $data['VM'] = (empty($profile['vconsole_management']) === false) ? $img : '';
    $data['NR'] = (empty($profile['network_config_view']) === false) ? $img : '';
    $data['NW'] = (empty($profile['network_config_edit']) === false) ? $img : '';
    $data['NM'] = (empty($profile['network_config_management']) === false) ? $img : '';
    $data['PM'] = (empty($profile['pandora_management']) === false) ? $img : '';
    $table->cellclass[]['operations'] = 'table_action_buttons';
    if ($is_management_allowed === true) {
        $data['operations'] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'&pure='.$pure.'">'.html_print_image(
            'images/edit.svg',
            true,
            [
                'title' => __('Edit'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>';
        if ((bool) check_acl($config['id_user'], 0, 'PM') === true || (bool) users_is_admin() === true) {
            $data['operations'] .= html_print_anchor(
                [
                    'href'    => 'index.php?sec='.$sec.'&sec2=godmode/users/profile_list&delete_profile=1&id='.$profile['id_perfil'].'&pure='.$pure,
                    'onClick' => 'if (!confirm(\' '.__('Are you sure?').'\')) return false;',
                    'content' => html_print_image(
                        'images/delete.svg',
                        true,
                        [
                            'title' => __('Delete'),
                            'class' => 'invert_filter main_menu_icon',
                        ]
                    ),
                ],
                true
            );
        }
    }

    array_push($table->data, $data);
}

if (isset($data) === true) {
    html_print_table($table);
} else {
    echo "<div class='nf'>".__('There are no defined profiles').'</div>';
}

if ($is_management_allowed === true) {
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/users/configure_profile&pure='.$pure.'">';
    html_print_input_hidden('new_profile', 1);
    html_print_action_buttons(
        html_print_submit_button(
            __('Create profile'),
            'crt',
            false,
            [ 'icon' => 'next' ],
            true
        ),
        [
            'type'  => 'data_table',
            'class' => 'fixed_action_buttons',
        ]
    );
    echo '</form>';
}

unset($table);
