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

require $config['homedir'].'/include/functions_inventory.php';
require $config['homedir'].'/include/functions_custom_graphs.php';
require $config['homedir'].'/include/functions_reports.php';

// Ensure script keeps running even if client-side of the AJAX request disconnected.
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
            $current_parsed_ini = parse_ini_file($directory_path.'/'.$file, true, INI_SCANNER_TYPED);
            if ($current_parsed_ini !== false) {
                $parsed_ini[$directory][] = array_merge(['filename' => $file], $current_parsed_ini);
            }
        }
    }

    if (enterprise_installed() === false) {
        unset($parsed_ini['services']);
    }

    $total_agents_to_create = (int) get_parameter('agents_num', 30);
    $total_items_count = count($parsed_ini);

    if ($total_agents_to_create > 0) {
        $agents_to_create = 0;
        $agents_created_count = [];
        $agents_last_ip = [];

        // First loop over agents to get the total agents to be created and init agent created count for each agent.
        foreach ($parsed_ini['agents'] as $ini_agent_data) {
            if (isset($ini_agent_data['agent_data']['agents_number']) === true
                && $ini_agent_data['agent_data']['agents_number'] > 0
            ) {
                $agents_to_create += (int) $ini_agent_data['agent_data']['agents_number'];
                $agents_created_count[$ini_agent_data['agent_data']['agent_alias']] = 0;
                $agents_last_ip[$ini_agent_data['agent_data']['agent_alias']] = null;
            }
        }

        $agent_created_total = 0;
        $agent_data_values_buffer = [];
        // TRAPS HISTORY: Removed due to performance issues
        // $agent_traps_values_buffer = [];
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
                    $agent_data = $ini_agent_data['agent_data'];

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

                    $modules_data = $ini_agent_data['modules'];
                    $inventory = $ini_agent_data['inventory'];
                    $inventory_values = $ini_agent_data['inventory_values'];
                    $traps = $ini_agent_data['traps'];

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
                                'item_id'    => $id_os,
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
                                'item_id'    => $created_agent_id,
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

                        // Register GIS data
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
                                'item_id'    => $created_agent_id,
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
                                $module_description = str_replace('_mac_', $mac, $modules_array['description']);
                            }

                            $values = [
                                'unit'            => $modules_array['unit'],
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
                                    'item_id'    => $created_mod_id,
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
                                                'item_id'    => $status_id,
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
                            foreach ($inventory as $key => $value) {
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
                                    'item_id'    => $created_inventory_mod_id,
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
                                    'item_id'    => $created_module_inventory_id,
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

                        // TRAPS HISTORY: Removed due to performance issues
                        /*
                            for ($p = 0; $p < $back_periods; $p++) {
                            $trap_access_idx = 1;

                            while (1) {
                                $traps_array = [];

                                foreach ($traps as $key => $value) {
                                    $traps_array[$key] = ($value[$trap_access_idx] ?? null);
                                }

                                $trap_access_idx++;
                                $test_empty_array = array_filter($traps_array);

                                if (empty($test_empty_array) === true) {
                                    break;
                                }

                                if (isset($traps_array['oid']) === false || is_string($traps_array['oid']) === false
                                    || isset($traps_array['value']) === false || is_string($traps_array['value']) === false
                                    || isset($traps_array['snmp_type']) === false || is_string($traps_array['snmp_type']) === false
                                    || isset($traps_array['chance_percent']) === false || is_string($traps_array['chance_percent']) === false
                                ) {
                                    register_error(
                                        DEMO_AGENT,
                                        __('Error in %s: all traps must have the following required fields: oid, value, snmp_type, chance_percent. Skipping creation of item with index %d', $filename, ($trap_access_idx - 1)),
                                        true
                                    );
                                    continue;
                                }

                                $create_trap = false;

                                $trap_creation_prob = (int) $traps_array['chance_percent'];

                                if ($trap_creation_prob > 0) {
                                    $randomNumber = rand(1, 100);
                                    if ($randomNumber <= $trap_creation_prob) {
                                        $create_trap = true;
                                    }
                                }

                                if ($create_trap === false) {
                                    continue;
                                }

                                $parsed = explode(';', $traps_array['value']);

                                $data = '';
                                if ((string) $parsed[0] === 'RANDOM') {
                                    $data = rand($parsed[1], $parsed[2]);
                                }

                                $values = [
                                    'oid'        => $traps_array['oid'],
                                    'source'     => $host_address,
                                    'value'      => $data,
                                    'type'       => $traps_array['snmp_type'],
                                    'timestamp'  => $current_date_time,
                                    'utimestamp' => $utimestamp,
                                ];

                                // Buffer history traps for later bulk insertion (performance reasons).
                                $agent_traps_values_buffer[] = $values;
                            }

                            if ($adv_options_is_enabled === false
                                || ($adv_options_is_enabled === true && $history_is_enabled === true)
                            ) {
                                $date_time->sub(new DateInterval("PT{$interval}S"));
                                $current_date_time = $date_time->format('Y-m-d H:i:s');
                                $utimestamp -= $interval;
                            }
                            }
                        */
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
            // TRAPS HISTORY: Removed due to performance issues
            // $agent_traps_values_buffer_chunks = array_chunk($agent_traps_values_buffer, 100000);
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
            }*/

            update_item_checked(DEMO_AGENT);
        }
    }

    $services_count = count(($parsed_ini['services'] ?? []));
    if ($services_count > 0) {
        // Create services.
        foreach ($parsed_ini['services'] as $ini_service_data) {
            $filename = $ini_service_data['filename'];
            $service_data = $ini_service_data['service_data'];
            $service_items = $ini_service_data['service_items'];

            // Check for mandatory fields.
            if (isset($service_data['name']) === false
                || is_string($service_data['name']) === false
                || isset($service_data['group']) === false
                || is_string($service_data['group']) === false
            ) {
                register_error(
                    DEMO_SERVICE,
                    __('Error in %s: name and/or group is not specified or does not have a valid format. Skipping service creation', $filename)
                );
                continue;
            }

            // Check whether services default agent exists in the system. Try to get default agent if not.
            $matched_agents = agents_get_agents(
                ['nombre' => $service_agent_name],
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
                $service_agent_id = $matched_agent;
            } else {
                // Skip element creation if agent does not exist.
                register_error(
                    DEMO_SERVICE,
                    __('Error in %s: the specified services agent does not exist in the system: %s. Skipping service creation', $filename, $service_agent_name)
                );
                continue;
            }

            $id_group = get_group_or_create_demo_group($service_data['group']);

            if ($id_group === false) {
                // Group could not be created. Skip service creation.
                register_error(
                    DEMO_SERVICE,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping service creation', $filename)
                );
                continue;
            }

            $service_values = [];

            $service_values['name'] = io_safe_input($service_data['name']);
            $service_values['description'] = io_safe_input($service_data['description']);
            $service_values['id_group'] = $id_group;
            $service_values['critical'] = $service_data['critical'];
            $service_values['warning'] = $service_data['warning'];
            $service_values['auto_calculate'] = (isset($service_data['mode']) === true && (string) $service_data['mode'] === 'smart') ? 1 : 0;

            if (isset($service_data['show_sunburst']) === true && $service_data['show_sunburst'] === true) {
                $service_values['enable_sunburst'] = 1;
            }

            $created_service_id = db_process_sql_insert('tservice', $service_values);

            $service_module_values = [];
            $service_module_values['flag'] = 0;
            $service_module_values['module_interval'] = 300;
            $service_module_values['custom_integer_1'] = $created_service_id;
            $service_module_values['prediction_module'] = 2;
            $service_module_values['id_modulo'] = 5;
            $service_module_values['id_module_group'] = 1;

            if ($created_service_id > 0) {
                // Register created service in tdemo_data.
                $values = [
                    'item_id'    => $created_service_id,
                    'table_name' => 'tservice',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback service creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tservice', ['id' => $created_service_id]);

                    register_error(
                        DEMO_SERVICE,
                        __('Uncaught error (source %s): could not create service %s', $filename, $service_values['name'])
                    );

                    continue;
                }

                $service_module_values['id_tipo_modulo'] = 22;
                $service_module_values['min_warning'] = $service_data['warning'];
                $service_module_values['min_critical'] = $service_data['critical'];

                $created_service_module_id = modules_create_agent_module(
                    $service_agent_id,
                    io_safe_input($service_data['name'].'_service'),
                    $service_module_values
                );

                if ($created_service_module_id > 0) {
                    // Register created demo item in tdemo_data.
                    $values = [
                        'item_id'    => $created_service_module_id,
                        'table_name' => 'tagente_modulo',
                    ];
                    $result = (bool) db_process_sql_insert('tdemo_data', $values);

                    if ($result === false) {
                        // Rollback demo item creation if could not be registered in tdemo_data.
                        db_process_sql_delete('tagente_modulo', ['id_agente_modulo' => $created_service_module_id]);

                        register_error(
                            DEMO_SERVICE,
                            __('Uncaught error (source %s): could not create service module %s', $filename, $service_module_values['nombre'])
                        );

                        continue;
                    }

                    $service_module_values['id_tipo_modulo'] = 21;
                    $created_sla_service_module_id = modules_create_agent_module(
                        $service_agent_id,
                        io_safe_input($service_data['name'].'_SLA_service'),
                        $service_module_values
                    );

                    if ($created_sla_service_module_id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $created_sla_service_module_id,
                            'table_name' => 'tagente_modulo',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item creation if could not be registered in tdemo_data.
                            db_process_sql_delete('tagente_modulo', ['id_agente_modulo' => $created_sla_service_module_id]);

                            register_error(
                                DEMO_SERVICE,
                                __('Uncaught error (source %s): could not create service SLA module %s', $filename, $service_module_values['nombre'])
                            );

                            continue;
                        }

                        $service_module_values['id_tipo_modulo'] = 22;
                        $created_sla_val_service_module_id = modules_create_agent_module(
                            $service_agent_id,
                            io_safe_input($service_data['name'].'_SLA_Value_service'),
                            $service_module_values
                        );

                        if ($created_sla_val_service_module_id > 0) {
                            // Register created demo item in tdemo_data.
                            $values = [
                                'item_id'    => $created_sla_val_service_module_id,
                                'table_name' => 'tagente_modulo',
                            ];
                            $result = (bool) db_process_sql_insert('tdemo_data', $values);
                            if ($result === false) {
                                // Rollback demo item creation if could not be registered in tdemo_data.
                                db_process_sql_delete('tagente_modulo', ['id_agente_modulo' => $created_sla_val_service_module_id]);

                                register_error(
                                    DEMO_SERVICE,
                                    __('Uncaught error (source %s): could not create service SLA value module %s', $filename, $service_module_values['nombre'])
                                );

                                continue;
                            }
                        } else {
                            register_error(
                                DEMO_SERVICE,
                                __('Uncaught error (source %s): could not create service SLA value module %s', $filename, $service_module_values['nombre'])
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_SERVICE,
                            __('Uncaught error (source %s): could not create service SLA module %s', $filename, $service_module_values['nombre'])
                        );

                        continue;
                    }

                    $update_service_module_ids = db_process_sql_update(
                        'tservice',
                        [
                            'id_agent_module'     => $created_service_module_id,
                            'sla_id_module'       => $created_sla_service_module_id,
                            'sla_value_id_module' => $created_sla_val_service_module_id,
                        ],
                        ['id' => $created_service_id]
                    );

                    if ($update_service_module_ids === false) {
                        continue;
                    }
                } else {
                    register_error(
                        DEMO_SERVICE,
                        __('Uncaught error (source %s): could not create service module %s', $filename, $service_module_values['nombre'])
                    );

                    continue;
                }
            } else {
                // Service could not be created. Skip creation of map.
                register_error(
                    DEMO_SERVICE,
                    __('Uncaught error (source %s): could not create service %s', $filename, $service_values['name'])
                );

                continue;
            }

            if (count($service_items) > 0) {
                $item_access_idx = 1;
                while (1) {
                    $items_array = [];
                    foreach ($service_items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    if (isset($items_array['type']) === false) {
                        // Service element type must be specified.
                        register_error(
                            DEMO_SERVICE,
                            __('Error in %s: all service items must have the following required fields: type. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );
                        continue;
                    }

                    $element_values = [];

                    $element_values = ['id_service' => $created_service_id];

                    $element_type = (string) $items_array['type'];

                    if (in_array($element_type, ['agent', 'module', 'dynamic', 'service']) === false) {
                        // Skip element creation if type not valid.
                        register_error(
                            DEMO_SERVICE,
                            __('Error in %s: the specified type of service item is not valid. All service items must have one of the following types: agent, module, dynamic, service. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );
                        continue;
                    }

                    if (in_array($element_type, ['agent', 'module']) === true) {
                        // Get agent ID and module ID.
                        $matched_agents = agents_get_agents(
                            ['nombre' => io_safe_input($items_array['agent_name'])],
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
                            $element_values['id_agent'] = $matched_agent;
                        } else {
                            // Skip element creation if agent does not exist.
                            register_error(
                                DEMO_SERVICE,
                                __('Error in %s: the specified agent does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['agent_name'], ($item_access_idx - 1))
                            );
                            continue;
                        }
                    }

                    if (in_array($element_type, ['module']) === true) {
                        if ($element_values['id_agent'] > 0) {
                            $module_id = db_get_value_sql('SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = "'.io_safe_input($items_array['module']).'" AND id_agente = '.$element_values['id_agent']);

                            if ($module_id > 0) {
                                $element_values['id_agente_modulo'] = $module_id;
                            } else {
                                // Skip element creation if agent module does not exist.
                                register_error(
                                    DEMO_SERVICE,
                                    __('Error in %s: the specified agent module does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['module'], ($item_access_idx - 1))
                                );
                                continue;
                            }
                        } else {
                            continue;
                        }
                    }

                    if ($element_type === 'dynamic') {
                        if ($service_values['auto_calculate'] === 1) {
                            if (isset($items_array['match']) === false
                                || ($items_array['match'] !== 'agent' && $items_array['match'] !== 'module')
                            ) {
                                // If failed to provide match value, 'agent' is assigned by default.
                                $match_value = 'agent';
                            } else {
                                $match_value = $items_array['match'];
                            }

                            if (isset($items_array['group']) === true) {
                                $group_id_value = get_group_or_create_demo_group($items_array['group']);

                                if ($group_id_value === false) {
                                    $group_id_value = -1;
                                }
                            } else {
                                $group_id_value = -1;
                            }

                            $element_values['id_agent'] = 0;
                            $element_values['id_agente_modulo'] = 0;

                            $rules_arr = [
                                'dynamic_type'  => $match_value,
                                'group'         => $group_id_value,
                                'agent_name'    => (isset($items_array['agent_name']) === true) ? $items_array['agent_name'] : '',
                                'module_name'   => (isset($items_array['module']) === true) ? $items_array['module'] : '',
                                'regex_mode'    => (isset($items_array['regex']) === true) ? $items_array['regex'] : false,
                                'custom_fields' => [],
                            ];

                            $element_values['rules'] = base64_encode(json_encode($rules_arr));
                        }
                    }

                    if ($element_type === 'service') {
                        if (isset($items_array['service_name']) === true
                            && is_string($items_array['service_name']) === true
                        ) {
                            $services = services_get_services(['name' => io_safe_input($items_array['service_name'])]);

                            $service_id = $services[0]['id'];

                            if ($service_id > 0) {
                                $element_values['id_service_child'] = $service_id;
                            } else {
                                // Skip element creation if specified service does not exist.
                                register_error(
                                    DEMO_SERVICE,
                                    __('Error in %s: the specified service does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['service_name'], ($item_access_idx - 1))
                                );
                                continue;
                            }
                        } else {
                            // Skip element creation if service name was not provided.
                            continue;
                        }
                    }

                    $id = db_process_sql_insert('tservice_element', $element_values);

                    if ($id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $id,
                            'table_name' => 'tservice_element',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback service element if could not be registered in tdemo_data.
                            db_process_sql_delete('tservice_element', ['id' => $id]);

                            register_error(
                                DEMO_SERVICE,
                                __('Uncaught error (source %s): could not create service item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_SERVICE,
                            __('Uncaught error (source %s): could not create service item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $services_count, $services_count);
        update_item_checked(DEMO_SERVICE);
    } else {
        register_error(DEMO_SERVICE, __('No configuration files found or failed to parse files'));
    }

    $nm_count = count(($parsed_ini['network_maps'] ?? []));
    if ($nm_count > 0) {
        // Create network maps.
        foreach ($parsed_ini['network_maps'] as $ini_nm_data) {
            $filename = $ini_nm_data['filename'];
            $map_data = $ini_nm_data['map_data'];
            $map_items = $ini_nm_data['map_items'];

            if (isset($map_data['name']) === false
                || is_string($map_data['name']) === false
                || isset($map_data['group']) === false
                || is_string($map_data['group']) === false
            ) {
                register_error(
                    DEMO_NETWORK_MAP,
                    __('Error in %s: name and/or group is not specified or does not have a valid format. Skipping network map creation', $filename)
                );
                continue;
            }

            $map_types = [
                'circular'       => 0,
                'radial_dynamic' => 6,
            ];

            $nm_name = $map_data['name'];
            $nm_group = $map_data['group'];
            $nm_description = (isset($map_data['description']) === true) ? $map_data['description'] : '';
            $nm_node_radius = (isset($map_data['node_radius']) === true) ? $map_data['node_radius'] : '40';
            $nm_generation_method = (isset($map_data['generation_method']) === true && isset($map_types[$map_data['generation_method']]) === true) ? $map_types[$map_data['generation_method']] : '0';

            $nm_id_group = get_group_or_create_demo_group($nm_group);

            if ($nm_id_group === false) {
                // Group could not be created. Skip network map creation.
                register_error(
                    DEMO_NETWORK_MAP,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping service creation', $filename)
                );
                continue;
            }

            $values = [];
            $new_map_filter = [];
            $new_map_filter['dont_show_subgroups'] = 0;
            $new_map_filter['node_radius'] = $nm_node_radius;
            $new_map_filter['x_offs'] = 0;
            $new_map_filter['y_offs'] = 0;
            $new_map_filter['z_dash'] = '0.5';
            $new_map_filter['node_sep'] = '0.25';
            $new_map_filter['rank_sep'] = '0.25';
            $new_map_filter['mindist'] = 1;
            $new_map_filter['kval'] = '0.3';
            $values['filter'] = json_encode($new_map_filter);
            $values['description'] = io_safe_input($nm_description);
            $values['id_group'] = $nm_id_group;
            $values['id_group_map'] = $nm_id_group;
            $values['source_data'] = $nm_id_group;
            $values['name'] = io_safe_input($nm_name);
            $values['refresh_time'] = 300;
            $values['generation_method'] = $nm_generation_method;

            $id_map = db_process_sql_insert('tmap', $values);

            if ($id_map > 0) {
                // Register created map in tdemo_data.
                $values = [
                    'item_id'    => $id_map,
                    'table_name' => 'tmap',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback demo item creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tmap', ['id' => $id_map]);

                    register_error(
                        DEMO_NETWORK_MAP,
                        __('Uncaught error (source %s): could not create network map %s', $filename, $nm_name)
                    );

                    continue;
                }
            } else {
                // Network map group could not be created. Skip creation of map.
                register_error(
                    DEMO_NETWORK_MAP,
                    __('Uncaught error (source %s): could not create network map %s', $filename, $nm_name)
                );
                continue;
            }

            if (count($map_items) > 0) {
                $item_access_idx = 1;
                $map_id_index = [];

                while (1) {
                    $items_array = [];
                    foreach ($map_items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    $item_values = [];

                    $item_values['id_map'] = $id_map;

                    if (isset($items_array['agent_name']) === true || is_string($items_array['agent_name']) === false) {
                        $matched_agents = agents_get_agents(
                            ['nombre' => $items_array['agent_name']],
                            [
                                'id_agente',
                                'id_os',
                                'alias',
                            ],
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
                        $alias = $matched_agents[0]['alias'];
                        if (isset($matched_agent) === true && $matched_agent > 0) {
                            $item_values['source_data'] = $matched_agent;
                            if (isset($matched_agents[0]['id_agente']) === true && $matched_agents[0]['id_os']) {
                                $agent_os_id = $matched_agents[0]['id_os'];
                                $icon_name = db_get_value('icon_name', 'tconfig_os', 'id_os', $agent_os_id);
                            }
                        } else {
                            // Skip report item creation if agent does not exist.
                            register_error(
                                DEMO_NETWORK_MAP,
                                __('Error in %s: the specified agent does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['agent_name'], ($item_access_idx - 1))
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_NETWORK_MAP,
                            __('Error in %s: all network map items must have the following required fields: agent_name. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );

                        continue;
                    }

                    $style_values = [
                        'shape'  => 'circle',
                        'image'  => 'images/networkmap/'.$icon_name,
                        'width'  => null,
                        'height' => null,
                        'label'  => $alias,
                    ];

                    $item_values['style'] = json_encode($style_values);
                    $item_values['x'] = (isset($items_array['x']) === true) ? $items_array['x'] : '0';
                    $item_values['y'] = (isset($items_array['y']) === true) ? $items_array['y'] : '0';

                    $created_nm_item_id = db_process_sql_insert('titem', $item_values);

                    if ($created_nm_item_id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $created_nm_item_id,
                            'table_name' => 'titem',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item if could not be registered in tdemo_data.
                            db_process_sql_delete('titem', ['id' => $created_nm_item_id]);

                            register_error(
                                DEMO_NETWORK_MAP,
                                __('Uncaught error (source %s): could not create network map item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }

                        $map_id_index[($item_access_idx - 1)] = $created_nm_item_id;

                        if (isset($items_array['parent']) === true && (int) $items_array['parent'] > 0) {
                            $parent_nm_id = $map_id_index[(int) $items_array['parent']];
                            $parent_nm_source_data = db_get_value('source_data', 'titem', 'id', $parent_nm_id);

                            $rel_values = [
                                'id_parent'             => $parent_nm_id,
                                'id_child'              => $created_nm_item_id,
                                'id_map'                => $id_map,
                                'id_parent_source_data' => $parent_nm_source_data,
                                'id_child_source_data'  => $item_values['source_data'],
                            ];

                            $created_nm_rel_item_id = db_process_sql_insert('trel_item', $rel_values);

                            if ($created_nm_rel_item_id > 0) {
                                // Register created demo item in tdemo_data.
                                $values = [
                                    'item_id'    => $created_nm_rel_item_id,
                                    'table_name' => 'trel_item',
                                ];
                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback demo item if could not be registered in tdemo_data.
                                    db_process_sql_delete('trel_item', ['id' => $created_nm_rel_item_id]);

                                    register_error(
                                        DEMO_NETWORK_MAP,
                                        __('Uncaught error (source %s): could not create network map item with index %d', $filename, ($item_access_idx - 1))
                                    );

                                    continue;
                                }
                            }
                        }
                    } else {
                        register_error(
                            DEMO_NETWORK_MAP,
                            __('Uncaught error (source %s): could not create network map item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $nm_count, $nm_count);
        update_item_checked(DEMO_NETWORK_MAP);
    } else {
        register_error(DEMO_NETWORK_MAP, __('No configuration files found or failed to parse files'));
    }

    $gis_count = count(($parsed_ini['gis_maps'] ?? []));
    if ($gis_count > 0) {
        // Enable GIS features
        $token = 'activate_gis';
        $activate_gis = db_get_value_filter('value', 'tconfig', ['token' => $token]);
        if ($activate_gis === false) {
            config_create_value($token, 1);
        } else {
            config_update_value($token, 1);
        }

        // Create GIS maps.
        foreach ($parsed_ini['gis_maps'] as $ini_gis_data) {
            $filename = $ini_gis_data['filename'];
            $gis_data = $ini_gis_data['gis_data'];
            $gis_layers = $ini_gis_data['gis_layers'];

            if (isset($gis_data['name']) === false
                || is_string($gis_data['name']) === false
                || isset($gis_data['group']) === false
                || is_string($gis_data['group']) === false
            ) {
                register_error(
                    DEMO_GIS_MAP,
                    __('Error in %s: name and/or group is not specified or does not have a valid format. Skipping GIS map creation', $filename)
                );
                continue;
            }

            $gis_name              = $gis_data['name'];
            $gis_group             = $gis_data['group'];
            $gis_zoom_level        = (isset($gis_data['zoom_level']) === true) ? $gis_data['zoom_level'] : '6';
            $gis_initial_latitude  = (isset($gis_data['initial_latitude']) === true) ? $gis_data['initial_latitude'] : '0';
            $gis_initial_longitude = (isset($gis_data['initial_longitude']) === true) ? $gis_data['initial_longitude'] : '0';
            $gis_initial_altitude  = (isset($gis_data['initial_altitude']) === true) ? $gis_data['initial_altitude'] : '0';
            $gis_default_latitude  = (isset($gis_data['default_latitude']) === true) ? $gis_data['default_latitude'] : '0';
            $gis_default_longitude = (isset($gis_data['default_longitude']) === true) ? $gis_data['default_longitude'] : '0';
            $gis_default_altitude  = (isset($gis_data['default_altitude']) === true) ? $gis_data['default_altitude'] : '0';

            $gis_id_group = get_group_or_create_demo_group($gis_group);

            if ($gis_id_group === false) {
                // Group could not be created. Skip GIS map creation.
                register_error(
                    DEMO_GIS_MAP,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping GIS map creation', $filename)
                );
                continue;
            }

            $values = [];
            $values['map_name']          = io_safe_input($gis_name);
            $values['group_id']          = $gis_id_group;
            $values['zoom_level']        = $gis_zoom_level;
            $values['initial_latitude']  = $gis_initial_latitude;
            $values['initial_longitude'] = $gis_initial_longitude;
            $values['initial_altitude']  = $gis_initial_altitude;
            $values['default_latitude']  = $gis_default_latitude;
            $values['default_longitude'] = $gis_default_longitude;
            $values['default_altitude']  = $gis_default_altitude;

            $id_map = db_process_sql_insert('tgis_map', $values);

            if ($id_map > 0) {
                // Register created map in tdemo_data.
                $values = [
                    'item_id'    => $id_map,
                    'table_name' => 'tgis_map',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback demo item creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tgis_map', ['id_tgis_map' => $id_map]);

                    register_error(
                        DEMO_GIS_MAP,
                        __('Uncaught error (source %s): could not create GIS map %s', $filename, $gis_name)
                    );

                    continue;
                }
            } else {
                // Network map group could not be created. Skip creation of map.
                register_error(
                    DEMO_GIS_MAP,
                    __('Uncaught error (source %s): could not create GIS map %s', $filename, $gis_name)
                );
                continue;
            }

            $values = [];
            $values['tgis_map_id_tgis_map'] = $id_map;
            $values['tgis_map_con_id_tmap_con'] = 1;

            db_process_sql_insert('tgis_map_has_tgis_map_con', $values);

            if (count($gis_layers) > 0) {
                $item_access_idx = 1;

                while (1) {
                    $items_array = [];
                    foreach ($gis_layers as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    $item_values = [];

                    $layer_order = ($item_access_idx - 2);

                    $item_values['tgis_map_id_tgis_map'] = $id_map;
                    $item_values['layer_stack_order']    = $layer_order;
                    $item_values['tgrupo_id_grupo']      = -1;
                    $item_values['view_layer']           = 1;
                    $item_values['layer_name']           = io_safe_input((isset($items_array['name']) === true) ? $items_array['name'] : ('layer-' - $layer_order));

                    if (isset($items_array['group']) === true) {
                        $layer_id_group = get_group_or_create_demo_group($items_array['group']);
                        if ($layer_id_group !== false) {
                            $item_values['tgrupo_id_grupo'] = $layer_id_group;
                        }
                    }

                    $created_gis_layer_id = db_process_sql_insert('tgis_map_layer', $item_values);

                    if ($created_gis_layer_id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $created_gis_layer_id,
                            'table_name' => 'tgis_map_layer',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item if could not be registered in tdemo_data.
                            db_process_sql_delete('tgis_map_layer', ['id_tmap_layer' => $created_gis_layer_id]);

                            register_error(
                                DEMO_GIS_MAP,
                                __('Uncaught error (source %s): could not create GIS map item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_GIS_MAP,
                            __('Uncaught error (source %s): could not create GIS map item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $gis_count, $gis_count);
        update_item_checked(DEMO_GIS_MAP);
    } else {
        register_error(DEMO_GIS_MAP, __('No configuration files found or failed to parse files'));
    }

    $cg_count = count(($parsed_ini['graphs'] ?? []));
    if ($cg_count > 0) {
        // Create graphs.
        foreach ($parsed_ini['graphs'] as $ini_graph_data) {
            // Constant graph types.
            $graph_types = [
                'line'   => 2,
                'area'   => 0,
                's_line' => 3,
                's_area' => 1,
                'h_bars' => 6,
                'v_bars' => 7,
                'gauge'  => 5,
                'pie'    => 8,
            ];

            $filename = $ini_graph_data['filename'];
            $graph_data = $ini_graph_data['graph_data'];
            $graph_items = $ini_graph_data['graph_items'];

            $graph_name = $graph_data['name'];
            $graph_group = $graph_data['group'];
            $graph_description = $graph_data['description'];
            $graph_type = (isset($graph_types[$graph_data['type']]) === true) ? $graph_types[$graph_data['type']] : 0;
            $graph_periodicity = $graph_data['periodicity'];

            $graph_id_group = get_group_or_create_demo_group($graph_group);

            if ($graph_id_group === false) {
                // Group could not be created. Skip graph creation.
                register_error(
                    DEMO_CUSTOM_GRAPH,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping service creation', $filename)
                );
                continue;
            }

            $values = [];
            $values['description'] = io_safe_input($graph_description);
            $values['id_group'] = $graph_id_group;
            $values['name'] = io_safe_input($graph_name);
            $values['period'] = $graph_periodicity;
            $values['stacked'] = $graph_type;

            $id_graph = db_process_sql_insert('tgraph', $values);

            if ($id_graph > 0) {
                // Register created graph in tdemo_data.
                $values = [
                    'item_id'    => $id_graph,
                    'table_name' => 'tgraph',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback graph creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tgraph', ['id_graph' => $id_graph]);

                    register_error(
                        DEMO_CUSTOM_GRAPH,
                        __('Uncaught error (source %s): could not create custom graph %s', $filename, $graph_name)
                    );

                    continue;
                }
            } else {
                // Graph could not be created. Skip creation of graph.
                register_error(
                    DEMO_CUSTOM_GRAPH,
                    __('Uncaught error (source %s): could not create custom graph %s', $filename, $graph_name)
                );
                continue;
            }

            if (count($graph_items) > 0) {
                $item_access_idx = 1;

                while (1) {
                    $items_array = [];
                    foreach ($graph_items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    $item_values = [];

                    if (isset($items_array['agent_name']) === false
                        || is_string($items_array['agent_name']) === false
                        || isset($items_array['module']) === false
                        || is_string($items_array['module']) === false
                    ) {
                        // Agent and module must be defined. Skip graph item creation.
                        register_error(
                            DEMO_CUSTOM_GRAPH,
                            __('Error in %s: one or more required fields (agent_name, module) were not found for custom graph item with index %d. Skipping creation of item with index %d', $filename, $item_access_idx, ($item_access_idx - 1))
                        );
                        continue;
                    }


                    $matched_agents = agents_get_agents(
                        ['nombre' => $items_array['agent_name']],
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
                    $agent_id = $matched_agents[0]['id_agente'];

                    if (!($agent_id > 0)) {
                        register_error(
                            DEMO_CUSTOM_GRAPH,
                            __('Error in %s: the specified agent does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['agent_name'], ($item_access_idx - 1))
                        );
                        continue;
                    }

                    $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                    $module_id = $module_row['id_agente_modulo'];

                    if (!($module_id > 0)) {
                        register_error(
                            DEMO_CUSTOM_GRAPH,
                            __('Error in %s: the specified agent module does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['module'], ($item_access_idx - 1))
                        );
                        continue;
                    }

                    $item_values = [
                        'id_graph'        => $id_graph,
                        'id_agent_module' => $module_id,
                        'weight'          => 1,
                    ];

                    $created_graph_item_id = db_process_sql_insert('tgraph_source', $item_values);

                    if ($created_graph_item_id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $created_graph_item_id,
                            'table_name' => 'tgraph_source',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item if could not be registered in tdemo_data.
                            db_process_sql_delete('tgraph_source', ['id_gs' => $created_graph_item_id]);

                            register_error(
                                DEMO_CUSTOM_GRAPH,
                                __('Uncaught error (source %s): could not create custom graph item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_CUSTOM_GRAPH,
                            __('Uncaught error (source %s): could not create custom graph item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $cg_count, $cg_count);
        update_item_checked(DEMO_CUSTOM_GRAPH);
    } else {
        register_error(DEMO_CUSTOM_GRAPH, __('No configuration files found or failed to parse files'));
    }

    $rep_count = count(($parsed_ini['reports'] ?? []));
    if ($rep_count > 0) {
        // Create reports.
        foreach ($parsed_ini['reports'] as $ini_report_data) {
            $filename = $ini_report_data['filename'];
            $report_data = $ini_report_data['report_data'];
            $report_items = $ini_report_data['report_items'];

            // Check for mandatory fields.
            if (isset($report_data['name']) === false
                || is_string($report_data['name']) === false
                || isset($report_data['group']) === false
                || is_string($report_data['group']) === false
            ) {
                register_error(
                    DEMO_REPORT,
                    __('Error in %s: name and/or group is not specified or does not have a valid format. Skipping custom report creation', $filename)
                );
            }

            $group_id = get_group_or_create_demo_group($report_data['group']);

            if ($group_id === false) {
                // Could not create group. Skip report creation.
                register_error(
                    DEMO_REPORT,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping service creation', $filename)
                );
                continue;
            }

            $report_values = [];
            $report_values['id_group'] = $group_id;
            $report_values['name'] = io_safe_input($report_data['name']);
            $report_values['description'] = io_safe_input($report_data['description']);

            $created_report_id = db_process_sql_insert('treport', $report_values);

            if ($created_report_id > 0) {
                // Register created graph in tdemo_data.
                $values = [
                    'item_id'    => $created_report_id,
                    'table_name' => 'treport',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback report creation if could not be registered in tdemo_data.
                    db_process_sql_delete('treport', ['id_report' => $created_report_id]);

                    register_error(
                        DEMO_REPORT,
                        __('Uncaught error (source %s): could not create custom report %s', $filename, $report_data['name'])
                    );

                    continue;
                }
            } else {
                // Report could not be created. Skip creation of map.
                register_error(
                    DEMO_REPORT,
                    __('Uncaught error (source %s): could not create custom report %s', $filename, $report_data['name'])
                );
                continue;
            }

            if (count($report_items) > 0) {
                $item_access_idx = 1;

                while (1) {
                    $items_array = [];
                    foreach ($report_items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    if (isset($items_array['name']) === false
                        || is_string($items_array['name']) === false
                        || isset($items_array['type']) === false
                        || is_string($items_array['type']) === false
                    ) {
                        // All report items must have a type.
                        register_error(
                            DEMO_REPORT,
                            __('Error in %s: all custom report items must have the following required fields: name, type. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );
                        continue;
                    }

                    $item_values = [];

                    $item_values['id_report'] = $created_report_id;
                    $item_values['name'] = io_safe_input($items_array['name']);
                    $item_values['type'] = $items_array['type'];

                    if (isset($items_array['agent_name']) === true) {
                        if (isset($items_array['module']) === false
                            || is_string($items_array['module']) === false
                        ) {
                            continue;
                        }

                        $matched_agents = agents_get_agents(
                            ['nombre' => $items_array['agent_name']],
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
                            $item_values['id_agent'] = $matched_agent;
                        } else {
                            // Skip report item creation if agent does not exist.
                            register_error(
                                DEMO_REPORT,
                                __('Error in %s: the specified agent does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['agent_name'], ($item_access_idx - 1))
                            );
                            continue;
                        }
                    }

                    if (isset($items_array['module']) === true) {
                        if (is_string($items_array['module']) === false) {
                            // Module wrong data type read. Skip.
                            continue;
                        }

                        if ($item_values['id_agent'] > 0) {
                            $module_id = db_get_value_sql('SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = "'.io_safe_input($items_array['module']).'" AND id_agente = '.$item_values['id_agent']);

                            if ($module_id > 0) {
                                $item_values['id_agent_module'] = $module_id;
                            } else {
                                // Skip report item creation if agent module does not exist.
                                register_error(
                                    DEMO_REPORT,
                                    __('Error in %s: the specified agent module does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['module'], ($item_access_idx - 1))
                                );
                                continue;
                            }
                        } else {
                            continue;
                        }
                    }

                    if (isset($items_array['graph_name']) === true && is_string($items_array['graph_name']) === true) {
                        $id_custom_graph = reset(custom_graphs_search('', $items_array['graph_name']))['id_graph'];

                        if ($id_custom_graph > 0) {
                            $item_values['id_gs'] = $id_custom_graph;
                        } else {
                            // Skip report item creation if specified custom graph does not exist.
                            register_error(
                                DEMO_REPORT,
                                __('Error in %s: the specified custom graph does not exist in the system: %s. Skipping creation of item with index %d', $filename, $items_array['graph_name'], ($item_access_idx - 1))
                            );
                            continue;
                        }
                    }

                    if ($items_array['type'] === 'simple_graph') {
                        $item_values['style'] = '{"show_in_same_row":0,"hide_notinit_agents":0,"priority_mode":1,"dyn_height":"250","percentil":0,"fullscale":0,"image_threshold":0,"label":""}';
                    }

                    if ($items_array['type'] === 'custom_graph') {
                        $item_values['style'] = '{"show_in_same_row":0,"hide_notinit_agents":0,"priority_mode":"1","dyn_height":"250"}';
                    }

                    $item_values['period'] = (isset($items_array['periodicity']) === true) ? $items_array['periodicity'] : 300;

                    $created_report_item_id = db_process_sql_insert('treport_content', $item_values);

                    if ($created_report_item_id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $created_report_item_id,
                            'table_name' => 'treport_content',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback report item if could not be registered in tdemo_data.
                            db_process_sql_delete('treport_content', ['id_rc' => $created_report_item_id]);

                            register_error(
                                DEMO_REPORT,
                                __('Uncaught error (source %s): could not create custom report item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }

                        if ($items_array['type'] === 'SLA') {
                            $sla_values = [
                                'id_report_content' => $created_report_item_id,
                                'id_agent_module'   => $item_values['id_agent_module'],
                                'sla_limit'         => 95,
                            ];

                            $created_report_content_sla_id = db_process_sql_insert('treport_content_sla_combined', $sla_values);

                            if ($created_report_content_sla_id > 0) {
                                // Register created demo item in tdemo_data.
                                $values = [
                                    'item_id'    => $created_report_content_sla_id,
                                    'table_name' => 'treport_content_sla_combined',
                                ];
                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback report item if could not be registered in tdemo_data.
                                    db_process_sql_delete('treport_content_sla_combined', ['id' => $created_report_content_sla_id]);

                                    register_error(
                                        DEMO_REPORT,
                                        __('Uncaught error (source %s): could not create custom report item with index %d', $filename, ($item_access_idx - 1))
                                    );

                                    continue;
                                }
                            } else {
                                register_error(
                                    DEMO_REPORT,
                                    __('Uncaught error (source %s): could not create custom report item with index %d', $filename, ($item_access_idx - 1))
                                );

                                continue;
                            }
                        }
                    } else {
                        register_error(
                            DEMO_REPORT,
                            __('Uncaught error (source %s): could not create custom report item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $rep_count, $rep_count);
        update_item_checked(DEMO_REPORT);
    } else {
        register_error(DEMO_REPORT, __('No configuration files found or failed to parse files'));
    }

    $vc_count = count(($parsed_ini['visual_consoles'] ?? []));
    if ($vc_count > 0) {
        // Create visual consoles.
        foreach ($parsed_ini['visual_consoles'] as $ini_data) {
            $filename = $ini_data['filename'];
            $data = $ini_data['visual_console_data'];
            $items = $ini_data['visual_console_items'];

            // Check for mandatory fields.
            if (isset($data['name']) === false
                || isset($data['group']) === false
            ) {
                // Name and group fields must be specified for vc.
                register_error(
                    DEMO_VISUAL_CONSOLE,
                    __('Error in %s: name and/or group is not specified or does not have a valid format. Skipping visual console creation', $filename)
                );
                continue;
            }

            $id_group = get_group_or_create_demo_group($data['group']);

            if ($id_group === false) {
                // Group could not be created. Skip dashboard creation.
                register_error(
                    DEMO_VISUAL_CONSOLE,
                    __('Error in %s: the specified group does not exist in the system and could not be created. Skipping visual console creation', $filename)
                );
                continue;
            }

            $insert_values = [];

            $insert_values['name'] = io_safe_input($data['name']);
            $insert_values['id_group'] = $id_group;
            $insert_values['background'] = (isset($data['background']) === true) ? $data['background'] : 'None.png';
            $insert_values['background_color'] = (isset($data['background_color']) === true) ? $data['background_color'] : '#ffffff';
            $insert_values['width'] = (isset($data['width']) === true) ? $data['width'] : 1024;
            $insert_values['height'] = (isset($data['height']) === true) ? $data['height'] : 768;

            $created_id = db_process_sql_insert('tlayout', $insert_values);

            if ($created_id > 0) {
                // Register created item in tdemo_data.
                $values = [
                    'item_id'    => $created_id,
                    'table_name' => 'tlayout',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback demo item creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tlayout', ['id' => $created_id]);

                    register_error(
                        DEMO_VISUAL_CONSOLE,
                        __('Uncaught error (source %s): could not create visual console %s', $filename, $insert_values['name'])
                    );

                    continue;
                }
            } else {
                // VC could not be created. Skip creation of item.
                register_error(
                    DEMO_VISUAL_CONSOLE,
                    __('Uncaught error (source %s): could not create visual console %s', $filename, $insert_values['name'])
                );
                continue;
            }

            if (count($items) > 0) {
                $item_access_idx = 1;
                while (1) {
                    $items_array = [];
                    foreach ($items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    if (isset($items_array['type']) === false) {
                        // All visual console items must have a type.
                        register_error(
                            DEMO_VISUAL_CONSOLE,
                            __('Error in %s: all visual console items must have the following required fields: type. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );
                        continue;
                    }

                    // Map used types.
                    $types = [
                        'static_image'              => 0,
                        'module_graph'              => 1,
                        'custom_graph'              => 1,
                        'value'                     => 2,
                        'percentile'                => 3,
                        'label'                     => 4,
                        'icon'                      => 5,
                        'bubble'                    => 9,
                        'box'                       => 12,
                        'event_history'             => 14,
                        'circular_progress_bar'     => 15,
                        'circular_progress_bar_int' => 16,
                        'color_cloud'               => 20,
                        'odometer'                  => 22,
                        'basic_chart'               => 23,
                    ];

                    $value_process_types = [
                        'max' => 6,
                        'min' => 7,
                        'avg' => 8,
                    ];

                    // Get ID of item type. Skip if it does not exist.
                    if (isset($types[$items_array['type']]) === false) {
                        register_error(
                            DEMO_VISUAL_CONSOLE,
                            __('Error in %s: the specified type is not a valid one. It must be one of the following values: static_image, module_graph, custom_graph, value, label, icon. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                        );
                        continue;
                    }

                    $element_values = [];

                    $element_values['type'] = $types[$items_array['type']];
                    if ($items_array['type'] == 'value') {
                        if (isset($items_array['process']) === true && isset($value_process_types[$items_array['process']])) {
                            $element_values['type'] = $value_process_types[$items_array['process']];

                            if (isset($items_array['interval']) === true) {
                                $element_values['period'] = $items_array['interval'];
                            }
                        }
                    }

                    $element_values['id_layout'] = $created_id;

                    if ($items_array['type'] === 'static_image') {
                        if (isset($items_array['image']) === false
                            || is_string($items_array['image']) === false
                        ) {
                            // The above fields are required for this item.
                            register_error(
                                DEMO_VISUAL_CONSOLE,
                                __('Error in %s: image field must be specified for static image item type. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                            );
                            continue;
                        }

                        $element_values['image'] = $items_array['image'];

                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }

                        if (isset($items_array['visual_console']) === true) {
                            $id_vc = db_get_value('id', 'tlayout', 'name', $items_array['visual_console']);

                            if (!($id_vc > 0)) {
                                continue;
                            }

                            $element_values['id_layout_linked'] = $id_vc;
                        }
                    }

                    if ($items_array['type'] === 'module_graph') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }

                        if (isset($items_array['interval']) === true) {
                            $element_values['period'] = $items_array['interval'];
                        }

                        if (isset($items_array['graph_type']) === true) {
                            $element_values['type_graph'] = $items_array['graph_type'];
                        }

                        if (isset($items_array['image']) === true) {
                            $element_values['image'] = $items_array['image'];
                        }
                    }

                    if ($items_array['type'] === 'custom_graph') {
                        if (isset($items_array['graph_name']) === true
                            && is_string($items_array['graph_name']) === true
                        ) {
                            $id_custom_graph = reset(custom_graphs_search('', $items_array['graph_name']))['id_graph'];

                            if ($id_custom_graph > 0) {
                                $element_values['id_custom_graph'] = $id_custom_graph;
                            } else {
                                continue;
                            }
                        }

                        if (isset($items_array['interval']) === true) {
                            $element_values['period'] = $items_array['interval'];
                        }

                        if (isset($items_array['image']) === true) {
                            $element_values['image'] = $items_array['image'];
                        }
                    }

                    if ($items_array['type'] === 'basic_chart') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }

                        if (isset($items_array['interval']) === true) {
                            $element_values['period'] = $items_array['interval'];
                        }
                    }

                    if ($items_array['type'] === 'event_history') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }

                        if (isset($items_array['interval']) === true) {
                            $element_values['period'] = $items_array['interval'];
                        }
                    }

                    if ($items_array['type'] === 'odometer') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }
                    }

                    if ($items_array['type'] === 'color_cloud') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }
                    }

                    if ($items_array['type'] === 'box') {
                        if (isset($items_array['border_color']) === true) {
                            $element_values['border_color'] = $items_array['border_color'];
                        }

                        if (isset($items_array['fill_color']) === true) {
                            $element_values['fill_color'] = $items_array['fill_color'];
                        }
                    }

                    if ($items_array['type'] === 'icon') {
                        if (isset($items_array['image']) === false
                            || is_string($items_array['image']) === false
                        ) {
                            // The above fields are required for this item.
                            register_error(
                                DEMO_VISUAL_CONSOLE,
                                __('Error in %s: image field must be specified for icon item type. Skipping creation of item with index %d', $filename, ($item_access_idx - 1))
                            );
                            continue;
                        }

                        $element_values['image'] = $items_array['image'];

                        if (isset($items_array['visual_console']) === true) {
                            $id_vc = db_get_value('id', 'tlayout', 'name', $items_array['visual_console']);

                            if (!($id_vc > 0)) {
                                continue;
                            }

                            $element_values['id_layout_linked'] = $id_vc;
                        }
                    }

                    if ($items_array['type'] === 'value') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }
                    }

                    if (isset($items_array['label']) === true) {
                        $element_values['label'] = io_safe_input($items_array['label']);
                    }

                    if (isset($items_array['label_position']) === true) {
                        $element_values['label_position'] = $items_array['label_position'];
                    }

                    if (isset($items_array['x']) === true) {
                        $element_values['pos_x'] = $items_array['x'];
                    }

                    if (isset($items_array['y']) === true) {
                        $element_values['pos_y'] = $items_array['y'];
                    }

                    if (isset($items_array['width']) === true) {
                        $element_values['width'] = $items_array['width'];
                    }

                    if (isset($items_array['height']) === true) {
                        $element_values['height'] = $items_array['height'];
                    }

                    $element_values['show_on_top'] = (isset($items_array['show_on_top']) === true && $items_array['show_on_top'] === true) ? 1 : 0;

                    // Check here percentile items as height is used for max value
                    if ($items_array['type'] === 'percentile' || $items_array['type'] === 'bubble' || $items_array['type'] === 'circular_progress_bar' || $items_array['type'] === 'circular_progress_bar_int') {
                        if (isset($items_array['agent_name']) === true) {
                            $matched_agents = agents_get_agents(
                                ['nombre' => $items_array['agent_name']],
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
                            $agent_id = $matched_agents[0]['id_agente'];

                            if (!($agent_id > 0)) {
                                continue;
                            }

                            $element_values['id_agent'] = $agent_id;

                            if (isset($items_array['module']) === true) {
                                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                                $module_id = $module_row['id_agente_modulo'];

                                if (!($module_id > 0)) {
                                    continue;
                                }

                                $element_values['id_agente_modulo'] = $module_id;
                            }
                        }

                        $element_values['border_width'] = 0;
                        if (isset($items_array['min']) === true) {
                            $element_values['border_width'] = $items_array['min'];
                        }

                        $element_values['height'] = 100;
                        if (isset($items_array['max']) === true) {
                            $element_values['height'] = $items_array['max'];
                        }

                        $element_values['image'] = 'percent';
                    }

                    $id = db_process_sql_insert('tlayout_data', $element_values);

                    if ($id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $id,
                            'table_name' => 'tlayout_data',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item if could not be registered in tdemo_data.
                            db_process_sql_delete('tlayout_data', ['id' => $id]);

                            register_error(
                                DEMO_VISUAL_CONSOLE,
                                __('Uncaught error (source %s): could not create visual console item with index %d', $filename, ($item_access_idx - 1))
                            );

                            continue;
                        }
                    } else {
                        register_error(
                            DEMO_VISUAL_CONSOLE,
                            __('Uncaught error (source %s): could not create visual console item with index %d', $filename, ($item_access_idx - 1))
                        );
                    }
                }
            }
        }

        update_progress($total_items_count, $vc_count, $vc_count);
        update_item_checked(DEMO_VISUAL_CONSOLE);
    } else {
        register_error(DEMO_VISUAL_CONSOLE, __('No configuration files found or failed to parse files'));
    }

    $dashboards_count = count(($parsed_ini['dashboards'] ?? []));
    if ($dashboards_count > 0) {
        // Create dashboards.
        foreach ($parsed_ini['dashboards'] as $ini_data) {
            $data = $ini_data['dashboard_data'];
            $items = $ini_data['dashboard_items'];

            // Check for mandatory fields.
            if (isset($data['name']) === false
                || isset($data['group']) === false
            ) {
                // Name and group fields must be specified for dashbaord.
                continue;
            }

            $id_group = get_group_or_create_demo_group($data['group']);

            if ($id_group === false) {
                // Group could not be created. Skip dashboard creation.
                continue;
            }

            $insert_values = [];

            $insert_values['name'] = io_safe_input($data['name']);
            $insert_values['id_group'] = $id_group;

            $created_id = db_process_sql_insert('tdashboard', $insert_values);

            if ($created_id > 0) {
                // Register created item in tdemo_data.
                $values = [
                    'item_id'    => $created_id,
                    'table_name' => 'tdashboard',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback demo item creation if could not be registered in tdemo_data.
                    db_process_sql_delete('tdashboard', ['id' => $created_id]);
                    continue;
                }
            } else {
                // Dashboard could not be created. Skip creation of item.
                continue;
            }

            if (count($items) > 0) {
                $item_access_idx = 1;
                $order = -1;
                while (1) {
                    $items_array = [];
                    foreach ($items as $key => $value) {
                        $items_array[$key] = ($value[$item_access_idx] ?? null);
                    }

                    $item_access_idx++;

                    $test_empty_array = array_filter($items_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    if (isset($items_array['type']) === false || isset($items_array['title']) === false) {
                        // All dashboard widgets must have a type and a title.
                        continue;
                    }

                    // Get ID of widget type. Skip if it does not exist.
                    $type_id = db_get_value('id', 'twidget', 'unique_name', $items_array['type']);

                    if (!($type_id > 0)) {
                        continue;
                    }

                    $title = io_safe_input($items_array['title']);
                    $element_values = [];

                    if ($items_array['type'] === 'single_graph') {
                        if (isset($items_array['agent_name']) === false
                            || isset($items_array['module']) === false
                        ) {
                            // The above fields are required for this item.
                            continue;
                        }

                        $matched_agents = agents_get_agents(
                            ['nombre' => $items_array['agent_name']],
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
                        $agent_id = $matched_agents[0]['id_agente'];

                        if (!($agent_id > 0)) {
                            continue;
                        }

                        $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                        $module_id = $module_row['id_agente_modulo'];

                        if (!($module_id > 0)) {
                            continue;
                        }

                        $options_data = [
                            'title'             => $title,
                            'background'        => '#ffffff',
                            'agentId'           => "$agent_id",
                            'metaconsoleId'     => 0,
                            'moduleId'          => "$module_id",
                            'period'            => (isset($items_array['interval']) === true) ? $items_array['interval'] : '86400',
                            'showLegend'        => 1,
                            'projection_switch' => false,
                            'period_projection' => '300',
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'custom_graph') {
                        if (isset($items_array['graph_name']) === false
                            || isset($items_array['graph_type']) === false
                        ) {
                            // The above fields are required for this item.
                            continue;
                        }

                        // Try to get graph and skip if not exists.
                        $id_graph = db_get_value('id_graph', 'tgraph', 'name', io_safe_input($items_array['graph_name']));

                        if (!($id_graph > 0)) {
                            continue;
                        }

                        $graph_types = [
                            'line'   => 2,
                            'area'   => 0,
                            's_line' => 3,
                            's_area' => 1,
                            'h_bars' => 6,
                            'v_bars' => 7,
                            'gauge'  => 5,
                            'pie'    => 8,
                        ];

                        if (isset($graph_types[$items_array['graph_type']]) === true) {
                            $graph_type_id = $items_array['graph_type'];
                        } else {
                            // Specified graph type is not a valid one.
                            continue;
                        }

                        $options_data = [
                            'title'      => $title,
                            'background' => '#ffffff',
                            'id_graph'   => $id_graph,
                            'type'       => $graph_type_id,
                            'period'     => (isset($items_array['interval']) === true) ? $items_array['interval'] : 86400,
                            'showLegend' => 1,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'reports') {
                        if (isset($items_array['report_name']) === false) {
                            // The above fields are required for this item.
                            continue;
                        }

                        $id_report = reports_get_reports(['name' => io_safe_input($items_array['report_name'])], ['id_report'])[0]['id_report'];

                        if (!($id_report > 0)) {
                            continue;
                        }

                        $options_data = [
                            'title'      => $title,
                            'background' => '#ffffff',
                            'reportId'   => $id_report,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'network_map') {
                        if (isset($items_array['map_name']) === false) {
                            // The above fields are required for this item.
                            continue;
                        }

                        $id_map = db_get_value('id', 'tmap', 'name', io_safe_input($items_array['map_name']));

                        if (!($id_map > 0)) {
                            continue;
                        }

                        $options_data = [
                            'title'        => $title,
                            'background'   => '#ffffff',
                            'networkmapId' => "$id_map",
                            'xOffset'      => '0',
                            'yOffset'      => '0',
                            'zoomLevel'    => 0.5,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'service_map') {
                        if (isset($items_array['service_name']) === false) {
                            // The above fields are required for this item.
                            continue;
                        }

                        $services = services_get_services(['name' => io_safe_input($items_array['service_name'])]);

                        $service_id = $services[0]['id'];

                        if (!($service_id > 0)) {
                            continue;
                        }

                        $options_data = [
                            'title'      => $title,
                            'background' => '#ffffff',
                            'serviceId'  => "$service_id",
                            'sunburst'   => (isset($items_array['show_sunburst']) === true && $items_array['show_sunburst'] === true) ? 1 : 0,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'system_group_status') {
                        $options_data = [
                            'title'      => $title,
                            'background' => '#ffffff',
                            'groupId'    => ['0'],
                            'status'     => ['4,1,0,2'],
                            'sunburst'   => false,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'graph_module_histogram') {
                        if (isset($items_array['agent_name']) === false
                            || isset($items_array['module']) === false
                        ) {
                            // The above fields are required for this item.
                            continue;
                        }

                        $matched_agents = agents_get_agents(
                            ['nombre' => $items_array['agent_name']],
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
                        $agent_id = $matched_agents[0]['id_agente'];

                        if (!($agent_id > 0)) {
                            continue;
                        }

                        $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                        $module_id = $module_row['id_agente_modulo'];

                        if (!($module_id > 0)) {
                            continue;
                        }

                        $options_data = [
                            'title'         => $title,
                            'background'    => '#ffffff',
                            'agentId'       => "$agent_id",
                            'metaconsoleId' => 0,
                            'moduleId'      => "$module_id",
                            'period'        => (isset($items_array['interval']) === true) ? $items_array['interval'] : '86400',
                            'sizeLabel'     => 30,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'events_list') {
                        $options_data = [
                            'title'                 => $title,
                            'background'            => '#ffffff',
                            'eventType'             => 0,
                            'maxHours'              => 8,
                            'limit'                 => 20,
                            'eventStatus'           => -1,
                            'severity'              => -1,
                            'groupId'               => [''],
                            'tagsId'                => [''],
                            'groupRecursion'        => 0,
                            'customFilter'          => -1,
                            'columns_events_widget' => [
                                'mini_severity,evento,estado,agent_name,timestamp',
                                '',
                            ],
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'top_n_events_by_group') {
                        $options_data = [
                            'title'           => $title,
                            'background'      => '#ffffff',
                            'amountShow'      => 10,
                            'maxHours'        => 8,
                            'groupId'         => ['0'],
                            'legendPosition'  => 'bottom',
                            'show_total_data' => 0,
                        ];

                        $order++;
                    }

                    if ($items_array['type'] === 'top_n') {
                        $options_data = [
                            'title'      => $title,
                            'background' => '#ffffff',
                            'agent'      => (isset($items_array['agent_name']) === true) ? $items_array['agent_name'] : '.*',
                            'module'     => (isset($items_array['module']) === true) ? $items_array['module'] : '.*',
                            'period'     => (isset($items_array['interval']) === true) ? $items_array['interval'] : '86400',
                            'quantity'   => '10',
                            'order'      => '2',
                            'display'    => '0',
                            'type_graph' => 'bar_vertical',
                            'legend'     => 'agent_module',
                        ];

                        $order++;
                    }

                    $item_x = $items_array['x'];
                    $item_y = $items_array['y'];
                    $item_width = $items_array['width'];
                    $item_height = $items_array['height'];

                    $position_data = [
                        'x'      => (isset($items_array['x']) === true) ? "$item_x" : '0',
                        'y'      => (isset($items_array['y']) === true) ? "$item_y" : '0',
                        'width'  => (isset($items_array['width']) === true) ? "$item_width" : '4',
                        'height' => (isset($items_array['height']) === true) ? "$item_height" : '4',
                    ];

                    $element_values = [
                        'position'     => json_encode($position_data),
                        'options'      => json_encode($options_data),
                        'order'        => $order,
                        'id_dashboard' => $created_id,
                        'id_widget'    => $type_id,
                        'prop_width'   => $items_array['width'],
                        'prop_height'  => $items_array['height'],
                    ];

                    $id = db_process_sql_insert('twidget_dashboard', $element_values);

                    if ($id > 0) {
                        // Register created demo item in tdemo_data.
                        $values = [
                            'item_id'    => $id,
                            'table_name' => 'twidget_dashboard',
                        ];
                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                        if ($result === false) {
                            // Rollback demo item if could not be registered in tdemo_data.
                            db_process_sql_delete('twidget_dashboard', ['id' => $id]);
                            continue;
                        }
                    }
                }
            }
        }

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
                'item_id'    => $created_plugin_id,
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
                            'item_id'    => $id_plugin_module,
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

    $module_items = array_filter(
        $demo_items,
        function ($item) {
            return ($item['table_name'] === 'tagente_modulo');
        }
    );

    $inventory_module_items = array_filter(
        $demo_items,
        function ($item) {
            return ($item['table_name'] === 'tagent_module_inventory');
        }
    );

    $items_delete_id_buffer = [];

    foreach ($inventory_module_items as $item) {
        $items_delete_id_buffer[] = $item['item_id'];
    }

    $in_clause = implode(',', $items_delete_id_buffer);
    // Delete from tagente_datos_inventory.
    db_process_sql('DELETE FROM tagente_datos_inventory where id_agent_module_inventory IN ('.$in_clause.')');


    $items_delete_id_buffer = [];

    foreach ($module_items as $item) {
        $items_delete_id_buffer[] = $item['item_id'];
    }

    $in_clause = implode(',', $items_delete_id_buffer);
    // Delete from tagente_datos.
    db_process_sql('DELETE FROM tagente_datos where id_agente_modulo IN ('.$in_clause.')');

    $items_delete_id_buffer = [];

    $table_id_field_dict = [
        'tconfig_os'                   => 'id_os',
        'tagente'                      => 'id_agente',
        'tgrupo'                       => 'id_grupo',
        'tagente_modulo'               => 'id_agente_modulo',
        'tmodule_inventory'            => 'id_module_inventory',
        'tagent_module_inventory'      => 'id_agent_module_inventory',
        'tgraph'                       => 'id_graph',
        'tmap'                         => 'id',
        'treport'                      => 'id_report',
        'treport_content'              => 'id_rc',
        'treport_content_sla_combined' => 'id',
        'tservice'                     => 'id',
        'tservice_element'             => 'id',
        'ttrap'                        => 'id_trap',
        'titem'                        => 'id',
        'tgraph_source'                => 'id_gs',
        'twidget_dashboard'            => 'id',
        'tdashboard'                   => 'id',
        'tlayout'                      => 'id',
        'tlayout_data'                 => 'id',
        'tagente_estado'               => 'id_agente_estado',
        'trel_item'                    => 'id',
        'tplugin'                      => 'id',
        'tgis_data_status'             => 'tagente_id_agente',
        'tgis_map'                     => 'id_tgis_map',
        'tgis_map_layer'               => 'id_tmap_layer',
    ];

    foreach ($demo_items as $item) {
        $items_delete_id_buffer[$item['table_name']][] = $item['item_id'];
    }

    foreach ($items_delete_id_buffer as $table_name => $ids_array) {
        $all_success = true;
        $in_clause = implode(',', $ids_array);
        $table_id_field = $table_id_field_dict[$table_name];
        $all_success = db_process_sql('DELETE FROM '.$table_name.' WHERE '.$table_id_field.' IN ('.$in_clause.')');

        if ($all_success !== false) {
            // Delete tdemo_data registers if there were no errors when deleting the environment demo items.
            db_process_sql('DELETE FROM tdemo_data WHERE table_name="'.$table_name.'" AND item_id IN ('.$in_clause.')');
        }
    }

    echo 1;
    return;
}

if ($action === 'get_progress_bar') {
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
                'item_id'    => $id_group,
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
