<?php
/**
 * Hook in Host&Devices for CSV import.
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

include $config['homedir'].'/include/functions_inventory.php';

$action = (string) get_parameter('action', null);

if ($action === 'create_demo_data') {
    $agents_num = (int) get_parameter('agents_num', 0);
    hd("qqqq ".$agents_num, true);
    if ($agents_num > 0) {
        // Read all ini files.
        $parsed_ini[] = parse_ini_file($config['homedir'].'/extras/demodata/agents/linux.ini', true, INI_SCANNER_TYPED);
hd($parsed_ini, true);
        $ag_groups_num = ($agents_num / 10);

        // Create new group per group of ten agents that are to be created.
        for ($i=0; $i<$ag_groups_num; $i++) {
            $group_name = db_get_value_sql(
                'SELECT tg.nombre
                FROM tdemo_data tdd INNER JOIN tgrupo tg ON tdd.item_id = tg.id_grupo
                WHERE tdd.table_name = "tgrupo"
                ORDER BY tdd.id DESC'
            );

            if ($group_name === false) {
                $group_last_id = 0;
            } else {
                $group_last_id = (int) explode(' ', $group_name)[2];
            }

            if (!($group_last_id > -1)) {
                echo json_encode(['msg' => 'Demo group ID not valid']);
                return;
            }

            $demo_group_name = 'Demo group '.($i + $group_last_id + 1);

            $created_group_id = groups_create_group($demo_group_name, []);

            if ($created_group_id > 0) {
                $demo_group_ids[] = $created_group_id;

                // Register created demo item in tdemo_data.
                $values = [
                    'item_id'    => $created_group_id,
                    'table_name' => 'tgrupo',
                ];
                $result = (bool) db_process_sql_insert('tdemo_data', $values);

                if ($result === false) {
                    // Rollback group creation.
                    db_process_sql_delete('tgrupo', ['id_grupo' => $created_group_id]);
                }
            } else {
                echo json_encode(['msg' => 'Could not create demo agent group']);
                return;
            }
        }

        $agents_created_count = 0;
        $group_idx = 0;

        $agent_alias = db_get_value_sql('SELECT ta.alias FROM tdemo_data tdd INNER JOIN tagente ta ON tdd.item_id = ta.id_agente WHERE tdd.table_name="tagente" ORDER BY tdd.id DESC');

        if ($agent_alias === false) {
            $agent_last_id = 0;
        } else {
            $agent_last_id = (int) explode(' ', $agent_alias)[1];
        }

        foreach ($parsed_ini as $ini_so_data) {
            $agent_data = $ini_so_data['agent_data'];
            $modules_data = $ini_so_data['modules'];
            $inventory = $ini_so_data['inventory'];
            $inventory_values = $ini_so_data['inventory_values'];

            $address_network = $agent_data['address_network'];
            $os_versions = $agent_data['os_versions'];
            $os_name = $agent_data['os_name'];

            // Get OS id given OS name.
            $id_os = db_get_value_filter('id_os', 'tconfig_os', ['name' => $os_name]);

            if ($id_os === false) {
                // Create OS if does not exist.
                $values = ['name' => $os_name];
                $id_os = (bool) db_process_sql_insert('tconfig_os', $values);

                if ($id_os === false) {
                    continue;
                }
            }

            // Define agents to be created per group of 10.
            $agents_per_os = [
                'Linux'   => 5,
                'Windows' => 2,
                'MAC OS'  => 1,
                'BSD'     => 1,
                'Cisco'   => 1,
            ];

            $agents_to_create = ($ag_groups_num * $agents_per_os[$os_name]);

            for ($i=0; $i < $agents_to_create; $i++) {
                $next_ip_address = calculateNextHostAddress($address_network);

                $os_version = current($os_versions);
                next($os_versions);

                if (current($os_versions) === false) {
                    reset($os_versions);
                }

                $agent_last_id++;

                $created_agent_id = agents_create_agent(
                    $agent_data['agent_alias'].' '.$agent_last_id,
                    $demo_group_ids[$group_idx],
                    // Default interval.
                    300,
                    $next_ip_address,
                    false,
                    false,
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
                }

                $agents_created_count++;

                if (($agents_created_count % 10) === 0) {
                    $group_idx++;
                }

                // Create agent modules.
                $module_access_idx = 1;

                while (1) {
                    $modules_array = [];
                    foreach ($modules_data as $key => $value) {
                        $modules_array[$key] = ($value[$module_access_idx] ?? null);
                    }

                    $test_empty_array = array_filter($modules_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    $id_tipo = db_get_value_filter('id_tipo', 'ttipo_modulo', ['nombre' => $modules_array['type']]);

                    $values = [
                        'unit'            => $modules_array['unit'],
                        'descripcion'     => $modules_array['description'],
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

                        if ((string) $parsed[0] === 'RANDOM') {
                            $data = rand($parsed[1], $parsed[2]);
                        } else if ((string) $parsed[0] === 'PROC') {
                            $probability = (int) $parsed[1];

                            $data = 0;

                            if ($probability > 0) {
                                $randomNumber = rand(1, 100);

                                if ($randomNumber <= $probability) {
                                    $data = 1;
                                }
                            }
                        }

                        $agent_data_values = [
                            'id_agente_modulo' => $created_mod_id,
                            'datos'            => $data,
                            'utimestamp'       => time(),
                        ];

                        db_process_sql_insert('tagente_datos', $agent_data_values);
                    }

                    $module_access_idx++;
                };

                // Create inventory modules.
                $module_access_idx = 1;
                $date_time = new DateTime();
                $current_date_time = $date_time->format('Y-m-d H:i:s');

                while (1) {
                    $modules_array = [];
                    foreach ($inventory as $key => $value) {
                        $modules_array[$key] = ($value[$module_access_idx] ?? null);
                    }

                    $test_empty_array = array_filter($modules_array);

                    if (empty($test_empty_array) === true) {
                        break;
                    }

                    $values = [
                        'name'        => $modules_array['name'],
                        'data_format' => $modules_array['format'],
                        'id_os'       => 1,
                    ];

                    // STEP 1: tmodule_inventory.
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

                    $module_access_idx++;

                    // STEP 2: tagent_module_inventory.
                    $values = [
                        'id_agente' => $created_agent_id,
                        'id_module_inventory' => $created_inventory_mod_id,
                        'interval' => 300,
                        'utimestamp' => time(),
                        'timestamp' => $current_date_time,
                    ];



                    // STEP 3: Create inventory values (tagente_datos_inventory).
                    $field_idx = 1;
                    $values_array = explode(';', $modules_array['values']);

                    $selected_inventory_values = array_filter(
                        $inventory_values,
                        function ($key) use ($values_array) {
                            return in_array($key, $values_array);
                        },
                        ARRAY_FILTER_USE_KEY
                    );

                    hd("INV VALUES", true);
                    hd($selected_inventory_values, true);

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

                    hd("DATA STR",true);
                    hd($data_str, true);

                    $inventory_data_values = [
                        'data'       => $data_str,
                        'utimestamp' => time(),
                        'timestamp'  => $current_date_time,
                    ];

                    $created_inventory_data = db_process_sql_insert('tagente_datos_inventory', $inventory_data_values);
                    hd("CID",true);
                    hd($inventory_data_values, true);
                    hd($created_inventory_data, true);

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
                };
            }
        }
    }

    return;
}


function calculateNextHostAddress($ip) {
    list($network, $subnet) = explode('/', $ip);

    // Convert the network address to an array of octets.
    $octets = explode('.', $network);

    // Convert the last octet to an integer.
    $lastOctet = (int)$octets[3];

    // Increment the last octet, and wrap around if it exceeds 255.
    $lastOctet = ($lastOctet + 1) % 256;

    // Assemble the next host address.
    $nextHost = implode('.', array($octets[0], $octets[1], $octets[2], $lastOctet));

    return $nextHost . '/' . $subnet;
}