<?php
/**
 * Demo data ajax.
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices - CSV Import Agents
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ==========================================================
 * Copyright (c) 2004-2023 Pandora FMS
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

// Login check.
check_login();

if (users_is_admin() === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access demo data manager'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/class/Prd.class.php';
require_once $config['homedir'].'/include/functions_inventory.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';
require_once $config['homedir'].'/include/functions_reports.php';

// Ensure script keeps running even if client-side of the AJAX request disconnected.
// Dev note: this is a script which is purposely external to the invoking client script, configured to run async
// operations in the background even if client is disconnected.
ignore_user_abort(true);

set_time_limit(500);

$action = (string) get_parameter('action', null);

if ($action === 'create_demo_data') {
    config_update_value('demo_data_load_progress', 0);
    config_update_value('demo_data_load_status', '{}');

    // Read agent prd files.
    $directories = [
        'agents',
        'graphs',
        'network_maps',
        'services',
        'reports',
        'dashboards',
        'visual_consoles',
        'gis_maps',
    ];

    $prd = new Prd();

    $demodata_directory = $config['homedir'].'/extras/demodata/';

    $adv_options_is_enabled = (bool) get_parameter('adv_options_is_enabled', false);
    if ($adv_options_is_enabled === true) {
        $service_agent_name = get_parameter('service_agent_name', 'demo-global-agent-1');
        $enabled_items = get_parameter('enabled_items');
        $history_is_enabled = (bool) $enabled_items['enable_history'];
        $days_hist_data = (int) get_parameter('days_hist_data', 15);
        $interval = (int) get_parameter('interval', 300);

        // Plugin values.
        $plugin_agent_name = get_parameter('plugin_agent', 'demo-global-agent-1');

        $traps_target_ip = (string) get_parameter('traps_target_ip', '');
        $traps_community = (string) get_parameter('traps_community', '');
        $tentacle_target_ip = (string) get_parameter('tentacle_target_ip', '');
        $tentacle_port = (int) get_parameter('tentacle_port', 0);
        $tentacle_extra_options = (string) get_parameter('tentacle_extra_options', '');

        $enabled_directories = array_intersect($directories, array_keys(array_filter($enabled_items)));
        $enabled_directories[] = 'agents';
    } else {
        $enabled_directories = $directories;

        // Set default values when advanced mode is disabled.
        $service_agent_name = 'demo-global-agent-1';
        $plugin_agent_name = 'demo-global-agent-1';
        $interval = 300;
        $days_hist_data = 15;
    }

    if (enterprise_installed() === false) {
        unset($enabled_directories['services']);
    }

    foreach ($enabled_directories as $directory) {
        $directory_path = $demodata_directory.$directory;
        if (is_dir($directory_path)) {
            $files = scandir($directory_path);
            $files = array_diff($files, ['.', '..']);
        }

        sort($files, (SORT_NATURAL | SORT_FLAG_CASE));

        foreach ($files as $file) {
            $parsed_ini_data = parse_ini_file($directory_path.'/'.$file, true, INI_SCANNER_TYPED);

            if ($parsed_ini_data !== false) {
                $parsed_ini[$directory][] = [
                    'filename' => $file,
                    'data'     => $parsed_ini_data,
                ];
            } else {
                return;
            }
        }
    }

    if (enterprise_installed() === false) {
        unset($parsed_ini['services']);
    }

    $total_agents_to_create = (int) get_parameter('agents_num', 30);
    // $total_agents_to_create = 10;.
    $total_items_count = count($parsed_ini);

    if ($total_agents_to_create > 0) {
        $agents_to_create = 0;
        $agents_created_count = [];
        $agents_last_ip = [];

        // First loop over agents to get the total agents to be created and init agent created count for each agent.
        foreach ($parsed_ini['agents'] as $ini_agent_data) {
            if (isset($ini_agent_data['data']['agent_data']['agents_number']) === true
                && $ini_agent_data['data']['agent_data']['agents_number'] > 0
            ) {
                $agents_to_create += (int) $ini_agent_data['data']['agent_data']['agents_number'];
                $agents_created_count[$ini_agent_data['data']['agent_data']['agent_alias']] = 0;
                $agents_last_ip[$ini_agent_data['data']['agent_data']['agent_alias']] = null;
            }
        }

        $agent_created_total = 0;
        $agent_data_values_buffer = [];
        // TRAPS HISTORY: Removed due to performance issues.
        if ($total_agents_to_create > 0 && $agents_to_create > 0) {
            while ($agent_created_total < ($total_agents_to_create - 1)) {
                if (count($parsed_ini['agents']) === 0) {
                    register_error(DEMO_AGENT, __('No configuration files found or failed to parse files'));
                    break;
                }

                // Get first server: general value for all created modules. .
                $server_name = db_get_value('name', 'tserver', 'id_server', 1);

                // Traverse agent ini files and create agents.
                foreach ($parsed_ini['agents'] as $ini_agent_data) {
                    $filename = $ini_agent_data['filename'];
                    $agent_data = $ini_agent_data['data']['agent_data'];

                    if (isset($agent_data['agents_number']) === true
                        && !((int) $agent_data['agents_number'] > 0)
                    ) {
                        // No agents are specified to be created for this agent.
                        continue;
                    }

                    if (isset($agent_data['agent_name']) === false
                        || is_string($agent_data['agent_name']) === false
                        || isset($agent_data['agent_alias']) === false
                        || is_string($agent_data['agent_alias']) === false
                    ) {
                        register_error(
                            DEMO_AGENT,
                            __('Error in %s: name and/or alias is not specified or does not have a valid format. Skipping agent creation', $filename),
                            true
                        );
                        continue;
                    }

                    $iter_agents_to_create = $agent_data['agents_number'];

                    if (($agent_created_total + $iter_agents_to_create) >= $total_agents_to_create) {
                        // Total agents limit specified by user has been reached.
                        break;
                    } else {
                        // Calculate max number of agents that can be created in this iteration until max number specified by user is reached.
                        $max_agents_to_limit = ($total_agents_to_create - ($agent_created_total + $iter_agents_to_create));
                    }

                    $modules_data = $ini_agent_data['data']['modules'];
                    $inventory = $ini_agent_data['data']['inventory'];
                    $inventory_values = $ini_agent_data['data']['inventory_values'];
                    $traps = $ini_agent_data['data']['traps'];

                    $address_network = $agent_data['address_network'];

                    if (isset($agent_data['mac']) === true && is_string($agent_data['mac']) === true) {
                        $mac = $agent_data['mac'];
                        if ($agent_data['mac'] === '__randomMAC__') {
                            $mac = generateRandomMacAddress();
                        }
                    }

                    if (isset($address_network) === false || is_string($address_network) === false) {
                        // Agent address is not specified or not valid.
                        register_error(
                            DEMO_AGENT,
                            __('Error in %s: address is not specified or does not have a valid format. Skipping agent creation', $filename),
                            true
                        );
                        continue;
                    }

                    $os_versions = $agent_data['os_versions'];
                    $os_name = $agent_data['os_name'];
                    $group_name = $agent_data['group'];

                    // Get OS id given OS name.
                    $id_os = db_get_value_filter('id_os', 'tconfig_os', ['name' => $os_name]);

                    if ($id_os === false) {
                        // Create OS if does not exist.
                        $values = ['name' => $os_name];
                        $id_os = db_process_sql_insert('tconfig_os', $values);

                        if ($id_os === false) {
                            // Could not create OS. Skip agent creation.
                            register_error(
                                DEMO_AGENT,
                                __('Error in %s: failed to create the specified operating system. Skipping agent creation', $filename),
                                true
                            );
                            continue;
                        }

                        if ($id_os > 0) {
                            // Register created OS in tdemo_data.
                            $values = [
                                'item_id'    => json_encode(['id_os' => $id_os]),
                                'table_name' => 'tconfig_os',
                            ];
                            $result = (bool) db_process_sql_insert('tdemo_data', $values);

                            if ($result === false) {
                                // Rollback OS creation if could not be registered in tdemo_data.
                                db_process_sql_delete('tconfig_os', ['id_os' => $id_os]);
                                continue;
                            }
                        }
                    }

                    $group_id = get_group_or_create_demo_group($group_name);

                    $agent_interval = ($adv_options_is_enabled === true) ? $interval : 300;

                    $iter_agents_created = 0;

                    // Create agents (remaining number of agents to reach number specified by user).
                    for ($i = 0; $i < min($iter_agents_to_create, $max_agents_to_limit); $i++) {
                        $curr_ip_address = ($agents_last_ip[$agent_data['agent_alias']] !== null) ? $agents_last_ip[$agent_data['agent_alias']] : $address_network;
                        $next_ip_address = calculateNextHostAddress($curr_ip_address);
                        $host_address = explode('/', $next_ip_address)[0];
                        $agents_last_ip[$agent_data['agent_alias']] = $next_ip_address;

                        $os_version = current($os_versions);
                        next($os_versions);

                        if (current($os_versions) === false) {
                            reset($os_versions);
                        }

                        $latitude = 0;
                        $longitude = 0;
                        $altitude = 0;

                        if (isset($agent_data['latitude']) === true) {
                            $gis_parsed = explode(';', $agent_data['latitude']);
                            if ((string) $gis_parsed[0] === 'RANDOM') {
                                $latitude = rand($gis_parsed[1], $gis_parsed[2]);
                            } else {
                                $latitude = $agent_data['latitude'];
                            }
                        }

                        if (isset($agent_data['longitude']) === true) {
                            $gis_parsed = explode(';', $agent_data['longitude']);
                            if ((string) $gis_parsed[0] === 'RANDOM') {
                                $longitude = rand($gis_parsed[1], $gis_parsed[2]);
                            } else {
                                $longitude = $agent_data['longitude'];
                            }
                        }

                        if (isset($agent_data['altitude']) === true) {
                            $gis_parsed = explode(';', $agent_data['altitude']);
                            if ((string) $gis_parsed[0] === 'RANDOM') {
                                $altitude = rand($gis_parsed[1], $gis_parsed[2]);
                            } else {
                                $altitude = $agent_data['altitude'];
                            }
                        }

                        $date_time = new DateTime();
                        $current_date_time = $date_time->format('Y-m-d H:i:s');

                        $values = [
                            'server_name'            => $server_name,
                            'id_os'                  => $id_os,
                            'os_version'             => $os_version,
                            'ultimo_contacto'        => $current_date_time,
                            'ultimo_contacto_remoto' => $current_date_time,
                        ];

                        $create_alias = $agent_data['agent_alias'].'-'.($agents_created_count[$agent_data['agent_alias']] + 1);
                        $created_agent_id = agents_create_agent(
                            $create_alias,
                            $group_id,
                            $agent_interval,
                            $host_address,
                            $values,
                            true
                        );

                        if ($created_agent_id > 0) {
                            // Register created demo item in tdemo_data.
                            $values = [
                                'item_id'    => json_encode(['id_agente' => $created_agent_id]),
                                'table_name' => 'tagente',
                            ];
                            $result = (bool) db_process_sql_insert('tdemo_data', $values);

                            if ($result === false) {
                                // Rollback agent creation if could not be registered in tdemo_data.
                                db_process_sql_delete('tagente', ['id_agente' => $created_agent_id]);

                                register_error(
                                    DEMO_AGENT,
                                    __('Uncaught error (source %s): could not create agent %s', $filename, $create_alias),
                                    true
                                );

                                continue;
                            }
                        } else {
                            // Could not create agent. Skip.
                            register_error(
                                DEMO_AGENT,
                                __('Uncaught error (source %s): could not create agent %s', $filename, $create_alias),
                                true
                            );
                            continue;
                        }

                        // Register GIS data.
                        $values = [
                            'tagente_id_agente'  => $created_agent_id,
                            'current_longitude'  => $longitude,
                            'current_latitude'   => $latitude,
                            'current_altitude'   => $altitude,
                            'stored_longitude'   => $longitude,
                            'stored_latitude'    => $latitude,
                            'stored_altitude'    => $altitude,
                            'number_of_packages' => 1,
                            'manual_placement'   => 1,
                        ];
                        $result = db_process_sql_insert('tgis_data_status', $values);

                        if ($result !== false) {
                            $values = [
                                'item_id'    => json_encode(['tagente_id_agente' => $created_agent_id]),
                                'table_name' => 'tgis_data_status',
                            ];
                            $result = (bool) db_process_sql_insert('tdemo_data', $values);

                            if ($result === false) {
                                // Rollback GIS data creation if could not be registered in tdemo_data.
                                db_process_sql_delete('tgis_data_status', ['tagente_id_agente' => $created_agent_id]);
                            }
                        }


                        $agents_created_count[$agent_data['agent_alias']]++;

                        $iter_agents_created++;

                        // Create agent modules.
                        $module_access_idx = 1;

                        while (1) {
                            $modules_array = [];
                            foreach ($modules_data as $key => $value) {
                                $modules_array[$key] = ($value[$module_access_idx] ?? null);
                            }

                            $module_access_idx++;

                            $test_empty_array = array_filter($modules_array);

                            if (empty($test_empty_array) === true) {
                                break;
                            }

                            if (isset($modules_array['name']) === false
                                || is_string($modules_array['name']) === false
                            ) {
                                register_error(
                                    DEMO_AGENT,
                                    __('Error in %s: all modules must have a name. Skipping creation of item with index %d', $filename, ($module_access_idx - 1)),
                                    true
                                );
                                continue;
                            }

                            if (isset($modules_array['type']) === false
                                || is_string($modules_array['type']) === false
                            ) {
                                // Module type not defined.
                                register_error(
                                    DEMO_AGENT,
                                    __('Error in %s: module type is not specified or does not have a valid format (%s). Skipping creation of item with index %d', $filename, ($module_access_idx - 1)),
                                    true
                                );
                                continue;
                            }

                            $id_tipo = db_get_value_filter('id_tipo', 'ttipo_modulo', ['nombre' => $modules_array['type']]);

                            if (!($id_tipo > 0)) {
                                register_error(
                                    DEMO_AGENT,
                                    __('Error in %s: the specified module type is not defined in the system (%s). Skipping creation of item with index %d', $filename, $modules_array['name'], ($module_access_idx - 1)),
                                    true
                                );
                                continue;
                            }

                            $module_description = '';

                            if (isset($modules_array['description']) === true && is_string($modules_array['description']) === true) {
                                if (isset($mac) === false) {
                                    $mac = '';
                                }

                                $module_description = str_replace('_mac_', $mac, $modules_array['description']);
                            }

                            $values = [
                                'unit'            => (isset($modules_array['unit']) === true) ? $modules_array['unit'] : '',
                                'descripcion'     => $module_description,
                                'id_tipo_modulo'  => $id_tipo,
                                'id_module_group' => ($modules_array['group'] ?? 0),
                                'id_modulo'       => 1,
                            ];

                            $created_mod_id = modules_create_agent_module(
                                $created_agent_id,
                                io_safe_input($modules_array['name']),
                                $values
                            );

                            if ($created_mod_id > 0) {
                                // Register created demo item in tdemo_data.
                                $values = [
                                    'item_id'    => json_encode(['id_agente_modulo' => $created_mod_id]),
                                    'table_name' => 'tagente_modulo',
                                ];

                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback agent module creation if could not be registered in tdemo_data.
                                    db_process_sql_delete('tagente_modulo', ['id_agente_modulo' => $created_mod_id]);

                                    register_error(
                                        DEMO_AGENT,
                                        __('Uncaught error (source %s): could not create module with index %d', $filename, ($module_access_idx - 1)),
                                        true
                                    );

                                    continue;
                                }

                                // Insert module data.
                                $parsed = explode(';', $modules_array['values']);

                                $date_time = new DateTime();
                                $current_date_time = $date_time->format('Y-m-d H:i:s');
                                $back_periods = 1;

                                if ($adv_options_is_enabled === false
                                    || ($adv_options_is_enabled === true && $history_is_enabled === true)
                                ) {
                                    $back_periods = round(($days_hist_data * SECONDS_1DAY) / $interval);
                                }


                                $utimestamp = time();

                                // Generate back_periods amounts of tagente_datos rows each period_mins minutes back in time.
                                for ($p = 0; $p < $back_periods; $p++) {
                                    $new_status = 0;
                                    if ((string) $parsed[0] === 'RANDOM') {
                                        $data = rand($parsed[1], $parsed[2]);
                                    } else if ((string) $parsed[0] === 'PROC') {
                                        $probability = (int) $parsed[1];

                                        $data = 1;

                                        if ($probability > 0) {
                                            $randomNumber = rand(1, 100);

                                            if ($randomNumber <= $probability) {
                                                // Set to 0 with a certain probability.
                                                $data = 0;

                                                // Set to critical status if 0.
                                                $new_status = 1;
                                            }
                                        }
                                    }

                                    $agent_data_values = [
                                        'id_agente_modulo' => $created_mod_id,
                                        'datos'            => $data,
                                        'utimestamp'       => $utimestamp,
                                    ];

                                    if ($p === 0) {
                                        // Insert current module data right away so module status is initialized with such value.
                                        $created_data_res = db_process_sql_insert('tagente_datos', $agent_data_values);

                                        if ($created_data_res === false) {
                                            continue;
                                        }

                                        // Proceed to update module status.
                                        $status_values = [
                                            'datos'             => $data,
                                            'estado'            => $new_status,
                                            'known_status'      => 0,
                                            'timestamp'         => $current_date_time,
                                            'utimestamp'        => $utimestamp,
                                            'last_status'       => 0,
                                            'last_known_status' => 0,
                                            'current_interval'  => $agent_interval,
                                        ];

                                        $status_id = db_get_value(
                                            'id_agente_estado',
                                            'tagente_estado',
                                            'id_agente_modulo',
                                            $created_mod_id
                                        );

                                        $update_status_res = db_process_sql_update(
                                            'tagente_estado',
                                            $status_values,
                                            ['id_agente_estado' => $status_id]
                                        );

                                        if ($update_status_res !== false) {
                                            // Register created demo item in tdemo_data.
                                            $values = [
                                                'item_id'    => json_encode(['id_agente_estado' => $status_id]),
                                                'table_name' => 'tagente_estado',
                                            ];

                                            $result = db_process_sql_insert('tdemo_data', $values);

                                            if ($result === false) {
                                                // Rollback demo item creation if could not be registered in tdemo_data.
                                                db_process_sql_delete('tagente_estado', ['id_agente_estado' => $status_id]);
                                            }
                                        }
                                    } else {
                                        // Buffer history data for later bulk insertion (performance reasons).
                                        $agent_data_values_buffer[] = $agent_data_values;
                                    }

                                    if ($adv_options_is_enabled === false
                                        || ($adv_options_is_enabled === true && $history_is_enabled === true)
                                    ) {
                                        $utimestamp -= $interval;
                                    }
                                }
                            } else {
                                // Could not create module.
                                register_error(
                                    DEMO_AGENT,
                                    __('Uncaught error (source %s): could not create module with index %d', $filename, ($module_access_idx - 1)),
                                    true
                                );

                                continue;
                            }
                        }

                        // Create inventory modules.
                        $module_access_idx = 1;
                        $date_time = new DateTime();
                        $current_date_time = $date_time->format('Y-m-d H:i:s');

                        while (1) {
                            // Insert in tmodule_inventory.
                            $modules_array = [];
                            if (isset($inventory) === true) {
                                if ($inventory !== '') {
                                    foreach ($inventory as $key => $value) {
                                        $modules_array[$key] = ($value[$module_access_idx] ?? null);
                                    }
                                }
                            }

                            $module_access_idx++;

                            $test_empty_array = array_filter($modules_array);

                            if (empty($test_empty_array) === true) {
                                break;
                            }

                            if (isset($modules_array['name']) === false
                                || is_string($modules_array['name']) === false
                            ) {
                                    register_error(
                                        DEMO_AGENT,
                                        __('Error in %s: all inventory modules must have a name. Skipping creation of item with index %d', $filename, ($module_access_idx - 1)),
                                        true
                                    );
                            }

                            if (isset($modules_array['format']) === false
                                || is_string($modules_array['format']) === false
                                || isset($modules_array['values']) === false
                                || is_string($modules_array['values']) === false
                            ) {
                                register_error(
                                    DEMO_AGENT,
                                    __('Error in %s: one or more required fields (format, values) were not found for inventory module %s. Skipping creation of item with index %d', $filename, $modules_array['name'], ($module_access_idx - 1)),
                                    true
                                );
                                continue;
                            }

                            $values = [
                                'name'        => $modules_array['name'],
                                'data_format' => $modules_array['format'],
                                'id_os'       => $id_os,
                            ];

                            $created_inventory_mod_id = inventory_create_inventory_module($values);

                            if ($created_inventory_mod_id > 0) {
                                // Register created demo item in tdemo_data.
                                $values = [
                                    'item_id'    => json_encode(['id_module_inventory' => $created_inventory_mod_id]),
                                    'table_name' => 'tmodule_inventory',
                                ];
                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback inventory module if could not be registered in tdemo_data.
                                    db_process_sql_delete('tmodule_inventory', ['id_module_inventory' => $created_inventory_mod_id]);

                                    register_error(
                                        DEMO_AGENT,
                                        __('Uncaught error (source %s): could not create inventory module with index %d', $filename, ($module_access_idx - 1)),
                                        true
                                    );

                                    continue;
                                }
                            } else {
                                register_error(
                                    DEMO_AGENT,
                                    __('Uncaught error (source %s): could not create inventory module with index %d', $filename, ($module_access_idx - 1)),
                                    true
                                );

                                continue;
                            }

                            // Insert in tagent_module_inventory and tagente_datos_inventory.
                            $field_idx = 1;
                            $values_array = explode(';', $modules_array['values']);

                            $selected_inventory_values = array_filter(
                                $inventory_values,
                                function ($key) use ($values_array) {
                                    return in_array($key, $values_array);
                                },
                                ARRAY_FILTER_USE_KEY
                            );

                            $data_lines = [];
                            while (1) {
                                $line_values = array_column($selected_inventory_values, $field_idx);

                                if (empty(array_filter($line_values)) === true) {
                                    break;
                                }

                                $data_lines[] = implode(';', $line_values);
                                $field_idx++;
                            }

                            $data_str = implode('\n', $data_lines);

                            $values = [
                                'id_agente'           => $created_agent_id,
                                'id_module_inventory' => $created_inventory_mod_id,
                                'interval'            => 300,
                                'utimestamp'          => time(),
                                'timestamp'           => $current_date_time,
                                'data'                => $data_str,
                            ];

                            $created_module_inventory_id = db_process_sql_insert('tagent_module_inventory', $values);

                            if ($created_module_inventory_id > 0) {
                                // Register created demo item in tdemo_data.
                                $values = [
                                    'item_id'    => json_encode(['id_agent_module_inventory' => $created_module_inventory_id]),
                                    'table_name' => 'tagent_module_inventory',
                                ];
                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback inventory module if could not be registered in tdemo_data.
                                    db_process_sql_delete('tagent_module_inventory', ['id_agent_module_inventory' => $created_module_inventory_id]);
                                    continue;
                                }
                            }

                            $inventory_data_values = [
                                'id_agent_module_inventory' => $created_module_inventory_id,
                                'data'                      => $data_str,
                                'utimestamp'                => time(),
                                'timestamp'                 => $current_date_time,
                            ];

                            db_process_sql_insert('tagente_datos_inventory', $inventory_data_values);
                        }

                        // Create traps.
                        $date_time = new DateTime();
                        $current_date_time = $date_time->format('Y-m-d H:i:s');
                        $back_periods = 1;

                        if ($adv_options_is_enabled === false
                            || ($adv_options_is_enabled === true && $history_is_enabled === true)
                        ) {
                            $back_periods = round(($days_hist_data * SECONDS_1DAY) / $interval);
                        }


                        $utimestamp = time();
                    }

                    update_progress($total_items_count, $total_agents_to_create, $iter_agents_created);
                }

                $agent_created_total = array_sum($agents_created_count);

                if ($agent_created_total === 0) {
                    // Stop traversing agent files if no agent could be created after first round.
                    break;
                }
            }

            $agent_data_values_buffer_chunks = array_chunk($agent_data_values_buffer, 100000);
            // TRAPS HISTORY: Removed due to performance issues.
            foreach ($agent_data_values_buffer_chunks as $chunk) {
                // Bulk inserts (insert batches of up to 100,000 as a performance limit).
                mysql_db_process_sql_insert_multiple(
                    'tagente_datos',
                    $chunk,
                    false
                );
            }

            // Get last trap in database.
            /*
                $id_trap_begin = db_get_value(
                    'MAX(id_trap)',
                    'ttrap',
                    1,
                    1,
                    false,
                    false
                    );

                    if ($id_trap_begin === false) {
                    $id_trap_begin = 0;
                    }

                    // TRAPS HISTORY: Removed due to performance issues
                    /*
                    foreach ($agent_traps_values_buffer_chunks as $chunk) {
                    // Bulk inserts (insert batches of up to 100,000 as a performance limit).
                    mysql_db_process_sql_insert_multiple(
                        'ttrap',
                        $chunk,
                        false
                    );
                    }

                    // Get last trap in database after insertion.
                    $id_trap_end = db_get_value(
                    'MAX(id_trap)',
                    'ttrap',
                    1,
                    1,
                    false,
                    false
                    );

                    if ($id_trap_end === false) {
                    $id_trap_end = 0;
                    }

                    $agent_traps_demo_registry_buffer = [];
                    for ($i = ($id_trap_begin + 1); $i <= $id_trap_end; $i++) {
                    // Get batches to be stored in tdemo_data.
                    $agent_traps_demo_registry_buffer[] = [
                        'item_id'    => $i,
                        'table_name' => 'ttrap',
                    ];
                    }

                    $agent_traps_demo_registry_buffer_chunks = array_chunk($agent_traps_demo_registry_buffer, 100000);

                    foreach ($agent_traps_demo_registry_buffer_chunks as $chunk) {
                    // Bulk inserts (insert batches of up to 100,000 as a performance limit).
                    mysql_db_process_sql_insert_multiple(
                        'tdemo_data',
                        $chunk,
                        false
                    );
                }
            */

            update_item_checked(DEMO_AGENT);
        }
    }

    $services_count = count(($parsed_ini['services'] ?? []));

    if ($services_count > 0) {
        // Create services.
        import_demo_prds(DEMO_SERVICE, $parsed_ini['services'], $prd);

        update_progress($total_items_count, $services_count, $services_count);
        update_item_checked(DEMO_SERVICE);
    } else {
        register_error(DEMO_SERVICE, __('No configuration files found or failed to parse files'));
    }

    $nm_count = count(($parsed_ini['network_maps'] ?? []));
    if ($nm_count > 0) {
        // Create network maps.
        import_demo_prds(DEMO_NETWORK_MAP, $parsed_ini['network_maps'], $prd);

        update_progress($total_items_count, $nm_count, $nm_count);
        update_item_checked(DEMO_NETWORK_MAP);
    } else {
        register_error(DEMO_NETWORK_MAP, __('No configuration files found or failed to parse files'));
    }

    $gis_count = count(($parsed_ini['gis_maps'] ?? []));
    if ($gis_count > 0) {
        // Enable GIS features.
        $token = 'activate_gis';
        $activate_gis = db_get_value_filter('value', 'tconfig', ['token' => $token]);
        if ($activate_gis === false) {
            config_create_value($token, 1);
        } else {
            config_update_value($token, 1);
        }

        // Create GIS maps.
        import_demo_prds(DEMO_GIS_MAP, $parsed_ini['gis_maps'], $prd);

        update_progress($total_items_count, $gis_count, $gis_count);
        update_item_checked(DEMO_GIS_MAP);
    } else {
        register_error(DEMO_GIS_MAP, __('No configuration files found or failed to parse files'));
    }

    $cg_count = count(($parsed_ini['graphs'] ?? []));
    if ($cg_count > 0) {
        // Create custom graphs.
        import_demo_prds(DEMO_CUSTOM_GRAPH, $parsed_ini['graphs'], $prd);

        update_progress($total_items_count, $cg_count, $cg_count);
        update_item_checked(DEMO_CUSTOM_GRAPH);
    } else {
        register_error(DEMO_CUSTOM_GRAPH, __('No configuration files found or failed to parse files'));
    }

    $rep_count = count(($parsed_ini['reports'] ?? []));
    if ($rep_count > 0) {
        // Create reports.
        import_demo_prds(DEMO_REPORT, $parsed_ini['reports'], $prd);

        update_progress($total_items_count, $rep_count, $rep_count);
        update_item_checked(DEMO_REPORT);
    } else {
        register_error(DEMO_REPORT, __('No configuration files found or failed to parse files'));
    }

    $vc_count = count(($parsed_ini['visual_consoles'] ?? []));
    if ($vc_count > 0) {
        // Create visual consoles.
        import_demo_prds(DEMO_VISUAL_CONSOLE, $parsed_ini['visual_consoles'], $prd);

        update_progress($total_items_count, $vc_count, $vc_count);
        update_item_checked(DEMO_VISUAL_CONSOLE);
    } else {
        register_error(DEMO_VISUAL_CONSOLE, __('No configuration files found or failed to parse files'));
    }

    $dashboards_count = count(($parsed_ini['dashboards'] ?? []));
    if ($dashboards_count > 0) {
        // Create dashboards.
        import_demo_prds(DEMO_DASHBOARD, $parsed_ini['dashboards'], $prd);

        update_progress($total_items_count, $dashboards_count, $dashboards_count);
        update_item_checked(DEMO_DASHBOARD);
    } else {
        register_error(DEMO_DASHBOARD, __('No configuration files found or failed to parse files'));
    }

    // Register plugin.
    $quit = false;

    // Check whether plugin agent exists in the system. Try to get default agent if not.
    $matched_agents = agents_get_agents(
        ['nombre' => $plugin_agent_name],
        ['id_agente'],
        'AR',
        [
            'field' => 'nombre',
            'order' => 'ASC',
        ],
        false,
        0,
        false,
        false,
        false
    );
    $matched_agent = $matched_agents[0]['id_agente'];

    if (isset($matched_agent) === true && $matched_agent > 0) {
        $plugin_agent_id = $matched_agent;
    } else {
        // Skip element creation if agent does not exist.
        register_error(
            DEMO_PLUGIN,
            __('Error in plugin creation: the specified agent for the plugin does not exist in the system: %s. Skipping plugin creation', $filename, $plugin_agent_name)
        );

        $quit = true;
    }

    if ($quit === false) {
        $values = [
            'name'         => io_safe_input('Pandora demo agents'),
            'description'  => io_safe_input('Generate XML and traps for demo agents based on agents definition files.'),
            'max_timeout'  => 300,
            'max_retries'  => 0,
            'execute'      => io_safe_input('perl /usr/share/pandora_server/util/plugin/pandora_demo_agents.pl'),
            'net_dst_opt'  => '',
            'net_port_opt' => '',
            'user_opt'     => '',
            'pass_opt'     => '',
            'plugin_type'  => 0,
            'macros'       => '{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Agents files folder path\",\"help\":\"\",\"value\":\"/usr/share/pandora_server/util/plugin/demodata_agents\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Number of agents\",\"help\":\"\",\"value\":\"10\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Traps target IP\",\"help\":\"\",\"value\":\"127.0.0.1\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Traps community\",\"help\":\"\",\"value\":\"public\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Tentacle target IP\",\"help\":\"\",\"value\":\"127.0.0.1\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Tentacle port\",\"help\":\"\",\"value\":\"41121\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Tentacle extra options\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"}}',
            'parameters'   => '&#039;_field1_&#039;&#x20;&#039;_field2_&#039;&#x20;&#039;_interval_&#039;&#x20;&#039;_field3_&#039;&#x20;&#039;_field4_&#039;&#x20;&#039;_field5_&#039;&#x20;&#039;_field6_&#039;&#x20;&#039;_field7_&#039;',
            'no_delete'    => 1,
        ];

        $created_plugin_id = db_process_sql_insert('tplugin', $values);

        if ($created_plugin_id > 0) {
            // Register created item in tdemo_data.
            $values = [
                'item_id'    => json_encode(['id' => $created_plugin_id]),
                'table_name' => 'tplugin',
            ];
            $result = (bool) db_process_sql_insert('tdemo_data', $values);

            if ($result === false) {
                // Rollback demo item creation if could not be registered in tdemo_data.
                db_process_sql_delete('tplugin', ['id' => $created_plugin_id]);

                register_error(
                    DEMO_PLUGIN,
                    __('Error in plugin creation: the plugin could not be registered. Skipping creation of plugin module')
                );
            } else {
                // Create plugin module.
                $module_values = [];

                $module_values['id_tipo_modulo'] = db_get_value('id_tipo', 'ttipo_modulo', 'nombre', 'generic_proc');

                if ($module_values['id_tipo_modulo'] === false) {
                    register_error(
                        DEMO_PLUGIN,
                        __('Error in plugin creation: module type "generic_proc" does not exist in the system. Skipping creation of plugin module')
                    );
                } else {
                    $module_values['module_interval'] = $interval;
                    $module_values['id_modulo'] = 4;
                    $module_values['id_plugin'] = $created_plugin_id;
                    $module_values['macros'] = '{"1":{"macro":"_field1_","desc":"Agents&#x20;files&#x20;folder&#x20;path","help":"","value":"/usr/share/pandora_server/util/plugin/demodata_agents","hide":""},"2":{"macro":"_field2_","desc":"Number&#x20;of&#x20;agents","help":"","value":"'.$total_agents_to_create.'","hide":""},"3":{"macro":"_field3_","desc":"Traps&#x20;target&#x20;IP","help":"","value":"'.$traps_target_ip.'","hide":""},"4":{"macro":"_field4_","desc":"Traps&#x20;community","help":"","value":"'.$traps_community.'","hide":""},"5":{"macro":"_field5_","desc":"Tentacle&#x20;target&#x20;IP","help":"","value":"'.$tentacle_target_ip.'","hide":""},"6":{"macro":"_field6_","desc":"Tentacle&#x20;port","help":"","value":"'.$tentacle_port.'","hide":""},"7":{"macro":"_field7_","desc":"Tentacle&#x20;extra&#x20;options","help":"","value":"'.$tentacle_extra_options.'","hide":""}}';

                    $id_plugin_module = modules_create_agent_module(
                        $plugin_agent_id,
                        io_safe_input('Pandora demo data'),
                        $module_values
                    );

                    if ($id_plugin_module > 0) {
                        // Register created item in tdemo_data.
                        $values = [
                            'item_id'    => json_encode(['id_agente_modulo' => $id_plugin_module]),
                            'table_name' => 'tagente_modulo',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item creation if could not be registered in tdemo_data.
                            db_process_sql_delete('tagente_modulo', ['id_agente_modulo' => $id_plugin_module]);
                        }
                    }
                }
            }
        } else {
            register_error(
                DEMO_PLUGIN,
                __('Error in plugin creation: the plugin could not be registered. Skipping creation of plugin module')
            );
        }
    }

    update_item_checked(DEMO_PLUGIN);

    $demo_agents_count = db_get_value('count(*)', 'tdemo_data', 'table_name', 'tagente');

    echo json_encode(['agents_count' => $demo_agents_count]);

    return;
}

if ($action === 'cleanup_demo_data') {
    config_update_value('demo_data_load_progress', 0);

    $demo_items = db_get_all_rows_in_table('tdemo_data');

    $module_items = array_map(
        function ($item) {
            $json_data = json_decode($item['item_id'], true);
            return $json_data['id_agente_modulo'];
        },
        array_filter(
            $demo_items,
            function ($item) {
                return ($item['table_name'] === 'tagente_modulo');
            }
        )
    );

    $inventory_module_items = array_map(
        function ($item) {
            $json_data = json_decode($item['item_id'], true);
            return $json_data['id_agent_module_inventory'];
        },
        array_filter(
            $demo_items,
            function ($item) {
                return ($item['table_name'] === 'tagent_module_inventory');
            }
        )
    );

    $in_clause = implode(',', $inventory_module_items);
    // Delete data from tagente_datos_inventory given inventory module id.
    db_process_sql('DELETE FROM tagente_datos_inventory where id_agent_module_inventory IN ('.$in_clause.')');

    $in_clause = implode(',', $module_items);
    // Delete data from tagente_datos give agent module id.
    db_process_sql('DELETE FROM tagente_datos where id_agente_modulo IN ('.$in_clause.')');

    $items_delete_id_bfr = [];
    $demo_items_delete_ids_bfr = [];

    foreach ($demo_items as $item) {
        $item_value_array = json_decode((string) $item['item_id'], true);

        if (is_array($item_value_array) === true) {
            // Database items to delete per table.
            $items_delete_id_bfr[$item['table_name']][] = $item_value_array;
            $demo_items_delete_ids_bfr[$item['table_name']][] = $item['id'];
        }
    }

    foreach ($items_delete_id_bfr as $table_name => $ids_per_table) {
        $all_success = true;
        $where_array = [];

        foreach ($ids_per_table as $ids_array) {
            foreach ($ids_array as $field_name => $item_id) {
                if (isset($where_array[$field_name]) === false) {
                    $where_array[$field_name] = [];
                }

                $where_array[$field_name][] = '"'.$item_id.'"';
            }
        }

        $where_str = '';

        $in_fields = implode(',', array_keys($where_array));

        if (count($where_array) > 1) {
            $pairs_array = createPairsFromArrays(array_values($where_array));

            $in_ftd_pairs = array_map(
                function ($inner_array) {
                    return '('.implode(',', $inner_array).')';
                },
                $pairs_array
            );
            $where_str = '('.$in_fields.') IN ('.implode(',', $in_ftd_pairs).')';
        } else {
            $where_str = '`'.$in_fields.'` IN ('.implode(',', reset($where_array)).')';
        }

        $all_success = db_process_sql('DELETE FROM '.$table_name.' WHERE '.$where_str);

        if ($all_success !== false) {
            $demo_items_delete_in_values = implode(',', $demo_items_delete_ids_bfr[$table_name]);

            // Delete tdemo_data registers belonging to current table.
            db_process_sql('DELETE FROM tdemo_data WHERE table_name="'.$table_name.'" AND id IN ('.$demo_items_delete_in_values.')');
        }
    }

    echo 1;
    return;
}


if ($action === 'get_progress') {
    $operation = (string) get_parameter('operation');

    if ($operation === 'create') {
        $current_progress_val = db_get_value_filter('value', 'tconfig', ['token' => 'demo_data_load_progress']);

        if ($current_progress_val === false) {
            $current_progress_val = 0;
        }

        $ret = ceil($current_progress_val);
    } else if ($operation === 'cleanup') {
        $demo_items_to_cleanup = (int) get_parameter('demo_items_to_cleanup');
        $count_current_demo_items = db_get_value('count(*)', 'tdemo_data');
        $current_progress_val = ((($demo_items_to_cleanup - $count_current_demo_items) * 100) / $demo_items_to_cleanup);
        config_update_value('demo_data_delete_progress', $current_progress_val);
        $ret = ceil($current_progress_val);
    }

    echo json_encode($ret);

    return;
}

if ($action === 'get_load_status') {
    $operation = (string) get_parameter('operation');

    if ($operation === 'create') {
        $current_progress_val = db_get_value_filter('value', 'tconfig', ['token' => 'demo_data_load_progress']);
        $demo_data_load_status = db_get_value_filter('value', 'tconfig', ['token' => 'demo_data_load_status']);

        if ($current_progress_val === false) {
            $current_progress_val = 0;
        }

        $ret = [
            'current_progress_val'  => $current_progress_val,
            'demo_data_load_status' => json_decode(io_safe_output($demo_data_load_status), true),
        ];
    } else if ($operation === 'cleanup') {
        $demo_items_to_cleanup = (int) get_parameter('demo_items_to_cleanup');
        $count_current_demo_items = db_get_value('count(*)', 'tdemo_data');
        $current_progress_val = ((($demo_items_to_cleanup - $count_current_demo_items) * 100) / $demo_items_to_cleanup);
        config_update_value('demo_data_delete_progress', $current_progress_val);
        $ret = ['current_progress_val' => $current_progress_val];
    }

    echo json_encode($ret);

    return;
}


/**
 * AUXILIARY FUNCTION: Calculate and return next host address within subnet given a CIDR-formatted address.
 *
 * @param string $ip CIDR IP address.
 *
 * @return string Next host address.
 */
function calculateNextHostAddress($ip)
{
    list($network, $subnet) = explode('/', $ip);

    // Convert the network address to an array of octets.
    $octets = explode('.', $network);

    // Convert the last octet to an integer.
    $lastOctet = (int) $octets[3];

    // Increment the last octet, and wrap around if it exceeds 255.
    $lastOctet = (($lastOctet + 1) % 256);

    // Assemble the next host address.
    $nextHost = implode('.', [$octets[0], $octets[1], $octets[2], $lastOctet]);

    return $nextHost.'/'.$subnet;
}


/**
 * AUXILIARY FUNCTION: Try to return the group ID of a group given its name or create
 * and return its ID if it does not exist.
 *
 * @param string $name Group name.
 *
 * @return mixed group ID or false if group not found and could not be created.
 */
function get_group_or_create_demo_group($name)
{
    if (is_string($name) === false) {
        return false;
    }

    $id_group = db_get_value('id_grupo', 'tgrupo', 'nombre', io_safe_input($name));

    if ($id_group > 0) {
        return $id_group;
    } else {
        $id_group = groups_create_group(
            io_safe_input($name),
            [
                'icon'        => 'applications.png',
                'description' => '',
                'contact'     => '',
                'other'       => '',
            ]
        );

        if ($id_group > 0) {
            // Register created group in tdemo_data.
            $values = [
                'item_id'    => json_encode(['id_grupo' => $id_group]),
                'table_name' => 'tgrupo',
            ];
            $result = (bool) db_process_sql_insert('tdemo_data', $values);

            if ($result === false) {
                // Rollback demo group creation if could not be registered in tdemo_data.
                db_process_sql_delete('tgrupo', ['id_grupo' => $id_group]);
                return false;
            }

            return $id_group;
        } else {
            // Network map group could not be created. Skip creation of map.
            return false;
        }
    }
}


/**
 * AUXILIARY FUNCTION: Generate and return a randomly generated MAC address.
 *
 * @return string Random MAC address string.
 */
function generateRandomMacAddress()
{
    $macAddress = [];

    // Generate the remaining five octets.
    for ($i = 0; $i < 6; $i++) {
        $macAddress[] = str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    // Join the octets with colons to form the MAC address.
    $randomMacAddress = implode(':', $macAddress);

    return $randomMacAddress;
}


/**
 * AUXILIARY FUNCTION: Update percentage progress.
 *
 * @param integer $total_items_count      Global number of items to be created.
 * @param integer $total_type_items_count Number of items of a specific type to be created.
 * @param integer $created_num            Number of items added to progress computation.
 *
 * @return void
 */
function update_progress($total_items_count, $total_type_items_count, $created_num=1)
{
    // Calculate progress.
    $percentage_inc = (($created_num * 100) / ($total_type_items_count * $total_items_count));
    $current_progress_val = db_get_value_filter(
        'value',
        'tconfig',
        ['token' => 'demo_data_load_progress'],
        'AND',
        false,
        false
    );

    if ($current_progress_val === false) {
        $current_progress_val = 0;
    }

    $new_val = ($current_progress_val + $percentage_inc);

    if ((int) round($new_val) === 100) {
        $new_val = 100;
    }

    config_update_value('demo_data_load_progress', $new_val);
}


/**
 * AUXILIARY FUNCTION: Mark item as checked in the load process.
 *
 * @param integer $item_id Item id.
 *
 * @return void
 */
function update_item_checked($item_id)
{
    $current_load_status_data = db_get_value_filter(
        'value',
        'tconfig',
        ['token' => 'demo_data_load_status'],
        'AND',
        false,
        false
    );

    if ($current_load_status_data === false || $current_load_status_data === '{}') {
        $current_load_data_arr = [];
    } else {
        $current_load_data_arr = json_decode(io_safe_output($current_load_status_data), true);
    }

    $current_load_data_arr['checked_items'][] = $item_id;

    config_update_value('demo_data_load_status', json_encode($current_load_data_arr));
}


/**
 * AUXILIARY FUNCTION: Register error in database config info.
 *
 * @param integer $item_id                  Item id.
 * @param string  $error_msg                Error text.
 * @param boolean $search_for_repeated_msgs Increases the count of messages already stored if true.
 *
 * @return void
 */
function register_error(
    $item_id,
    $error_msg,
    $search_for_repeated_msgs=false
) {
    $current_load_status_data = db_get_value_filter(
        'value',
        'tconfig',
        ['token' => 'demo_data_load_status'],
        'AND',
        false,
        false
    );

    if ($current_load_status_data === false || $current_load_status_data === '{}') {
        $current_load_data_arr = [];
    } else {
        $current_load_data_arr = json_decode(io_safe_output($current_load_status_data), true);
    }

    if ($search_for_repeated_msgs === true && isset($current_load_data_arr['errors'][$item_id]) === true) {
        $matching_string = null;
        $msg_key = array_search($error_msg, $current_load_data_arr['errors'][$item_id]);

        if ($msg_key === false) {
            foreach ($current_load_data_arr['errors'][$item_id] as $key => $string) {
                // Use regular expression to check if the part after "(...) " matches the error string.
                if (preg_match('/\s*\((.*?)\)\s*(.*)/', $string, $matches)) {
                    $rest_of_string = $matches[2];

                    if ($rest_of_string === $error_msg) {
                        $matching_string = $string;
                        $msg_key = $key;
                        break;
                    }
                }
            }

            if ($matching_string === null) {
                // String not found.
                $current_load_data_arr['errors'][$item_id][] = $error_msg;
            } else {
                // Count parentheses string was found, then replace number.
                $new_error_msg = preg_replace_callback(
                    '/\((\d+)\)/',
                    function ($matches) {
                        $currentNumber = $matches[1];
                        $newNumber = ($currentNumber + 1);
                        return "($newNumber)";
                    },
                    $matching_string
                );
                $current_load_data_arr['errors'][$item_id][$msg_key] = $new_error_msg;
            }
        } else {
            // String without count found.
            $new_error_msg = '(2) '.$error_msg;
            $current_load_data_arr['errors'][$item_id][$msg_key] = $new_error_msg;
        }
    } else {
        $current_load_data_arr['errors'][$item_id][] = $error_msg;
    }

    config_update_value('demo_data_load_status', json_encode($current_load_data_arr));
}


/**
 * AUXILIARY FUNCTION: Import PRD files.
 *
 * @param integer $item_id    Item id.
 * @param array   $parsed_ini Parsed PRD files.
 * @param object  $prd        Prd object.
 *
 * @return void
 */
function import_demo_prds($item_id, $parsed_ini, $prd)
{
    foreach ($parsed_ini as $ini_data) {
        $filename = $ini_data['filename'];
        $data = $ini_data['data'];
        $result = $prd->importPrd($data);
        if ($result['status'] === true) {
            foreach ($result['items'] as $item) {
                // Register created items in tdemo_data.
                $values = [
                    'item_id'    => json_encode($item[1]),
                    'table_name' => $item[0],
                ];

                db_process_sql_insert('tdemo_data', $values);
            }
        } else {
            foreach ($result['errors'] as $error) {
                register_error($item_id, '['.$filename.'] '.$error);
            }
        }
    }
}
