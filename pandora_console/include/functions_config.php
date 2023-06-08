<?php

/**
 * Main configuration of Pandora FMS
 *
 * @category   Config
 * @package    Pandora FMS
 * @subpackage Config
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

// Config functions.
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/functions.php';
enterprise_include_once('include/functions_config.php');

use PandoraFMS\Core\DBMaintainer;
use PandoraFMS\Core\Config;


/**
 * Creates a single config value in the database.
 *
 * @param string $token Config token to create.
 * @param string $value Value to set.
 *
 * @return boolean Config id if success. False on failure.
 */
function config_create_value($token, $value)
{
    return db_process_sql_insert(
        'tconfig',
        [
            'value' => $value,
            'token' => $token,
        ]
    );
}


/**
 * Update a single config value in the database.
 *
 * If the config token doesn't exists, it's created.
 *
 * @param string  $token   Config token to update.
 * @param string  $value   New value to set.
 * @param boolean $noticed If true, not necessary audit it.
 *
 * @return boolean True if success. False on failure.
 */
function config_update_value($token, $value, $noticed=false, $password=false)
{
    global $config;
    // Include functions_io to can call __() function.
    include_once $config['homedir'].'/include/functions_io.php';

    if ($token === 'list_ACL_IPs_for_API') {
        $value = str_replace(
            [
                "\r\n",
                "\r",
                "\n",
            ],
            ';',
            io_safe_output($value)
        );
    }

    if ($token === 'default_assign_tags') {
        $value = ($value);
    }

    if (isset($config[$token]) === false) {
        $config[$token] = $value;
        if (($password === false)) {
            return (bool) config_create_value($token, io_safe_input($value));
        } else {
            return (bool) config_create_value($token, io_input_password($value));
        }
    }

    // If it has not changed.
    if ($config[$token] === $value) {
        return true;
    }

    $config[$token] = $value;
    $value = io_safe_output($value);

    $result = db_process_sql_update(
        'tconfig',
        ['value' => ($password === false) ? io_safe_input($value) : io_input_password($value)],
        ['token' => $token]
    );

    if ($result === 0) {
        return true;
    } else {
        // Something in setup changes.
        if ($noticed === false) {
            db_pandora_audit(
                AUDIT_LOG_SETUP,
                'Setup has changed',
                false,
                false,
                sprintf('Token << %s >> updated.', $token)
            );
        }

        return (bool) $result;
    }
}


/**
 * Updates all config values in case setup page was invoked
 *
 * @return boolean
 */
function config_update_config()
{
    global $config;

    // Include functions_io to can call __() function.
    include_once $config['homedir'].'/include/functions_io.php';

    // If user is not even log it, don't try this.
    if (isset($config['id_user']) === false) {
        $config['error_config_update_config'] = [];
        $config['error_config_update_config']['correct'] = false;
        $config['error_config_update_config']['message'] = __('Failed updated: User did not login.');

        return false;
    }

    if (!check_acl($config['id_user'], 0, 'PM') && !is_user_admin($config['id_user'])) {
        $config['error_config_update_config'] = [];
        $config['error_config_update_config']['correct'] = false;
        $config['error_config_update_config']['message'] = __('Failed updated: User is not admin.');

        return false;
    }

    $update_config = (bool) get_parameter('update_config');

    if ($update_config === false) {
        // Do nothing.
        return false;
    }

    $error_update = [];
    $errors = [];
    $warnings = [];

    $sec2 = get_parameter('sec2');

    switch ($sec2) {
        case 'godmode/setup/setup':
            $section_setup = get_parameter('section');
            // MAIN SETUP.
            // Setup now is divided in different tabs.
            switch ($section_setup) {
                case 'general':
                    if (config_update_value('language', (string) get_parameter('language'), true) === false) {
                        $error_update[] = __('Language settings');
                    }

                    if (config_update_value('remote_config', (string) get_parameter('remote_config'), true) === false) {
                        $error_update[] = __('Remote config directory');
                    }

                    if (config_update_value('chromium_path', (string) get_parameter('chromium_path'), true) === false) {
                        $error_update[] = __('Chromium config directory');
                    }

                    if (config_update_value('loginhash_pwd', io_input_password((string) get_parameter('loginhash_pwd')), true) === false) {
                        $error_update[] = __('Auto login (hash) password');
                    }

                    if (config_update_value('timesource', (string) get_parameter('timesource'), true) === false) {
                        $error_update[] = __('Time source');
                    }

                    if (config_update_value('autoupdate', (bool) get_parameter('autoupdate'), true) === false) {
                        $error_update[] = __('Automatic check for updates');
                    }

                    if (config_update_value('cert_path', get_parameter('cert_path'), true) === false) {
                        $error_update[] = __('SSL cert path');
                    }

                    if (config_update_value('https', (bool) get_parameter('https'), true) === false) {
                        $error_update[] = __('Enforce https');
                    }

                    if (config_update_value('use_cert', (bool) get_parameter('use_cert'), true) === false) {
                        $error_update[] = __('Use cert.');
                    }

                    $attachment_store = (string) get_parameter('attachment_store');
                    if (file_exists($attachment_store) === false
                        || is_writable($attachment_store) === false
                    ) {
                        $error_update[] = __('Attachment store');
                        $error_update[] .= __(
                            "Path doesn't exists or is not writable"
                        );
                    } else {
                        if (config_update_value('attachment_store', $attachment_store, true) === false) {
                            $error_update[] = __(
                                'Attachment store.'
                            );
                        }
                    }

                    if (config_update_value('list_ACL_IPs_for_API', (string) get_parameter('list_ACL_IPs_for_API'), true) === false) {
                        $error_update[] = __('IP list with API access');
                    }

                    if (config_update_value('api_password', io_input_password(get_parameter('api_password')), true) === false) {
                        $error_update[] = __('API password');
                    }

                    if (config_update_value('activate_gis', (bool) get_parameter('activate_gis'), true) === false) {
                        $error_update[] = __('Enable GIS features');
                    }

                    if (config_update_value('integria_inventory', get_parameter('integria_inventory'), true) === false) {
                        $error_update[] = __('Integria inventory');
                    }

                    if (config_update_value('integria_api_password', io_input_password(get_parameter('integria_api_password')), true) === false) {
                        $error_update[] = __('Integria API password');
                    }

                    if (config_update_value('integria_url', get_parameter('integria_url'), true) === false) {
                        $error_update[] = __('Integria URL');
                    }

                    if (config_update_value('activate_netflow', (bool) get_parameter('activate_netflow'), true) === false) {
                        $error_update[] = __('Enable Netflow');
                    }

                    if (config_update_value('activate_sflow', (bool) get_parameter('activate_sflow'), true) === false) {
                        $error_update[] = __('Enable Sflow');
                    }

                    if (config_update_value('general_network_path', get_parameter('general_network_path'), true) === false) {
                        $error_update[] = __('General network path');
                    } else {
                        if (empty($config['netflow_name_dir']) === false && $config['netflow_name_dir'] !== '') {
                            $path = get_parameter('general_network_path');
                            config_update_value('netflow_path', $path.$config['netflow_name_dir']);
                        }

                        if (empty($config['sflow_name_dir']) === false && $config['sflow_name_dir'] !== '') {
                            $path = get_parameter('general_network_path');
                            config_update_value('sflow_path', $path.$config['sflow_name_dir']);
                        }
                    }

                    $timezone = (string) get_parameter('timezone');
                    if (empty($timezone) === true || config_update_value('timezone', $timezone, true) === false) {
                        $error_update[] = __('Timezone setup');
                    }

                    if (config_update_value('sound_alert', get_parameter('sound_alert'), true) === false) {
                        $error_update[] = __('Sound for Alert fired');
                    }

                    if (config_update_value('sound_critical', get_parameter('sound_critical'), true) === false) {
                        $error_update[] = __('Sound for Monitor critical');
                    }

                    if (config_update_value('sound_warning', get_parameter('sound_warning'), true) === false) {
                        $error_update[] = __('Sound for Monitor warning');
                    }

                    // Update of Pandora FMS license.
                    $update_manager_installed = db_get_value('value', 'tconfig', 'token', 'update_manager_installed');

                    if ($update_manager_installed == 1) {
                        $license_info_key = get_parameter('license_info_key', '');
                        if (empty($license_info_key) === false) {
                            $values = [db_escape_key_identifier('value') => $license_info_key];
                            $where = [db_escape_key_identifier('key') => 'customer_key'];
                            $update_manage_settings_result = db_process_sql_update('tupdate_settings', $values, $where);
                            if ($update_manage_settings_result === false) {
                                $error_update[] = __('License information');
                            }
                        }
                    }

                    if (config_update_value('public_url', get_parameter('public_url'), true) === false) {
                        $error_update[] = __('Public URL');
                    }

                    if (config_update_value('force_public_url', get_parameter_switch('force_public_url'), true) === false) {
                        $error_update[] = __('Force use Public URL');
                    }

                    if (config_update_value('public_url_exclusions', get_parameter('public_url_exclusions'), true) === false) {
                        $error_update[] = __('Public URL host exclusions');
                    }

                    if (config_update_value('referer_security', get_parameter('referer_security'), true) === false) {
                        $error_update[] = __('Referer security');
                    }

                    if (config_update_value('event_storm_protection', get_parameter('event_storm_protection', 0), true) === false) {
                        $error_update[] = __('Event storm protection');
                    }

                    if (config_update_value('command_snapshot', get_parameter('command_snapshot'), true) === false) {
                        $error_update[] = __('Command Snapshot');
                    }

                    if (config_update_value('use_custom_encoding', get_parameter('use_custom_encoding', 0), true) === false) {
                        $error_update[] = __('Use custom encoding');
                    }

                    if (config_update_value('server_log_dir', io_safe_input(strip_tags(io_safe_output(get_parameter('server_log_dir')))), true) === false) {
                        $error_update[] = __('Server logs directory');
                    }

                    if (config_update_value('max_log_size', get_parameter('max_log_size'), true) === false) {
                        $error_update[] = __('Log size limit in system logs viewer extension');
                    }

                    if (config_update_value('tutorial_mode', get_parameter('tutorial_mode'), true) === false) {
                        $error_update[] = __('Tutorial mode');
                    }

                    if (config_update_value('past_planned_downtimes', get_parameter('past_planned_downtimes'), true) === false) {
                        $error_update[] = __('Allow create scheduled downtimes in the past');
                    }

                    if (config_update_value('limit_parameters_massive', get_parameter('limit_parameters_massive'), true) === false) {
                        $error_update[] = __('Limit parameters bulk');
                    }

                    if (config_update_value('identification_reminder', get_parameter('identification_reminder'), true) === false) {
                        $error_update[] = __('Identification_reminder');
                    }

                    if (config_update_value('include_agents', (bool) get_parameter('include_agents'), true) === false) {
                        $error_update[] = __('Include_agents');
                    }

                    if (config_update_value('alias_as_name', get_parameter('alias_as_name'), true) === false) {
                        $error_update[] = __('alias_as_name');
                    }

                    if (config_update_value('keep_in_process_status_extra_id', get_parameter('keep_in_process_status_extra_id'), true) === false) {
                        $error_update[] = __('keep_in_process_status_extra_id');
                    }

                    if (config_update_value('console_log_enabled', get_parameter('console_log_enabled'), true) === false) {
                        $error_update[] = __('Console log enabled');
                    }

                    if (config_update_value('audit_log_enabled', get_parameter('audit_log_enabled'), true) === false) {
                        $error_update[] = __('Audit log enabled');
                    }

                    if (config_update_value('module_custom_id_ro', get_parameter('module_custom_id_ro'), true) === false) {
                        $error_update[] = __('Module Custom ID read only');
                    }

                    if (config_update_value('reporting_console_enable', get_parameter('reporting_console_enable'), true) === false) {
                        $error_update[] = __('Enable console report');
                    }

                    if (config_update_value('check_conexion_interval', get_parameter('check_conexion_interval'), true) === false) {
                        $error_update[] = __('Check conexion interval');
                    }

                    if (config_update_value('unique_ip', get_parameter('unique_ip'), true) === false) {
                        $error_update[] = __('Unique IP');
                    }

                    if (config_update_value('email_smtpServer', get_parameter('email_smtpServer'), true) === false) {
                        $error_update[] = __('Server SMTP');
                    }

                    if (config_update_value('email_from_dir', get_parameter('email_from_dir'), true) === false) {
                        $error_update[] = __('From dir');
                    }

                    if (config_update_value('email_from_name', get_parameter('email_from_name'), true) === false) {
                        $error_update[] = __('From name');
                    }

                    if (config_update_value('email_smtpPort', (int) get_parameter('email_smtpPort'), true) === false) {
                        $error_update[] = __('Port SMTP');
                    }

                    if (config_update_value('email_encryption', get_parameter('email_encryption'), true) === false) {
                        $error_update[] = __('Encryption');
                    }

                    if (config_update_value('email_username', get_parameter('email_username'), true) === false) {
                        $error_update[] = __('Email user');
                    }

                    if (config_update_value('email_password', io_input_password(get_parameter('email_password')), true) === false) {
                        $error_update[] = __('Email password');
                    }

                    $inventory_changes_blacklist = get_parameter('inventory_changes_blacklist', []);
                    if (config_update_value('inventory_changes_blacklist', implode(',', $inventory_changes_blacklist), true) === false) {
                        $error_update[] = __('Inventory changes blacklist');
                    }
                break;

                case 'enterprise':
                    if (isset($config['enterprise_installed']) === true && (bool) $config['enterprise_installed'] === true) {
                        if (config_update_value('trap2agent', (string) get_parameter('trap2agent'), true) === false) {
                            $error_update[] = __('Forward SNMP traps to agent (if exist)');
                        }

                        if (config_update_value('acl_enterprise', get_parameter('acl_enterprise'), true) === false) {
                            $error_update[] = __('Use Enterprise ACL System');
                        }

                        if (config_update_value('metaconsole', get_parameter('metaconsole'), true) === false) {
                            $error_update[] = __('Activate Metaconsole');
                        }

                        if (config_update_value('collection_max_size', get_parameter('collection_max_size'), true) === false) {
                            $error_update[] = __('Size of collection');
                        }

                        if (config_update_value('replication_dbhost', (string) get_parameter('replication_dbhost'), true) === false) {
                            $error_update[] = __('Replication DB host');
                        }

                        if (config_update_value('replication_dbname', (string) get_parameter('replication_dbname'), true) === false) {
                            $error_update[] = __('Replication DB database');
                        }

                        if (config_update_value('replication_dbuser', (string) get_parameter('replication_dbuser'), true) === false) {
                            $error_update[] = __('Replication DB user');
                        }

                        if (config_update_value('replication_dbpass', io_input_password((string) get_parameter('replication_dbpass')), true) === false) {
                            $error_update[] = __('Replication DB password');
                        }

                        if (config_update_value('replication_dbport', (string) get_parameter('replication_dbport'), true) === false) {
                            $error_update[] = __('Replication DB port');
                        }

                        if (config_update_value('metaconsole_agent_cache', (int) get_parameter('metaconsole_agent_cache'), true) === false) {
                            $error_update[] = __('Metaconsole agent cache');
                        }

                        if (config_update_value('log_collector', (bool) get_parameter('log_collector'), true) === false) {
                            $error_update[] = __('Activate Log Collector');
                        }

                        if (config_update_value('enable_update_manager', get_parameter('enable_update_manager'), true) === false) {
                            $error_update[] = __('Enable Update Manager');
                        }

                        if (config_update_value('legacy_database_ha', get_parameter('legacy_database_ha'), true) === false) {
                            $error_update[] = __('Legacy database HA');
                        }

                        if (config_update_value('ipam_ocuppied_critical_treshold', get_parameter('ipam_ocuppied_critical_treshold'), true) === false) {
                            $error_update[] = __('Ipam Ocuppied Manager Critical');
                        }

                        if (config_update_value('ipam_ocuppied_warning_treshold', get_parameter('ipam_ocuppied_warning_treshold'), true) === false) {
                            $error_update[] = __('Ipam Ocuppied Manager Warning');
                        }
                    }
                break;

                case 'pass':
                    if (isset($config['enterprise_installed']) === true && (bool) $config['enterprise_installed'] === true) {
                        if (config_update_value('enable_pass_policy', get_parameter('enable_pass_policy'), true) === false) {
                            $error_update[] = __('Enable password policy');
                        }

                        if (config_update_value('pass_size', get_parameter('pass_size'), true) === false) {
                            $error_update[] = __('Min. size password');
                        }

                        if (config_update_value('pass_expire', get_parameter('pass_expire'), true) === false) {
                            $error_update[] = __('Password expiration');
                        }

                        if (config_update_value('first_login', get_parameter('first_login'), true) === false) {
                            $error_update[] = __('Force change password on first login');
                        }

                        if (config_update_value('mins_fail_pass', get_parameter('mins_fail_pass'), true) === false) {
                            $error_update[] = __('User blocked if login fails');
                        }

                        if (config_update_value('number_attempts', get_parameter('number_attempts'), true) === false) {
                            $error_update[] = __('Number of failed login attempts');
                        }

                        if (config_update_value('pass_needs_numbers', get_parameter('pass_needs_numbers'), true) === false) {
                            $error_update[] = __('Password must have numbers');
                        }

                        if (config_update_value('pass_needs_symbols', get_parameter('pass_needs_symbols'), true) === false) {
                            $error_update[] = __('Password must have symbols');
                        }

                        if (config_update_value('enable_pass_policy_admin', get_parameter('enable_pass_policy_admin'), true) === false) {
                            $error_update[] = __('Apply password policy to admin users');
                        }

                        if (config_update_value('enable_pass_history', get_parameter('enable_pass_history'), true) === false) {
                            $error_update[] = __('Enable password history');
                        }

                        if (config_update_value('compare_pass', get_parameter('compare_pass'), true) === false) {
                            $error_update[] = __('Compare previous password');
                        }

                        if (config_update_value('reset_pass_option', (bool) get_parameter('reset_pass_option'), true) === false) {
                            $error_update[] = __('Activate reset password');
                        }

                        if (config_update_value('exclusion_word_list', (string) get_parameter('exclusion_word_list'), true) === false) {
                            $error_update[] = __('Exclusion word list for passwords');
                        }
                    }
                break;

                case 'auth':
                    $validatedCSRF = validate_csrf_code();

                    // CSRF Validation.
                    if ($validatedCSRF === false) {
                        include_once 'general/login_page.php';
                        // Finish the execution.
                        exit('</html>');
                    }

                    // AUTHENTICATION SETUP.
                    if (config_update_value('auth', get_parameter('auth'), true) === false) {
                        $error_update[] = __('Authentication method');
                    }

                    if (config_update_value('autocreate_remote_users', get_parameter('autocreate_remote_users'), true) === false) {
                        $error_update[] = __('Autocreate remote users');
                    }

                    if (config_update_value('default_remote_profile', get_parameter('default_remote_profile'), true) === false) {
                        $error_update[] = __('Autocreate profile');
                    }

                    if (config_update_value('default_remote_group', get_parameter('default_remote_group'), true) === false) {
                        $error_update[] = __('Autocreate profile group');
                    }

                    if (config_update_value('default_assign_tags', implode(',', get_parameter('default_assign_tags', [])), true) === false) {
                        $error_update[] = __('Autocreate profile tags');
                    }

                    if (config_update_value('default_no_hierarchy', (int) get_parameter('default_no_hierarchy'), true) === false) {
                        $error_update[] = __('Automatically assigned no hierarchy');
                    }

                    if (config_update_value('timezonevisual', (string) get_parameter('timezonevisual'), true) === false) {
                        $error_update[] = __('Automatically timezone visual');
                    }

                    if (config_update_value('autocreate_blacklist', get_parameter('autocreate_blacklist'), true) === false) {
                        $error_update[] = __('Autocreate blacklist');
                    }

                    if (config_update_value('ad_server', get_parameter('ad_server'), true) === false) {
                        $error_update[] = __('Active directory server');
                    }

                    if (config_update_value('ad_port', get_parameter('ad_port'), true) === false) {
                        $error_update[] = __('Active directory port');
                    }

                    if (config_update_value('ad_start_tls', get_parameter('ad_start_tls'), true) === false) {
                        $error_update[] = __('Start TLS');
                    }

                    if (config_update_value('recursive_search', get_parameter('recursive_search'), true) === false) {
                        $error_update[] = __('Recursive search');
                    }

                    if (config_update_value('ad_advanced_config', get_parameter('ad_advanced_config'), true) === false) {
                        $error_update[] = __('Advanced Config AD');
                    }

                    if (config_update_value('ldap_advanced_config', get_parameter('ldap_advanced_config'), true) === false) {
                        $error_update[] = __('Advanced Config LDAP');
                    }

                    if (config_update_value('ad_domain', get_parameter('ad_domain'), true) === false) {
                        $error_update[] = __('Domain');
                    }

                    if (config_update_value('ad_adv_perms', get_parameter('ad_adv_perms'), true) === false) {
                        $error_update[] = __('Advanced Permisions AD');
                    }

                    if (config_update_value('ldap_adv_perms', get_parameter('ldap_adv_perms'), true) === false) {
                        $error_update[] = __('Advanced Permissions LDAP');
                    }

                    if (config_update_value('ldap_server', get_parameter('ldap_server'), true) === false) {
                        $error_update[] = __('LDAP server');
                    }

                    if (config_update_value('ldap_port', get_parameter('ldap_port'), true) === false) {
                        $error_update[] = __('LDAP port');
                    }

                    if (config_update_value('ldap_version', get_parameter('ldap_version'), true) === false) {
                        $error_update[] = __('LDAP version');
                    }

                    if (config_update_value('ldap_start_tls', get_parameter('ldap_start_tls'), true) === false) {
                        $error_update[] = __('Start TLS');
                    }

                    if (config_update_value('ldap_base_dn', get_parameter('ldap_base_dn'), true) === false) {
                        $error_update[] = __('Base DN');
                    }

                    if (config_update_value('ldap_login_attr', get_parameter('ldap_login_attr'), true) === false) {
                        $error_update[] = __('Login attribute');
                    }

                    if (config_update_value('ldap_admin_login', get_parameter('ldap_admin_login'), true) === false) {
                        $error_update[] = __('Admin LDAP login');
                    }

                    if (config_update_value('ldap_admin_pass', io_input_password(get_parameter('ldap_admin_pass')), true) === false) {
                        $error_update[] = __('Admin LDAP password');
                    }

                    if (config_update_value('ldap_search_timeout', (int) get_parameter('ldap_search_timeout', 5), true) === false) {
                        $error_update[] = __('Ldap search timeout');
                    }

                    if (config_update_value('ldap_server_secondary', get_parameter('ldap_server_secondary'), true) === false) {
                        $error_update[] = __('Secondary LDAP server');
                    }

                    if (config_update_value('ldap_port_secondary', get_parameter('ldap_port_secondary'), true) === false) {
                        $error_update[] = __('Secondary LDAP port');
                    }

                    if (config_update_value('ldap_version_secondary', get_parameter('ldap_version_secondary'), true) === false) {
                        $error_update[] = __('Secondary LDAP version');
                    }

                    if (config_update_value('ldap_start_tls_secondary', get_parameter('ldap_start_tls_secondary'), true) === false) {
                        $error_update[] = __('Secontary start TLS');
                    }

                    if (config_update_value('ldap_base_dn_secondary', get_parameter('ldap_base_dn_secondary'), true) === false) {
                        $error_update[] = __('Secondary base DN');
                    }

                    if (config_update_value('ldap_login_attr_secondary', get_parameter('ldap_login_attr_secondary'), true) === false) {
                        $error_update[] = __('Secondary login attribute');
                    }

                    if (config_update_value('ldap_admin_login_secondary', get_parameter('ldap_admin_login_secondary'), true) === false) {
                        $error_update[] = __('Admin secondary LDAP login');
                    }

                    if (config_update_value('ldap_admin_pass_secondary', io_input_password(get_parameter('ldap_admin_pass_secondary')), true) === false) {
                        $error_update[] = __('Admin secondary LDAP password');
                    }

                    if (config_update_value('fallback_local_auth', get_parameter('fallback_local_auth'), true) === false) {
                        $error_update[] = __('Fallback to local authentication');
                    }

                    if (config_update_value('ldap_login_user_attr', get_parameter('ldap_login_user_attr'), true) === false) {
                        $error_update[] = __('Login user attribute');
                    }

                    if (config_update_value('ldap_function', get_parameter('ldap_function'), true) === false) {
                        $error_update[] = __('LDAP function');
                    }

                    if (isset($config['fallback_local_auth']) === true && (int) $config['fallback_local_auth'] === 0) {
                        if (config_update_value('ldap_save_password', get_parameter('ldap_save_password'), true) === false) {
                            $error_update[] = __('Save Password');
                        }
                    } else if (isset($config['fallback_local_auth']) === false && (int) $config['fallback_local_auth'] === 1) {
                        config_update_value('ldap_save_password', 1);
                    }

                    if (config_update_value('ldap_save_profile', get_parameter('ldap_save_profile'), true) === false) {
                        $error_update[] = __('Save profile');
                    }

                    if (config_update_value('secondary_ldap_enabled', get_parameter('secondary_ldap_enabled'), true) === false) {
                        $error_update[] = __('LDAP secondary enabled');
                    }

                    if (config_update_value('rpandora_server', get_parameter('rpandora_server'), true) === false) {
                        $error_update[] = __('MySQL host');
                    }

                    if (config_update_value('rpandora_port', get_parameter('rpandora_port'), true) === false) {
                        $error_update[] = __('MySQL port');
                    }

                    if (config_update_value('rpandora_dbname', get_parameter('rpandora_dbname'), true) === false) {
                        $error_update[] = __('Database name');
                    }

                    if (config_update_value('rpandora_user', get_parameter('rpandora_user'), true) === false) {
                        $error_update[] = __('User');
                    }

                    if (config_update_value('rpandora_pass', io_input_password(get_parameter('rpandora_pass')), true) === false) {
                        $error_update[] = __('Password');
                    }

                    if (config_update_value('rintegria_server', get_parameter('rintegria_server'), true) === false) {
                        $error_update[] = __('Integria host');
                    }

                    if (config_update_value('rintegria_port', get_parameter('rintegria_port'), true) === false) {
                        $error_update[] = __('MySQL port');
                    }

                    if (config_update_value('rintegria_dbname', get_parameter('rintegria_dbname'), true) === false) {
                        $error_update[] = __('Database name');
                    }

                    if (config_update_value('rintegria_user', get_parameter('rintegria_user'), true) === false) {
                        $error_update[] = __('User');
                    }

                    if (config_update_value('rintegria_pass', io_input_password(get_parameter('rintegria_pass')), true) === false) {
                        $error_update[] = __('Password');
                    }

                    if (config_update_value('saml_path', get_parameter('saml_path'), true) === false) {
                        $error_update[] = __('Saml path');
                    }

                    if (config_update_value('saml_source', get_parameter('saml_source'), true) === false) {
                        $error_update[] = __('Saml source');
                    }

                    if (config_update_value('saml_user_id', get_parameter('saml_user_id'), true) === false) {
                        $error_update[] = __('Saml user id parameter');
                    }

                    if (config_update_value('saml_mail', get_parameter('saml_mail'), true) === false) {
                        $error_update[] = __('Saml mail parameter');
                    }

                    if (config_update_value('saml_group_name', get_parameter('saml_group_name'), true) === false) {
                        $error_update[] = __('Saml group name parameter');
                    }

                    if (config_update_value('saml_attr_type', (bool) get_parameter('saml_attr_type'), true) === false) {
                        $error_update[] = __('Saml attr type parameter');
                    }

                    if (config_update_value('saml_profiles_and_tags', get_parameter('saml_profiles_and_tags'), true) === false) {
                        $error_update[] = __('Saml profiles and tags parameter');
                    }

                    if (config_update_value('saml_profile', get_parameter('saml_profile'), true) === false) {
                        $error_update[] = __('Saml profile parameters');
                    }

                    if (config_update_value('saml_tag', get_parameter('saml_tag'), true) === false) {
                        $error_update[] = __('Saml tag parameter');
                    }

                    if (config_update_value('saml_profile_tag_separator', get_parameter('saml_profile_tag_separator'), true) === false) {
                        $error_update[] = __('Saml profile and tag separator');
                    }

                    if (config_update_value('double_auth_enabled', get_parameter('double_auth_enabled'), true) === false) {
                        $error_update[] = __('Double authentication');
                    }

                    if (config_update_value('2FA_all_users', get_parameter('2FA_all_users'), true) === false) {
                        $error_update[] = __('2FA all users');
                    }

                    if (config_update_value('session_timeout', get_parameter('session_timeout'), true) === false) {
                        $error_update[] = __('Session timeout');
                    } else {
                        if ((int) get_parameter('session_timeout') === 0) {
                            $error_update[] = __('Session timeout forced to 90 minutes');

                            if (config_update_value('session_timeout', 90, true) === false) {
                                $error_update[] = __('Session timeout');
                            }
                        }
                    }

                    if (isset($config['fallback_local_auth']) === true && (int) $config['fallback_local_auth'] === 0) {
                        if (config_update_value('ad_save_password', get_parameter('ad_save_password'), true) === false) {
                            $error_update[] = __('Save Password');
                        }
                    } else if (isset($config['fallback_local_auth']) === true && (int) $config['fallback_local_auth'] === 1) {
                        config_update_value('ad_save_password', 1);
                    }
                break;

                case 'perf':
                    // PERFORMANCE SETUP.
                    if (config_update_value('event_purge', get_parameter('event_purge'), true) === false) {
                        $error_update[] = __('Event purge');
                    }

                    if (config_update_value('trap_purge', get_parameter('trap_purge'), true) === false) {
                        $error_update[] = __('Max. days before delete traps');
                    }

                    if (config_update_value('string_purge', get_parameter('string_purge'), true) === false) {
                        $error_update[] = __('Max. days before delete string data');
                    }

                    if (config_update_value('audit_purge', get_parameter('audit_purge'), true) === false) {
                        $error_update[] = __('Max. days before delete audit events');
                    }

                    if (config_update_value('gis_purge', get_parameter('gis_purge'), true) === false) {
                        $error_update[] = __('Max. days before delete GIS data');
                    }

                    if (config_update_value('days_purge', (int) get_parameter('days_purge'), true) === false) {
                        $error_update[] = __('Max. days before purge');
                    }

                    if (config_update_value('days_delete_unknown', (int) get_parameter('days_delete_unknown'), true) === false) {
                        $error_update[] = __('Max. days before delete unknown modules');
                    }

                    if (config_update_value('days_delete_not_initialized', (int) get_parameter('days_delete_not_initialized'), true) === false) {
                        $error_update[] = __('Max. days before delete not initialized modules');
                    }

                    if (config_update_value('days_compact', (int) get_parameter('days_compact'), true) === false) {
                        $error_update[] = __('Max. days before compact data');
                    }

                    if (config_update_value('days_autodisable_deletion', (int) get_parameter('days_autodisable_deletion'), true) === false) {
                        $error_update[] = __('Max. days before autodisable deletion');
                    }

                    if (config_update_value('report_limit', (int) get_parameter('report_limit'), true) === false) {
                        $error_update[] = __('Item limit for realtime reports)');
                    }

                    if (config_update_value('events_per_query', (int) get_parameter('events_per_query'), true) === false) {
                        $error_update[] = __('Limit of events per query');
                    }

                    if (config_update_value('step_compact', (int) get_parameter('step_compact'), true) === false) {
                        $error_update[] = __('Compact interpolation in hours (1 Fine-20 bad)');
                    }

                    if (config_update_value('event_view_hr', (int) get_parameter('event_view_hr'), true) === false) {
                        $error_update[] = __('Default hours for event view');
                    }

                    if (config_update_value('realtimestats', get_parameter('realtimestats'), true) === false) {
                        $error_update[] = __('Use realtime statistics');
                    }

                    if (config_update_value('stats_interval', get_parameter('stats_interval'), true) === false) {
                        $error_update[] = __('Batch statistics period (secs)');
                    }

                    if (config_update_value('agentaccess', (int) get_parameter('agentaccess'), true) === false) {
                        $error_update[] = __('Use agent access graph');
                    }

                    if (config_update_value('num_files_attachment', (int) get_parameter('num_files_attachment'), true) === false) {
                        $error_update[] = __('Max. recommended number of files in attachment directory');
                    }

                    if (config_update_value('delete_notinit', get_parameter('delete_notinit'), true) === false) {
                        $error_update[] = __('Delete not init modules');
                    }

                    if (config_update_value('big_operation_step_datos_purge', get_parameter('big_operation_step_datos_purge'), true) === false) {
                        $error_update[] = __('Big Operatiopn Step to purge old data');
                    }

                    if (config_update_value('small_operation_step_datos_purge', get_parameter('small_operation_step_datos_purge'), true) === false) {
                        $error_update[] = __('Small Operation Step to purge old data');
                    }

                    if (config_update_value('num_past_special_days', get_parameter('num_past_special_days'), true) === false) {
                        $error_update[] = __('Retention period of past special days');
                    }

                    if (config_update_value('max_macro_fields', get_parameter('max_macro_fields'), true) === false) {
                        $error_update[] = __('Max. macro data fields');
                    }

                    if (isset($config['enterprise_installed']) === true && (bool) $config['enterprise_installed'] === true) {
                        if (config_update_value('inventory_purge', get_parameter('inventory_purge'), true) === false) {
                            $error_update[] = __('Max. days before delete inventory data');
                        }
                    }

                    if (config_update_value('delete_old_messages', get_parameter('delete_old_messages'), true) === false) {
                        $error_update[] = __('Max. days before delete old messages');
                    }

                    if (config_update_value('delete_old_network_matrix', get_parameter('delete_old_network_matrix'), true) === false) {
                        $error_update[] = __('Max. days before delete old network matrix data');
                    }

                    if (config_update_value('max_graph_container', get_parameter('max_graph_container'), true) === false) {
                        $error_update[] = __('Graph container - Max. Items');
                    }

                    if (config_update_value('max_execution_event_response', get_parameter('max_execution_event_response'), true) === false) {
                        $error_update[] = __('Max execution event response');
                    }

                    if (config_update_value('row_limit_csv', get_parameter('row_limit_csv'), true) === false) {
                        $error_update[] = __('Row limit in csv log');
                    }

                    if (config_update_value('snmpwalk', get_parameter('snmpwalk'), true) === false) {
                        $error_update[] = __('SNMP walk binary path');
                    }

                    if (config_update_value('snmpwalk_fallback', get_parameter('snmpwalk_fallback'), true) === false) {
                        $error_update[] = __('SNMP walk binary path (fallback for v1)');
                    }

                    if (config_update_value('wmiBinary', get_parameter('wmiBinary'), true) === false) {
                        $error_update[] = __('Default WMI Binary');
                    }

                    // Walk the array with defaults.
                    $defaultAgentWizardOptions = json_decode(io_safe_output($config['agent_wizard_defaults']));
                    foreach ($defaultAgentWizardOptions as $key => $value) {
                        $selectedAgentWizardOptions[$key] = get_parameter_switch('agent_wizard_defaults_'.$key);
                    }

                    if (config_update_value('agent_wizard_defaults', json_encode($selectedAgentWizardOptions), true) === false) {
                        $error_update[] = __('SNMP Interface Agent Wizard');
                    }
                break;

                case 'vis':
                    // VISUAL STYLES SETUP.
                    if (config_update_value('date_format', (string) get_parameter('date_format'), true) === false) {
                        $error_update[] = __('Date format string');
                    }

                    if (config_update_value('notification_autoclose_time', (string) get_parameter('notification_autoclose_time'), true) === false) {
                        $error_update[] = __('Notification Autoclose time');
                    }

                    if (config_update_value('prominent_time', (string) get_parameter('prominent_time'), true) === false) {
                        $error_update[] = __('Timestamp or time comparation');
                    }

                    if (config_update_value('graph_color1', (string) get_parameter('graph_color1'), true) === false) {
                        $error_update[] = __('Graph color #1');
                    }

                    if (config_update_value('graph_color2', (string) get_parameter('graph_color2'), true) === false) {
                        $error_update[] = __('Graph color #2');
                    }

                    if (config_update_value('graph_color3', (string) get_parameter('graph_color3'), true) === false) {
                        $error_update[] = __('Graph color #3');
                    }

                    if (config_update_value('graph_color4', (string) get_parameter('graph_color4'), true) === false) {
                        $error_update[] = __('Graph color #4');
                    }

                    if (config_update_value('graph_color5', (string) get_parameter('graph_color5'), true) === false) {
                        $error_update[] = __('Graph color #5');
                    }

                    if (config_update_value('graph_color6', (string) get_parameter('graph_color6'), true) === false) {
                        $error_update[] = __('Graph color #6');
                    }

                    if (config_update_value('graph_color7', (string) get_parameter('graph_color7'), true) === false) {
                        $error_update[] = __('Graph color #7');
                    }

                    if (config_update_value('graph_color8', (string) get_parameter('graph_color8'), true) === false) {
                        $error_update[] = __('Graph color #8');
                    }

                    if (config_update_value('graph_color9', (string) get_parameter('graph_color9'), true) === false) {
                        $error_update[] = __('Graph color #9');
                    }

                    if (config_update_value('graph_color10', (string) get_parameter('graph_color10'), true) === false) {
                        $error_update[] = __('Graph color #10');
                    }

                    if (config_update_value('interface_unit', (string) get_parameter('interface_unit', __('Bytes')), true) === false) {
                        $error_update[] = __('Value to interface graphics');
                    }

                    if (config_update_value('graph_precision', (string) get_parameter('graph_precision', 1), true) === false) {
                        $error_update[] = __('Data precision for reports');
                    }

                    $style = (string) get_parameter('style');
                    if ($style !== (string) $config['style']) {
                        $style = substr($style, 0, (strlen($style) - 4));
                    }

                    if (config_update_value('style', $style, true) === false) {
                        $error_update[] = __('Style template');
                    }

                    if (config_update_value('block_size', (int) get_parameter('block_size'), true) === false) {
                        $error_update[] = __('Block size for pagination');
                    }

                    if (config_update_value('round_corner', (bool) get_parameter('round_corner'), true) === false) {
                        $error_update[] = __('Use round corners');
                    }

                    if (config_update_value('maximum_y_axis', (bool) get_parameter('maximum_y_axis'), true) === false) {
                        $error_update[] = __('Chart fit to content');
                    }

                    if (config_update_value('show_qr_code_header', (bool) get_parameter('show_qr_code_header'), true) === false) {
                        $error_update[] = __('Show QR code header');
                    }

                    if (config_update_value('status_images_set', (string) get_parameter('status_images_set'), true) === false) {
                        $error_update[] = __('Status icon set');
                    }

                    if (config_update_value('fontpath', (string) get_parameter('fontpath'), true) === false) {
                        $error_update[] = __('Font path');
                    }

                    if (config_update_value('font_size', get_parameter('font_size'), true) === false) {
                        $error_update[] = __('Font size');
                    }

                    if (config_update_value('custom_favicon', (string) get_parameter('custom_favicon'), true) === false) {
                        $error_update[] = __('Custom favicon');
                    }

                    if (config_update_value('custom_logo', (string) get_parameter('custom_logo'), true) === false) {
                        $error_update[] = __('Custom logo');
                    }

                    if (config_update_value('custom_logo_collapsed', (string) get_parameter('custom_logo_collapsed'), true) === false) {
                        $error_update[] = __('Custom logo collapsed');
                    }

                    if (config_update_value('custom_logo_white_bg', (string) get_parameter('custom_logo_white_bg'), true) === false) {
                        $error_update[] = __('Custom logo white background');
                    }

                    if (config_update_value('custom_logo_login', (string) get_parameter('custom_logo_login'), true) === false) {
                        $error_update[] = __('Custom logo login');
                    }

                    if (config_update_value('custom_splash_login', (string) get_parameter('custom_splash_login'), true) === false) {
                        $error_update[] = __('Custom splash login');
                    }

                    if (config_update_value('custom_docs_logo', (string) get_parameter('custom_docs_logo'), true) === false) {
                        $error_update[] = __('Custom documentation logo');
                    }

                    if (config_update_value('custom_support_logo', (string) get_parameter('custom_support_logo'), true) === false) {
                        $error_update[] = __('Custom support logo');
                    }

                    if (config_update_value('custom_network_center_logo', (string) get_parameter('custom_network_center_logo'), true) === false) {
                        $error_update[] = __('Custom networkmap center logo');
                    }

                    if (config_update_value('custom_mobile_console_logo', (string) get_parameter('custom_mobile_console_logo'), true) === false) {
                        $error_update[] = __('Custom networkmap center logo');
                    }

                    if (config_update_value('custom_title_header', (string) get_parameter('custom_title_header'), true) === false) {
                        $error_update[] = __('Custom title header');
                    }

                    if (config_update_value('custom_subtitle_header', (string) get_parameter('custom_subtitle_header'), true) === false) {
                        $error_update[] = __('Custom subtitle header');
                    }

                    if (config_update_value('meta_custom_title_header', (string) get_parameter('meta_custom_title_header'), true) === false) {
                        $error_update[] = __('Meta custom title header');
                    }

                    if (config_update_value('meta_custom_subtitle_header', (string) get_parameter('meta_custom_subtitle_header'), true) === false) {
                        $error_update[] = __('Meta custom subtitle header');
                    }

                    if (config_update_value('custom_title1_login', (string) get_parameter('custom_title1_login'), true) === false) {
                        $error_update[] = __('Custom title1 login');
                    }

                    if (config_update_value('custom_title2_login', (string) get_parameter('custom_title2_login'), true) === false) {
                        $error_update[] = __('Custom title2 login');
                    }

                    if (config_update_value('login_background', (string) get_parameter('login_background'), true) === false) {
                        $error_update[] = __('Login background');
                    }

                    if (config_update_value('custom_docs_url', (string) get_parameter('custom_docs_url'), true) === false) {
                        $error_update[] = __('Custom Docs url');
                    }

                    if (config_update_value('custom_support_url', (string) get_parameter('custom_support_url'), true) === false) {
                        $error_update[] = __('Custom support url');
                    }

                    if (config_update_value('rb_product_name', (string) get_parameter('rb_product_name'), true) === false) {
                        $error_update[] = __('Product name');
                    }

                    if (config_update_value('rb_copyright_notice', (string) get_parameter('rb_copyright_notice'), true) === false) {
                        $error_update[] = __('Copyright notice');
                    }

                    if (config_update_value('background_opacity', (string) get_parameter('background_opacity'), true) === false) {
                        $error_update[] = __('Background opacity % (login)');
                    }

                    if (config_update_value('meta_background_opacity', (string) get_parameter('meta_background_opacity'), true) === false) {
                        $error_update[] = __('Background opacity % (login)');
                    }

                    if (config_update_value('meta_custom_logo_white_bg', (string) get_parameter('meta_custom_logo_white_bg'), true) === false) {
                        $error_update[] = __('Custom logo metaconsole (white background)');
                    }

                    if (config_update_value('meta_custom_logo_login', (string) get_parameter('meta_custom_logo_login'), true) === false) {
                        $error_update[] = __('Custom logo login metaconsole');
                    }

                    if (config_update_value('meta_custom_splash_login', (string) get_parameter('meta_custom_splash_login'), true) === false) {
                        $error_update[] = __('Custom splash login metaconsole');
                    }

                    if (config_update_value('meta_custom_title1_login', (string) get_parameter('meta_custom_title1_login'), true) === false) {
                        $error_update[] = __('Custom title1 login metaconsole');
                    }

                    if (config_update_value('meta_custom_title2_login', (string) get_parameter('meta_custom_title2_login'), true) === false) {
                        $error_update[] = __('Custom title2 login metaconsole');
                    }

                    if (config_update_value('meta_login_background', (string) get_parameter('meta_login_background'), true) === false) {
                        $error_update[] = __('Login background metaconsole');
                    }

                    if (config_update_value('meta_custom_docs_url', (string) get_parameter('meta_custom_docs_url'), true) === false) {
                        $error_update[] = __('Custom Docs url');
                    }

                    if (config_update_value('meta_custom_support_url', (string) get_parameter('meta_custom_support_url'), true) === false) {
                        $error_update[] = __('Custom support url');
                    }

                    if (config_update_value('legacy_vc', (int) get_parameter('legacy_vc'), true) === false) {
                        $error_update[] = __('Use the legacy Visual Console');
                    }

                    if (config_update_value('vc_default_cache_expiration', (int) get_parameter('vc_default_cache_expiration'), true) === false) {
                        $error_update[] = __("Default expiration of the Visual Console item's cache");
                    }

                    if (config_update_value('vc_refr', (int) get_parameter('vc_refr'), true) === false) {
                        $error_update[] = __('Default interval for refresh on Visual Console');
                    }

                    if (config_update_value('vc_favourite_view', (int) get_parameter('vc_favourite_view', 0), true) === false) {
                        $error_update[] = __('Default line favourite_view for the Visual Console');
                    }

                    if (config_update_value('vc_menu_items', (int) get_parameter('vc_menu_items', 10), true) === false) {
                        $error_update[] = __('Default line menu items for the Visual Console');
                    }

                    if (config_update_value('vc_line_thickness', (int) get_parameter('vc_line_thickness'), true) === false) {
                        $error_update[] = __('Default line thickness for the Visual Console');
                    }

                    if (config_update_value('mobile_view_orientation_vc', (int) get_parameter('mobile_view_orientation_vc'), true) === false) {
                        $error_update[] = __('Mobile view not allow visual console orientation');
                    }

                    if (config_update_value('display_item_frame', (int) get_parameter('display_item_frame'), true) === false) {
                        $error_update[] = __('Display item frame on alert triggered');
                    }

                    if (config_update_value('ser_menu_items', (int) get_parameter('ser_menu_items', 10), true) === false) {
                        $error_update[] = __('Default line menu items for the Services');
                    }

                    if (config_update_value('agent_size_text_small', get_parameter('agent_size_text_small'), true) === false) {
                        $error_update[] = __('Agent size text');
                    }

                    if (config_update_value('agent_size_text_medium', get_parameter('agent_size_text_medium'), true) === false) {
                        $error_update[] = __('Agent size text');
                    }

                    if (config_update_value('module_size_text_small', get_parameter('module_size_text_small'), true) === false) {
                        $error_update[] = __('Module size text');
                    }

                    if (config_update_value('module_size_text_medium', get_parameter('module_size_text_medium'), true) === false) {
                        $error_update[] = __('Description size text');
                    }

                    if (config_update_value('description_size_text', get_parameter('description_size_text'), true) === false) {
                        $error_update[] = __('Description size text');
                    }

                    if (config_update_value('item_title_size_text', get_parameter('item_title_size_text'), true) === false) {
                        $error_update[] = __('Item title size text');
                    }

                    if (config_update_value('gis_label', get_parameter('gis_label'), true) === false) {
                        $error_update[] = __('GIS Labels');
                    }

                    if (config_update_value('simple_module_value', get_parameter('simple_module_value'), true) === false) {
                        $error_update[] = __('Show units in values report');
                    }

                    if (config_update_value('gis_default_icon', get_parameter('gis_default_icon'), true) === false) {
                        $error_update[] = __('Default icon in GIS');
                    }

                    if (config_update_value('autohidden_menu', get_parameter('autohidden_menu'), true) === false) {
                        $error_update[] = __('Autohidden menu');
                    }

                    if (config_update_value('visual_animation', get_parameter('visual_animation'), true) === false) {
                        $error_update[] = __('Visual animation');
                    }

                    if (config_update_value('random_background', get_parameter('random_background'), true) === false) {
                        $error_update[] = __('Random background');
                    }

                    if (config_update_value('meta_random_background', get_parameter('meta_random_background'), true) === false) {
                        $error_update[] = __('Random background');
                    }

                    if (config_update_value('disable_help', get_parameter('disable_help'), true) === false) {
                        $error_update[] = __('Disable help');
                    }

                    if (config_update_value('fixed_graph', get_parameter('fixed_graph'), true) === false) {
                        $error_update[] = __('Fixed graph');
                    }

                    if (config_update_value('fixed_header', get_parameter('fixed_header'), true) === false) {
                        $error_update[] = __('Fixed header');
                    }

                    if (config_update_value('paginate_module', get_parameter('paginate_module'), true) === false) {
                        $error_update[] = __('Paginate module');
                    }

                    if (config_update_value('graphviz_bin_dir', get_parameter('graphviz_bin_dir'), true) === false) {
                        $error_update[] = __('Custom graphviz directory');
                    }

                    if (config_update_value('networkmap_max_width', get_parameter('networkmap_max_width'), true) === false) {
                        $error_update[] = __('Networkmap max width');
                    }

                    if (config_update_value('short_module_graph_data', get_parameter('short_module_graph_data'), true) === false) {
                        $error_update[] = __('Shortened module graph data');
                    }

                    if (config_update_value('show_group_name', get_parameter('show_group_name'), true) === false) {
                        $error_update[] = __('Show the group name instead the group icon.');
                    }

                    if (config_update_value('show_empty_groups', get_parameter('show_empty_groups'), true) === false) {
                        $error_update[] = __('Show empty groups in group view.');
                    }

                    if (config_update_value('custom_graph_width', (int) get_parameter('custom_graph_width', 1), true) === false) {
                        $error_update[] = __('Default line thickness for the Custom Graph.');
                    }

                    if (config_update_value('type_module_charts', (string) get_parameter('type_module_charts', 'area'), true) === false) {
                        $error_update[] = __('Default type of module charts.');
                    }

                    if (config_update_value('items_combined_charts', (string) get_parameter('items_combined_charts', 10), true) === false) {
                        $error_update[] = __('Default Number of elements in Custom Graph.');
                    }

                    if (config_update_value('type_interface_charts', (string) get_parameter('type_interface_charts', 'line'), true) === false) {
                        $error_update[] = __('Default type of interface charts.');
                    }

                    if (config_update_value('render_proc', (bool) get_parameter('render_proc', false), true) === false) {
                        $error_update[] = __('Display data of proc modules in other format');
                    }

                    if (config_update_value('render_proc_ok', (string) get_parameter('render_proc_ok', __('Ok')), true) === false) {
                        $error_update[] = __('Display text proc modules have state is ok');
                    }

                    if (config_update_value('render_proc_fail', (string) get_parameter('render_proc_fail', __('Fail')), true) === false) {
                        $error_update[] = __('Display text when proc modules have state critical');
                    }

                    if (config_update_value('click_display', (bool) get_parameter('click_display', false), true) === false) {
                        $error_update[] = __('Display lateral menus with left click');
                    }

                    if (isset($config['enterprise_installed']) === true && (bool) $config['enterprise_installed'] === true) {
                        if (config_update_value('service_label_font_size', get_parameter('service_label_font_size', false), true) === false) {
                            $error_update[] = __('Service label font size');
                        }

                        if (config_update_value('service_item_padding_size', get_parameter('service_item_padding_size', false), true) === false) {
                            $error_update[] = __('Service item padding size');
                        }
                    }

                    if (config_update_value('percentil', (int) get_parameter('percentil', 0), true) === false) {
                        $error_update[] = __('Default percentil');
                    }

                    if (config_update_value('full_scale_option', (int) get_parameter('full_scale_option', 0), true) === false) {
                        $error_update[] = __('Default full scale (TIP)');
                    }

                    if (config_update_value('type_mode_graph', (int) get_parameter('type_mode_graph', 0), true) === false) {
                        $error_update[] = __('Default soft graphs');
                    }

                    if (config_update_value('zoom_graph', (int) get_parameter('zoom_graph', 1), true) === false) {
                        $error_update[] = __('Default zoom graphs');
                    }

                    if (config_update_value(
                        'graph_image_height',
                        (int) get_parameter('graph_image_height', 130)
                    ) === false
                    ) {
                        $error_update[] = __(
                            'Default height of the chart image'
                        );
                    }

                    // --------------------------------------------------
                    // CUSTOM VALUES POST PROCESS
                    // --------------------------------------------------
                    $custom_value = io_safe_input(strip_tags(io_safe_output(get_parameter('custom_value'))));
                    $custom_text = io_safe_input(strip_tags(io_safe_output(get_parameter('custom_text'))));
                    $custom_value_add = (bool) get_parameter('custom_value_add', 0);
                    $custom_value_to_delete = get_parameter('custom_value_to_delete', 0);

                    $custom_value = str_replace(',', '.', $custom_value);

                    if ($custom_value_add === true) {
                        include_once 'include/functions_post_process.php';

                        if (post_process_add_custom_value(
                            $custom_text,
                            (string) $custom_value
                        ) === false
                        ) {
                            $error_update[] = __('Add the custom post process');
                        }
                    }

                    if ($custom_value_to_delete > 0) {
                        include_once 'include/functions_post_process.php';

                        if (post_process_delete_custom_value($custom_value_to_delete) === false) {
                            $error_update[] = __('Delete the custom post process');
                        }
                    }

                    // --------------------------------------------------
                    // --------------------------------------------------
                    // CUSTOM INTERVAL VALUES
                    // --------------------------------------------------
                    $interval_values = get_parameter('interval_values');

                    // Add new interval value if is provided.
                    $interval_value = (float) get_parameter('interval_value', 0);

                    if ($interval_value > 0) {
                        $interval_unit = (int) get_parameter('interval_unit');
                        $new_interval = ($interval_value * $interval_unit);

                        if ($interval_values === '') {
                            $interval_values = $new_interval;
                        } else {
                            $interval_values_array = explode(',', $interval_values);
                            if (in_array($new_interval, $interval_values_array) === false) {
                                $interval_values_array[] = $new_interval;
                                $interval_values = implode(',', $interval_values_array);
                            }
                        }
                    }

                    // Delete interval value if is required.
                    $interval_to_delete = (float) get_parameter('interval_to_delete');
                    if ($interval_to_delete > 0) {
                        $interval_values_array = explode(',', $interval_values);
                        foreach ($interval_values_array as $k => $iva) {
                            if ($interval_to_delete == $iva) {
                                unset($interval_values_array[$k]);
                            }
                        }

                        $interval_values = implode(',', $interval_values_array);
                    }

                    if (config_update_value('interval_values', $interval_values, true) === false) {
                        $error_update[] = __('Delete interval');
                    }

                    // --------------------------------------------------
                    // --------------------------------------------------
                    // MODULE CUSTOM UNITS
                    // --------------------------------------------------
                    $custom_unit = io_safe_input(strip_tags(io_safe_output(get_parameter('custom_module_unit'))));
                    $custom_unit_to_delete = io_safe_input(strip_tags(io_safe_output(get_parameter('custom_module_unit_to_delete', ''))));

                    if (empty($custom_unit) === false) {
                        if (add_custom_module_unit($custom_unit) === false) {
                            $error_update[] = __('Add custom module unit');
                        }
                    }

                    if (empty($custom_unit_to_delete) === false) {
                        if (delete_custom_module_unit($custom_unit_to_delete) === false) {
                            $error_update[] = __('Delete custom module unit');
                        }
                    }

                    if (config_update_value('custom_report_info', get_parameter('custom_report_info'), true) === false) {
                        $error_update[] = __('Custom report info');
                    }

                    if (config_update_value('font_size_item_report', get_parameter('font_size_item_report', 2), true) === false) {
                        $error_update[] = __('HTML font size for SLA (em)');
                    }

                    if (config_update_value('global_font_size_report', get_parameter('global_font_size_report', 10), true) === false) {
                        $error_update[] = __('PDF font size (px)');
                    }

                    if (config_update_value('custom_report_front', get_parameter('custom_report_front'), true) === false) {
                        $error_update[] = __('Custom report front');
                    }

                    if (config_update_value('custom_report_front_font', get_parameter('custom_report_front_font'), true) === false) {
                        $error_update[] = __('Custom report front').' - '.__('Font family');
                    }

                    if (config_update_value('custom_report_front_logo', get_parameter('custom_report_front_logo'), true) === false) {
                        $error_update[] = __('Custom report front').' - '.__('Custom logo');
                    }

                    if (config_update_value('custom_report_front_header', get_parameter('custom_report_front_header'), true) === false) {
                        $error_update[] = __('Custom report front').' - '.__('Header');
                    }

                    if (config_update_value('custom_report_front_firstpage', get_parameter('custom_report_front_firstpage'), true) === false) {
                        $error_update[] = __('Custom report front').' - '.__('First page');
                    }

                    if (config_update_value('custom_report_front_footer', get_parameter('custom_report_front_footer'), true) === false) {
                        $error_update[] = __('Custom report front').' - '.__('Footer');
                    }

                    if (config_update_value('csv_divider', (string) get_parameter('csv_divider', ';'), true) === false) {
                        $error_update[] = __('CSV divider');
                    }

                    if (config_update_value('csv_decimal_separator', (string) get_parameter('csv_decimal_separator', '.'), true) === false) {
                        $error_update[] = __('CSV decimal separator');
                    }

                    if (config_update_value('use_data_multiplier', get_parameter('use_data_multiplier', '1'), true) === false) {
                        $error_update[] = __('Use data multiplier');
                    }

                    if (config_update_value('decimal_separator', (string) get_parameter('decimal_separator', '.'), true) === false) {
                        $error_update[] = __('Decimal separator');
                    } else {
                        $thousand_separator = ((string) get_parameter('decimal_separator', '.') === '.') ? ',' : '.';
                        if (config_update_value('thousand_separator', $thousand_separator, true) === false) {
                            $error_update[] = __('Thousand separator');
                        }
                    }
                break;

                case 'net':
                    if (config_update_value('netflow_name_dir', get_parameter('netflow_name_dir'), true) === false) {
                        $error_update[] = __('Name storage path');
                    } else {
                        if (empty($config['general_network_path']) === false && $config['general_network_path'] !== '') {
                            $name = get_parameter('netflow_name_dir');
                            config_update_value('netflow_path', $config['general_network_path'].$name);
                        }
                    }

                    if (config_update_value('netflow_daemon', get_parameter('netflow_daemon'), true) === false) {
                        $error_update[] = __('Daemon binary path');
                    }

                    if (config_update_value('netflow_nfdump', get_parameter('netflow_nfdump'), true) === false) {
                        $error_update[] = __('Nfdump binary path');
                    }

                    if (config_update_value('netflow_nfexpire', get_parameter('netflow_nfexpire'), true) === false) {
                        $error_update[] = __('Nfexpire binary path');
                    }

                    if (config_update_value('netflow_max_resolution', (int) get_parameter('netflow_max_resolution'), true) === false) {
                        $error_update[] = __('Maximum chart resolution');
                    }

                    if (config_update_value('netflow_disable_custom_lvfilters', get_parameter('netflow_disable_custom_lvfilters'), true) === false) {
                        $error_update[] = __('Disable custom live view filters');
                    }

                    if (config_update_value('netflow_max_lifetime', (int) get_parameter('netflow_max_lifetime'), true) === false) {
                        $error_update[] = __('Netflow max lifetime');
                    }

                    if (config_update_value('netflow_get_ip_hostname', (int) get_parameter('netflow_get_ip_hostname'), true) === false) {
                        $error_update[] = __('Name resolution for IP address');
                    }
                break;

                case 'sflow':
                    if (config_update_value('sflow_name_dir', get_parameter('sflow_name_dir'), true) === false) {
                        $error_update[] = __('Sflow name dir');
                    } else {
                        if (empty($config['general_network_path']) === false && $config['general_network_path'] !== '') {
                            $name = get_parameter('sflow_name_dir');
                            config_update_value('sflow_path', $config['general_network_path'].$name);
                        }
                    }

                    if (config_update_value('sflow_interval', (int) get_parameter('sflow_interval'), true) === false) {
                        $error_update[] = __('Daemon interval');
                    }

                    if (config_update_value('sflow_daemon', get_parameter('sflow_daemon'), true) === false) {
                        $error_update[] = __('Daemon binary path');
                    }

                    if (config_update_value('sflow_nfdump', get_parameter('sflow_nfdump'), true) === false) {
                        $error_update[] = __('Nfdump binary path');
                    }

                    if (config_update_value('sflow_nfexpire', get_parameter('sflow_nfexpire'), true) === false) {
                        $error_update[] = __('Nfexpire binary path');
                    }

                    if (config_update_value('sflow_max_resolution', (int) get_parameter('sflow_max_resolution'), true) === false) {
                        $error_update[] = __('Maximum chart resolution');
                    }

                    if (config_update_value('sflow_disable_custom_lvfilters', get_parameter('sflow_disable_custom_lvfilters'), true) === false) {
                        $error_update[] = __('Disable custom live view filters');
                    }

                    if (config_update_value('sflow_max_lifetime', (int) get_parameter('sflow_max_lifetime'), true) === false) {
                        $error_update[] = __('Sflow max lifetime');
                    }

                    if (config_update_value('sflow_get_ip_hostname', (int) get_parameter('sflow_get_ip_hostname'), true) === false) {
                        $error_update[] = __('Name resolution for IP address');
                    }
                break;

                case 'log':
                    if (config_update_value('elasticsearch_ip', get_parameter('elasticsearch_ip'), true) === false) {
                        $error_update[] = __('IP ElasticSearch server');
                    }

                    if (config_update_value('elasticsearch_port', get_parameter('elasticsearch_port'), true) === false) {
                        $error_update[] = __('Port ElasticSearch server');
                    }

                    if (config_update_value('number_logs_viewed', (int) get_parameter('number_logs_viewed'), true) === false) {
                        $error_update[] = __('Number of logs viewed');
                    }

                    if (config_update_value('Days_purge_old_information', (int) get_parameter('Days_purge_old_information'), true) === false) {
                        $error_update[] = __('Days to purge old information');
                    }
                break;

                case 'hist_db':
                    if ($config['dbname'] === get_parameter('history_db_name')
                        && $config['dbport'] === get_parameter('history_db_port')
                        && $config['dbhost'] === io_input_password(get_parameter('history_db_host'))
                    ) {
                        // Same definition for active and historical database!
                        // This is a critical error.
                        $config['error_config_update_config']['correct'] = false;
                        $config['error_config_update_config']['message'] = __(
                            'Active and historical database cannot be the same.'
                        );
                        return;
                    } else {
                        if (config_update_value('history_db_host', get_parameter('history_db_host'), true) === false) {
                            $error_update[] = __('Host');
                        }

                        if (config_update_value('history_db_port', get_parameter('history_db_port'), true) === false) {
                            $error_update[] = __('Port');
                        }

                        if (config_update_value('history_db_name', get_parameter('history_db_name'), true) === false) {
                            $error_update[] = __('Database name');
                        }
                    }

                    if (config_update_value('history_db_enabled', get_parameter('history_db_enabled'), true) === false) {
                        $error_update[] = __('Enable history database');
                    }

                    if (config_update_value('history_event_enabled', get_parameter('history_event_enabled'), true) === false) {
                        $error_update[] = __('Enable history event');
                    }

                    if (config_update_value('history_trap_enabled', get_parameter('history_trap_enabled'), true) === false) {
                        $error_update[] = __('Enable history trap');
                    }

                    if (config_update_value('history_db_user', get_parameter('history_db_user'), true) === false) {
                        $error_update[] = __('Database user');
                    }

                    if (config_update_value('history_db_pass', io_input_password(get_parameter('history_db_pass')), true) === false) {
                        $error_update[] = __('Database password');
                    }

                    $history_db_days = get_parameter('history_db_days');
                    if (is_numeric($history_db_days) === false
                        || $history_db_days <= 0
                        || config_update_value('history_db_days', $history_db_days) === false
                    ) {
                        $error_update[] = __('Days');
                    }

                    if (config_update_value('history_db_adv', get_parameter_switch('history_db_adv', 0), true) === false) {
                        $error_update[] = __('Enable history database advanced');
                    }

                    $history_db_string_days = get_parameter('history_db_string_days');
                    if ((is_numeric($history_db_string_days) === false
                        || $history_db_string_days <= 0
                        || config_update_value('history_db_string_days', $history_db_string_days) === false)
                        && get_parameter_switch('history_db_adv', 0) === 1
                    ) {
                        $error_update[] = __('String Days');
                    }

                    $history_event_days = get_parameter('history_event_days');
                    if (is_numeric($history_event_days) === false
                        || $history_event_days <= 0
                        || config_update_value('history_event_days', $history_event_days) === false
                    ) {
                        $error_update[] = __('Event Days');
                    }

                    $history_trap_days = get_parameter('history_trap_days');
                    if (is_numeric($history_trap_days) === false
                        || $history_trap_days <= 0
                        || config_update_value('history_trap_days', $history_trap_days) === false
                    ) {
                        $error_update[] = __('Trap Days');
                    }

                    $trap_history_purge = get_parameter('history_traps_days_purge');
                    if (is_numeric($trap_history_purge) === false
                        || $trap_history_purge <= 0
                        || config_update_value('trap_history_purge', $trap_history_purge) === false
                    ) {
                        $error_update[] = __('Trap history purge');
                    }

                    $history_db_step = get_parameter('history_db_step');
                    if (!is_numeric($history_db_step)
                        || $history_db_step <= 0
                        || !config_update_value('history_db_step', $history_db_step)
                    ) {
                        $error_update[] = __('Step');
                    }

                    $history_db_delay = get_parameter('history_db_delay');
                    if (!is_numeric($history_db_delay)
                        || $history_db_delay <= 0
                        || !config_update_value('history_db_delay', $history_db_delay)
                    ) {
                        $error_update[] = __('Delay');
                    }

                    if ((bool) $config['history_db_enabled'] === true) {
                        $dbm = new DBMaintainer(
                            [
                                'host' => $config['history_db_host'],
                                'port' => $config['history_db_port'],
                                'name' => $config['history_db_name'],
                                'user' => $config['history_db_user'],
                                'pass' => io_output_password($config['history_db_pass']),
                            ]
                        );

                        // Performs several checks and installs if needed.
                        if ($dbm->checkDatabaseDefinition() === true
                            && $dbm->isInstalled() === false
                        ) {
                            // Target is ready but several tasks are pending.
                            $dbm->process();
                        } else if ($dbm->check() !== true) {
                            $errors[] = $dbm->getLastError();
                            config_update_value('history_db_enabled', false);
                        }

                        if ($dbm->check() === true) {
                            // Historical configuration tokens (stored in historical db).
                            if ($dbm->setConfigToken(
                                'days_purge',
                                get_parameter('history_dbh_purge')
                            ) !== true
                            ) {
                                $error_update[] = __('Historical database purge');
                            }

                            if ($dbm->setConfigToken(
                                'history_partitions_auto',
                                get_parameter_switch('history_partitions_auto', 0)
                            ) !== true
                            ) {
                                $error_update[] = __('Historical database partitions');
                            }

                            if ($dbm->setConfigToken(
                                'event_purge',
                                get_parameter('history_dbh_events_purge')
                            ) !== true
                            ) {
                                $error_update[] = __('Historical database events purge');
                            }

                            if ($dbm->setConfigToken(
                                'trap_history_purge',
                                get_parameter('history_traps_days_purge')
                            ) !== true
                            ) {
                                $error_update[] = __('Historical database traps purge');
                            }

                            if ($dbm->setConfigToken(
                                'string_purge',
                                get_parameter('history_dbh_string_purge')
                            ) !== true
                            ) {
                                $error_update[] = __('Historical database string purge');
                            }

                            // Disable history db in history db.
                            $dbm->setConfigToken('history_db_enabled', 0);
                        }
                    }
                break;

                case 'ehorus':
                    if (config_update_value('ehorus_enabled', (int) get_parameter('ehorus_enabled', 0), true) === false) {
                        $error_update[] = __('Enable eHorus');
                    }

                    if (config_update_value('ehorus_user_level_conf', (int) get_parameter('ehorus_user_level_conf', 0), true) === false) {
                        $error_update[] = __('eHorus user login');
                    }

                    if (config_update_value('ehorus_user', (string) get_parameter('ehorus_user', $config['ehorus_user']), true) === false) {
                        $error_update[] = __('eHorus user');
                    }

                    if (config_update_value('ehorus_pass', io_input_password((string) get_parameter('ehorus_pass', $config['ehorus_pass'])), true) === false) {
                        $error_update[] = __('eHorus password');
                    }

                    if (config_update_value('ehorus_hostname', (string) get_parameter('ehorus_hostname', $config['ehorus_hostname']), true) === false) {
                        $error_update[] = __('eHorus API hostname');
                    }

                    if (config_update_value('ehorus_port', (int) get_parameter('ehorus_port', $config['ehorus_port']), true) === false) {
                        $error_update[] = __('eHorus API port');
                    }

                    if (config_update_value('ehorus_req_timeout', (int) get_parameter('ehorus_req_timeout', $config['ehorus_req_timeout']), true) === false) {
                        $error_update[] = __('eHorus request timeout');
                    }

                    if (config_update_value('ehorus_custom_field', (string) get_parameter('ehorus_custom_field', $config['ehorus_custom_field']), true) === false) {
                        $error_update[] = __('eHorus id custom field');
                    }
                break;

                case 'integria':
                    if (config_update_value('integria_user_level_conf', (int) get_parameter('integria_user_level_conf', 0), true) === false) {
                        $error_update[] = __('Integria user login');
                    }

                    if (config_update_value('integria_enabled', (int) get_parameter('integria_enabled', 0), true) === false) {
                        $error_update[] = __('Enable Integria IMS');
                    }

                    if (config_update_value('integria_user', (string) get_parameter('integria_user', $config['integria_user']), true) === false) {
                        $error_update[] = __('Integria user');
                    }

                    if (config_update_value('integria_pass', io_input_password((string) get_parameter('integria_pass', $config['integria_pass'])), true) === false) {
                        $error_update[] = __('Integria password');
                    }

                    $integria_hostname = (string) get_parameter('integria_hostname', $config['integria_hostname']);

                    if (parse_url($integria_hostname, PHP_URL_SCHEME) === null) {
                        if (empty($_SERVER['HTTPS']) === false) {
                            $integria_hostname = 'https://'.$integria_hostname;
                        } else {
                            $integria_hostname = 'http://'.$integria_hostname;
                        }
                    }

                    if (config_update_value('integria_hostname', $integria_hostname, true) === false) {
                        $error_update[] = __('integria API hostname');
                    }

                    if (config_update_value('integria_api_pass', io_input_password((string) get_parameter('integria_api_pass', $config['integria_api_pass'])), true) === false) {
                        $error_update[] = __('Integria API password');
                    }

                    if (config_update_value('integria_req_timeout', (int) get_parameter('integria_req_timeout', $config['integria_req_timeout']), true) === false) {
                        $error_update[] = __('Integria request timeout');
                    }

                    if (config_update_value('default_group', (int) get_parameter('default_group', $config['default_group']), true) === false) {
                        $error_update[] = __('Integria default group');
                    }

                    if (config_update_value('cr_default_group', (int) get_parameter('cr_default_group', $config['cr_default_group']), true) === false) {
                        $error_update[] = __('Integria custom response default group');
                    }

                    if (config_update_value('default_criticity', (int) get_parameter('default_criticity', $config['default_criticity']), true) === false) {
                        $error_update[] = __('Integria default priority');
                    }

                    if (config_update_value('cr_default_criticity', (int) get_parameter('cr_default_criticity', $config['cr_default_criticity']), true) === false) {
                        $error_update[] = __('Integria custom response default priority');
                    }

                    if (config_update_value('default_creator', (string) get_parameter('default_creator', $config['default_creator']), true) === false) {
                        $error_update[] = __('Integria default creator');
                    }

                    if (config_update_value('default_owner', (string) get_parameter('default_owner', $config['default_owner']), true) === false) {
                        $error_update[] = __('Integria default owner');
                    }

                    if (config_update_value('cr_default_owner', (string) get_parameter('cr_default_owner', $config['cr_default_owner']), true) === false) {
                        $error_update[] = __('Integria custom response default owner');
                    }

                    if (config_update_value('incident_type', (int) get_parameter('incident_type', $config['incident_type']), true) === false) {
                        $error_update[] = __('Integria default ticket type');
                    }

                    if (config_update_value('cr_incident_type', (int) get_parameter('cr_incident_type', $config['cr_incident_type']), true) === false) {
                        $error_update[] = __('Integria custom response default ticket type');
                    }

                    if (config_update_value('incident_status', (int) get_parameter('incident_status', $config['incident_status']), true) === false) {
                        $error_update[] = __('Integria default ticket status');
                    }

                    if (config_update_value('cr_incident_status', (int) get_parameter('cr_incident_status', $config['cr_incident_status']), true) === false) {
                        $error_update[] = __('Integria custom response default ticket status');
                    }

                    if (config_update_value('incident_title', (string) get_parameter('incident_title', $config['incident_title']), true) === false) {
                        $error_update[] = __('Integria default ticket title');
                    }

                    if (config_update_value('cr_incident_title', (string) get_parameter('cr_incident_title', $config['cr_incident_title']), true) === false) {
                        $error_update[] = __('Integria custom response default ticket title');
                    }

                    if (config_update_value('incident_content', (string) get_parameter('incident_content', $config['incident_content']), true) === false) {
                        $error_update[] = __('Integria default ticket content');
                    }

                    if (config_update_value('cr_incident_content', (string) get_parameter('cr_incident_content', $config['cr_incident_content']), true) === false) {
                        $error_update[] = __('Integria custom response default ticket content');
                    }
                break;

                case 'module_library':
                    if (config_update_value('module_library_user', get_parameter('module_library_user'), true) === false) {
                        $error_update[] = __('Module Library User');
                    }

                    if (config_update_value('module_library_password', get_parameter('module_library_password'), true) === false) {
                        $error_update[] = __('Module Library Password');
                    }
                break;

                case 'websocket_engine':
                    if (config_update_value('ws_bind_address', get_parameter('ws_bind_address'), true) === false) {
                        $error_update[] = __('WebSocket bind address');
                    }

                    if (config_update_value('ws_port', get_parameter('ws_port'), true) === false) {
                        $error_update[] = __('WebSocket port');
                    }

                    if (config_update_value('ws_proxy_url', get_parameter('ws_proxy_url'), true) === false) {
                        $error_update[] = __('WebSocket proxy url');
                    }
                break;

                default:
                    // Ignore.
                break;
            }

        default:
            // Ignore.
        break;
    }

    if (count($error_update) > 0) {
        $config['error_config_update_config'] = [];
        $config['error_config_update_config']['correct'] = false;
        $values = implode('<br> -', $error_update);
        $config['error_config_update_config']['message'] = sprintf(
            __('Update failed. The next values could not be updated: <br> -%s'),
            $values
        );

        db_pandora_audit(
            AUDIT_LOG_SETUP,
            'Failed changing Setup',
            false,
            false,
            $config['error_config_update_config']['message']
        );
    } else {
        $config['error_config_update_config'] = [];
        $config['error_config_update_config']['correct'] = true;

        db_pandora_audit(
            AUDIT_LOG_SETUP,
            'Setup has changed'
        );
    }

    if (count($errors) > 0) {
        $config['error_config_update_config']['errors'] = $errors;
    }

    if (count($warnings) > 0) {
        $config['error_config_update_config']['warnings'] = $warnings;
    }

    enterprise_include_once('include/functions_policies.php');
    $enterprise = enterprise_include_once('include/functions_skins.php');
    if ($enterprise !== ENTERPRISE_NOT_HOOK) {
        $config['relative_path'] = get_parameter('relative_path', $config['relative_path']);
    }
}


/**
 * Process config variables.
 *
 * @return void
 */
function config_process_config()
{
    global $config;

    $configs = db_get_all_rows_in_table('tconfig');

    if (empty($configs)) {
        include $config['homedir'].'/general/error_emptyconfig.php';
        exit;
    }

    $is_windows = false;
    if (substr(strtolower(PHP_OS), 0, 3) === 'win') {
        $is_windows = true;
    }

    // Compatibility fix.
    foreach ($configs as $c) {
        $config[$c['token']] = $c['value'];
    }

    if (!isset($config['language'])) {
        config_update_value('language', 'en');
    }

    if (isset($config['homeurl']) && (strlen($config['homeurl']) > 0)) {
        if ($config['homeurl'][0] != '/') {
            $config['homeurl'] = '/'.$config['homeurl'];
        }
    }

    if (!isset($config['remote_config'])) {
        if ($is_windows) {
            $default = 'C:\PandoraFMS\Pandora_Server\data_in';
        } else {
            $default = '/var/spool/pandora/data_in';
        }

        config_update_value('remote_config', $default);
    }

    if (isset($config['chromium_path']) === false) {
        $default = '/usr/bin/chromium-browser';
        config_update_value('chromium_path', $default);
    }

    if (!isset($config['date_format'])) {
        config_update_value('date_format', 'F j, Y, g:i a');
    }

    if (!isset($config['event_view_hr'])) {
        config_update_value('event_view_hr', 8);
    }

    if (!isset($config['report_limit'])) {
        config_update_value('report_limit', 100);
    }

    if (!isset($config['events_per_query'])) {
        config_update_value('events_per_query', 5000);
    }

    if (!isset($config['loginhash_pwd'])) {
        config_update_value('loginhash_pwd', io_input_password((rand(0, 1000) * rand(0, 1000)).'pandorahash'));
    }

    if (!isset($config['trap2agent'])) {
        config_update_value('trap2agent', 0);
    }

    if (!isset($config['prominent_time'])) {
        // Prominent time tells us what to show prominently when a timestamp is
        // displayed. The comparation (... days ago) or the timestamp (full date).
        config_update_value('prominent_time', 'comparation');
    }

    if (!isset($config['timesource'])) {
        // Timesource says where time comes from (system or mysql).
        config_update_value('timesource', 'system');
    }

    if (!isset($config['https'])) {
        // Sets whether or not we want to enforce https. We don't want to go to a
        // potentially unexisting config by default.
        config_update_value('https', false);
    }

    if (!isset($config['use_cert'])) {
        config_update_value('use_cert', false);
    }

    if (!isset($config['cert_path'])) {
        // Sets name and path of ssl path for use in application.
        config_update_value('cert_path', '/etc/ssl/certs/pandorafms.pem');
    }

    if (!isset($config['num_files_attachment'])) {
        config_update_value('num_files_attachment', 100);
    }

    if (!isset($config['status_images_set'])) {
        config_update_value('status_images_set', 'default');
    }

    // Load user session.
    if (isset($_SESSION['id_usuario'])) {
        $config['id_user'] = $_SESSION['id_usuario'];
    }

    if (!isset($config['round_corner'])) {
        config_update_value('round_corner', false);
    }

    if (isset($config['maximum_y_axis']) === false) {
        config_update_value('maximum_y_axis', false);
    }

    if (!isset($config['show_qr_code_header'])) {
        config_update_value('show_qr_code_header', false);
    }

    if (!isset($config['agentaccess'])) {
        config_update_value('agentaccess', true);
    }

    if (!isset($config['timezone'])) {
        config_update_value('timezone', 'Europe/Berlin');
    }

    if (!isset($config['stats_interval'])) {
        config_update_value('stats_interval', SECONDS_5MINUTES);
    }

    if (!isset($config['realtimestats'])) {
        config_update_value('realtimestats', 1);
    }

    if (!isset($config['delete_notinit'])) {
        config_update_value('delete_notinit', 0);
    }

    if (!isset($config['big_operation_step_datos_purge'])) {
        config_update_value('big_operation_step_datos_purge', 100);
    }

    if (!isset($config['small_operation_step_datos_purge'])) {
        config_update_value('small_operation_step_datos_purge', 1000);
    }

    if (!isset($config['num_past_special_days'])) {
        config_update_value('num_past_special_days', 0);
    }

    if (isset($config['enterprise_installed'])) {
        if (!isset($config['inventory_purge'])) {
            config_update_value('inventory_purge', 21);
        }
    }

    if (!isset($config['delete_old_messages'])) {
        config_update_value('delete_old_messages', 21);
    }

    if (!isset($config['delete_old_network_matrix'])) {
        config_update_value('delete_old_network_matrix', 10);
    }

    if (!isset($config['max_graph_container'])) {
        config_update_value('max_graph_container', 10);
    }

    if (!isset($config['max_execution_event_response'])) {
        config_update_value('max_execution_event_response', 10);
    }

    if (!isset($config['max_number_of_events_per_node'])) {
        config_update_value('max_number_of_events_per_node', 100000);
    }

    if (!isset($config['max_macro_fields'])) {
        config_update_value('max_macro_fields', 10);
    }

    if (!isset($config['row_limit_csv'])) {
        config_update_value('row_limit_csv', 10000);
    }

    if (!isset($config['snmpwalk'])) {
        switch (PHP_OS) {
            case 'FreeBSD':
                config_update_value('snmpwalk', '/usr/local/bin/snmpwalk');
            break;

            case 'NetBSD':
                config_update_value('snmpwalk', '/usr/pkg/bin/snmpwalk');
            break;

            case 'WIN32':
            case 'WINNT':
            case 'Windows':
                config_update_value('snmpwalk', 'snmpwalk');
            break;

            default:
                config_update_value('snmpwalk', 'snmpbulkwalk');
            break;
        }
    }

    if (!isset($config['snmpwalk_fallback'])) {
        config_update_value('snmpwalk_fallback', 'snmpwalk');
    }

    if (isset($config['wmiBinary']) === false) {
        config_update_value('wmiBinary', 'pandorawmic');
    }

    if (!isset($config['event_purge'])) {
        config_update_value('event_purge', 15);
    }

    if (!isset($config['realtimestats'])) {
        config_update_value('realtimestats', 1);
    }

    if (!isset($config['trap_purge'])) {
        config_update_value('trap_purge', 7);
    }

    if (!isset($config['string_purge'])) {
        config_update_value('string_purge', 14);
    }

    if (!isset($config['audit_purge'])) {
        config_update_value('audit_purge', 30);
    }

    if (!isset($config['acl_enterprise'])) {
        config_update_value('acl_enterprise', 0);
    }

    if (!isset($config['metaconsole'])) {
        config_update_value('metaconsole', 0);
    }

    if (!isset($config['gis_purge'])) {
        config_update_value('gis_purge', 7);
    }

    if (!isset($config['collection_max_size'])) {
        config_update_value('collection_max_size', 1000000);
    }

    if (!isset($config['policy_add_max_agents'])) {
        config_update_value('policy_add_max_agents', 200);
    }

    if (!isset($config['replication_dbhost'])) {
        config_update_value('replication_dbhost', '');
    }

    if (!isset($config['replication_dbname'])) {
        config_update_value('replication_dbname', '');
    }

    if (!isset($config['replication_dbuser'])) {
        config_update_value('replication_dbuser', '');
    }

    if (!isset($config['replication_dbpass'])) {
        config_update_value('replication_dbpass', '');
    }

    if (!isset($config['replication_dbport'])) {
        config_update_value('replication_dbport', '');
    }

    if (!isset($config['metaconsole_agent_cache'])) {
        config_update_value('metaconsole_agent_cache', 0);
    }

    if (!isset($config['log_collector'])) {
        config_update_value('log_collector', 0);
    }

    if (!isset($config['enable_update_manager'])) {
        config_update_value('enable_update_manager', 1);
    }

    if (!isset($config['legacy_database_ha'])) {
        config_update_value('legacy_database_ha', 0);
    }

    if (!isset($config['disabled_newsletter'])) {
        config_update_value('disabled_newsletter', 0);
    }

    if (!isset($config['ipam_ocuppied_critical_treshold'])) {
        config_update_value('ipam_ocuppied_critical_treshold', 90);
    }

    if (!isset($config['ipam_ocuppied_warning_treshold'])) {
        config_update_value('ipam_ocuppied_warning_treshold', 80);
    }

    if (!isset($config['reset_pass_option'])) {
        config_update_value('reset_pass_option', 0);
    }

    if (isset($config['exclusion_word_list']) === false) {
        config_update_value('exclusion_word_list', '');
    }

    if (!isset($config['include_agents'])) {
        config_update_value('include_agents', 0);
    }

    if (!isset($config['alias_as_name'])) {
        config_update_value('alias_as_name', 0);
    }

    if (!isset($config['keep_in_process_status_extra_id'])) {
        config_update_value('keep_in_process_status_extra_id', 0);
    }

    if (!isset($config['console_log_enabled'])) {
        config_update_value('console_log_enabled', 0);
    }

    if (!isset($config['audit_log_enabled'])) {
        config_update_value('audit_log_enabled', 0);
    }

    if (!isset($config['module_custom_id_ro'])) {
        config_update_value('module_custom_id_ro', 0);
    }

    if (!isset($config['reporting_console_enable'])) {
        config_update_value('reporting_console_enable', 0);
    }

    if (!isset($config['check_conexion_interval'])) {
        config_update_value('check_conexion_interval', 180);
    }

    if (!isset($config['elasticsearch_ip'])) {
        config_update_value('elasticsearch_ip', '');
    }

    if (!isset($config['elasticsearch_port'])) {
        config_update_value('elasticsearch_port', 9200);
    }

    if (!isset($config['number_logs_viewed'])) {
        config_update_value('number_logs_viewed', 50);
    }

    if (!isset($config['Days_purge_old_information'])) {
        config_update_value('Days_purge_old_information', 90);
    }

    if (!isset($config['font_size'])) {
        config_update_value('font_size', 8);
    }

    if (!isset($config['limit_parameters_massive'])) {
        config_update_value('limit_parameters_massive', (ini_get('max_input_vars') / 2));
    }

    if (!isset($config['unique_ip'])) {
        config_update_value('unique_ip', 0);
    }

    if (!isset($config['welcome_state'])) {
        config_update_value('welcome_state', WELCOME_STARTED);
    }

    if (!isset($config['2Fa_auth'])) {
        config_update_value('2Fa_auth', '');
    }

    if (isset($config['performance_variables_control']) === false) {
        config_update_value(
            'performance_variables_control',
            json_encode(
                [
                    'event_purge'                      => [
                        'max' => 45,
                        'min' => 1,
                    ],
                    'trap_purge'                       => [
                        'max' => 45,
                        'min' => 1,
                    ],
                    'audit_purge'                      => [
                        'max' => 365,
                        'min' => 7,
                    ],
                    'string_purge'                     => [
                        'max' => 365,
                        'min' => 7,
                    ],
                    'gis_purge'                        => [
                        'max' => 365,
                        'min' => 7,
                    ],
                    'days_purge'                       => [
                        'max' => 365,
                        'min' => 7,
                    ],
                    'days_compact'                     => [
                        'max' => 365,
                        'min' => 0,
                    ],
                    'days_delete_unknown'              => [
                        'max' => 90,
                        'min' => 0,
                    ],
                    'days_delete_not_initialized'      => [
                        'max' => 90,
                        'min' => 0,
                    ],
                    'days_autodisable_deletion'        => [
                        'max' => 90,
                        'min' => 0,
                    ],
                    'delete_old_network_matrix'        => [
                        'max' => 30,
                        'min' => 1,
                    ],
                    'report_limit'                     => [
                        'max' => 500,
                        'min' => 1,
                    ],
                    'event_view_hr'                    => [
                        'max' => 360,
                        'min' => 1,
                    ],
                    'big_operation_step_datos_purge'   => [
                        'max' => 10000,
                        'min' => 100,
                    ],
                    'small_operation_step_datos_purge' => [
                        'max' => 10000,
                        'min' => 100,
                    ],
                    'row_limit_csv'                    => [
                        'max' => 1000000,
                        'min' => 1,
                    ],
                    'limit_parameters_massive'         => [
                        'max' => 2000,
                        'min' => 100,
                    ],
                    'block_size'                       => [
                        'max' => 200,
                        'min' => 10,
                    ],
                    'short_module_graph_data'          => [
                        'max' => 20,
                        'min' => 1,
                    ],
                    'graph_precision'                  => [
                        'max' => 5,
                        'min' => 1,
                    ],
                ]
            )
        );
    }

    if (isset($config['agent_wizard_defaults']) === false) {
        config_update_value(
            'agent_wizard_defaults',
            json_encode(
                [
                    'ifOperStatus'    => 1,
                    'ifInOctets'      => 1,
                    'ifOutOctets'     => 1,
                    'ifInUcastPkts'   => 0,
                    'ifOutUcastPkts'  => 0,
                    'ifInNUcastPkts'  => 0,
                    'ifOutNUcastPkts' => 0,
                    'locIfInCRC'      => 1,
                    'Bandwidth'       => 1,
                    'inUsage'         => 1,
                    'outUsage'        => 1,
                    'ifAdminStatus'   => 0,
                    'ifInDiscards'    => 0,
                    'ifOutDiscards'   => 0,
                    'ifInErrors'      => 0,
                    'ifOutErrors'     => 0,
                ]
            )
        );
    }

    /*
     * Parse the ACL IP list for access API
     */

    $temp_list_ACL_IPs_for_API = [];
    if (isset($config['list_ACL_IPs_for_API'])) {
        if (!empty($config['list_ACL_IPs_for_API'])) {
            $temp_list_ACL_IPs_for_API = explode(';', $config['list_ACL_IPs_for_API']);
        }
    }

    $config['list_ACL_IPs_for_API'] = $temp_list_ACL_IPs_for_API;
    $keysConfig = array_keys($config);

    /*
     * This is not set here. The first time, when no
     * setup is done, update_manager extension manage it
     * the first time make a conenction and disable itself
     * Not Managed here !
     * if (!isset ($config["autoupdate"])) {
     * config_update_value ('autoupdate', true);.
     * }
     */

    include_once $config['homedir'].'/include/auth/mysql.php';
    include_once $config['homedir'].'/include/functions_io.php';

    // Next is the directory where "/attachment" directory is placed,
    // to upload files stores. This MUST be writtable by http server
    // user, and should be in pandora root. By default, Pandora adds
    // /attachment to this, so by default is the pandora console home
    // dir.
    $attachment_store_path = $config['homedir'].'/attachment';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows.
        $attachment_store_path = $config['homedir'].'\attachment';
    }

    if (!isset($config['attachment_store'])) {
        config_update_value(
            'attachment_store',
            $attachment_store_path
        );
    } else {
        // Fixed when the user moves the pandora console to another dir
        // after the first uses.
        if (!is_dir($config['attachment_store'])) {
            config_update_value(
                'attachment_store',
                $attachment_store_path
            );
        }
    }

    if (!isset($config['fontpath'])) {
        config_update_value(
            'fontpath',
            'Lato-Regular.ttf'
        );
    }

    if (!isset($config['style'])) {
        config_update_value('style', 'pandora');
    }

    if (!isset($config['login_background'])) {
        config_update_value('login_background', '');
    }

    if (!isset($config['login_background_meta'])) {
        config_update_value('login_background_meta', 'background_metaconsole.png');
    }

    if (!isset($config['paginate_module'])) {
        config_update_value('paginate_module', false);
    }

    if (!isset($config['graphviz_bin_dir'])) {
        config_update_value('graphviz_bin_dir', '');
    }

    if (!isset($config['disable_help'])) {
        config_update_value('disable_help', false);
    }

    if (!isset($config['fixed_header'])) {
        config_update_value('fixed_header', false);
    }

    if (!isset($config['fixed_graph'])) {
        config_update_value('fixed_graph', false);
    }

    if (!isset($config['custom_favicon'])) {
        config_update_value('custom_favicon', '');
    }

    if (isset($config['custom_logo']) === false) {
        config_update_value('custom_logo', HEADER_LOGO_DEFAULT_CLASSIC);
    }

    if (isset($config['custom_logo_collapsed']) === false) {
        config_update_value('custom_logo_collapsed', HEADER_LOGO_DEFAULT_COLLAPSED);
    }

    if (is_metaconsole()) {
        if (!isset($config['meta_custom_logo'])) {
            config_update_value('meta_custom_logo', 'pandoraFMS_metaconsole_full.svg');
        }

        if (!isset($config['meta_custom_logo_collapsed'])) {
            config_update_value('meta_custom_logo_collapsed', 'pandoraFMS_metaconsole_collapse.svg');
        }

        if (!isset($config['meta_menu_type'])) {
            config_update_value('meta_menu_type', 'classic');
        }
    }

    if (!isset($config['custom_logo_white_bg'])) {
        config_update_value('custom_logo_white_bg', 'pandora_logo_head_white_bg.png');
    }

    if (!isset($config['custom_logo_login'])) {
        config_update_value('custom_logo_login', 'Pandora-FMS-1.png');
    }

    if (!isset($config['custom_splash_login'])) {
        config_update_value('custom_splash_login', 'none.png');
    }

    if (!isset($config['custom_docs_logo'])) {
        config_update_value('custom_docs_logo', '');
    }

    if (!isset($config['custom_support_logo'])) {
        config_update_value('custom_support_logo', '');
    }

    if (!isset($config['custom_network_center_logo'])) {
        config_update_value('custom_network_center_logo', '');
    }

    if (!isset($config['custom_mobile_console_logo'])) {
        config_update_value('custom_mobile_console_logo', '');
    }

    if (!isset($config['custom_title_header'])) {
        config_update_value('custom_title_header', __('Pandora FMS'));
    }

    if (!isset($config['custom_subtitle_header'])) {
        config_update_value('custom_subtitle_header', __('the Flexible Monitoring System'));
    }

    if (!isset($config['meta_custom_title_header'])) {
        config_update_value('meta_custom_title_header', __('PandoraFMS Metaconsole'));
    }

    if (!isset($config['meta_custom_subtitle_header'])) {
        config_update_value('meta_custom_subtitle_header', __('Centralized operation console'));
    }

    if (!isset($config['custom_title1_login'])) {
        config_update_value('custom_title1_login', __('ONE TOOL TO RULE THEM ALL'));
    }

    if (!isset($config['custom_title2_login'])) {
        config_update_value('custom_title2_login', '');
    }

    if (!isset($config['custom_docs_url'])) {
        config_update_value('custom_docs_url', 'https://pandorafms.com/manual');
    }

    if (!isset($config['custom_support_url'])) {
        config_update_value('custom_support_url', 'https://support.pandorafms.com');
    }

    if (!isset($config['rb_product_name'])) {
        config_update_value('rb_product_name', get_product_name());
    }

    if (!isset($config['rb_copyright_notice'])) {
        config_update_value('rb_copyright_notice', get_copyright_notice());
    }

    if (!isset($config['background_opacity'])) {
        config_update_value('background_opacity', 20);
    }

    if (!isset($config['meta_background_opacity'])) {
        config_update_value('meta_background_opacity', 30);
    }

    if (!isset($config['meta_custom_docs_url'])) {
        config_update_value('meta_custom_docs_url', 'https://pandorafms.com/manual/');
    }

    if (!isset($config['meta_custom_support_url'])) {
        config_update_value('meta_custom_support_url', 'https://support.pandorafms.com');
    }

    if (!isset($config['meta_custom_logo_white_bg'])) {
        config_update_value('pandora_logo_head_white_bg', 'pandora_logo_head_white_bg.png');
    }

    if (!isset($config['meta_custom_logo_login'])) {
        config_update_value('meta_custom_logo_login', 'Pandora-FMS-1.png');
    }

    if (!isset($config['meta_custom_splash_login'])) {
        config_update_value('meta_custom_splash_login', 'default');
    }

    if (!isset($config['meta_custom_title1_login'])) {
        config_update_value('meta_custom_title1_login', __('ONE TOOL TO RULE THEM ALL'));
    }

    if (!isset($config['meta_custom_title2_login'])) {
        config_update_value('meta_custom_title2_login', __('COMMAND CENTER'));
    }

    if (!isset($config['vc_favourite_view'])) {
        config_update_value('vc_favourite_view', 0);
    }

    if (!isset($config['vc_menu_items'])) {
        config_update_value('vc_menu_items', 10);
    }

    if (!isset($config['ser_menu_items'])) {
        config_update_value('ser_menu_items', 10);
    }

    if (!isset($config['history_db_enabled'])) {
        config_update_value('history_db_enabled', false);
    }

    if (!isset($config['history_event_enabled'])) {
        config_update_value('history_event_enabled', false);
    }

    if (!isset($config['history_trap_enabled'])) {
        config_update_value('history_trap_enabled', false);
    }

    if (!isset($config['history_db_host'])) {
        config_update_value('history_db_host', '');
    }

    if (!isset($config['history_db_port'])) {
        config_update_value('history_db_port', 3306);
    }

    if (!isset($config['history_db_name'])) {
        config_update_value('history_db_name', 'pandora');
    }

    if (!isset($config['history_db_user'])) {
        config_update_value('history_db_user', 'pandora');
    }

    if (!isset($config['history_db_pass'])) {
        config_update_value('history_db_pass', '');
    }

    if (!isset($config['history_db_days'])) {
        config_update_value('history_db_days', 0);
    }

    if (!isset($config['history_db_adv'])) {
        config_update_value('history_db_adv', false);
    }

    if (!isset($config['history_db_string_days'])) {
        config_update_value('history_db_string_days', 0);
    }

    if (!isset($config['history_event_days'])) {
        config_update_value('history_event_days', 90);
    }

    if (!isset($config['history_trap_days'])) {
        config_update_value('history_trap_days', 90);
    }

    if (!isset($config['trap_history_purge'])) {
        config_update_value('trap_history_purge', 180);
    }

    if (!isset($config['history_db_step'])) {
        config_update_value('history_db_step', 0);
    }

    if (!isset($config['history_db_delay'])) {
        config_update_value('history_db_delay', 0);
    }

    if (!isset($config['email_from_dir'])) {
        config_update_value('email_from_dir', 'pandora@pandorafms.org');
    }

    if (!isset($config['email_from_name'])) {
        config_update_value('email_from_name', 'Pandora FMS');
    }

    if (!isset($config['email_smtpServer'])) {
        config_update_value('email_smtpServer', '127.0.0.1');
    }

    if (!isset($config['email_smtpPort'])) {
        config_update_value('email_smtpPort', 25);
    }

    if (!isset($config['email_encryption'])) {
        config_update_value('email_encryption', 0);
    }

    if (!isset($config['email_username'])) {
        config_update_value('email_username', '');
    }

    if (!isset($config['email_password'])) {
        config_update_value('email_password', '');
    }

    if (!isset($config['activate_gis'])) {
        config_update_value('activate_gis', 0);
    }

    if (!isset($config['activate_netflow'])) {
        config_update_value('activate_netflow', 0);
    }

    if (!isset($config['activate_sflow'])) {
        config_update_value('activate_sflow', 0);
    }

    if (!isset($config['general_network_path'])) {
        if ($is_windows) {
            $default = 'C:\PandoraFMS\Pandora_Server\data_in\\';
        } else {
            $default = '/var/spool/pandora/data_in/';
        }

        config_update_value('general_network_path', $default);
    }

    if (!isset($config['netflow_name_dir'])) {
        config_update_value('netflow_name_dir', 'netflow');
    }

    if (!isset($config['sflow_name_dir'])) {
        config_update_value('sflow_name_dir', 'sflow');
    }

    if (!isset($config['netflow_path'])) {
        if ($is_windows) {
            $default = 'C:\PandoraFMS\Pandora_Server\data_in\netflow';
        } else {
            $default = '/var/spool/pandora/data_in/netflow';
        }

        config_update_value('netflow_path', $default);
    }

    if (!isset($config['netflow_daemon'])) {
        config_update_value('netflow_daemon', '/usr/bin/nfcapd');
    }

    if (!isset($config['netflow_nfdump'])) {
        config_update_value('netflow_nfdump', '/usr/bin/nfdump');
    }

    if (!isset($config['netflow_nfexpire'])) {
        config_update_value('netflow_nfexpire', '/usr/bin/nfexpire');
    }

    if (!isset($config['netflow_max_resolution'])) {
        config_update_value('netflow_max_resolution', '50');
    }

    if (!isset($config['netflow_disable_custom_lvfilters'])) {
        config_update_value('netflow_disable_custom_lvfilters', 0);
    }

    if (!isset($config['netflow_max_lifetime'])) {
        config_update_value('netflow_max_lifetime', '5');
    }

    if (!isset($config['sflow_interval'])) {
        config_update_value('sflow_interval', SECONDS_10MINUTES);
    }

    if (!isset($config['sflow_daemon'])) {
        config_update_value('sflow_daemon', '/usr/bin/sfcapd');
    }

    if (!isset($config['sflow_nfdump'])) {
        config_update_value('sflow_nfdump', '/usr/bin/nfdump');
    }

    if (!isset($config['sflow_nfexpire'])) {
        config_update_value('sflow_nfexpire', '/usr/bin/nfexpire');
    }

    if (!isset($config['sflow_max_resolution'])) {
        config_update_value('sflow_max_resolution', '50');
    }

    if (!isset($config['sflow_disable_custom_lvfilters'])) {
        config_update_value('sflow_disable_custom_lvfilters', 0);
    }

    if (!isset($config['sflow_max_lifetime'])) {
        config_update_value('sflow_max_lifetime', '5');
    }

    if (!isset($config['sflow_name_dir'])) {
        config_update_value('sflow_name_dir', 'sflow');
    }

    if (!isset($config['sflow_path'])) {
        if ($is_windows) {
            $default = 'C:\PandoraFMS\Pandora_Server\data_in\sflow';
        } else {
            $default = '/var/spool/pandora/data_in/sflow';
        }

        config_update_value('sflow_path', $default);
    }

    if (!isset($config['auth'])) {
        config_update_value('auth', 'mysql');
    }

    if (!isset($config['autocreate_remote_users'])) {
        config_update_value('autocreate_remote_users', 0);
    }

    if (!isset($config['autocreate_blacklist'])) {
        config_update_value('autocreate_blacklist', '');
    }

    if (!isset($config['default_remote_profile'])) {
        config_update_value('default_remote_profile', 0);
    }

    if (!isset($config['default_remote_group'])) {
        config_update_value('default_remote_group', 0);
    }

    if (!isset($config['default_assign_tags'])) {
        config_update_value('default_assign_tags', '');
    }

    if (!isset($config['default_no_hierarchy'])) {
        config_update_value('default_no_hierarchy', 0);
    }

    if (!isset($config['ldap_server'])) {
        config_update_value('ldap_server', 'localhost');
    }

    if (!isset($config['ldap_port'])) {
        config_update_value('ldap_port', 389);
    }

    if (!isset($config['ldap_version'])) {
        config_update_value('ldap_version', '3');
    }

    if (!isset($config['ldap_start_tls'])) {
        config_update_value('ldap_start_tls', 0);
    }

    if (!isset($config['ldap_base_dn'])) {
        config_update_value(
            'ldap_base_dn',
            'ou=People,dc=edu,dc=example,dc=org'
        );
    }

    if (!isset($config['ldap_login_attr'])) {
        config_update_value('ldap_login_attr', 'uid');
    }

    if (!isset($config['ldap_admin_login'])) {
        config_update_value('ldap_admin_login', '');
    }

    if (!isset($config['ldap_admin_pass'])) {
        config_update_value('ldap_admin_pass', '');
    }

    if (!isset($config['ldap_search_timeout'])) {
        config_update_value('ldap_search_timeout', 5);
    }

    if (!isset($config['ldap_server_secondary'])) {
        config_update_value('ldap_server_secondary', 'localhost');
    }

    if (!isset($config['ldap_port_secondary'])) {
        config_update_value('ldap_port_secondary', 389);
    }

    if (!isset($config['ldap_version_secondary'])) {
        config_update_value('ldap_version_secondary', '3');
    }

    if (!isset($config['ldap_start_tls_secondary'])) {
        config_update_value('ldap_start_tls_secondary', 0);
    }

    if (!isset($config['ldap_base_dn_secondary'])) {
        config_update_value(
            'ldap_base_dn_secondary',
            'ou=People,dc=edu,dc=example,dc=org'
        );
    }

    if (!isset($config['ldap_login_attr_secondary'])) {
        config_update_value('ldap_login_attr_secondary', 'uid');
    }

    if (!isset($config['ldap_admin_login_secondary'])) {
        config_update_value('ldap_admin_login_secondary', '');
    }

    if (!isset($config['ldap_admin_pass_secondary'])) {
        config_update_value('ldap_admin_pass_secondary', '');
    }

    if (!isset($config['ldap_function'])) {
        config_update_value('ldap_function', 'local');
    }

    if (!isset($config['fallback_local_auth'])) {
        config_update_value('fallback_local_auth', '0');
    }

    if (!isset($config['ad_server'])) {
        config_update_value('ad_server', 'localhost');
    }

    if (!isset($config['ad_port'])) {
        config_update_value('ad_port', 389);
    }

    if (!isset($config['ad_start_tls'])) {
        config_update_value('ad_start_tls', 0);
    }

    if (!isset($config['recursive_search'])) {
        config_update_value('recursive_search', 1);
    }

    if (!isset($config['ad_advanced_config'])) {
        config_update_value('ad_advanced_config', 0);
    }

    if (!isset($config['ldap_advanced_config'])) {
        config_update_value('ldap_advanced_config', 0);
    }

    if (!isset($config['ad_adv_user_node'])) {
        config_update_value('ad_adv_user_node', 1);
    }

    if (!isset($config['ldap_adv_user_node'])) {
        config_update_value('ldap_adv_user_node', 1);
    }

    if (!isset($config['ad_domain'])) {
        config_update_value('ad_domain', '');
    }

    if (!isset($config['ad_adv_perms'])) {
        config_update_value('ad_adv_perms', '');
    } else {
        $temp_ad_adv_perms = [];
        if (!json_decode(io_safe_output($config['ad_adv_perms']))) {
            if ($config['ad_adv_perms'] != '') {
                $perms = explode(';', io_safe_output($config['ad_adv_perms']));
                foreach ($perms as $ad_adv_perm) {
                    if (preg_match('/[\[\]]/', $ad_adv_perm)) {
                        $all_data = explode(',', io_safe_output($ad_adv_perm));
                        $profile = $all_data[0];
                        $group_pnd = $all_data[1];
                        $groups_ad = str_replace(['[', ']'], '', $all_data[2]);
                        $tags = str_replace(['[', ']'], '', $all_data[3]);
                        $groups_ad = explode('|', $groups_ad);
                        $tags_name = explode('|', $tags);
                        $tags_ids = [];
                        foreach ($tags_name as $tag) {
                            $tags_ids[] = tags_get_id($tag);
                        }

                        $profile = profile_get_profiles(
                            [
                                'name' => io_safe_input($profile),
                            ]
                        );
                        if (!$profile) {
                            continue;
                        }

                        $profile_id = array_keys($profile);
                        $id_grupo = groups_get_id(io_safe_input($group_pnd), false);
                        $new_ad_adv_perms[] = [
                            'profile'   => $profile_id[0],
                            'group'     => [$id_grupo],
                            'tags'      => $tags_ids,
                            'groups_ad' => $groups_ad,
                        ];
                    } else {
                        $all_data = explode(',', io_safe_output($ad_adv_perm));
                        $profile = $all_data[0];
                        $group_pnd = $all_data[1];
                        $groups_ad = $all_data[2];
                        $tags = $all_data[3];
                        $profile = profile_get_profiles(
                            [
                                'name' => io_safe_input($profile),
                            ]
                        );
                        if (!$profile) {
                            continue;
                        }

                        $profile_id = array_keys($profile);
                        $id_grupo = groups_get_id(io_safe_input($group_pnd), false);

                        $new_ad_adv_perms[] = [
                            'profile'   => $profile_id[0],
                            'group'     => [$id_grupo],
                            'tags'      => [$tags],
                            'groups_ad' => [$groups_ad],
                        ];
                    }
                }

                if (!empty($new_ad_adv_perms)) {
                    $temp_ad_adv_perms = json_encode($new_ad_adv_perms);
                }
            } else {
                $temp_ad_adv_perms = '';
            }
        } else {
            $temp_ad_adv_perms = $config['ad_adv_perms'];
        }

        config_update_value('ad_adv_perms', $temp_ad_adv_perms);
    }

    if (!isset($config['ldap_adv_perms'])) {
        config_update_value('ldap_adv_perms', '');
    } else {
        $temp_ldap_adv_perms = [];
        if (!json_decode(io_safe_output($config['ldap_adv_perms']))) {
            if ($config['ldap_adv_perms'] != '') {
                $perms = explode(';', io_safe_output($config['ldap_adv_perms']));
                foreach ($perms as $ldap_adv_perm) {
                    if (preg_match('/[\[\]]/', $ldap_adv_perm)) {
                        $all_data = explode(',', io_safe_output($ldap_adv_perm));
                        $profile = $all_data[0];
                        $group_pnd = $all_data[1];
                        $groups_ad = str_replace(['[', ']'], '', $all_data[2]);
                        $tags = str_replace(['[', ']'], '', $all_data[3]);
                        $groups_ad = explode('|', $groups_ad);
                        $tags_name = explode('|', $tags);
                        $tags_ids = [];
                        foreach ($tags_name as $tag) {
                            $tags_ids[] = tags_get_id($tag);
                        }

                        $profile = profile_get_profiles(
                            [
                                'name' => io_safe_input($profile),
                            ]
                        );
                        if (!$profile) {
                            continue;
                        }

                        $profile_id = array_keys($profile);
                        $id_grupo = groups_get_id(io_safe_input($group_pnd), false);
                        $new_ldap_adv_perms[] = [
                            'profile'     => $profile_id[0],
                            'group'       => [$id_grupo],
                            'tags'        => $tags_ids,
                            'groups_ldap' => $groups_ldap,
                        ];
                    } else {
                        $all_data = explode(',', io_safe_output($ldap_adv_perm));
                        $profile = $all_data[0];
                        $group_pnd = $all_data[1];
                        $groups_ad = $all_data[2];
                        $tags = $all_data[3];
                        $profile = profile_get_profiles(
                            [
                                'name' => io_safe_input($profile),
                            ]
                        );
                        if (!$profile) {
                            continue;
                        }

                        $profile_id = array_keys($profile);
                        $id_grupo = groups_get_id(io_safe_input($group_pnd), false);

                        $new_ldap_adv_perms[] = [
                            'profile'     => $profile_id[0],
                            'group'       => [$id_grupo],
                            'tags'        => [$tags],
                            'groups_ldap' => [$groups_ldap],
                        ];
                    }
                }

                if (!empty($new_ldap_adv_perms)) {
                    $temp_ldap_adv_perms = json_encode($new_ldap_adv_perms);
                }
            } else {
                $temp_ldap_adv_perms = '';
            }
        } else {
            $temp_ldap_adv_perms = $config['ldap_adv_perms'];
        }

        config_update_value('ldap_adv_perms', $temp_ldap_adv_perms);
    }

    if (!isset($config['rpandora_server'])) {
        config_update_value('rpandora_server', 'localhost');
    }

    if (!isset($config['rpandora_port'])) {
        config_update_value('rpandora_port', 3306);
    }

    if (!isset($config['rpandora_dbname'])) {
        config_update_value('rpandora_dbname', 'pandora');
    }

    if (!isset($config['rpandora_user'])) {
        config_update_value('rpandora_user', 'pandora');
    }

    if (!isset($config['rpandora_pass'])) {
        config_update_value('rpandora_pass', '');
    }

    if (!isset($config['rintegria_server'])) {
        config_update_value('rintegria_server', 'localhost');
    }

    if (!isset($config['rintegria_port'])) {
        config_update_value('rintegria_port', 3306);
    }

    if (!isset($config['rintegria_dbname'])) {
        config_update_value('rintegria_dbname', 'integria');
    }

    if (!isset($config['rintegria_user'])) {
        config_update_value('rintegria_user', 'integria');
    }

    if (!isset($config['rintegria_pass'])) {
        config_update_value('rintegria_pass', '');
    }

    if (!isset($config['saml_path'])) {
        config_update_value('saml_path', '/opt/');
    }

    if (!isset($config['saml_source'])) {
        config_update_value('saml_source', '');
    }

    if (!isset($config['saml_user_id'])) {
        config_update_value('saml_user_id', '');
    }

    if (!isset($config['saml_mail'])) {
        config_update_value('saml_mail', '');
    }

    if (!isset($config['saml_group_name'])) {
        config_update_value('saml_group_name', '');
    }

    if (!isset($config['saml_attr_type'])) {
        config_update_value('saml_attr_type', false);
    }

    if (!isset($config['saml_profiles_and_tags'])) {
        config_update_value('saml_profiles_and_tags', '');
    }

    if (!isset($config['saml_profile'])) {
        config_update_value('saml_profile', '');
    }

    if (!isset($config['saml_tag'])) {
        config_update_value('saml_tag', '');
    }

    if (!isset($config['saml_profile_tag_separator'])) {
        config_update_value('saml_profile_tag_separator', '');
    }

    if (!isset($config['autoupdate'])) {
        config_update_value('autoupdate', 1);
    }

    if (!isset($config['api_password'])) {
        config_update_value('api_password', '');
    }

    if (defined('METACONSOLE')) {
        // Customizable sections (Metaconsole).
        enterprise_include_once('include/functions_enterprise.php');
        $customizable_sections = enterprise_hook('enterprise_get_customizable_sections');

        if ($customizable_sections != ENTERPRISE_NOT_HOOK) {
            foreach ($customizable_sections as $k => $v) {
                if (!isset($config[$k])) {
                    config_update_value($k, $v['default']);
                }
            }
        }

        if (!isset($config['meta_num_elements'])) {
            config_update_value('meta_num_elements', 100);
        }
    }

    if (!isset($config['relative_path']) && (isset($_POST['nick'])
        || isset($config['id_user'])) && isset($config['enterprise_installed'])
    ) {
        $isFunctionSkins = enterprise_include_once('include/functions_skins.php');
        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            // Try to update user table in order to refresh skin inmediatly.
            $is_user_updating = get_parameter('sec2', '');

            if ($is_user_updating === 'godmode/users/configure_user') {
                $id = get_parameter_get('id', $config['id_user']);
                // ID given as parameter.
                $user_info = get_user_info($id);

                // If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info.
                if (($config['id_user'] == $id || check_acl($config['id_user'], users_get_groups($id), 'UM')) && $config['user_can_update_info']) {
                    $view_mode = false;
                } else {
                    $view_mode = true;
                }

                if (isset($_GET['modified']) && !$view_mode) {
                    $upd_info['id_skin'] = get_parameter('skin', $user_info['id_skin']);
                    $return_update_skin = update_user($id, $upd_info);
                }
            }

            if (!is_metaconsole()) {
                // Skins are available only in console mode.
                if (isset($config['id_user'])) {
                    $relative_path = enterprise_hook('skins_set_image_skin_path', [$config['id_user']]);
                } else {
                    $relative_path = enterprise_hook('skins_set_image_skin_path', [get_parameter('nick')]);
                }
            } else {
                $relative_path = '';
            }

            $config['relative_path'] = $relative_path;
        }
    }

    if (!isset($config['dbtype'])) {
        config_update_value('dbtype', 'mysql');
    }

    if (!isset($config['legacy_vc'])) {
        config_update_value('legacy_vc', 0);
    }

    if (!isset($config['vc_default_cache_expiration'])) {
        config_update_value('vc_default_cache_expiration', 60);
    }

    if (!isset($config['vc_refr'])) {
        config_update_value('vc_refr', 300);
    }

    if (!isset($config['vc_line_thickness'])) {
        config_update_value('vc_line_thickness', 2);
    }

    if (isset($config['mobile_view_orientation_vc']) === false) {
        config_update_value('mobile_view_orientation_vc', 0);
    }

    if (isset($config['display_item_frame']) === false) {
        config_update_value('display_item_frame', 1);
    }

    if (!isset($config['agent_size_text_small'])) {
        config_update_value('agent_size_text_small', 18);
    }

    if (!isset($config['agent_size_text_medium'])) {
        config_update_value('agent_size_text_medium', 50);
    }

    if (!isset($config['module_size_text_small'])) {
        config_update_value('module_size_text_small', 25);
    }

    if (!isset($config['module_size_text_medium'])) {
        config_update_value('module_size_text_medium', 50);
    }

    if (!isset($config['description_size_text'])) {
        config_update_value('description_size_text', 60);
    }

    if (!isset($config['item_title_size_text'])) {
        config_update_value('item_title_size_text', 45);
    }

    if (!isset($config['simple_module_value'])) {
        config_update_value('simple_module_value', 1);
    }

    if (!isset($config['gis_label'])) {
        config_update_value('gis_label', 0);
    }

    if (!isset($config['interface_unit'])) {
        config_update_value('interface_unit', __('Bytes'));
    }

    if (!isset($config['graph_precision'])) {
        config_update_value('graph_precision', 1);
    } else {
        if (!isset($config['enterprise_installed'])) {
            config_update_value('graph_precision', 1);
        }
    }

    if (!isset($config['gis_default_icon'])) {
        config_update_value('gis_default_icon', 'marker');
    }

    if (!isset($config['interval_values'])) {
        config_update_value('interval_values', '');
    }

    if (!isset($config['public_url'])) {
        config_update_value('public_url', '');
    }

    if (!isset($config['referer_security'])) {
        config_update_value('referer_security', 0);
    }

    if (!isset($config['event_storm_protection'])) {
        config_update_value('event_storm_protection', 0);
    }

    if (!isset($config['use_custom_encoding'])) {
        config_update_value('use_custom_encoding', 0);
    }

    if (!isset($config['server_log_dir'])) {
        config_update_value('server_log_dir', '');
    }

    if (!isset($config['max_log_size'])) {
        config_update_value('max_log_size', 512);
    }

    if (!isset($config['show_group_name'])) {
        config_update_value('show_group_name', 0);
    }

    if (!isset($config['show_empty_groups'])) {
        config_update_value('show_empty_groups', 1);
    }

    if (!isset($config['custom_graph_width'])) {
        config_update_value('custom_graph_width', 1);
    }

    if (!isset($config['type_module_charts'])) {
        config_update_value('type_module_charts', 'area');
    }

    if (!isset($config['items_combined_charts'])) {
        config_update_value('items_combined_charts', 10);
    }

    if (!isset($config['type_interface_charts'])) {
        config_update_value('type_interface_charts', 'line');
    }

    if (!isset($config['render_proc'])) {
        config_update_value('render_proc', 0);
    }

    if (!isset($config['graph_image_height'])) {
        config_update_value('graph_image_height', 130);
    }

    if (!isset($config['zoom_graph'])) {
        config_update_value('zoom_graph', 1);
    }

    if (!isset($config['percentil'])) {
        config_update_value('percentil', 95);
    }

    if (!isset($config['render_proc_ok'])) {
        config_update_value('render_proc_ok', __('Ok'));
    }

    if (!isset($config['render_proc_fail'])) {
        config_update_value('render_proc_fail', __('Fail'));
    }

    // Daniel maya 02/06/2016 Display menu with click --INI.
    if (!isset($config['click_display'])) {
        config_update_value('click_display', 1);
    }

    // Daniel maya 02/06/2016 Display menu with click --END.
    if (isset($config['enterprise_installed']) && $config['enterprise_installed'] == 1) {
        if (!isset($config['service_label_font_size'])) {
            config_update_value('service_label_font_size', 20);
        }

        if (!isset($config['service_item_padding_size'])) {
            config_update_value('service_item_padding_size', 80);
        }
    }

    if (!isset($config['csv_divider'])) {
        config_update_value('csv_divider', ';');
    }

    if (!isset($config['csv_decimal_separator'])) {
        config_update_value('csv_decimal_separator', '.');
    }

    if (!isset($config['use_data_multiplier'])) {
        config_update_value('use_data_multiplier', '1');
    }

    if (!isset($config['command_snapshot'])) {
        config_update_value('command_snapshot', 1);
    }

    if (!isset($config['custom_report_info'])) {
        config_update_value('custom_report_info', 1);
    }

    if (!isset($config['custom_report_front'])) {
        config_update_value('custom_report_front', 0);
    }

    if (!isset($config['global_font_size_report'])) {
        config_update_value('global_font_size_report', 10);
    }

    if (!isset($config['font_size_item_report'])) {
        config_update_value('font_size_item_report', 2);
    }

    if (!isset($config['custom_report_front_font'])) {
        config_update_value('custom_report_front_font', 'Lato-Regular.ttf');
    }

    if (!isset($config['custom_report_front_logo'])) {
        config_update_value(
            'custom_report_front_logo',
            'images/pandora_logo_white.jpg'
        );
    }

    if (!isset($config['custom_report_front_header'])) {
        config_update_value('custom_report_front_header', '');
    }

    if (!isset($config['custom_report_front_firstpage'])) {
        config_update_value(
            'custom_report_front_firstpage',
            '&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;img&#x20;src=&quot;&#40;_URLIMAGE_&#41;/images/pandora_report_logo.png&quot;&#x20;alt=&quot;&quot;&#x20;width=&quot;800&quot;&#x20;/&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;xx-large;&quot;&gt;&#40;_REPORT_NAME_&#41;&lt;/span&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;large;&quot;&gt;&#40;_DATETIME_&#41;&lt;/span&gt;&lt;/p&gt;'
        );
    }

    if (!isset($config['custom_report_front_footer'])) {
        config_update_value('custom_report_front_footer', '');
    }

    if (!isset($config['autohidden_menu'])) {
        config_update_value('autohidden_menu', 0);
    }

    if (!isset($config['visual_animation'])) {
        config_update_value('visual_animation', 1);
    }

    if (!isset($config['random_background'])) {
        config_update_value('random_background', 1);
    }

    if (!isset($config['meta_random_background'])) {
        config_update_value('meta_random_background', '');
    }

    if (!isset($config['networkmap_max_width'])) {
        config_update_value('networkmap_max_width', 900);
    }

    if (!isset($config['short_module_graph_data'])) {
        config_update_value('short_module_graph_data', '');
    }

    if (!isset($config['tutorial_mode'])) {
        config_update_value('tutorial_mode', 'full');
    }

    if (!isset($config['post_process_custom_values'])) {
        config_update_value(
            'post_process_custom_values',
            json_encode([])
        );
    }

    if (!isset($config['update_manager_proxy_server'])) {
        config_update_value(
            'update_manager_proxy_server',
            ''
        );
    }

    if (!isset($config['update_manager_proxy_port'])) {
        config_update_value(
            'update_manager_proxy_port',
            ''
        );
    }

    if (!isset($config['update_manager_proxy_user'])) {
        config_update_value(
            'update_manager_proxy_user',
            ''
        );
    }

    if (!isset($config['update_manager_proxy_password'])) {
        config_update_value(
            'update_manager_proxy_password',
            ''
        );
    }

    if (!isset($config['session_timeout'])) {
        config_update_value('session_timeout', 90);
    }

    if (!isset($config['max_file_size'])) {
        config_update_value('max_file_size', '2M');
    }

    if (!isset($config['initial_wizard'])) {
        config_update_value('initial_wizard', 0);
    }

    if (!isset($config['identification_reminder'])) {
        config_update_value('identification_reminder', 1);
    }

    if (!isset($config['identification_reminder_timestamp'])) {
        config_update_value('identification_reminder_timestamp', 0);
    }

    if (!isset($config['instance_registered'])) {
        config_update_value('instance_registered', 0);
    }

    // Ehorus.
    if (!isset($config['ehorus_enabled'])) {
        config_update_value('ehorus_enabled', 0);
    }

    if (!isset($config['ehorus_custom_field'])) {
        config_update_value('ehorus_custom_field', 'eHorusID');
    }

    if (!isset($config['ehorus_hostname'])) {
        config_update_value('ehorus_hostname', 'portal.ehorus.com');
    }

    if (!isset($config['ehorus_port'])) {
        config_update_value('ehorus_port', 443);
    }

    if (!isset($config['ehorus_req_timeout'])) {
        config_update_value('ehorus_req_timeout', 5);
    }

    // Integria.
    if (!isset($config['integria_user_level_conf'])) {
        config_update_value('integria_user_level_conf', 0);
    }

    if (!isset($config['integria_enabled'])) {
        config_update_value('integria_enabled', 0);
    }

    if (!isset($config['integria_req_timeout'])) {
        config_update_value('integria_req_timeout', 5);
    }

    if (!isset($config['integria_hostname'])) {
        config_update_value('integria_hostname', '');
    }

    // Module Library.
    if (!isset($config['module_library_user'])) {
        config_update_value('module_library_user', '');
    }

    if (!isset($config['module_library_password'])) {
        config_update_value('module_library_password', '');
    }

    if (!isset($config['decimal_separator'])) {
        config_update_value('decimal_separator', '.');
    }

    if (isset($config['notification_autoclose_time']) === false) {
        config_update_value('notification_autoclose_time', 5);
    }

    // Finally, check if any value was overwritten in a form.
    config_update_config();
}


/**
 * Start supervisor.
 *
 * @return void
 */
function config_check()
{
    global $config;

    include_once __DIR__.'/class/ConsoleSupervisor.php';

    // Enterprise customers launch supervisor using discovery task.
    if (enterprise_installed() === false) {
        $supervisor = new ConsoleSupervisor(false);
        $supervisor->run();
    } else {
        $supervisor = new ConsoleSupervisor(false);
        $supervisor->runBasic();
    }
}


/**
 * Retrieves base url stored for Update Manager.
 *
 * @return string URL.
 */
function get_um_url()
{
    global $config;

    if (isset($config['url_update_manager']) === true) {
        $url = $config['url_update_manager'];
        $url = substr($url, 0, (strlen($url) - strpos(strrev($url), '/')));
    } else {
        $url = 'https://licensing.pandorafms.com/pandoraupdate7/';
        config_update_value(
            'url_update_manager',
            $url.'/server.php'
        );
    }

    return $url;
}


/**
 * Return in bytes
 *
 * @param string $val Value to convert.
 *
 * @return integer
 */
function config_return_in_bytes($val)
{
    $last = strtolower($val[(strlen($val) - 1)]);
    $val = (int) trim($val);
    switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0.
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
        default:
            // Ignore.
        break;
    }

    return $val;
}


/**
 * Undocumented function
 *
 * @return void
 */
function config_user_set_custom_config()
{
    global $config;

    $userinfo = get_user_info($config['id_user']);

    // Refresh the last_connect info in the user table.
    // if last update was more than 5 minutes ago.
    if ($userinfo['last_connect'] < (time() - SECONDS_1MINUTE)) {
        update_user($config['id_user'], ['last_connect' => time()]);
    }

    if (!empty($userinfo['block_size']) && ($userinfo['block_size'] != 0)) {
        $config['block_size'] = $userinfo['block_size'];
    }

    // Each user could have it's own timezone).
    if (isset($userinfo['timezone'])) {
        if ($userinfo['timezone'] != '') {
            date_default_timezone_set($userinfo['timezone']);
        }
    }

    if ((isset($userinfo['id_skin']) && (int) $userinfo['id_skin'] !== 0)) {
        if ((int) $userinfo['id_skin'] === 1) {
            $config['style'] = 'pandora';
        }

        if ((int) $userinfo['id_skin'] === 2) {
            $config['style'] = 'pandora_black';
        }
    }

    $skin = get_parameter('skin', false);
    $sec2_aux = get_parameter('sec2');

    if ($sec2_aux != 'godmode/groups/group_list' && $skin !== false) {
        $id_user_aux = get_parameter('id');
        if ($id_user_aux == $config['id_user']) {
            if ($config['style'] === 'pandora_black' && (int) $skin === 0 || (int) $skin === 2) {
                $config['style'] = 'pandora_black';
            } else if ((int) $skin === 1 || (int) $skin === 0) {
                $config['style'] = 'pandora';
            }
        }
    }

    if (is_metaconsole() === true) {
        $config['metaconsole_access'] = $userinfo['metaconsole_access'];
    }
}


/**
 * Undocumented function
 *
 * @return void
 */
function config_prepare_session()
{
    global $config;

    if (isset($config['id_user'])) {
        $user = users_get_user_by_id($config['id_user']);
        $user_sesion_time = $user['session_time'];
    } else {
        $user_sesion_time = null;
    }

    if ($user_sesion_time == 0) {
        // Change the session timeout value to session_timeout minutes  // 8*60*60 = 8 hours.
        $sessionCookieExpireTime = $config['session_timeout'];
    } else {
        // Change the session timeout value to session_timeout minutes  // 8*60*60 = 8 hours.
        $sessionCookieExpireTime = $user_sesion_time;
    }

    if ($sessionCookieExpireTime <= 0) {
        $sessionCookieExpireTime = (10 * 365 * 24 * 60 * 60);
    } else {
        $sessionCookieExpireTime *= 60;
    }

    // Reset the expiration time upon page load //session_name() is default name of session PHPSESSID.
    if (isset($_COOKIE[session_name()])) {
        $update_cookie = true;
        if (is_ajax()) {
            // Avoid session upadte while processing ajax responses - notifications.
            if (get_parameter('check_new_notifications', false)) {
                $update_cookie = false;
            }
        }

        if ($update_cookie === true) {
            setcookie(session_name(), $_COOKIE[session_name()], (time() + $sessionCookieExpireTime), '/');
        }
    }

    ini_set('post_max_size', $config['max_file_size']);
    ini_set('upload_max_filesize', $config['max_file_size']);
}
