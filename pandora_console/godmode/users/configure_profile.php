<?php
/**
 * * Configure profiles.
 *
 * @category   Profiles
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Global variables.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Profile Management'
    );
    include 'general/noaccess.php';
    return;
}

enterprise_include_once('meta/include/functions_users_meta.php');
// Get parameters.
$tab         = get_parameter('tab', 'profile');
$pure        = get_parameter('pure', 0);
$new_profile = (bool) get_parameter('new_profile');
$id_profile  = (int) get_parameter('id');
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
                    'class' => 'invert_filter main_menu_icon',
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
                    'class' => 'invert_filter main_menu_icon',
                ]
            ).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;

    $profile = db_get_row('tperfil', 'id_perfil', $id_profile);

    ui_print_standard_header(
        __('Edit profile %s', $profile['name']),
        'images/user.svg',
        false,
        'configure_profiles_tab',
        true,
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
            [
                'link'  => ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile'),
                'label' => __('User Profile management'),
            ],
        ]
    );
    $sec2 = 'gusuarios';
} else {
    user_meta_print_header();
    $sec2 = 'advanced';
}

// Edit profile.
if ($id_profile || $new_profile) {
    if ($new_profile) {
        // Name.
        $name = '';

        // Agents.
        $agent_view = 0;
        $agent_edit = 0;
        $agent_disable = 0;

        // Alerts.
        $alert_edit = 0;
        $alert_management = 0;

        // Users.
        $user_management = 0;

        // DB.
        $db_management = 0;

        // Pandora.
        $pandora_management = 0;

        // Events.
        $event_view = 0;
        $event_edit = 0;
        $event_management = 0;

        // Reports.
        $report_view = 0;
        $report_edit = 0;
        $report_management = 0;

        // Network maps.
        $map_view = 0;
        $map_edit = 0;
        $map_management = 0;

        // Visual console.
        $vconsole_view = 0;
        $vconsole_edit = 0;
        $vconsole_management = 0;

        // NCM.
        $network_config_view = 0;
        $network_config_edit = 0;
        $network_config_management = 0;

        $page_title = __('Create profile');
    } else {
        $profile = db_get_row('tperfil', 'id_perfil', $id_profile);

        if ($profile === false) {
            ui_print_error_message(__('There was a problem loading profile')).'</table>';
            echo '</div>';
            echo '<div id="both">&nbsp;</div>';
            echo '</div>';
            echo '<div id="foot">';
            // include 'general/footer.php';
            echo '</div>';
            echo '</div>';

            exit;
        }

        // Name.
        $name = $profile['name'];

        // Agents.
        $agent_view = (bool) $profile['agent_view'];
        $agent_edit = (bool) $profile['agent_edit'];
        $agent_disable = (bool) $profile['agent_disable'];

        // Alerts.
        $alert_edit = (bool) $profile['alert_edit'];
        $alert_management = (bool) $profile['alert_management'];

        // Users.
        $user_management = (bool) $profile['user_management'];

        // DB.
        $db_management = (bool) $profile['db_management'];

        // Pandora.
        $pandora_management = (bool) $profile['pandora_management'];

        // Events.
        $event_view = (bool) $profile['event_view'];
        $event_edit = (bool) $profile['event_edit'];
        $event_management = (bool) $profile['event_management'];

        // Reports.
        $report_view = (bool) $profile['report_view'];
        $report_edit = (bool) $profile['report_edit'];
        $report_management = (bool) $profile['report_management'];

        // Network maps.
        $map_view = (bool) $profile['map_view'];
        $map_edit = (bool) $profile['map_edit'];
        $map_management = (bool) $profile['map_management'];

        // Visual console.
        $vconsole_view = (bool) $profile['vconsole_view'];
        $vconsole_edit = (bool) $profile['vconsole_edit'];
        $vconsole_management = (bool) $profile['vconsole_management'];

        // NCM.
        $network_config_management = (bool) $profile['network_config_management'];
        $network_config_view = (bool) $profile['network_config_view'] || $network_config_management;
        $network_config_edit = (bool) $profile['network_config_edit'] || $network_config_management;

        $id_audit = db_pandora_audit(
            AUDIT_LOG_USER_MANAGEMENT,
            'Edit profile '.io_safe_output($name)
        );
        enterprise_include_once('include/functions_audit.php');

        $info = 'Name: '.$name;
        $info .= ' Agent view: '.$agent_view;
        $info .= ' Agent edit: '.$agent_edit;
        $info .= ' Agent disable: '.$agent_disable;
        $info .= ' Alert edit: '.$alert_edit;
        $info .= ' Alert management: '.$alert_management;
        $info .= ' User management: '.$user_management;
        $info .= ' DB management: '.$db_management;
        $info .= ' Event view: '.$event_view;
        $info .= ' Event edit: '.$event_edit;
        $info .= ' Event management: '.$event_management;
        $info .= ' Report view: '.$report_view;
        $info .= ' Report edit: '.$report_edit;
        $info .= ' Report management: '.$report_management;
        $info .= ' Network map view: '.$map_view;
        $info .= ' Network map edit: '.$map_edit;
        $info .= ' Network map management: '.$map_management;
        $info .= ' Visual console view: '.$vconsole_view;
        $info .= ' Visual console edit: '.$vconsole_edit;
        $info .= ' Visual console management: '.$vconsole_management;
        $info .= ' Network config view: '.$network_config_view;
        $info .= ' Network config write: '.$network_config_write;
        $info .= ' Network config management: '.$network_config_management;
        $info .= ' '.get_product_name().' Management: '.$pandora_management;

        enterprise_hook('audit_pandora_enterprise', [$id_audit, $info]);


        $page_title = __('Update profile');
    }

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox filters';
    if (is_metaconsole()) {
        $table->width = '100%';
        $table->class = 'databox data';
        if ($id_profile) {
            $table->head[0] = __('Update Profile');
        } else {
            $table->head[0] = __('Create Profile');
        }

        $table->head_colspan[0] = 4;
        $table->headstyle[0] = 'text-align: center';
    }

    $table->size = [];
    $table->style = [];
    $table->style[0] = 'font-weight: bold';
    $table->data = [];

    // Name.
    $row = [];
    $row['name'] = __('Profile name');
    $row['input'] = html_print_input_text('name', $name, '', 30, 60, true);
    $table->data['name'] = $row;
    $table->data[] = '<hr>';

    // Agents.
    $row = [];
    $row['name'] = __('View agents');
    $row['input'] = html_print_checkbox('agent_view', 1, $agent_view, true);
    $table->data['AR'] = $row;
    $row = [];
    $row['name'] = __('Disable agents');
    $row['input'] = html_print_checkbox('agent_disable', 1, $agent_disable, true);
    $table->data['AD'] = $row;
    $row = [];
    $row['name'] = __('Edit agents');
    $row['input'] = html_print_checkbox('agent_edit', 1, $agent_edit, true, false, 'autoclick_profile_users(\'agent_edit\',\'agent_view\', \'agent_disable\')');
    $table->data['AW'] = $row;
    $table->data[] = '<hr>';

    // Alerts.
    $row = [];
    $row['name'] = __('Edit alerts');
    $row['input'] = html_print_checkbox('alert_edit', 1, $alert_edit, true);
    $table->data['LW'] = $row;
    $row = [];
    $row['name'] = __('Manage alerts');
    $row['input'] = html_print_checkbox('alert_management', 1, $alert_management, true, false, 'autoclick_profile_users(\'alert_management\', \'alert_edit\', \'false\')');
    $table->data['LM'] = $row;
    $table->data[] = '<hr>';

    // Events.
    $row = [];
    $row['name'] = __('View events');
    $row['input'] = html_print_checkbox('event_view', 1, $event_view, true);
    $table->data['ER'] = $row;
    $row = [];
    $row['name'] = __('Edit events');
    $row['input'] = html_print_checkbox('event_edit', 1, $event_edit, true, false, 'autoclick_profile_users(\'event_edit\', \'event_view\', \'false\')');
    $table->data['EW'] = $row;
    $row = [];
    $row['name'] = __('Manage events');
    $row['input'] = html_print_checkbox('event_management', 1, $event_management, true, false, 'autoclick_profile_users(\'event_management\', \'event_view\', \'event_edit\')');
    $table->data['EM'] = $row;
    $table->data[] = '<hr>';

    // Reports.
    $row = [];
    $row['name'] = __('View reports');
    $row['input'] = html_print_checkbox('report_view', 1, $report_view, true);
    $table->data['RR'] = $row;
    $row = [];
    $row['name'] = __('Edit reports');
    $row['input'] = html_print_checkbox('report_edit', 1, $report_edit, true, false, 'autoclick_profile_users(\'report_edit\', \'report_view\', \'false\')');
    $table->data['RW'] = $row;
    $row = [];
    $row['name'] = __('Manage reports');
    $row['input'] = html_print_checkbox('report_management', 1, $report_management, true, false, 'autoclick_profile_users(\'report_management\', \'report_view\', \'report_edit\')');
    $table->data['RM'] = $row;
    $table->data[] = '<hr>';

    // Network maps.
    $row = [];
    $row['name'] = __('View network maps');
    $row['input'] = html_print_checkbox('map_view', 1, $map_view, true);
    $table->data['MR'] = $row;
    $row = [];
    $row['name'] = __('Edit network maps');
    $row['input'] = html_print_checkbox('map_edit', 1, $map_edit, true, false, 'autoclick_profile_users(\'map_edit\', \'map_view\', \'false\')');
    $table->data['MW'] = $row;
    $row = [];
    $row['name'] = __('Manage network maps');
    $row['input'] = html_print_checkbox('map_management', 1, $map_management, true, false, 'autoclick_profile_users(\'map_management\', \'map_view\', \'map_edit\')');
    $table->data['MM'] = $row;
    $table->data[] = '<hr>';

    // Visual console.
    $row = [];
    $row['name'] = __('View visual console');
    $row['input'] = html_print_checkbox('vconsole_view', 1, $vconsole_view, true);
    $table->data['VR'] = $row;
    $row = [];
    $row['name'] = __('Edit visual console');
    $row['input'] = html_print_checkbox('vconsole_edit', 1, $vconsole_edit, true, false, 'autoclick_profile_users(\'vconsole_edit\', \'vconsole_view\', \'false\')');
    $table->data['VW'] = $row;
    $row = [];
    $row['name'] = __('Manage visual console');
    $row['input'] = html_print_checkbox('vconsole_management', 1, $vconsole_management, true, false, 'autoclick_profile_users(\'vconsole_management\', \'vconsole_view\', \'vconsole_edit\')');
    $table->data['VM'] = $row;
    $table->data[] = '<hr>';

    $disable_option = 'javascript: return false;';
    if (check_acl($config['id_user'], 0, 'PM') || users_is_admin()) {
        $disable_option = '';
    }

    // NCM.
    $row = [];
    $row['name'] = __('View NCM data');
    $row['input'] = html_print_checkbox('network_config_view', 1, $network_config_view, true);
    $table->data['NR'] = $row;
    $row = [];
    $row['name'] = __('Operate NCM');
    $row['input'] = html_print_checkbox('network_config_edit', 1, $network_config_edit, true, false, 'autoclick_profile_users(\'network_config_edit\', \'network_config_view\', \'false\')');
    $table->data['NW'] = $row;
    $row = [];
    $row['name'] = __('Manage NCM');
    $row['input'] = html_print_checkbox('network_config_management', 1, $network_config_management, true, false, 'autoclick_profile_users(\'network_config_management\', \'network_config_view\', \'network_config_edit\')');
    $table->data['NM'] = $row;
    $table->data[] = '<hr>';

    // Users.
    $row = [];
    $row['name'] = __('Manage users');
    $row['input'] = html_print_checkbox('user_management', 1, $user_management, true, false, $disable_option);
    $table->data['UM'] = $row;
    $table->data[] = '<hr>';

    // DB.
    $row = [];
    $row['name'] = __('Manage database');
    $row['input'] = html_print_checkbox('db_management', 1, $db_management, true, false, $disable_option);
    $table->data['DM'] = $row;
    $table->data[] = '<hr>';

    // Pandora.
    $row = [];
    $row['name'] = __('%s management', get_product_name());
    $row['input'] = html_print_checkbox('pandora_management', 1, $pandora_management, true, false, $disable_option);
    $table->data['PM'] = $row;
    $table->data[] = '<hr>';

    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/users/profile_list&pure='.$pure.'">';

    html_print_table($table);

    $actionButtons = [];

    if ($new_profile === true) {
        $actionButtons[] = html_print_submit_button(__('Create profile'), 'crt', false, [ 'icon' => 'wand' ], true);
        html_print_input_hidden('create_profile', 1);
    } else {
        $actionButtons[] = html_print_submit_button(__('Update'), 'upd', false, [ 'icon' => 'update' ], true);
        html_print_input_hidden('id', $id_profile);
        html_print_input_hidden('old_name_profile', $name);
        html_print_input_hidden('update_profile', 1);
    }

    $actionButtons[] = html_print_go_back_button(
        ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure=0'),
        ['button_class' => ''],
        true
    );

    html_print_action_buttons(
        implode('', $actionButtons),
        ['type' => 'form_action']
    );

    echo '</form>';
}

?>

<script type="text/javascript" language="javascript">
    $(document).ready (function () {
        var disable_option = '<?php echo $disable_option; ?>';

        if (disable_option != '') {
            var ids = ['#checkbox-db_management', '#checkbox-user_management', '#checkbox-pandora_management'];
            ids.forEach(id => {
                $(id).css({'cursor':'not-allowed', 'opacity':'0.5'});
            });
        }

        //Not enable enter for prevent submits
        $(window).keydown(function(event){
            if(event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    });

    $('#text-name').on('blur',function(){
        /* Check if the name is already on use for new profiles or check if the
        name is already on use for update checking if the name is distinct of the original*/
        if($('#hidden-create_profile').val()==1 || ($('#hidden-update_profile').val()==1 && $('#hidden-old_name_profile').val()!=$('#text-name').val())){
            $.ajax({
                type: "POST",
                url: "ajax.php",
                dataType: "html",
                data: {
                    page: 'include/ajax/profile',
                    search_profile_nanme: true,
                    profile_name: $('#text-name').val().trim(),
                },
                success: function (data) {
                    if(data === 'true'){
                        alert( <?php echo "'".__('Profile name already on use, please, change the name before save')."'"; ?> );
                        if($('#hidden-old_name_profile').val()){
                            $('#text-name').val($('#hidden-old_name_profile').val());
                        }else{
                            $('#text-name').val("");
                        }
                    }
                },
                error: function (data) {
                    console.error("Fatal error in AJAX call to interpreter order", data)
                }
            });
        }
    });
</script>
