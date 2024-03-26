<?php

/**
 * Godmode menu.
 *
 * @category   Menu
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

// Begin.
require_once 'include/config.php';
require_once 'include/functions_menu.php';
require_once $config['homedir'].'/godmode/wizards/ManageExtensions.class.php';

check_login();

$access_console_node = is_reporting_console_node() === false;
$menu_godmode = [];
$menu_godmode['class'] = 'godmode';

$menuGodmode = [];
if ($access_console_node === true) {
    enterprise_include('godmode/menu.php');
}

if ((bool) check_acl($config['id_user'], 0, 'AR') === true
    || (bool) check_acl($config['id_user'], 0, 'AW') === true
    || (bool) check_acl($config['id_user'], 0, 'RR') === true
    || (bool) check_acl($config['id_user'], 0, 'RW') === true
    || (bool) check_acl($config['id_user'], 0, 'PM') === true
) {
    $sub = [];
    $sub['godmode/servers/discovery&wiz=main']['text'] = __('Start');
    $sub['godmode/servers/discovery&wiz=main']['id'] = 'discovery';

    $sub['godmode/servers/discovery&wiz=tasklist']['text'] = __('Task list');
    $sub['godmode/servers/discovery&wiz=tasklist']['id'] = 'tasklist';

    if ($access_console_node === true) {
        if ((bool) check_acl($config['id_user'], 0, 'AW') === true
            || (bool) check_acl($config['id_user'], 0, 'PM') === true
        ) {
            if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
                $sub2 = [];
                $sub2['godmode/servers/discovery&wiz=hd&mode=netscan']['text'] = __('Network scan');
                enterprise_hook('hostdevices_submenu');
                $sub2['godmode/servers/discovery&wiz=hd&mode=customnetscan']['text'] = __('Custom network scan');
            }

            if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
                $sub2['godmode/servers/discovery&wiz=hd&mode=managenetscanscripts']['text'] = __('Manage scan scripts');
            }

            $sub['godmode/servers/discovery&wiz=hd']['text'] = __('Host & Devices');
            $sub['godmode/servers/discovery&wiz=hd']['id'] = 'hd';
            $sub['godmode/servers/discovery&wiz=hd']['type'] = 'direct';
            $sub['godmode/servers/discovery&wiz=hd']['subtype'] = 'nolink';
            $sub['godmode/servers/discovery&wiz=hd']['sub2'] = $sub2;
        }

        if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
            // Applications.
            $sub2 = [];
            $extensions = ManageExtensions::getExtensionBySection('app');
            if ($extensions !== false) {
                foreach ($extensions as $key => $extension) {
                    $url = sprintf(
                        'godmode/servers/discovery&wiz=app&mode=%s',
                        $extension['short_name']
                    );
                    $sub2[$url]['text'] = __($extension['name']);
                }
            }

            if ($extensions !== false || enterprise_installed() === true) {
                $sub['godmode/servers/discovery&wiz=app']['text'] = __('Applications');
                $sub['godmode/servers/discovery&wiz=app']['id'] = 'app';
                $sub['godmode/servers/discovery&wiz=app']['type'] = 'direct';
                $sub['godmode/servers/discovery&wiz=app']['subtype'] = 'nolink';
                $sub['godmode/servers/discovery&wiz=app']['sub2'] = $sub2;
            }

            // Cloud.
            $sub2 = [];
            $extensions = ManageExtensions::getExtensionBySection('cloud');
            if ($extensions !== false) {
                foreach ($extensions as $key => $extension) {
                    $url = sprintf(
                        'godmode/servers/discovery&wiz=cloud&mode=%s',
                        $extension['short_name']
                    );
                    $sub2[$url]['text'] = __($extension['name']);
                }
            }

            if ($extensions !== false || enterprise_installed() === true) {
                $sub['godmode/servers/discovery&wiz=cloud']['text'] = __('Cloud');
                $sub['godmode/servers/discovery&wiz=cloud']['id'] = 'cloud';
                $sub['godmode/servers/discovery&wiz=cloud']['type'] = 'direct';
                $sub['godmode/servers/discovery&wiz=cloud']['subtype'] = 'nolink';
                $sub['godmode/servers/discovery&wiz=cloud']['sub2'] = $sub2;
            }

            // Custom.
            $sub2 = [];
            $extensions = ManageExtensions::getExtensionBySection('custom');
            if ($extensions !== false) {
                foreach ($extensions as $key => $extension) {
                    $url = sprintf(
                        'godmode/servers/discovery&wiz=custom&mode=%s',
                        $extension['short_name']
                    );
                    $sub2[$url]['text'] = __($extension['name']);
                }

                $sub['godmode/servers/discovery&wiz=custom']['text'] = __('Custom');
                $sub['godmode/servers/discovery&wiz=custom']['id'] = 'customExt';
                $sub['godmode/servers/discovery&wiz=custom']['type'] = 'direct';
                $sub['godmode/servers/discovery&wiz=custom']['subtype'] = 'nolink';
                $sub['godmode/servers/discovery&wiz=custom']['sub2'] = $sub2;
            }

            if (check_acl($config['id_user'], 0, 'RW')
                || check_acl($config['id_user'], 0, 'RM')
                || check_acl($config['id_user'], 0, 'PM')
            ) {
                $sub['godmode/servers/discovery&wiz=magextensions']['text'] = __('Manage disco packages');
                $sub['godmode/servers/discovery&wiz=magextensions']['id'] = 'mextensions';
            }

            if ((bool) check_acl($config['id_user'], 0, 'RW') === true
                || (bool) check_acl($config['id_user'], 0, 'RM') === true
                || (bool) check_acl($config['id_user'], 0, 'PM') === true
            ) {
                enterprise_hook('console_task_menu');
            }
        }
    }

    // Add to menu.
    $menu_godmode['discovery']['text'] = __('Discovery');
    $menu_godmode['discovery']['sec2'] = '';
    $menu_godmode['discovery']['id'] = 'god-discovery';
    $menu_godmode['discovery']['sub'] = $sub;
}

if ($access_console_node === true) {
    $sub = [];
    if ((bool) check_acl($config['id_user'], 0, 'AW') === true || (bool) check_acl($config['id_user'], 0, 'AD') === true) {
        $sub['godmode/agentes/modificar_agente']['text'] = __('Manage agents');
        $sub['godmode/agentes/modificar_agente']['id'] = 'Manage_agents';
        $sub['godmode/agentes/modificar_agente']['subsecs'] = ['godmode/agentes/configurar_agente'];
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['godmode/agentes/fields_manager']['text'] = __('Custom fields');
        $sub['godmode/agentes/fields_manager']['id'] = 'custom_fields';

        $sub['godmode/modules/manage_nc_groups']['text'] = __('Component groups');
        $sub['godmode/modules/manage_nc_groups']['id'] = 'component_groups';
        // Category.
        $sub['godmode/category/category']['text'] = __('Module categories');
        $sub['godmode/category/category']['id'] = 'module_categories';
        $sub['godmode/category/category']['subsecs'] = 'godmode/category/edit_category';

        $sub['godmode/modules/module_list']['text'] = __('Module types');
        $sub['godmode/modules/module_list']['id'] = 'module_types';

        $sub['godmode/groups/modu_group_list']['text'] = __('Module groups');
        $sub['godmode/groups/modu_group_list']['id'] = 'module_groups';

        $sub['godmode/setup/os']['text'] = __('Operating systems');
        $sub['godmode/setup/os']['id'] = 'edit_OS';

        $sub['godmode/resources/resources_export_import']['text'] = __('Resources export/import');
        $sub['godmode/resources/resources_export_import']['id'] = 'resources_export_import';
    }

    if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
        // Netflow.
        if ((bool) $config['activate_netflow'] === true) {
            $sub['godmode/netflow/nf_edit']['text'] = __('Netflow filters');
            $sub['godmode/netflow/nf_edit']['id'] = 'netflow_filters';
        }
    }

    if (empty($sub) === false) {
        $menu_godmode['gagente']['text'] = __('Resources');
        $menu_godmode['gagente']['sec2'] = 'godmode/agentes/modificar_agente';
        $menu_godmode['gagente']['id'] = 'god-resources';
        $menu_godmode['gagente']['sub'] = $sub;
    }

    $sub = [];
    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['godmode/groups/group_list']['text'] = __('Manage agents groups');
        $sub['godmode/groups/group_list']['id'] = 'manage_agents_groups';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        // Tag.
        $sub['godmode/tag/tag']['text'] = __('Module tags');
        $sub['godmode/tag/tag']['id'] = 'module_tags';
        $sub['godmode/tag/tag']['subsecs'] = 'godmode/tag/edit_tag';

        enterprise_hook('enterprise_acl_submenu');
    }

    if ((bool) check_acl($config['id_user'], 0, 'UM') === true) {
        $sub['godmode/users/user_list']['text'] = __('Users management');
        $sub['godmode/users/user_list']['id'] = 'Users_management';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['godmode/users/profile_list']['text'] = __('Profile management');
        $sub['godmode/users/profile_list']['id'] = 'Profile_management';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['godmode/users/token_list']['text'] = __('Token management');
        $sub['godmode/users/token_list']['id'] = 'token_management';
    }

    if (empty($sub) === false) {
        $menu_godmode['gusuarios']['sub'] = $sub;
        $menu_godmode['gusuarios']['text'] = __('Profiles');
        $menu_godmode['gusuarios']['sec2'] = 'godmode/users/user_list';
        $menu_godmode['gusuarios']['id'] = 'god-users';
    }

    $sub = [];
    if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
        $sub['wizard']['text'] = __('Configuration wizard');
        $sub['wizard']['id'] = 'conf_wizard';
        $sub['wizard']['type'] = 'direct';
        $sub['wizard']['subtype'] = 'nolink_no_arrow';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['templates']['text'] = __('Templates');
        $sub['templates']['id'] = 'Templates';
        $sub['templates']['type'] = 'direct';
        $sub['templates']['subtype'] = 'nolink';
        $sub2 = [];
        $sub2['godmode/modules/manage_module_templates']['text'] = __('Module templates');
        $sub2['godmode/modules/manage_module_templates']['id'] = 'module_templates';
        $sub2['godmode/modules/private_enterprise_numbers']['text'] = __('Private Enterprise Numbers');
        enterprise_hook('local_components_menu');
        $sub2['godmode/modules/private_enterprise_numbers']['id'] = 'private_Enterprise_Numbers';
        $sub2['godmode/modules/manage_network_components']['text'] = __('Remote components');
        $sub2['godmode/modules/manage_network_components']['id'] = 'network_components';
        $sub['templates']['sub2'] = $sub2;

        $sub['godmode/modules/manage_inventory_modules']['text'] = __('Inventory modules');
        $sub['godmode/modules/manage_inventory_modules']['id'] = 'Inventory_modules';

        enterprise_hook('autoconfiguration_menu');
        enterprise_hook('agent_repository_menu');
    }

    if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
        enterprise_hook('policies_menu');
        enterprise_hook('agents_submenu');
    }

    if ((bool) check_acl($config['id_user'], 0, 'NW') === true) {
        enterprise_hook('agents_ncm_submenu');
    }

    if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
        $sub['gmassive']['text'] = __('Bulk operations');
        $sub['gmassive']['id'] = 'Bulk_operations';
        $sub['gmassive']['type'] = 'direct';
        $sub['gmassive']['subtype'] = 'nolink';
        $sub2 = [];
        $sub2['godmode/massive/massive_operations&tab=massive_agents']['text'] = __('Agents operations');
        $sub2['godmode/massive/massive_operations&tab=massive_modules']['text'] = __('Modules operations');
        $sub2['godmode/massive/massive_operations&tab=massive_plugins']['text'] = __('Plugins operations');
        if ((bool) check_acl($config['id_user'], 0, 'UM') === true) {
            $sub2['godmode/massive/massive_operations&tab=massive_users']['text'] = __('Users operations');
        }

        $sub2['godmode/massive/massive_operations&tab=massive_alerts']['text'] = __('Alerts operations');
        $sub2['godmode/massive/massive_operations&tab=massive_policies_alerts']['text'] = __('Policies alerts');
        $sub2['godmode/massive/massive_operations&tab=massive_policies_alerts_external']['text'] = __('Policies External alerts');
        enterprise_hook('massivepolicies_submenu');
        enterprise_hook('massivesnmp_submenu');
        enterprise_hook('massivesatellite_submenu');

        $sub['gmassive']['sub2'] = $sub2;
        $sub2 = [];
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true || (bool) check_acl($config['id_user'], 0, 'UM') === true) {
        $sub['godmode/groups/group_list&tab=credbox']['text'] = __('Credential store');
        $sub['godmode/groups/group_list&tab=credbox']['id'] = 'credential_store';
    }

    // Manage events.
    $sub2 = [];
    if ((bool) check_acl($config['id_user'], 0, 'EW') === true || (bool) check_acl($config['id_user'], 0, 'EM') === true) {
        // Custom event fields.
        $sub2['godmode/events/events&section=filter']['text'] = __('Event filters');
        $sub2['godmode/events/events&section=filter']['id'] = 'event_filters';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub2['godmode/events/events&section=fields']['text'] = __('Custom columns');
        $sub2['godmode/events/events&section=fields']['id'] = 'Custom_events';
        $sub2['godmode/events/events&section=responses']['text'] = __('Event responses');
        $sub2['godmode/events/events&section=responses']['id'] = 'Event_responses';
    }

    if (empty($sub2) === false) {
        $sub['geventos']['text'] = __('Events');
        $sub['geventos']['id'] = 'events';
        $sub['geventos']['sec2'] = 'godmode/events/events&section=filter';
        $sub['geventos']['type'] = 'direct';
        $sub['geventos']['subtype'] = 'nolink';
        $sub['geventos']['sub2'] = $sub2;
    }

    if (empty($sub) === false) {
        $menu_godmode['gmodules']['text'] = __('Configuration');
        $menu_godmode['gmodules']['sec2'] = 'godmode/modules/manage_network_templates';
        $menu_godmode['gmodules']['id'] = 'god-configuration';
        $menu_godmode['gmodules']['sub'] = $sub;
    }

    if ((bool) check_acl($config['id_user'], 0, 'LW') === true
        || (bool) check_acl($config['id_user'], 0, 'LM') === true
    ) {
        $menu_godmode['galertas']['text'] = __('Alerts');
        $menu_godmode['galertas']['sec2'] = 'godmode/alerts/alert_list';
        $menu_godmode['galertas']['id'] = 'god-alerts';

        $sub = [];
        $sub['godmode/alerts/alert_list']['text'] = __('List of Alerts');
        $sub['godmode/alerts/alert_list']['id'] = 'List_of_Alerts';
        $sub['godmode/alerts/alert_list']['pages'] = ['godmode/alerts/alert_view'];
        $sub['godmode/agentes/planned_downtime.list']['text'] = __('Scheduled downtime');
        $sub['godmode/agentes/planned_downtime.list']['id'] = 'scheduled_downtime';

        if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
            $sub['godmode/alerts/alert_templates']['text'] = __('Templates');
            $sub['godmode/alerts/alert_templates']['id'] = 'templates';
            $sub['godmode/alerts/alert_templates']['pages'] = ['godmode/alerts/configure_alert_template'];

            $sub['godmode/alerts/alert_actions']['text'] = __('Actions');
            $sub['godmode/alerts/alert_actions']['id'] = 'Actions';
            $sub['godmode/alerts/alert_actions']['pages'] = ['godmode/alerts/configure_alert_action'];
            $sub['godmode/alerts/alert_commands']['text'] = __('Commands');
            $sub['godmode/alerts/alert_commands']['id'] = 'Commands';
            $sub['godmode/alerts/alert_commands']['pages'] = ['godmode/alerts/configure_alert_command'];
            $sub['godmode/alerts/alert_special_days']['text'] = __('Special days list');
            $sub['godmode/alerts/alert_special_days']['id'] = 'Special_days_list';
            $sub['godmode/alerts/alert_special_days']['pages'] = ['godmode/alerts/configure_alert_special_days'];

            enterprise_hook('eventalerts_submenu');
            enterprise_hook('alert_log_submenu');
            $sub['godmode/snmpconsole/snmp_alert']['text'] = __('SNMP alerts');
            $sub['godmode/snmpconsole/snmp_alert']['id'] = 'SNMP_alerts';
            enterprise_hook('alert_inventory_submenu');
        }

        $menu_godmode['galertas']['sub'] = $sub;
    }

    if ((bool) check_acl($config['id_user'], 0, 'AW') === true || (bool) check_acl($config['id_user'], 0, 'PM') === true) {
        // Servers.
        $menu_godmode['gservers']['text'] = __('Servers');
        $menu_godmode['gservers']['sec2'] = 'godmode/servers/modificar_server';
        $menu_godmode['gservers']['id'] = 'god-servers';

        $sub = [];

        if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
            $sub['godmode/servers/modificar_server']['text'] = __('Manage servers');
            $sub['godmode/servers/modificar_server']['id'] = 'Manage_servers';
        }

        if ((bool) check_acl($config['id_user'], 0, 'PM') === true
            || is_user_admin($config['id_user']) === true
        ) {
            $sub['godmode/consoles/consoles']['text'] = __('Manage consoles');
            $sub['godmode/consoles/consoles']['id'] = 'Manage consoles';
        }

        // This subtabs are only for Pandora Admin.
        if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
            enterprise_hook('ha_cluster');

            $sub['godmode/servers/plugin']['text'] = __('Plugins');
            $sub['godmode/servers/plugin']['id'] = 'Plugins';

            $sub['godmode/servers/plugin_registration']['text'] = __('Register Plugin');
            $sub['godmode/servers/plugin_registration']['id'] = 'register_plugin';

            enterprise_hook('export_target_submenu');

            enterprise_hook('manage_satellite_submenu');
        }

        $menu_godmode['gservers']['sub'] = $sub;
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        // Setup.
        $menu_godmode['gsetup']['text'] = __('Setup');
        $menu_godmode['gsetup']['sec2'] = 'general';
        $menu_godmode['gsetup']['id'] = 'god-setup';

        $sub = [];

        // Options Setup.
        $sub['general']['text'] = __('Setup');
        $sub['general']['id'] = 'Setup';
        $sub['general']['type'] = 'direct';
        $sub['general']['subtype'] = 'nolink';
        $sub2 = [];

        $sub2['godmode/setup/setup&section=general']['text'] = __('General Setup');
        $sub2['godmode/setup/setup&section=general']['id'] = 'general_Setup';
        $sub2['godmode/setup/setup&section=general']['refr'] = 0;

        enterprise_hook('password_submenu');
        enterprise_hook('enterprise_submenu');
        enterprise_hook('historydb_submenu');
        enterprise_hook('log_collector_submenu');

        $sub2['godmode/setup/setup&section=auth']['text'] = __('Authentication');
        $sub2['godmode/setup/setup&section=auth']['refr'] = 0;

        $sub2['godmode/setup/setup&section=perf']['text'] = __('Performance');
        $sub2['godmode/setup/setup&section=perf']['refr'] = 0;

        $sub2['godmode/setup/setup&section=vis']['text'] = __('Visual styles');
        $sub2['godmode/setup/setup&section=vis']['refr'] = 0;

        if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
            if ((bool) $config['activate_netflow'] === true) {
                $sub2['godmode/setup/setup&section=net']['text'] = __('Netflow');
                $sub2['godmode/setup/setup&section=net']['refr'] = 0;
            }

            if ((bool) $config['activate_sflow'] === true) {
                $sub2['godmode/setup/setup&section=sflow']['text'] = __('Sflow');
                $sub2['godmode/setup/setup&section=sflow']['refr'] = 0;
            }
        }

        $sub2['godmode/setup/setup&section=pandorarc']['text'] = __('Pandora RC');
        $sub2['godmode/setup/setup&section=pandorarc']['refr'] = 0;

        $sub2['godmode/setup/setup&section=ITSM']['text'] = __('ITSM');
        $sub2['godmode/setup/setup&section=ITSM']['refr'] = 0;

        enterprise_hook('module_library_submenu');

        $sub2['godmode/setup/setup&section=notifications']['text'] = __('Notifications');
        $sub2['godmode/setup/setup&section=notifications']['refr'] = 0;

        $sub2['godmode/setup/setup&section=quickshell']['text'] = __('QuickShell');
        $sub2['godmode/setup/setup&section=quickshell']['refr'] = 0;

        $sub2['godmode/setup/setup&section=external_tools']['text'] = __('External Tools');
        $sub2['godmode/setup/setup&section=external_tools']['refr'] = 0;

        $sub2['godmode/setup/setup&section=welcome_tips']['text'] = __('Welcome Tips');
        $sub2['godmode/setup/setup&section=welcome_tips']['refr'] = 0;

        $sub2['godmode/setup/setup&section=demo_data']['text'] = __('Demo data');
        $sub2['godmode/setup/setup&section=demo_data']['refr'] = 0;

        if ((bool) $config['activate_gis'] === true) {
            $sub2['godmode/setup/setup&section=gis']['text'] = __('Map conections GIS');
        }

        $sub['general']['sub2'] = $sub2;
        $sub['godmode/setup/license']['text'] = __('License');
        $sub['godmode/setup/license']['id'] = 'license';

        enterprise_hook('translate_string_submenu');

        $menu_godmode['gsetup']['sub'] = $sub;
    }
}


if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
    $show_ipam = false;
    $ipam = db_get_all_rows_sql('SELECT users_operator FROM tipam_network');
    if ($ipam !== false) {
        foreach ($ipam as $row) {
            if (str_contains($row['users_operator'], '-1') || str_contains($row['users_operator'], $config['id_user'])) {
                $show_ipam = true;
                break;
            }
        }
    }
}

if ((bool) check_acl($config['id_user'], 0, 'PM') === true || (bool) check_acl($config['id_user'], 0, 'DM') === true || $show_ipam === true) {
    $menu_godmode['gextensions']['text'] = __('Admin tools');
    $menu_godmode['gextensions']['sec2'] = 'godmode/extensions';
    $menu_godmode['gextensions']['id'] = 'god-extensions';

    $sub = [];

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        if ($access_console_node === true) {
            // Audit //meter en extensiones.
            $sub['godmode/audit_log']['text'] = __('System audit log');
            $sub['godmode/audit_log']['id'] = 'system_audit_log';
            $sub['godmode/setup/links']['text'] = __('Links');
            $sub['godmode/setup/links']['id'] = 'links';
            $sub['tools/diagnostics']['text'] = __('Diagnostic info');
            $sub['tools/diagnostics']['id'] = 'diagnostic_info';
            enterprise_hook('omnishell');
            $sub['godmode/setup/news']['text'] = __('Site news');
            $sub['godmode/setup/news']['id'] = 'site_news';
        }

        $sub['godmode/setup/file_manager']['text'] = __('File manager');
        $sub['godmode/setup/file_manager']['id'] = 'file_manager';

        if ($access_console_node === true) {
            if (is_user_admin($config['id_user']) === true) {
                $sub['extensions/db_status']['text'] = __('DB Schema Check');
                $sub['extensions/db_status']['id'] = 'DB_Schema_Check';
                $sub['extensions/db_status']['sec'] = 'gextensions';
                $sub['extensions/dbmanager']['text'] = __('DB Interface');
                $sub['extensions/dbmanager']['id'] = 'DB_Interface';
                $sub['extensions/dbmanager']['sec'] = 'gextensions';
                enterprise_hook('dbBackupManager');
                enterprise_hook('elasticsearch_interface_menu');
            }
        }
    }

    if (((bool) check_acl($config['id_user'], 0, 'PM') === true && $access_console_node === true) || $show_ipam === true) {
        enterprise_hook('ipam_submenu');
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true || (bool) check_acl($config['id_user'], 0, 'DM') === true) {
        $sub['godmode/events/configuration_sounds']['text'] = __('Acoustic console setup');
        $sub['godmode/events/configuration_sounds']['id'] = 'Acoustic console setup';
        $sub['godmode/events/configuration_sounds']['pages'] = ['godmode/events/configuration_sounds'];
    }

    $menu_godmode['gextensions']['sub'] = $sub;
}

if ($access_console_node === true) {
    if (is_array($config['extensions']) === true) {
        $sub = [];
        $sub2 = [];

        foreach ($config['extensions'] as $extension) {
            // If no godmode_menu is a operation extension.
            if (empty($extension['godmode_menu']) === true) {
                continue;
            }

            if ($extension['godmode_menu']['name'] === 'System Info') {
                continue;
            }

            $extmenu = [];
            if ($extension['godmode_menu']['name'] !== __('DB Schema check') && $extension['godmode_menu']['name'] !== __('DB interface')) {
                $extmenu = $extension['godmode_menu'];
            }

            // Check the ACL for this user.
            if ((bool) check_acl($config['id_user'], 0, ($extmenu['acl'] ?? '')) === false) {
                continue;
            }

            // Check if was displayed inside other menu.
            if (empty($extension['godmode_menu']['fatherId']) === true) {
                $sub2[$extmenu['sec2']]['text'] = __($extmenu['name']);
                $sub2[$extmenu['sec2']]['id'] = str_replace(' ', '_', $extmenu['name']);
                $sub2[$extmenu['sec2']]['refr'] = 0;
            } else {
                if (is_array($extmenu) === true && array_key_exists('fatherId', $extmenu) === true) {
                    if (empty($extmenu['fatherId']) === false
                        && strlen($extmenu['fatherId']) > 0
                    ) {
                        if (array_key_exists('subfatherId', $extmenu) === true) {
                            if (empty($extmenu['subfatherId']) === false
                                && strlen($extmenu['subfatherId']) > 0
                            ) {
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['id'] = str_replace(' ', '_', $extmenu['name']);
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['refr'] = 0;
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['sec'] = 'extensions';
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['extension'] = true;
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                                $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                            } else {
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['id'] = str_replace(' ', '_', $extmenu['name']);
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['refr'] = 0;
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['sec'] = $extmenu['fatherId'];
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['extension'] = true;
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                                $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                            }
                        } else {
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['id'] = str_replace(' ', '_', $extmenu['name']);
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['refr'] = 0;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['sec'] = 'gextensions';
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['extension'] = true;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                            $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                        }
                    }
                }
            }
        }

        // Complete the submenu.
        if (users_is_admin($config['id_user']) === true) {
            $extension_view = [];
            $extension_view['godmode/extensions']['id'] = 'extension_manager_view';
            $extension_view['godmode/extensions']['text'] = __('Extension manager view');
            $extension_submenu = array_merge($extension_view, $sub2);

            $sub['godmode/extensions']['sub2'] = $extension_submenu;
            $sub['godmode/extensions']['text'] = __('Extension manager');
            $sub['godmode/extensions']['id'] = 'extension_manager';
            $sub['godmode/extensions']['type'] = 'direct';
            $sub['godmode/extensions']['subtype'] = 'nolink';
        }

        if (is_array($menu_godmode['gextensions']['sub']) === true) {
            $submenu = array_merge($menu_godmode['gextensions']['sub'], $sub);
            if ($menu_godmode['gextensions']['sub'] != null) {
                $menu_godmode['gextensions']['sub'] = $submenu;
            }
        }
    }

    /*
        $menu_godmode['links']['text'] = __('Links');
        $menu_godmode['links']['sec2'] = '';
        $menu_godmode['links']['id'] = 'god-links';

        $sub = [];
        $rows = db_get_all_rows_in_table('tlink', 'name');
        foreach ($rows as $row) {
        // Audit //meter en extensiones.
        $sub[$row['link']]['text'] = $row['name'];
        $sub[$row['link']]['id'] = $row['name'];
        $sub[$row['link']]['type'] = 'direct';
        $sub[$row['link']]['subtype'] = 'new_blank';
        }

        $menu_godmode['links']['sub'] = $sub;
    */
}

// Warp Manager.
if ((bool) check_acl($config['id_user'], 0, 'PM') === true && (bool) $config['enable_update_manager'] === true) {
    $menu_godmode['messages']['text'] = __('Warp Update');
    $menu_godmode['messages']['id'] = 'god-um_messages';
    $menu_godmode['messages']['sec2'] = '';

    $sub = [];
    $sub['godmode/update_manager/update_manager&tab=offline']['text'] = __('Update offline');
    $sub['godmode/update_manager/update_manager&tab=offline']['id'] = 'Offline';

    $sub['godmode/update_manager/update_manager&tab=online']['text'] = __('Update online');
    $sub['godmode/update_manager/update_manager&tab=online']['id'] = 'Online';

    $sub['godmode/update_manager/update_manager&tab=setup']['text'] = __('Options');
    $sub['godmode/update_manager/update_manager&tab=setup']['id'] = 'Options';

    $sub['godmode/update_manager/update_manager&tab=history']['text'] = __('Warp journal');
    $sub['godmode/update_manager/update_manager&tab=history']['id'] = 'Journal';

    $menu_godmode['messages']['sub'] = $sub;
}

if ($access_console_node === true) {
    // Module library.
    if ((bool) check_acl($config['id_user'], 0, 'AR') === true) {
        $menu_godmode['gmodule_library']['text'] = __('Module library');
        $menu_godmode['gmodule_library']['id'] = 'god-module_library';

        $sub = [];
        $sub['godmode/module_library/module_library_view']['text'] = __('View');
        $sub['godmode/module_library/module_library_view']['id'] = 'View';

        $sub['godmode/module_library/module_library_view&tab=categories']['text'] = __('Categories');
        $sub['godmode/module_library/module_library_view&tab=categories']['id'] = 'categories';

        $menu_godmode['gmodule_library']['sub'] = $sub;
    }
}

if ($access_console_node === true) {
    // Tools.
    $menu_godmode['tools']['text'] = __('Tools');
    $menu_godmode['tools']['sec2'] = 'operation/extensions';
    $menu_godmode['tools']['id'] = 'oper-extensions';
    $sub = [];

    if (check_acl($config['id_user'], 0, 'RR')
        || check_acl($config['id_user'], 0, 'RW')
        || check_acl($config['id_user'], 0, 'RM')
    ) {
        $sub['operation/agentes/exportdata']['text'] = __('Export data');
        $sub['operation/agentes/exportdata']['id'] = 'export_data';
    }

    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
        $sub['godmode/files_repo/files_repo']['text'] = __('File repository');
        $sub['godmode/files_repo/files_repo']['id'] = 'file_repository';
    }

    $menu_godmode['tools']['sub'] = $sub;

    // About.
    $menu_godmode['about']['text'] = __('About');
    $menu_godmode['about']['id'] = 'about';
}

if ((bool) $config['pure'] === false) {
    menu_print_menu($menu_godmode);
}

echo '<div id="about-div"></div>';
// Need to be here because the translate string.
if (check_acl($config['id_user'], 0, 'AW')) {
    ?>
<script type="text/javascript">
$("#conf_wizard").click(function() {
    $("#conf_wizard").addClass("selected");

    if (!$("#welcome_modal_window").length) {
        $(document.body).append('<div id="welcome_modal_window"></div>');
        $(document.body).append(
            $('<link rel="stylesheet" type="text/css" />').attr(
                "href",
                "include/styles/new_installation_welcome_window.css"
            )
        );
    }

    load_modal({
        target: $('#welcome_modal_window'),
        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
        modal: {
            title: "<?php echo __('Welcome to').' '.io_safe_output(get_product_name()); ?>",
            cancel: '<?php echo __('Do not show anymore'); ?>',
            ok: '<?php echo __('Close wizard'); ?>',
            overlay: true,
            overlayExtraClass: 'welcome-overlay',
        },
        onshow: {
            page: 'include/ajax/welcome_window',
            method: 'loadWelcomeWindow',
            width: 1000,
        },
        oncancel: {
            page: 'include/ajax/welcome_window',
            title: "<?php echo __('Cancel Configuration Window'); ?>",
            method: 'cancelWelcome',
            confirm: function (fn) {
                confirmDialog({
                    title: '<?php echo __('Are you sure?'); ?>',
                    message: '<?php echo __('Are you sure you want to cancel this tutorial?'); ?>',
                    ok: '<?php echo __('OK'); ?>',
                    cancel: '<?php echo __('Cancel'); ?>',
                    onAccept: function() {
                        // Continue execution.
                        fn();
                    }
                })
            }
        },
        onload: () => {
            $(document).ready(function () {
                var buttonpane = $("div[aria-describedby='welcome_modal_window'] .ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix");
                $(buttonpane).append(`
                <div class="welcome-wizard-buttons">
                    <label>
                        <input type="checkbox" class="welcome-wizard-do-not-show" value="1" />
                        <?php echo __('Do not show anymore'); ?>
                    </label>
                    <button class="close-wizard-button"><?php echo __('Close wizard'); ?></button>
                </div>
                `);

                var closeWizard = $("button.close-wizard-button");

                $(closeWizard).click(function (e) {
                    var close = $("div[aria-describedby='welcome_modal_window'] button.sub.ok.submit-next.ui-button");
                    var cancel = $("div[aria-describedby='welcome_modal_window'] button.sub.upd.submit-cancel.ui-button");
                    var checkbox = $("div[aria-describedby='welcome_modal_window'] .welcome-wizard-do-not-show:checked").length;

                    if (checkbox === 1) {
                        $(cancel).click();
                    } else {
                        $(close).click()
                    }
                });
            });
        }
    });
});
</script>

    <?php
}
