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

include $config['homedir'].'/include/functions_inventory.php';
include $config['homedir'].'/include/functions_custom_graphs.php';

$action = (string) get_parameter('action', null);

if ($action === 'create_demo_data') {
    config_update_value('demo_data_load_progress', 0);

    // Read agent ini files.
    $directories = [
        'agents',
        'graphs',
        'network_maps',
        'services',
        'reports',
    ];

    $demodata_directory = $config['homedir'].'/extras/demodata/';

    foreach ($directories as $directory) {
        $directory_path = $demodata_directory.$directory;
        if (is_dir($directory_path)) {
            $files = scandir($directory_path);
            $files = array_diff($files, ['.', '..']);
        }

        foreach ($files as $file) {
            $parsed_ini[$directory][] = parse_ini_file($directory_path.'/'.$file, true, INI_SCANNER_TYPED);
        }
    }

    $total_agents_to_create = (int) get_parameter('agents_num', 0);

    if ($total_agents_to_create > 0) {
        $agents_to_create = 0;
        $agents_created_count = [];

        // First loop over agents to get the total agents to be created and init agent created count for each agent.
        foreach ($parsed_ini['agents'] as $ini_agent_data) {
            if (isset($ini_agent_data['agent_data']['agents_number']) === true
                && $ini_agent_data['agent_data']['agents_number'] > 0
            ) {
                $agents_to_create += (int) $ini_agent_data['agent_data']['agents_number'];
                $agents_created_count[$ini_agent_data['agent_data']['agent_name']] = 0;
            }
        }

        $agent_created_total = 0;

        if ($total_agents_to_create > 0 && $agents_to_create > 0) {
            while ($agent_created_total < $total_agents_to_create) {
                // Traverse agent ini files and create agents.
                foreach ($parsed_ini['agents'] as $ini_agent_data) {
                    // Read blocks: agent data, modules, inventory, inventory values.
                    $agent_data = $ini_agent_data['agent_data'];

                    if (isset($agent_data['agents_number']) === true
                        && !((int) $agent_data['agents_number'] > 0)
                    ) {
                        // No agents are specified to be created for this agent.
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
                        $id_os = (bool) db_process_sql_insert('tconfig_os', $values);

                        if ($id_os === false) {
                            // Could not create OS. Skip agent creation.
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

                    // Create agents (remaining number of agents to reach number specified by user).
                    for ($i = 0; $i < min($iter_agents_to_create, $max_agents_to_limit); $i++) {
                        $next_ip_address = calculateNextHostAddress($address_network);

                        $os_version = current($os_versions);
                        next($os_versions);

                        if (current($os_versions) === false) {
                            reset($os_versions);
                        }

                        $created_agent_id = agents_create_agent(
                            $agent_data['agent_alias'].'-'.($agents_created_count[$agent_data['agent_name']] + 1),
                            $group_id,
                            // Default interval.
                            300,
                            $next_ip_address,
                            false,
                            true,
                            $id_os,
                            $os_version
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
                                continue;
                            }
                        } else {
                            // Could not create agent. Skip.
                            continue;
                        }

                        $agents_created_count[$agent_data['agent_name']]++;

                        // Calculate progress.
                        $percentage_inc = (100 / ($agents_to_create * count($parsed_ini)));
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

                        config_update_value('demo_data_load_progress', ($current_progress_val + $percentage_inc));

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

                            $id_tipo = db_get_value_filter('id_tipo', 'ttipo_modulo', ['nombre' => $modules_array['type']]);

                            if (isset($modules_array['type']) === false
                                || is_string($modules_array['type']) === false
                            ) {
                                // Module type not defined.
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
                                    continue;
                                }

                                // Insert module data.
                                $parsed = explode(';', $modules_array['values']);

                                $date_time = new DateTime();
                                $current_date_time = $date_time->format('Y-m-d H:i:s');
                                $back_periods = 10;
                                $period_mins = 5;

                                $utimestamp = time();

                                // Generate back_periods amounts of tagente_datos rows each period_mins minutes back in time.
                                for ($p = 0; $p < $back_periods; $p++) {
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
                                            }
                                        }
                                    }

                                    $agent_data_values = [
                                        'id_agente_modulo' => $created_mod_id,
                                        'datos'            => $data,
                                        'utimestamp'       => $utimestamp,
                                    ];

                                    $created_data_id = db_process_sql_insert('tagente_datos', $agent_data_values);

                                    if ($created_data_id > 0) {
                                        // Register created demo item in tdemo_data.
                                        $values = [
                                            'item_id'    => $created_data_id,
                                            'table_name' => 'tagente_datos',
                                        ];

                                        $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                        if ($result === false) {
                                            // Rollback demo item creation if could not be registered in tdemo_data.
                                            db_process_sql_delete('tagente_datos', ['id_agente_modulo' => $created_data_id]);
                                            continue;
                                        }
                                    }

                                    $utimestamp -= 300;
                                }
                            }
                        };

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
                                    continue;
                                }
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

                            $created_inventory_data = db_process_sql_insert('tagente_datos_inventory', $inventory_data_values);

                            if ($created_inventory_data > 0) {
                                // Register created inventory data element in tdemo_data.
                                $values = [
                                    'item_id'    => $created_inventory_data,
                                    'table_name' => 'tagente_datos_inventory',
                                ];
                                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                if ($result === false) {
                                    // Rollback inventory module if could not be registered in tdemo_data.
                                    db_process_sql_delete(
                                        'tagente_datos_inventory',
                                        ['id_agent_module_inventory' => $created_inventory_data]
                                    );

                                    continue;
                                }
                            }
                        }

                        // Create traps.
                        $date_time = new DateTime();
                        $current_date_time = $date_time->format('Y-m-d H:i:s');
                        $back_periods = 10;
                        $period_mins = 5;
                        $utimestamp = time();

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

                                $create_trap = false;
                                if (isset($traps_array['chance_percent']) === false) {
                                    // Chance percent must be specified.
                                    continue;
                                } else {
                                    $trap_creation_prob = (int) $traps_array['chance_percent'];

                                    if ($trap_creation_prob > 0) {
                                        $randomNumber = rand(1, 100);
                                        if ($randomNumber <= $trap_creation_prob) {
                                            $create_trap = true;
                                        }
                                    }
                                }

                                if ($create_trap === false) {
                                    continue;
                                }

                                if (isset($traps_array['value']) === false || is_string($traps_array['value']) === false) {
                                    // Trap value must be specified.
                                    continue;
                                } else {
                                    $parsed = explode(';', $traps_array['values']);

                                    $data = '';
                                    if ((string) $parsed[0] === 'RANDOM') {
                                        $data = rand($parsed[1], $parsed[2]);
                                    }
                                }

                                $values = [
                                    'oid'        => $traps_array['oid'],
                                    'value'      => $data,
                                    'type'       => $traps_array['snmp_type'],
                                    'timestamp'  => $current_date_time,
                                    'utimestamp' => $utimestamp,
                                ];

                                $created_trap_id = db_process_sql_insert('ttrap', $values);

                                if ($created_trap_id > 0) {
                                    // Register created demo item in tdemo_data.
                                    $values = [
                                        'item_id'    => $created_trap_id,
                                        'table_name' => 'ttrap',
                                    ];
                                    $result = (bool) db_process_sql_insert('tdemo_data', $values);

                                    if ($result === false) {
                                        // Rollback inventory module if could not be registered in tdemo_data.
                                        db_process_sql_delete('ttrap', ['id_module_inventory' => $created_trap_id]);
                                        continue;
                                    }
                                }
                            }

                            $date_time->sub(new DateInterval("PT{$period_mins}M"));
                            $current_date_time = $date_time->format('Y-m-d H:i:s');
                            $utimestamp -= 300;
                        }
                    }
                }

                $agent_created_total = array_sum($agents_created_count);

                if ($agent_created_total === 0) {
                    // Stop traversing agent files if no agent could be created after first round.
                    break;
                }
            }
        }
    }

    // Create network maps.
    foreach ($parsed_ini['network_maps'] as $ini_nm_data) {
        $map_data = $ini_nm_data['map_data'];
        $map_items = $ini_nm_data['map_items'];

        $nm_name = $map_data['name'];
        $nm_group = $map_data['group'];
        $nm_description = $map_data['description'];
        $nm_node_radius = $map_data['node_radius'];

        $nm_id_group = get_group_or_create_demo_group($nm_group);

        if ($nm_id_group === false) {
            // Network map could not be created. Skip network map creation.
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
        $values['description'] = $nm_description;
        $values['id_group'] = $nm_id_group;
        $values['name'] = $nm_name;

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
                continue;
            }
        } else {
            // Network map group could not be created. Skip creation of map.
            continue;
        }

        if (count($map_items) > 0) {
            $item_access_idx = 1;

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
                    $matched_agents = agents_get_agents(['nombre' => $items_array['agent_name']], ['id_agente', 'id_os', 'alias']);

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
                        continue;
                    }
                } else {
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
                        continue;
                    }
                }
            }
        }

        // Calculate progress.
        $percentage_inc = (100 / count($parsed_ini));
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

        config_update_value('demo_data_load_progress', ($current_progress_val + $percentage_inc));
    }

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
            continue;
        }

        $values = [];
        $values['description'] = $graph_description;
        $values['id_group'] = $graph_id_group;
        $values['name'] = $graph_name;
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
                continue;
            }
        } else {
            // Graph could not be created. Skip creation of graph.
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

                if (isset($items_array['agent_name']) === false || is_string($items_array['agent_name']) === false) {
                    // Agent must be defined. Skip graph item creation.
                    continue;
                }

                if (isset($items_array['module']) === false || is_string($items_array['module']) === false) {
                    // Module must be defined. Skip graph item creation.
                    continue;
                }

                $matched_agents = agents_get_agents(['nombre' => $items_array['agent_name']], ['id_agente']);
                $agent_id = $matched_agents[0]['id_agente'];

                $module_row = modules_get_agentmodule_id(io_safe_input($items_array['module']), $agent_id);

                $module_id = $module_row['id_agente_modulo'];

                if (!($module_id > 0)) {
                    continue;
                }

                $item_values = [
                    'id_graph'        => $id_graph,
                    'id_agent_module' => $module_id,
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
                        continue;
                    }
                }
            }
        }

        // Calculate progress.
        $percentage_inc = (100 / count($parsed_ini));
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

        config_update_value('demo_data_load_progress', ($current_progress_val + $percentage_inc));
    }

    // Create reports.
    foreach ($parsed_ini['reports'] as $ini_report_data) {
        $report_data = $ini_report_data['report_data'];
        $report_items = $ini_report_data['report_items'];

        $group_id = get_group_or_create_demo_group($report_data['group']);

        if ($group_id === false) {
            // Could not create group. Skip report creation.
            continue;
        }

        $report_values = [];
        $report_values['id_group'] = $group_id;
        $report_values['name'] = $report_data['name'];
        $report_values['description'] = $report_data['description'];

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
                continue;
            }
        } else {
            // Report could not be created. Skip creation of map.
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

                $item_values = [];

                $item_values['id_report'] = $created_report_id;

                if (isset($items_array['agent_name']) === true) {
                    if (is_string($items_array['module']) === false) {
                        continue;
                    }

                    $matched_agents = agents_get_agents(['nombre' => $items_array['agent_name']], ['id_agente']);

                    $matched_agent = $matched_agents[0]['id_agente'];
                    if (isset($matched_agent) === true && $matched_agent > 0) {
                        $item_values['id_agent'] = $matched_agent;
                    } else {
                        // Skip report item creation if agent does not exist.
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
                        continue;
                    }
                }

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
                        continue;
                    }
                }
            }
        }

        // Calculate progress.
        $percentage_inc = (100 / count($parsed_ini));
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

        config_update_value('demo_data_load_progress', ($current_progress_val + $percentage_inc));
    }

    // Create services.
    foreach ($parsed_ini['services'] as $ini_service_data) {
        $service_data = $ini_service_data['service_data'];
        $service_items = $ini_service_data['service_items'];

        // Check for mandatory fields.
        if (isset($service_data['name']) === false
            || isset($service_data['group']) === false
        ) {
            continue;
        }

        $id_group = get_group_or_create_demo_group($service_data['group']);

        if ($id_group === false) {
            // Group could not be created. Skip graph creation.
            continue;
        }

        $service_values = [];

        $service_values['name'] = $service_data['name'];
        $service_values['description'] = $service_data['description'];
        $service_values['id_group'] = $id_group;
        $service_values['critical'] = $service_data['critical'];
        $service_values['warning'] = $service_data['warning'];
        $service_values['auto_calculate'] = (isset($service_data['mode']) === true && (string) $service_data['mode'] === 'smart') ? 1 : 0;

        $created_service_id = db_process_sql_insert('tservice', $service_values);

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
                continue;
            }
        } else {
            // Service could not be created. Skip creation of map.
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

                $element_values = [];

                $element_values = ['id_service' => $created_service_id];

                $element_type = (string) $items_array['type'];

                if (in_array($element_type, ['agent', 'module', 'dynamic', 'service']) === false) {
                    // Skip element creation if type not valid.
                    continue;
                }

                if (in_array($element_type, ['agent', 'module', 'dynamic']) === true) {
                    // Get agent ID and module ID.
                    $matched_agents = agents_get_agents(['nombre' => $items_array['agent_name']], ['id_agente']);
                    $matched_agent = $matched_agents[0]['id_agente'];

                    if (isset($matched_agent) === true && $matched_agent > 0) {
                        $element_values['id_agent'] = $matched_agent;
                    } else {
                        // Skip element creation if agent does not exist.
                        continue;
                    }
                }

                if (in_array($element_type, ['module', 'dynamic']) === true) {
                    if ($element_values['id_agent'] > 0) {
                        $module_id = db_get_value_sql('SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = "'.io_safe_input($items_array['module']).'" AND id_agente = '.$element_values['id_agent']);

                        if ($module_id > 0) {
                            $element_values['id_agente_modulo'] = $module_id;
                        } else {
                            // Skip element creation if agent module does not exist.
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
                        $services = services_get_services(['name' => $items_array['service_name']]);

                        $service_id = $services[0]['id'];

                        if ($service_id > 0) {
                            $element_values['id_service_child'] = $service_id;
                        } else {
                            // Skip element creation if specified service does not exist.
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
                        continue;
                    }
                }
            }
        }

        // Calculate progress.
        $percentage_inc = (100 / count($parsed_ini));
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

        config_update_value('demo_data_load_progress', ($current_progress_val + $percentage_inc));
    }


    $demo_agents_count = db_get_value('count(*)', 'tdemo_data', 'table_name', 'tagente');

    echo json_encode(['agents_count' => $demo_agents_count]);

    return;
}

if ($action === 'cleanup_demo_data') {
    config_update_value('demo_data_load_progress', 0);

    $demo_items = db_get_all_rows_in_table('tdemo_data');

    foreach ($demo_items as $item) {
        $table_id_field_dict = [
            'tconfig_os'              => 'id_os',
            'tagente'                 => 'id_agente',
            'tgrupo'                  => 'id_grupo',
            'tagente_modulo'          => 'id_agente_modulo',
            'tmodule_inventory'       => 'id_module_inventory',
            'tagent_module_inventory' => 'id_agent_module_inventory',
            'tagente_datos_inventory' => 'id_agent_module_inventory',
            'tgraph'                  => 'id_graph',
            'tmap'                    => 'id',
            'treport'                 => 'id_report',
            'treport_content'         => 'id_rc',
            'tservice'                => 'id',
            'tservice_element'        => 'id',
            'ttrap'                   => 'id_trap',
            'tagente_datos'           => 'id_agente_modulo',
            'titem'                   => 'id',
            'tgraph_source'           => 'id_gs',
        ];

        $table_id_field = $table_id_field_dict[$item['table_name']];

        $result = db_process_sql_delete(
            $item['table_name'],
            [$table_id_field => $item['item_id']]
        );

        if ($result !== false) {
            db_process_sql_delete(
                'tdemo_data',
                ['item_id' => $item['item_id']]
            );
        }
    }

    echo 1;
}

if ($action === 'get_progress_bar') {
    $operation = (string) get_parameter('operation');

    if ($operation === 'create') {
        $current_progress_val = db_get_value_filter('value', 'tconfig', ['token' => 'demo_data_load_progress']);

        if ($current_progress_val === false) {
            $current_progress_val = 0;
        }
    } else if ($operation === 'cleanup') {
        $demo_items_to_cleanup = (int) get_parameter('demo_items_to_cleanup');
        $count_current_demo_items = db_get_value('count(*)', 'tdemo_data');
        $current_progress_val = ((($demo_items_to_cleanup - $count_current_demo_items) * 100) / $demo_items_to_cleanup);
    }

    echo $current_progress_val;

    return;
}

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

function get_group_or_create_demo_group($name)
{
    if (is_string($name) === false) {
        return false;
    }

    $id_group = db_get_value('id_grupo', 'tgrupo', 'nombre', $name);

    if ($id_group > 0) {
        return $id_group;
    } else {
        $id_group = groups_create_group($group, []);

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
