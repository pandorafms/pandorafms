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

require_once 'include/functions_gis.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('include/functions_metaconsole.php');

ui_require_javascript_file('openlayers.pandora');
ui_require_css_file('agent_view');

enterprise_include_once('operation/agentes/ver_agente.php');

check_login();
if (is_ajax()) {
    $get_agent_json = (bool) get_parameter('get_agent_json');
    $get_agent_modules_json = (bool) get_parameter('get_agent_modules_json');
    $get_agent_status_tooltip = (bool) get_parameter('get_agent_status_tooltip');
    $get_agents_group_json = (bool) get_parameter('get_agents_group_json');
    $get_modules_group_json = (bool) get_parameter('get_modules_group_json');
    $get_modules_group_value_name_json = (bool) get_parameter('get_modules_group_value_name_json');
    $get_agent_modules_json_for_multiple_agents = (bool) get_parameter('get_agent_modules_json_for_multiple_agents');
    $get_agent_modules_alerts_json_for_multiple_agents = (bool) get_parameter('get_agent_modules_alerts_json_for_multiple_agents');
    $get_agent_modules_multiple_alerts_json_for_multiple_agents = (bool) get_parameter('get_agent_modules_multiple_alerts_json_for_multiple_agents');
    $get_agents_json_for_multiple_modules = (bool) get_parameter('get_agents_json_for_multiple_modules');
    $get_agent_modules_json_for_multiple_agents_id = (bool) get_parameter('get_agent_modules_json_for_multiple_agents_id');
    $get_agentmodule_status_tooltip = (bool) get_parameter('get_agentmodule_status_tooltip');
    $get_group_status_tooltip = (bool) get_parameter('get_group_status_tooltip');
    $get_agent_id = (bool) get_parameter('get_agent_id');
    $get_agents_source_json = (bool) get_parameter('get_agents_source_json');
    $cluster_mode = (bool) get_parameter('cluster_mode', 0);
    $agent_alias = get_parameter('alias', '');
    $agents_inserted = get_parameter('agents_inserted', []);
    $id_group = (int) get_parameter('id_group');

    $refresh_contact = get_parameter('refresh_contact', 0);

    if ($refresh_contact) {
        $id_agente = get_parameter('id_agente', 0);
        if ($id_agente > 0) {
            $last_contact = db_get_value_sql(
                sprintf(
                    'SELECT format(intervalo,2) - (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(IF(ultimo_contacto > ultimo_contacto_remoto, ultimo_contacto, ultimo_contacto_remoto))) as "val"
                     FROM `tagente`
                     WHERE id_agente = %d ',
                    $id_agente
                )
            );

            $progress = agents_get_next_contact($id_agente);
            if ($progress < 0 || $progress > 100) {
                $progress = 100;
            }

            echo json_encode(
                [
                    'progress'     => $progress,
                    'last_contact' => $last_contact,
                ]
            );
        }

        return;
    }

    if ($get_agents_group_json) {
        $id_group = (int) get_parameter('id_group');
        $recursion = (bool) get_parameter('recursion');
        $id_os = get_parameter('id_os', '');
        $agent_name = get_parameter('name', '');

        $privilege = (string) get_parameter('privilege', 'AR');
        // Is is possible add keys prefix to avoid auto sorting in js object conversion.
        $keys_prefix = (string) get_parameter('keys_prefix', '');
        $status_agents = (int) get_parameter('status_agents', AGENT_STATUS_ALL);

        // Build filter.
        $filter = [];

        if (!empty($id_os)) {
            $filter['id_os'] = $id_os;
        }

        if (!empty($agent_name)) {
            $filter['nombre'] = '%'.$agent_name.'%';
        }

        if (!empty($agent_alias)) {
            $filter['alias'] = '%'.$agent_alias.'%';
        }

        $filter['status'] = $status_agents;

        if ($cluster_mode) {
            $agent_id_os = db_get_all_rows_sql('select id_os from tconfig_os where id_os != 100');

            foreach ($agent_id_os as $key => $value) {
                $agent_id_os_array[] = $agent_id_os[$key]['id_os'];
            }

            $filter['id_os'] = $agent_id_os_array;

            if ($agents_inserted[0] != '') {
                $agents_id_list = '';

                foreach ($agents_inserted as $elem) {
                    if ($elem === end($agents_inserted)) {
                        $agents_id_list .= $elem;
                    } else {
                            $agents_id_list .= $elem.',';
                    }
                }

                $agent_id_agente = db_get_all_rows_sql('select id_agente from tagente where id_agente not in ('.$agents_id_list.')');

                foreach ($agent_id_agente as $key => $value) {
                    $agent_id_agente_array[] = $agent_id_agente[$key]['id_agente'];
                }

                $filter['id_agente'] = $agent_id_agente_array;
            }
        }

        // Perform search.
        $agents = agents_get_group_agents(
            $id_group,
            $filter,
            'lower',
            false,
            $recursion,
            false,
            '|',
            $cluster_mode
        );

        if (empty($agents)) {
            $agents = [];
        }

        // Add keys prefix.
        if ($keys_prefix !== '') {
            $i = 0;
            foreach ($agents as $k => $v) {
                $agents[$keys_prefix.$i] = [
                    'id_agente' => $k,
                    'alias'     => io_safe_output($v),
                ];
                unset($agents[$k]);
                $i++;
            }
        }

        echo json_encode($agents);
        return;
    }

    if ($get_modules_group_json) {
        $id_group = (int) get_parameter('id_module_group', 0);
        $id_agents = get_parameter('id_agents');
        $selection = get_parameter('selection');

        select_modules_for_agent_group($id_group, $id_agents, $selection);
    }

    if ($get_modules_group_value_name_json) {
        $id_agents = get_parameter('id_agents');
        $selection = get_parameter('selection');

        // No filter by module group.
        $modules = select_modules_for_agent_group(0, $id_agents, $selection, false, true);
        echo json_encode($modules);
        return;
    }

    if ($get_agent_json) {
        $id_agent = (int) get_parameter('id_agent');

        $agent = db_get_row('tagente', 'id_agente', $id_agent);

        echo json_encode($agent);
        return;
    }

    if ($get_agent_modules_json_for_multiple_agents_id) {
        $idAgents = get_parameter('id_agent');

        $modules = db_get_all_rows_sql(
            '
			SELECT nombre, id_agente_modulo
			FROM tagente_modulo
			WHERE id_agente IN ('.implode(',', $idAgents).')'
        );

        $return = [];
        foreach ($modules as $module) {
            $return[$module['id_agente_modulo']] = io_safe_output($module['nombre']);
        }

        echo json_encode($return);
        return;
    }

    if ($get_agents_json_for_multiple_modules) {
        $nameModules = get_parameter('module_name');
        $selection_mode = get_parameter('selection_mode', 'common') == 'all';
        $status_modulo = (int) get_parameter('status_module', -1);
        $tags_selected = (array) get_parameter('tags', []);
        $names = select_agents_for_module_group(
            $nameModules,
            $selection_mode,
            [
                'status' => $status_modulo,
                'tags'   => $tags_selected,
            ],
            'AW'
        );

        echo json_encode($names);
        return;
    }

    if ($get_agent_modules_alerts_json_for_multiple_agents) {
        $idAgents = (array) get_parameter('id_agent');
        $templates = (array) get_parameter('templates');

        $selection_mode = get_parameter('selection_mode', 'common');

        $sql = 'SELECT DISTINCT(nombre)
			FROM tagente_modulo t1, talert_template_modules t2
			WHERE t2.id_agent_module = t1.id_agente_modulo
				AND delete_pending = 0
				AND id_alert_template IN ('.implode(',', $templates).')
				AND id_agente IN ('.implode(',', $idAgents).')';

        if ($selection_mode == 'common') {
            $sql .= ' AND (
					SELECT count(nombre)
					FROM tagente_modulo t3, talert_template_modules t4
					WHERE t4.id_agent_module = t3.id_agente_modulo
						AND delete_pending = 0 AND t1.nombre = t3.nombre
						AND id_agente IN ('.implode(',', $idAgents).')
						AND id_alert_template IN ('.implode(',', $templates).')) = ('.count($idAgents).')';
        }

        $sql .= ' ORDER BY t1.nombre';

        $nameModules = db_get_all_rows_sql($sql);

        if ($nameModules == false) {
            $nameModules = [];
        }

        $result = [];
        foreach ($nameModules as $nameModule) {
            $result[] = io_safe_output($nameModule['nombre']);
        }

        echo json_encode($result);
        return;
    }

    if ($get_agent_modules_multiple_alerts_json_for_multiple_agents) {
        $idAgents = get_parameter('id_agent');
        $id_template = get_parameter('template');

        $selection_mode = get_parameter('selection_mode', 'common');

        $sql = 'SELECT DISTINCT(nombre)
			FROM tagente_modulo t1, talert_template_modules t2
			WHERE t2.id_agent_module = t1.id_agente_modulo
				AND delete_pending = 0
				AND id_alert_template = '.$id_template.'
				AND id_agente IN ('.implode(',', $idAgents).')';

        if ($selection_mode == 'common') {
            $sql .= ' AND (
					SELECT count(nombre)
					FROM tagente_modulo t3, talert_template_modules t4
					WHERE t4.id_agent_module = t3.id_agente_modulo
						AND delete_pending = 0 AND t1.nombre = t3.nombre
						AND id_agente IN ('.implode(',', $idAgents).')
						AND id_alert_template = '.$id_template.') = ('.count($idAgents).')';
        }

        $sql .= ' ORDER BY t1.nombre';

        $nameModules = db_get_all_rows_sql($sql);

        if ($nameModules == false) {
            $nameModules = [];
        }

        $result = [];
        foreach ($nameModules as $nameModule) {
            $result[] = io_safe_output($nameModule['nombre']);
        }

        echo json_encode($result);
        return;
    }

    if ($get_agent_modules_json_for_multiple_agents) {
        $idAgents = (array) get_parameter('id_agent');
        $tags = get_parameter('tags', null);
        $module_types_excluded = get_parameter('module_types_excluded', []);
        $module_name = (string) get_parameter('name');
        $selection_mode = get_parameter('selection_mode', 'common');
        $serialized = get_parameter('serialized', '');
        $id_server = (int) get_parameter('id_server', 0);
        $status_modulo = (int) get_parameter('status_module', -1);
        $metaconsole_server_name = null;
        if (!empty($id_server)) {
            $metaconsole_server_name = db_get_value(
                'server_name',
                'tmetaconsole_setup',
                'id',
                $id_server
            );
        }

        if (empty($idAgents[0])) {
            echo json_encode([]);
            return;
        }

        $filter = '1 = 1';

        $all = (string) get_parameter('all', 'all');
        switch ($all) {
            default:
            case 'all':
                $filter .= ' AND 1 = 1';
            break;
            case 'enabled':
                $filter .= ' AND t1.disabled = 0';
            break;
        }

        if (!empty($module_types_excluded) && is_array($module_types_excluded)) {
            $filter .= ' AND t1.id_tipo_modulo NOT IN ('.implode($module_types_excluded).')';
        }

        if (!empty($module_name)) {
            $filter .= " AND t1.nombre COLLATE utf8_general_ci LIKE '%".$module_name."%'";
        }

        // Status selector.
        if ($status_modulo == AGENT_MODULE_STATUS_NORMAL) {
            // Normal.
            $sql_conditions .= ' estado = 0 AND utimestamp > 0 )
			OR (t1.id_tipo_modulo IN(21,22,23,100)) ';
        } else if ($status_modulo == AGENT_MODULE_STATUS_CRITICAL_BAD) {
            // Critical.
            $sql_conditions .= ' estado = 1 AND utimestamp > 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_WARNING) {
            // Warning.
            $sql_conditions .= ' estado = 2 AND utimestamp > 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_NOT_NORMAL) {
            // Not normal.
            $sql_conditions .= ' estado <> 0)';
        } else if ($status_modulo == AGENT_MODULE_STATUS_UNKNOWN) {
            // Unknown.
            $sql_conditions .= ' estado = 3 AND utimestamp <> 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_NOT_INIT) {
            // Not init.
            $sql_conditions .= ' utimestamp = 0 )
				AND t1.id_tipo_modulo NOT IN (21,22,23,100)';
        }

        if ($status_modulo != -1) {
            $filter .= ' AND t1.id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_estado where '.$sql_conditions;
        }

        $sql_tags_join = '';
        $where_tags = '';
        if (tags_has_user_acl_tags($config['id_user'])) {
            $where_tags = tags_get_acl_tags(
                $config['id_user'],
                $id_groups,
                'AR',
                'module_condition',
                'AND',
                'tagente_modulo',
                false,
                [],
                true
            );

            $sql_tags_join = 'INNER JOIN tagente ON tagente.id_agente = t1.id_agente
				INNER JOIN ttag_module ON ttag_module.id_agente_modulo = t1.id_agente_modulo
				LEFT JOIN tagent_secondary_group tasg ON tagente.id_agente = tasg.id_agent';
        }

        if (is_metaconsole()) {
            $result = [];
            $nameModules = [];
            $temp = [];
            $first = true;
            $temp_element = [];
            $counter = 0;
            $first_elements = [];

            $array_mapped = array_map(
                function ($item) use ($metaconsole_server_name) {
                    if (empty($metaconsole_server_name)) {
                        if (strstr($item, '|@_@|')) {
                            $row = explode('|@_@|', $item);
                        } else {
                            $row = explode('|', $item);
                        }

                        $server_name = array_shift($row);
                        $id_agent = array_shift($row);
                    } else {
                        $server_name = $metaconsole_server_name;
                        $id_agent = $item;
                    }

                    return [
                        'server_name' => $server_name,
                        'id_agent'    => $id_agent,
                    ];
                },
                $idAgents
            );

            $array_reduced = array_reduce(
                $array_mapped,
                function ($carry, $item) {
                    if (!isset($carry[$item['server_name']])) {
                        $carry[$item['server_name']] = [];
                    }

                    $carry[$item['server_name']][] = $item['id_agent'];

                    return $carry;
                },
                []
            );

            $last_modules_set = [];

            foreach ($array_reduced as $server_name => $id_agents) {
                // Metaconsole db connection
                // $server_name can be the server id (ugly hack, I know).
                if (is_numeric($server_name)) {
                    $connection = metaconsole_get_connection_by_id($server_name);
                } else {
                    $connection = metaconsole_get_connection($server_name);
                }

                if (metaconsole_load_external_db($connection) != NOERR) {
                    continue;
                }

                // Get agent's modules.
                $sql = sprintf(
                    'SELECT t1.id_agente, t1.id_agente_modulo, t1.nombre
								FROM tagente_modulo t1 %s
								WHERE %s %s
									AND t1.delete_pending = 0
									AND t1.id_agente IN (%s)
									AND (
										SELECT COUNT(nombre)
										FROM tagente_modulo t2
										WHERE t2.delete_pending = 0
											AND t1.nombre = t2.nombre
											AND t2.id_agente IN (%s)) = (%d)',
                    $sql_tags_join,
                    $filter,
                    $where_tags,
                    implode(',', $id_agents),
                    implode(',', $id_agents),
                    count($id_agents)
                );

                $modules = db_get_all_rows_sql($sql);
                if (empty($modules)) {
                    $modules = [];
                }

                $modules_aux = [];
                foreach ($modules as $key => $module) {
                    // Don't change this order, is used in the serialization.
                    $module_data = [
                        'id_module'   => $module['id_agente_modulo'],
                        'id_agent'    => $module['id_agente'],
                        'server_name' => $server_name,
                    ];
                    if (!isset($modules_aux[$module['nombre']])) {
                        $modules_aux[$module['nombre']] = [];
                    }

                    $modules_aux[$module['nombre']][] = $module_data;
                }

                $modules = $modules_aux;

                // Build the next array using the common values.
                if (!empty($last_modules_set)) {
                    $modules = array_intersect_key($modules, $last_modules_set);

                    array_walk(
                        $modules,
                        function (&$module_data, $module_name) use ($last_modules_set) {
                            $module_data = array_merge($module_data, $last_modules_set[$module_name]);
                        }
                    );
                }

                $last_modules_set = $modules;

                // Restore db connection.
                metaconsole_restore_db();
            }

            $result = [];
            foreach ($last_modules_set as $module_name => $module_data) {
                $value = ui_print_truncate_text(io_safe_output($module_name), 'module_medium', false, true);

                $module_data_processed = array_map(
                    function ($item) {
                        return implode('|', $item);
                    },
                    $module_data
                );
                $key = implode(';', $module_data_processed);

                $result[$key] = $value;
            }

            asort($result);
        } else {
            if ($idAgents[0] < 0) {
                if ($selection_mode == 'common') {
                    $sql_agent_total = 'SELECT count(*) FROM tagente WHERE disabled=0';
                    $agent_total = db_get_value_sql($sql_agent_total);
                    $sql = sprintf(
                        "SELECT t1.nombre, t1.id_agente_modulo FROM tagente_modulo t1
						JOIN (SELECT COUNT(*) AS num_names, nombre FROM tagente_modulo
						WHERE disabled=0 AND delete_pending=0 GROUP BY nombre) AS tj
						ON tj.num_names = $agent_total AND tj.nombre = t1.nombre %s %s",
                        $sql_tags_join,
                        (empty($where_tags)) ? '' : " WHERE 1=1 $where_tags"
                    );
                } else {
                    $sql = sprintf(
                        'SELECT t1.nombre, t1.id_agente_modulo FROM tagente_modulo t1 %s %s',
                        $sql_tags_join,
                        (empty($where_tags)) ? '' : " WHERE 1=1 $where_tags"
                    );
                }
            } else {
                $sql = sprintf(
                    'SELECT DISTINCT t1.nombre, t1.id_agente_modulo FROM tagente_modulo t1
					INNER JOIN tagente_estado t2 ON t1.id_agente_modulo = t2.id_agente_modulo
					%s WHERE %s AND t1.delete_pending = 0
					AND t1.id_agente IN ('.implode(',', $idAgents).')
					%s %s',
                    $sql_tags_join,
                    $filter,
                    ' AND t2.datos NOT LIKE "%image%"',
                    $where_tags
                );

                if ($selection_mode == 'common') {
                    $sql .= ' AND (
								SELECT count(nombre)
								FROM tagente_modulo t2
								WHERE t2.delete_pending = 0
									AND t1.nombre = t2.nombre
									AND t2.id_agente IN ('.implode(',', $idAgents).')) = ('.count($idAgents).')';
                } else if ($selection_mode == 'unknown') {
                    $sql .= 'AND t1.id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_estado where estado = 3 OR estado = 4)';
                }
            }

            $sql .= ' ORDER BY nombre';
            $nameModules = db_get_all_rows_sql($sql);
            if ($tags != null) {
                if ((count($tags) >= 1) && ($tags[0] != '') && ($tags[0] != -1)) {
                    $implode_tags = implode(',', $tags);
                    $tag_modules = db_get_all_rows_sql('SELECT DISTINCT id_agente_modulo FROM ttag_module WHERE id_tag IN ('.$implode_tags.')');
                    if ($tag_modules) {
                        $final_modules = [];
                        foreach ($nameModules as $key => $module) {
                            $in_array = false;
                            foreach ($tag_modules as $t_module) {
                                if ($module['id_agente_modulo'] == $t_module['id_agente_modulo']) {
                                    $in_array = true;
                                }
                            }

                            if ($in_array) {
                                $final_modules[] = $module;
                            }
                        }

                        $nameModules = $final_modules;
                    } else {
                        $nameModules = [];
                    }
                }
            }

            if ($nameModules == false) {
                $nameModules = [];
            }

            $result = [];
            foreach ($nameModules as $nameModule) {
                if (empty($serialized)) {
                    $result[io_safe_output($nameModule['nombre'])] = ui_print_truncate_text(
                        io_safe_output($nameModule['nombre']),
                        'module_medium',
                        false,
                        true
                    );
                } else {
                    $result[io_safe_output($nameModule['nombre']).'$*$'.implode('|', $idAgents)] = ui_print_truncate_text(io_safe_output($nameModule['nombre']), 'module_medium', false, true);
                }
            }
        }

        echo json_encode($result);
        return;
    }

    if ($get_agent_modules_json) {
        $id_agent = (int) get_parameter('id_agent');

        // Use -1 as not received.
        $disabled = (int) get_parameter('disabled', -1);
        $delete_pending = (int) get_parameter('delete_pending', -1);
        // Use 0 as not received.
        $id_tipo_modulo = (int) get_parameter('id_tipo_modulo', 0);
        $status_modulo = (int) get_parameter('status_module', -1);

        $tags = (array) get_parameter('tags', []);

        // Filter.
        $filter = [];
        if ($disabled !== -1) {
            $filter['disabled'] = $disabled;
        }

        if ($delete_pending !== -1) {
            $filter['delete_pending'] = $delete_pending;
        }

        if (!empty($id_tipo_modulo)) {
            $filter['id_tipo_modulo'] = $id_tipo_modulo;
        }

        if (empty($filter)) {
            $filter = false;
        }

        $get_only_string_modules = get_parameter(
            'get_only_string_modules',
            false
        );

        if ($get_only_string_modules) {
            $filter['tagente_modulo.id_tipo_modulo IN'] = '(17,23,3,10,33)';
        }

        // Status selector.
        if ($status_modulo == AGENT_MODULE_STATUS_NORMAL) {
            // Normal.
            $sql_conditions .= ' estado = 0 AND utimestamp > 0 )
			OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100)) ';
        } else if ($status_modulo == AGENT_MODULE_STATUS_CRITICAL_BAD) {
            // Critical.
            $sql_conditions .= ' estado = 1 AND utimestamp > 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_WARNING) {
            // Warning.
            $sql_conditions .= ' estado = 2 AND utimestamp > 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_NOT_NORMAL) {
            // Not normal.
            $sql_conditions .= ' estado <> 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_UNKNOWN) {
            // Unknown.
            $sql_conditions .= ' estado = 3 AND utimestamp <> 0 )';
        } else if ($status_modulo == AGENT_MODULE_STATUS_NOT_INIT) {
            // Not init.
            $sql_conditions .= ' utimestamp = 0 )
				AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)';
        }

        if ($status_modulo != -1) {
            $filter['tagente_modulo.id_agente_modulo IN'] = ' (SELECT id_agente_modulo FROM tagente_estado where '.$sql_conditions;
        }


        $get_id_and_name = (bool) get_parameter('get_id_and_name');
        $get_distinct_name = (bool) get_parameter('get_distinct_name');

        // Fields.
        $fields = '*';
        if ($get_id_and_name) {
            $fields = [
                'id_agente_modulo',
                'nombre',
            ];
        }

        if ($get_distinct_name) {
            $fields = ['DISTINCT(tagente_modulo.nombre)'];
        }

        $indexed = (bool) get_parameter('indexed', true);
        $agentName = (string) get_parameter('agent_name', null);
        $server_name = (string) get_parameter('server_name', null);
        $server_id = (int) get_parameter('server_id', 0);
        // This will force to get local modules although metaconsole is active,
        // by default get all modules from all nodes.
        $force_local_modules = (int) get_parameter('force_local_modules', 0);

        if ($agentName != null) {
            $search = [];
            $search['alias'] = io_safe_output($agentName);
        } else {
            $search = false;
        }

        $force_tags = !empty($tags);
        if ($force_tags) {
            $filter['ttag_module.id_tag IN '] = '('.implode(',', $tags).')';
        }

        if (is_metaconsole() && !$force_local_modules) {
            if (enterprise_include_once('include/functions_metaconsole.php') !== ENTERPRISE_NOT_HOOK) {
                $connection = metaconsole_get_connection($server_name);
                if ($server_id > 0) {
                    $connection = metaconsole_get_connection_by_id($server_id);
                }

                if (metaconsole_load_external_db($connection) == NOERR) {
                    // Get all agents if no agent was given.
                    if ($id_agent == 0) {
                        $id_agent = array_keys(
                            agents_get_group_agents(
                                array_keys(users_get_groups()),
                                $search,
                                'none'
                            )
                        );
                    }

                    $agent_modules = agents_get_modules($id_agent, $fields, $filter, $indexed, true, $force_tags);
                }

                // Restore db connection.
                metaconsole_restore_db();
            }
        } else {
            // Get all agents if no agent was given.
            if ($id_agent == 0) {
                $id_agent = array_keys(
                    agents_get_group_agents(
                        array_keys(users_get_groups()),
                        $search,
                        'none'
                    )
                );
            }

            $agent_modules = agents_get_modules($id_agent, $fields, $filter, $indexed, true, $force_tags);
        }

        if (empty($agent_modules)) {
            $agent_modules = [];
        }

        foreach ($agent_modules as $key => $module) {
            $agent_modules[$key]['nombre'] = io_safe_output($module['nombre']);
        }

        $get_order_json = (bool) get_parameter('get_order_json', false);
        if ($get_order_json) {
            $new_elements = [];
            $index = 0;
            foreach ($agent_modules as $key => $module) {
                $new_elements[$index]['id_agente_modulo'] = $module['id_agente_modulo'];
                $new_elements[$index]['nombre'] = io_safe_output($module['nombre']);
                $index++;
            }

            $agent_modules = $new_elements;
        }

        echo json_encode($agent_modules);

        return;
    }

    if ($get_agent_status_tooltip) {
        $id_agent = (int) get_parameter('id_agent');
        $metaconsole = (bool) get_parameter('metaconsole', false);
        $id_server = (int) get_parameter('id_server', 0);
        // Metaconsole.
        $server = null;
        if ($metaconsole) {
            $filter = [];
            if (!empty($id_agent)) {
                $filter['id_tagente'] = $id_agent;
            }

            if (!empty($id_server)) {
                $filter['id_tmetaconsole_setup'] = $id_server;
            }

            $agent = db_get_row_filter('tmetaconsole_agent', $filter);
        } else {
            $agent = db_get_row('tagente', 'id_agente', $id_agent);
        }

        if ($agent === false) {
            return;
        }

        echo '<h3>'.$agent['nombre'].'</h3>';
        echo '<strong>'.__('Main IP').':</strong> '.$agent['direccion'].'<br />';
        echo '<strong>'.__('Group').':</strong> ';

        $hack_metaconsole = '';
        if ($metaconsole) {
            $hack_metaconsole = '../../';
        }

        echo html_print_image($hack_metaconsole.'images/groups_small/'.groups_get_icon($agent['id_grupo']).'.png', true);
        echo groups_get_name($agent['id_grupo']).'<br />';

        echo '<strong>'.__('Last contact').':</strong> '.human_time_comparation($agent['ultimo_contacto']).'<br />';
        echo '<strong>'.__('Last remote contact').':</strong> '.human_time_comparation($agent['ultimo_contacto_remoto']).'<br />';

        if (!$metaconsole) {
            // Fix : Only show agents with module with tags of user profile.
            $_user_tags = tags_get_user_tags($config['id_user'], 'RR');

            $_sql_post = '';
            if (is_array($_user_tags) && !empty($_user_tags)) {
                $_tags = implode(',', array_keys($_user_tags));

                $_sql_post .= ' AND tagente_modulo.id_agente_modulo IN (SELECT a.id_agente_modulo FROM tagente_modulo a, ttag_module b WHERE a.id_agente_modulo=b.id_agente_modulo AND b.id_tag IN ('.$_tags.')) ';
            }

            $sql = sprintf(
                'SELECT tagente_modulo.descripcion,
					tagente_modulo.nombre
				FROM tagente_estado, tagente_modulo 
				WHERE tagente_modulo.id_agente = %d
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.disabled = 0 
					AND tagente_estado.estado = 1',
                $id_agent
            );

            $sql .= $_sql_post;

            $bad_modules = db_get_all_rows_sql($sql);

            $sql = sprintf(
                'SELECT COUNT(*)
				FROM tagente_modulo
				WHERE id_agente = %d
					AND disabled = 0',
                $id_agent
            );
            $total_modules = db_get_sql($sql);

            if ($bad_modules === false) {
                $size_bad_modules = 0;
            } else {
                $size_bad_modules = count($bad_modules);
            }

            // Modules down.
            if ($size_bad_modules > 0) {
                echo '<strong>'.__('Monitors down').':</strong> '.$size_bad_modules.' / '.$total_modules;
                echo '<ul>';
                foreach ($bad_modules as $module) {
                    echo '<li>';
                    echo ui_print_truncate_text($module['nombre'], 'module_small');
                    echo '</li>';
                }

                echo '</ul>';
            }

            // Alerts (if present).
            $sql = sprintf(
                'SELECT COUNT(talert_template_modules.id)
					FROM talert_template_modules, tagente_modulo, tagente
					WHERE tagente.id_agente = %d
						AND tagente.disabled = 0
						AND tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.disabled = 0
						AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
						AND talert_template_modules.times_fired > 0 ',
                $id_agent
            );

            $alert_modules = (int) db_get_sql($sql);

            if ($alert_modules > 0) {
                $sql = sprintf(
                    'SELECT tagente_modulo.nombre, talert_template_modules.last_fired
					FROM talert_template_modules, tagente_modulo, tagente
					WHERE tagente.id_agente = %d
						AND tagente.disabled = 0
						AND tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.disabled = 0
						AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
						AND talert_template_modules.times_fired > 0 ',
                    $id_agent
                );

                $alerts = db_get_all_rows_sql($sql);

                echo '<strong>'.__('Alerts fired').':</strong>';
                echo '<ul>';
                foreach ($alerts as $alert_item) {
                    echo '<li>';
                    echo ui_print_truncate_text($alert_item['nombre']).' -> ';
                    echo human_time_comparation($alert_item['last_fired']);
                    echo '</li>';
                }

                echo '</ul>';
            }
        }

        return;
    }

    if ($get_agentmodule_status_tooltip) {
        $id_module = (int) get_parameter('id_module');
        $metaconsole = (bool) get_parameter('metaconsole');
        $id_server = (int) get_parameter('id_server');

        if ($metaconsole) {
            $server = db_get_row('tmetaconsole_setup', 'id', $id_server);

            if (metaconsole_connect($server) != NOERR) {
                return;
            }
        }

        $module = db_get_row('tagente_modulo', 'id_agente_modulo', $id_module);

        echo '<h3>';
        echo html_print_image('images/brick.png', true).'&nbsp;';
        echo ui_print_truncate_text($module['nombre'], 'module_small', false, true, false).'</h3>';
        echo '<strong>'.__('Type').':</strong> ';
        $agentmoduletype = modules_get_agentmodule_type($module['id_agente_modulo']);
        echo modules_get_moduletype_name($agentmoduletype).'&nbsp;';
        echo html_print_image('images/'.modules_get_type_icon($agentmoduletype), true).'<br />';
        echo '<strong>'.__('Module group').':</strong> ';
        $modulegroup = modules_get_modulegroup_name(modules_get_agentmodule_modulegroup($module['id_agente_modulo']));
        if ($modulegroup === false) {
            echo __('None').'<br />';
        } else {
            echo $modulegroup.'<br />';
        }

        echo '<strong>'.__('Agent').':</strong> ';
        echo ui_print_truncate_text(modules_get_agentmodule_agent_alias($module['id_agente_modulo']), 'agent_small', false, true, false).'<br />';

        if ($module['id_tipo_modulo'] == 18) {
            echo '<strong>'.__('Address').':</strong> ';

            // Get the IP/IPs from the module description Always the IP
            // is the last part of the description (after the last space).
            $ips = explode(' ', $module['descripcion']);
            $ips = $ips[(count($ips) - 1)];

            $ips = explode(',', $ips);
            if (count($ips) == 1) {
                echo $ips[0];
            } else {
                echo '<ul style="display:inline;">';
                foreach ($ips as $ip) {
                    echo '<li>'.$ip.'</li>';
                }

                echo '</ul>';
            }
        }

        if ($metaconsole) {
            metaconsole_restore_db();
        }

        return;
    }

    if ($get_group_status_tooltip) {
        $id_group = (int) get_parameter('id_group');
        $group = db_get_row('tgrupo', 'id_grupo', $id_group);
        echo '<h3>'.html_print_image('images/groups_small/'.groups_get_icon($group['id_grupo']).'.png', true);
        echo ui_print_truncate_text($group['nombre'], GENERIC_SIZE_TEXT, false, true, false).'</h3>';
        echo '<strong>'.__('Parent').':</strong> ';
        if ($group['parent'] == 0) {
            echo __('None').'<br />';
        } else {
            $group_parent = db_get_row('tgrupo', 'id_grupo', $group['parent']);
            echo html_print_image('images/groups_small/'.groups_get_icon($group['parent']).'.png', true);
            echo $group_parent['nombre'].'<br />';
        }

        echo '<strong>'.__('Sons').':</strong> ';
        $groups_sons = db_get_all_fields_in_table('tgrupo', 'parent', $group['id_grupo']);
        if ($groups_sons === false) {
            echo __('None').'<br />';
        } else {
            echo '<br /><br />';
            foreach ($groups_sons as $group_son) {
                echo html_print_image('images/groups_small/'.groups_get_icon($group_son['id_grupo']).'.png', true);
                echo $group_son['nombre'].'<br />';
            }
        }

        return;
    }

    if ($get_agent_id) {
        $agent_name = (string) get_parameter('agent_name');

        echo agents_get_agent_id($agent_name);
        return;
    }

    if ($get_agents_source_json) {
        $source = get_parameter('source', '');

        if (empty($source)) {
            $sql_report_log = 'SELECT id_agente, alias
				FROM tagente, tagent_module_log
				WHERE tagente.id_agente = tagent_module_log.id_agent AND tagente.disabled = 0';
        } else {
            $sql_report_log = 'SELECT id_agente, alias
				FROM tagente, tagent_module_log
				WHERE tagente.id_agente = tagent_module_log.id_agent AND tagente.disabled = 0 AND tagent_module_log.source like "'.$source.'"';
        }

        $all_agent_log = db_get_all_rows_sql($sql_report_log);

        foreach ($all_agent_log as $key => $value) {
            $agents2[$value['id_agente']] = $value['alias'];
        }

        echo json_encode($agents2);
        return;
    }

    return;
}

$id_agente = (int) get_parameter('id_agente', 0);
if (empty($id_agente)) {
    return;
}

$agent_a = check_acl($config['id_user'], 0, 'AR');
$agent_w = check_acl($config['id_user'], 0, 'AW');
$access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');
$agent = db_get_row('tagente', 'id_agente', $id_agente);
// Get group for this id_agente.
$id_grupo = $agent['id_grupo'];

$is_extra = enterprise_hook('policies_is_agent_extra_policy', [$id_agente]);

if ($is_extra === ENTERPRISE_NOT_HOOK) {
    $is_extra = false;
}

$all_groups = agents_get_all_groups_agent($id_agente, $id_grupo);

if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') && ! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW', $id_agente) && !$is_extra) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access (read) to agent '.agents_get_name($id_agente)
    );
    include 'general/noaccess.php';
    return;
}

// Check for Network FLAG change request.
$flag = get_parameter('flag', '');
if ($flag !== '') {
    if ($flag == 1 && check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        $id_agent_module = get_parameter('id_agente_modulo');

        db_process_sql_update(
            'tagente_modulo',
            ['flag' => 1],
            ['id_agente_modulo' => $id_agent_module]
        );
    }
}

// Check for Network FLAG change request.
$flag_agent = get_parameter('flag_agent', '');
if ($flag_agent !== '') {
    if ($flag_agent == 1 && check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        db_process_sql_update('tagente_modulo', ['flag' => 1], ['id_agente' => $id_agente]);
    }
}

if ($agent['icon_path']) {
    $icon = gis_get_agent_icon_map($agent['id_agente'], true);
} else {
    $icon = 'images/bricks.png';
}


// Code for the tabs in the header of agent page.
$tab = get_parameter('tab', 'main');

// Manage tab.
$managetab = [];

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') || $is_extra) {
    $managetab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">'.html_print_image('images/setup.png', true, ['title' => __('Manage')]).'</a>';

    if ($tab == 'manage') {
        $managetab['active'] = true;
    } else {
        $managetab['active'] = false;
    }

    $managetab['godmode'] = 1;
}


// Main tab.
$maintab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">'.html_print_image('images/agent_mc.png', true, ['title' => __('Main')]).'</a>';

if ($tab == 'main') {
    $maintab['active'] = true;
} else {
    $maintab['active'] = false;
}



// Alert tab.
$alerttab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&tab=alert">'.html_print_image('images/op_alerts.png', true, ['title' => __('Alerts')]).'</a>';

if ($tab == 'alert') {
    $alerttab['active'] = true;
} else {
    $alerttab['active'] = false;
}

// Inventory.
$inventorytab = enterprise_hook('inventory_tab');
if ($inventorytab == -1) {
    $inventorytab = '';
}


// Collection.
$collectiontab = enterprise_hook('collection_tab');
if ($collectiontab == -1) {
    $collectiontab = '';
}


// Policy.
$policyTab = enterprise_hook('policy_tab');
if ($policyTab == -1) {
    $policyTab = '';
}

// WUX Console.
$modules_wux = enterprise_hook('get_wux_modules', [$id_agente]);
if ($modules_wux) {
    $wux_console_tab = enterprise_hook('wux_console_tab');
    if ($wux_console_tab == -1) {
        $wux_console_tab = '';
    }
}

$url_route_analyzer = enterprise_hook('get_url_route_analyzer_modules', [$id_agente]);
if ($url_route_analyzer) {
    $url_route_analyzer_tab = enterprise_hook('url_route_analyzer_tab');
    if ($url_route_analyzer_tab == -1) {
        $url_route_analyzer_tab = '';
    }
}

// GIS tab.
$gistab = [];
if ($config['activate_gis']) {
    $gistab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=gis&id_agente='.$id_agente.'">'.html_print_image('images/op_gis.png', true, [ 'title' => __('GIS data')]).'</a>';

    if ($tab == 'gis') {
        $gistab['active'] = true;
    } else {
        $gistab['active'] = false;
    }
}


// Incident tab.
$total_incidents = agents_get_count_incidents($id_agente);
if ($total_incidents > 0) {
    $incidenttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&tab=incident&id_agente='.$id_agente.'">'.html_print_image('images/book_edit.png', true, ['title' => __('Incidents')]).'</a>';

    if ($tab == 'incident') {
        $incidenttab['active'] = true;
    } else {
        $incidenttab['active'] = false;
    }
}


// Url address tab.
if ($agent['url_address'] != '') {
    $urladdresstab['text'] = '<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&tab=url_address&id_agente='.$id_agente.'">'.html_print_image('images/link.png', true, ['title' => __('Url address')]).'</a>';
}

if ($tab == 'url_address') {
    $urladdresstab['active'] = true;
} else {
    $urladdresstab['active'] = false;
}


// Custom fields tab.
$custom_fields['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=custom_fields&id_agente='.$id_agente.'">'.html_print_image('images/custom_field.png', true, ['title' => __('Custom fields')]).'</a>';
if ($tab == 'custom_fields') {
    $custom_fields['active'] = true;
} else {
    $custom_fields['active'] = false;
}


// Graphs tab.
$graphs['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente='.$id_agente.'">'.html_print_image('images/chart.png', true, ['title' => __('Graphs')]).'</a>';
if ($tab == 'graphs') {
    $graphs['active'] = true;
} else {
    $graphs['active'] = false;
}


// Log viewer tab.
if (enterprise_installed() && $config['log_collector']) {
    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    $agent_has_logs = (bool) db_get_value('id_agent', 'tagent_module_log', 'id_agent', $id_agente);

    if ($agent_has_logs && !$is_windows) {
        $log_viewer_tab = [];
        $log_viewer_tab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=log_viewer&id_agente='.$id_agente.'">'.html_print_image('images/gm_log.png', true, ['title' => __('Log Viewer')]).'</a>';
        $log_viewer_tab['active'] = $tab == 'log_viewer';
    }
}

// EHorus tab.
if ($config['ehorus_enabled'] && !empty($config['ehorus_custom_field'])
    && (check_acl_one_of_groups(
        $config['id_user'],
        $all_groups,
        'AW'
    ) || is_user_admin($config['id_user']))
) {
    $user_info = users_get_user_by_id($config['id_user']);
    if ($config['ehorus_user_level_conf'] && !$user_info['ehorus_user_level_enabled']) {
        // If ehorus user configuration is enabled, and userr acces level is disabled do not show eHorus tab.
    } else {
        $ehorus_agent_id = agents_get_agent_custom_field($id_agente, $config['ehorus_custom_field']);
        if (!empty($ehorus_agent_id)) {
            $tab_url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=ehorus&id_agente='.$id_agente;
            $ehorus_tab['text'] = '<a href="'.$tab_url.'" class="ehorus_tab">'.html_print_image('images/ehorus/ehorus.png', true, [ 'title' => __('eHorus')]).'</a>';

            // Hidden subtab layer.
            $ehorus_tab['sub_menu'] = '<ul class="mn subsubmenu" style="float:none;">';
            $ehorus_tab['sub_menu'] .= '<a class="tab_terminal" href="'.$tab_url.'&client_tab=terminal">';
            $ehorus_tab['sub_menu'] .= '<li class="nomn tab_godmode" style="text-align: center;">'.html_print_image('images/ehorus/terminal.png', true, [ 'title' => __('Terminal')]);
            $ehorus_tab['sub_menu'] .= '</li>';
            $ehorus_tab['sub_menu'] .= '</a>';
            $ehorus_tab['sub_menu'] .= '<a class="tab_display" href="'.$tab_url.'&client_tab=display">';
            $ehorus_tab['sub_menu'] .= '<li class="nomn tab_godmode" style="text-align: center;">'.html_print_image('images/ehorus/vnc.png', true, [ 'title' => __('Display')]);
            $ehorus_tab['sub_menu'] .= '</li>';
            $ehorus_tab['sub_menu'] .= '</a>';
            $ehorus_tab['sub_menu'] .= '<a class="tab_processes" href="'.$tab_url.'&client_tab=processes">';
            $ehorus_tab['sub_menu'] .= '<li class="nomn tab_godmode" style="text-align: center;">'.html_print_image('images/ehorus/processes.png', true, [ 'title' => __('Processes')]);
            $ehorus_tab['sub_menu'] .= '</li>';
            $ehorus_tab['sub_menu'] .= '</a>';
            $ehorus_tab['sub_menu'] .= '<a class="tab_services" href="'.$tab_url.'&client_tab=services">';
            $ehorus_tab['sub_menu'] .= '<li class="nomn tab_godmode" style="text-align: center;">'.html_print_image('images/ehorus/services.png', true, [ 'title' => __('Services')]);
            $ehorus_tab['sub_menu'] .= '</li>';
            $ehorus_tab['sub_menu'] .= '</a>';
            $ehorus_tab['sub_menu'] .= '<a class="tab_files" href="'.$tab_url.'&client_tab=files">';
            $ehorus_tab['sub_menu'] .= '<li class="nomn tab_godmode" style="text-align: center;">'.html_print_image('images/ehorus/files.png', true, [ 'title' => __('Files')]);
            $ehorus_tab['sub_menu'] .= '</li>';
            $ehorus_tab['sub_menu'] .= '</a>';
            $ehorus_tab['sub_menu'] .= '</ul>';

            $ehorus_tab['active'] = $tab == 'ehorus';
        }
    }
}

$onheader = [
    'manage'             => $managetab,
    'main'               => $maintab,
    'alert'              => $alerttab,
    'inventory'          => $inventorytab,
    'collection'         => $collectiontab,
    'gis'                => $gistab,
    'custom'             => $custom_fields,
    'graphs'             => $graphs,
    'policy'             => $policyTab,
    'ux_console'         => $ux_console_tab,
    'wux_console'        => $wux_console_tab,
    'url_route_analyzer' => $url_route_analyzer_tab,
];

// Added after it exists
// If the agent has incidents associated.
if ($total_incidents) {
    $onheader['incident'] = $incidenttab;
}

if ($agent['url_address'] != '') {
    $onheader['url_address'] = $urladdresstab;
}

// If the log viewer tab exists.
if (isset($log_viewer_tab) && !empty($log_viewer_tab)) {
    $onheader['log_viewer'] = $log_viewer_tab;
}

// If the ehorus id exists.
if (isset($ehorus_tab) && !empty($ehorus_tab)) {
    $onheader['ehorus'] = $ehorus_tab;
}

// Tabs for extensions.
$tab_name_extensions = '';
foreach ($config['extensions'] as $extension) {
    if (isset($extension['extension_ope_tab']) && !isset($extension['extension_god_tab'])) {
        if (check_acl_one_of_groups($config['id_user'], $all_groups, $extension['extension_ope_tab']['acl'])) {
            // VMware extension is only available for VMware OS.
            if ($extension['extension_ope_tab']['id'] === 'vmware_manager') {
                // Check if OS is vmware.
                $id_remote_field = db_get_value(
                    'id_field',
                    'tagent_custom_fields',
                    'name',
                    'vmware_type'
                );

                $vmware_type = db_get_value_filter(
                    'description',
                    'tagent_custom_data',
                    [
                        'id_field' => $id_remote_field,
                        'id_agent' => $agent['id_agente'],
                    ]
                );

                if ($vmware_type != 'vm') {
                    continue;
                }
            }

            // RHEV extension is only available for RHEV Virtual Machines.
            if ($extension['extension_ope_tab']['id'] === 'rhev_manager') {
                // Get id for remote field "rhev_type".
                $id_remote_field = db_get_value('id_field', 'tagent_custom_fields', 'name', 'rhev_type');
                // Get rhev type for this agent.
                $rhev_type = db_get_value_filter('description', 'tagent_custom_data', ['id_field' => $id_remote_field, 'id_agent' => $agent['id_agente']]);
                // Check if rhev type is a vm.
                if ($rhev_type != 'vm') {
                    continue;
                }
            }

            $image = $extension['extension_ope_tab']['icon'];
            $name = $extension['extension_ope_tab']['name'];
            $id = $extension['extension_ope_tab']['id'];
            $tab_name_extensions = $name;

            $id_extension = get_parameter('id_extension', '');

            if ($id_extension == $id) {
                $active = true;
            } else {
                $active = false;
            }

            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=extension&id_agente='.$id_agente.'&id_extension='.$id;

            $extension_tab = [
                'text'   => '<a href="'.$url.'">'.html_print_image($image, true, [ 'title' => $name]).'</a>',
                'active' => $active,
            ];

            $onheader = ($onheader + [$id => $extension_tab]);
        }
    }
}

switch ($tab) {
    case 'custom_fields':
        $tab_name = 'Custom fields';
    break;

    case 'gis':
        $tab_name = 'GIS data';
    break;

    case 'manage':
        $tab_name = 'Manage';
    break;

    case 'main':
        $tab_name = 'Main';
        $help_header = 'agent_'.$tab.'_tab';
    break;

    case 'data_view':
        $tab_name = '';
    break;

    case 'alert':
        $tab_name = 'Alerts';
    break;

    case 'inventory':
        $tab_name = 'Inventory';
    break;

    case 'collection':
        $tab_name = 'Collection';
    break;

    case 'policy':
        $tab_name = 'Policies';
    break;

    case 'ux_console_tab':
        $tab_name = 'UX Console';
    break;

    case 'wux_console_tab':
        $tab_name = 'WUX Console';
        $help_header = 'wux_console_tab';
    break;

    case 'url_route_analyzer_tab':
        $tab_name = 'URL Route Analyzer';
    break;

    case 'graphs';
        $tab_name = 'Graphs';
    break;

    case 'incident':
        $tab_name = 'Incidents';
    break;

    case 'url_address':
        $tab_name = 'Url address';
    break;

    case 'log_viewer':
        $tab_name = 'Log viewer';
    break;

    case 'ehorus':
        $tab_name = 'eHorus';
    break;

    case 'extension':
        $tab_name = $tab_name_extensions;
    break;

    default:
        $tab_name = '';
        $help_header = '';
    break;
}

ui_print_page_header(
    agents_get_alias($id_agente),
    $icon,
    false,
    $help_header,
    false,
    $onheader,
    false,
    '',
    $config['item_title_size_text'],
    '',
    ui_print_breadcrums(
        [
            __('Monitoring'),
            __('View'),
            '<span class="breadcrumb_active">'.$tab_name.'</span>',
        ]
    )
);


switch ($tab) {
    case 'custom_fields':
        include 'custom_fields.php';
    break;

    case 'gis':
        include 'gis_view.php';
    break;

    case 'manage':
        include 'estado_generalagente.php';
    break;

    case 'main':
    default:
        include 'estado_generalagente.php';
        echo "<a name='monitors'></a>";
        include 'estado_monitores.php';
        echo "<a name='alerts'></a>";
        include 'alerts_status.php';
        // Check permissions to read events
        if (check_acl($config['id_user'], 0, 'ER')) {
            echo "<a name='events'></a>";
            include 'status_events.php';
        }
    break;

    case 'data_view':
        include 'datos_agente.php';
    break;

    case 'alert':
        include 'alerts_status.php';
    break;

    case 'inventory':
        enterprise_include('operation/agentes/agent_inventory.php');
    break;

    case 'collection':
        enterprise_include('operation/agentes/collection_view.php');
    break;

    case 'policy':
        enterprise_include('operation/agentes/policy_view.php');
    break;

    case 'ux_console_tab':
        enterprise_include('operation/agentes/ux_console_view.php');
    break;

    case 'wux_console_tab':
        enterprise_include('operation/agentes/wux_console_view.php');
    break;

    case 'url_route_analyzer_tab':
        enterprise_include('operation/agentes/url_route_analyzer.php');
    break;

    case 'graphs';
        include 'operation/agentes/graphs.php';
    break;

    case 'incident':
        include 'godmode/agentes/agent_incidents.php';
    break;

    case 'url_address':
        include 'operation/agentes/url_address.php';
    break;

    case 'log_viewer':
        $embebed_into_agent_view = true;
        enterprise_include('operation/log/log_viewer.php');
    break;

    case 'ehorus':
        include 'operation/agentes/ehorus.php';
    break;

    case 'extension':
        $found = false;
        foreach ($config['extensions'] as $extension) {
            if (isset($extension['extension_ope_tab'])) {
                $id = $extension['extension_ope_tab']['id'];
                $function = $extension['extension_ope_tab']['function'];

                $id_extension = get_parameter('id_extension', '');

                if ($id_extension == $id) {
                    call_user_func_array($function, []);
                    $found = true;
                }
            }
        }

        if (!$found) {
            ui_print_error_message('Invalid tab specified in '.__FILE__.':'.__LINE__);
        }
    break;
}
