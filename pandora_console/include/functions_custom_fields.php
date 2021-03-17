<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Custom fields View
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


/**
 * Returns custom field all or 1.
 *
 * @param integer custom_field_id id.
 * @param bool Prepare for select or return all rows.
 *
 * @return array custom fields data.
 */
function get_custom_fields($custom_field_id=false, $select=true, $display_on_front=false)
{
    $fields = ($select) ? ' tcf.id_field, tcf.name ' : ' tcf.* ';

    $where = ($custom_field_id) ? ' WHERE tcf.id_field ='.$custom_field_id : ' WHERE 1=1';

    $display = ($display_on_front) ? ' AND tcf.display_on_front = 1' : '';

    $result_array = [];
    if (!is_metaconsole()) {
        $sql = sprintf(
            'SELECT
			%s
			FROM tagent_custom_fields tcf
			%s
			%s
			',
            $fields,
            $where,
            $display
        );

        $result = db_get_all_rows_sql($sql);

        if (isset($result) && is_array($result)) {
            foreach ($result as $key => $value) {
                if ($select) {
                    $result_array[$value['name']] = $value['name'];
                } else {
                    $result_array[$value['name']] = $value;
                }
            }
        }
    } else {
        $metaconsole_connections = metaconsole_get_connection_names();
        // For all nodes.
        if (isset($metaconsole_connections) && is_array($metaconsole_connections)) {
            $result_meta = [];
            foreach ($metaconsole_connections as $metaconsole) {
                // Get server connection data.
                $server_data = metaconsole_get_connection($metaconsole);

                // Establishes connection.
                if (metaconsole_load_external_db($server_data) !== NOERR) {
                    continue;
                }

                $sql = sprintf(
                    'SELECT
					%s
					FROM tagent_custom_fields tcf
					%s
					%s
					',
                    $fields,
                    $where,
                    $display
                );

                $result[] = db_get_all_rows_sql($sql);

                // Restore connection to root node.
                metaconsole_restore_db();

                if (isset($result) && is_array($result)) {
                    foreach ($result as $custom) {
                        foreach ($custom as $key => $value) {
                            if ($select) {
                                $result_array[$value['name']] = $value['name'];
                            } else {
                                $result_array[$value['name']] = $value;
                            }
                        }
                    }
                }
            }
        } else {
            $result_array = false;
        }
    }

    return $result_array;
}


/**
 * Returns custom field data.
 *
 * @param integer $custom_field_name Custom_field_id id.
 *
 * @return array custom fields data.
 */
function get_custom_fields_data($custom_field_name)
{
    if (!isset($custom_field_name)) {
        return false;
    }

    if (!is_metaconsole()) {
        $sql = sprintf(
            "SELECT tcf.id_field, tcf.name, tcd.description
			FROM tagent_custom_fields tcf
			INNER JOIN tagent_custom_data tcd
				ON tcf.id_field = tcd.id_field
			INNER JOIN tagente ta
				ON tcd.id_agent = ta.id_agente
			WHERE tcd.description <> ''
				AND tcf.name = '%s'
			GROUP BY tcf.id_field, tcd.description",
            $custom_field_name
        );

        $result = db_get_all_rows_sql($sql);
        if (isset($result) && is_array($result)) {
            foreach ($result as $k => $v) {
                $array_result[$v['description']] = $v['description'];
            }
        }
    } else {
        $metaconsole_connections = metaconsole_get_connection_names();
        // For all nodes.
        if (isset($metaconsole_connections) && is_array($metaconsole_connections)) {
            $result_meta = [];
            foreach ($metaconsole_connections as $metaconsole) {
                // Get server connection data.
                $server_data = metaconsole_get_connection($metaconsole);

                // Establishes connection.
                if (metaconsole_load_external_db($server_data) !== NOERR) {
                    continue;
                }

                $sql = sprintf(
                    "SELECT tcf.id_field, tcf.name, tcd.description
					FROM tagent_custom_fields tcf
					INNER JOIN tagent_custom_data tcd
						ON tcf.id_field = tcd.id_field
					INNER JOIN tagente ta
						ON tcd.id_agent = ta.id_agente
					WHERE tcd.description <> ''
						AND tcf.name = '%s'
					GROUP BY tcf.id_field, tcd.description",
                    $custom_field_name
                );

                $result_meta[] = db_get_all_rows_sql($sql);

                // Restore connection to root node.
                metaconsole_restore_db();
            }

            $array_result = [];
            if (isset($result_meta) === true
                && is_array($result_meta) === true
            ) {
                foreach ($result_meta as $result) {
                    if (isset($result) === true
                        && is_array($result) === true
                    ) {
                        foreach ($result as $k => $v) {
                            $array_result[$v['description']] = $v['description'];
                        }
                    }
                }
            }
        } else {
            $array_result = false;
        }
    }

    return $array_result;
}


/**
 * Function for custom field view return all conter for agents
 *
 * @param  array $filters Params for search.
 * @return void False or array.
 */
function agent_counters_custom_fields($filters)
{
    // Filter by status agent.
    $and_status = '';

    $agent_state_normal = 0;
    $agent_state_critical = 0;
    $agent_state_warning = 0;
    $agent_state_unknown = 0;
    $agent_state_notinit = 0;
    $agent_state_total = 0;
    if (is_array($filters['id_status'])) {
        if (!in_array(-1, $filters['id_status'])) {
            if (!in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $filters['id_status'])) {
                if (count($filters['id_status']) > 0) {
                    $and_status = ' AND ( ';
                    foreach ($filters['id_status'] as $key => $value) {
                        $and_status .= ($key != 0) ? ' OR ' : ' ';
                        switch ($value) {
                            default:
                            case AGENT_STATUS_NORMAL:
                                $agent_state_normal = agents_get_status_clause(AGENT_STATUS_NORMAL);
                                $and_status .= agents_get_status_clause(AGENT_STATUS_NORMAL);
                            break;
                            case AGENT_STATUS_CRITICAL:
                                $and_status .= agents_get_status_clause(AGENT_STATUS_CRITICAL);
                                $agent_state_critical = agents_get_status_clause(AGENT_STATUS_CRITICAL);
                            break;

                            case AGENT_STATUS_WARNING:
                                $and_status .= agents_get_status_clause(AGENT_STATUS_WARNING);
                                $agent_state_warning = agents_get_status_clause(AGENT_STATUS_WARNING);
                            break;

                            case AGENT_STATUS_UNKNOWN:
                                $and_status .= agents_get_status_clause(AGENT_STATUS_UNKNOWN);
                                $agent_state_unknown = agents_get_status_clause(AGENT_STATUS_UNKNOWN);
                            break;

                            case AGENT_STATUS_NOT_INIT:
                                $and_status .= agents_get_status_clause(AGENT_STATUS_NOT_INIT);
                                $agent_state_notinit = agents_get_status_clause(AGENT_STATUS_NOT_INIT);
                            break;
                        }
                    }

                    $and_status .= ' ) ';
                    $agent_state_total = agents_get_status_clause(AGENT_STATUS_ALL);
                }
            } else {
                $and_status = ' AND (
					( ta.critical_count > 0 )
					OR ( ta.critical_count = 0 AND ta.warning_count > 0 )
					OR ( ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0 )
					OR ( ta.total_count = ta.notinit_count )
				) ';

                $agent_state_critical = agents_get_status_clause(AGENT_STATUS_CRITICAL);
                $agent_state_warning = agents_get_status_clause(AGENT_STATUS_WARNING);
                $agent_state_unknown = agents_get_status_clause(AGENT_STATUS_UNKNOWN);
                $agent_state_notinit = agents_get_status_clause(AGENT_STATUS_NOT_INIT);
                $agent_state_total = agents_get_status_clause(AGENT_STATUS_ALL);
            }
        } else {
            $agent_state_normal = agents_get_status_clause(AGENT_STATUS_NORMAL);
            $agent_state_critical = agents_get_status_clause(AGENT_STATUS_CRITICAL);
            $agent_state_warning = agents_get_status_clause(AGENT_STATUS_WARNING);
            $agent_state_unknown = agents_get_status_clause(AGENT_STATUS_UNKNOWN);
            $agent_state_notinit = agents_get_status_clause(AGENT_STATUS_NOT_INIT);
            $agent_state_total = agents_get_status_clause(AGENT_STATUS_ALL);
        }
    }

    // Filter by status module.
    $empty_agents_count = "UNION ALL
		SELECT ta.id_agente,
			0 AS c_m_total,
			0 AS mm_normal,
			0 AS mm_critical,
			0 AS mm_warning,
			0 AS mm_unknown,
			0 AS mm_not_init,
			0 AS mm_total
		FROM tagente ta
		LEFT JOIN tagent_secondary_group tasg
			ON ta.id_agente = tasg.id_agent
		WHERE ta.disabled = 0
			AND ta.total_count = 0
		$groups_and";

    $and_module_status = '';
    if (is_array($filters['module_status'])) {
        if (!in_array(-1, $filters['module_status'])) {
            $empty_agents_count = '';
            if (!in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $filters['module_status'])) {
                if (count($filters['module_status']) > 0) {
                    $and_module_status = ' AND ( ';
                    foreach ($filters['module_status'] as $key => $value) {
                        $and_module_status .= ($key != 0) ? ' OR (' : ' (';
                        switch ($value) {
                            default:
                            case AGENT_STATUS_NORMAL:
                                $and_module_status .= ' tae.estado = 0 OR tae.estado = 300 ) ';
                            break;
                            case AGENT_STATUS_CRITICAL:
                                $and_module_status .= ' tae.estado = 1 OR tae.estado = 100 ) ';
                            break;

                            case AGENT_STATUS_WARNING:
                                $and_module_status .= ' tae.estado = 2 OR tae.estado = 200 ) ';
                            break;

                            case AGENT_STATUS_UNKNOWN:
                                $and_module_status .= ' tae.estado = 3 ) ';
                            break;

                            case AGENT_STATUS_NOT_INIT:
                                $and_module_status .= ' tae.estado = 4 OR tae.estado = 5 ) ';
                            break;
                        }
                    }

                    $and_module_status .= ' ) ';
                }
            } else {
                // Not normal.
                $and_module_status = 'AND tae.estado <> 0 AND tae.estado <> 300 ';
                $empty_agents_count = '';
            }
        }
    }

    // Filters module.
    if ($filters['module_search']) {
        $and_module_search = 'AND tam.nombre LIKE "%'.$filters['module_search'].'%"';
        $empty_agents_count = '';
    }

    // Filter group and check ACL groups.
    $groups_and = '';
    if (!users_can_manage_group_all('AR')) {
        if ($filters['group']) {
            $user_groups = array_keys(users_get_groups());
            $id_groups = implode(', ', $user_groups);
            $groups_and = " AND (ta.id_grupo IN ($id_groups) OR tasg.id_group IN($id_groups))";
        }
    }

    if ($filters['group']) {
        // Recursion check acl.
        if ($filters['recursion']) {
            $recursion_groups = groups_get_children_ids($filters['group'], true);
            if (!users_can_manage_group_all('AR')) {
                if (isset($user_groups) && is_array($user_groups)) {
                    $groups_intersect = array_intersect($user_groups, $recursion_groups);
                    if (isset($groups_intersect) && is_array($groups_intersect)) {
                        $groups_intersect = implode(', ', $groups_intersect);
                        $groups_and = " AND (ta.id_grupo IN ($groups_intersect) OR tasg.id_group IN($groups_intersect))";
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                $recursion_groups = implode(', ', $recursion_groups);
                $groups_and = " AND (ta.id_grupo IN ($recursion_groups) OR tasg.id_group IN($recursion_groups))";
            }
        } else {
            $groups_and = ' AND (ta.id_grupo ='.$filters['group'].' OR tasg.id_group ='.$filters['group'].')';
        }
    }

    // Filter custom data.
    $custom_data_and = '';
    if (isset($filters['id_custom_fields_data']) === true
        && is_array($filters['id_custom_fields_data']) === true
    ) {
        if (!in_array(-1, $filters['id_custom_fields_data'])) {
            $custom_data_array = implode("', '", $filters['id_custom_fields_data']);
            $custom_data_and = "AND tcd.description IN ('".$custom_data_array."')";
        }
    }

    // Filter custom name.
    $custom_field_name = $filters['id_custom_fields'];

    if (is_metaconsole()) {
        $metaconsole_connections = metaconsole_get_connection_names();
        // For all nodes.
        if (isset($metaconsole_connections) && is_array($metaconsole_connections)) {
            $result_meta = [];
            $data = [];
            foreach ($metaconsole_connections as $metaconsole) {
                // Get server connection data.
                $server_data = metaconsole_get_connection($metaconsole);
                // Establishes connection.
                if (metaconsole_load_external_db($server_data) !== NOERR) {
                    continue;
                }

                $query = sprintf(
                    "SELECT tcd.description AS name_data,
						SUM(IF($agent_state_total, 1, 0)) AS a_agents,
						SUM(IF($agent_state_critical, 1, 0)) AS a_critical,
						SUM(IF($agent_state_warning, 1, 0)) AS a_warning,
						SUM(IF($agent_state_unknown, 1, 0)) AS a_unknown,
						SUM(IF($agent_state_normal, 1, 0)) AS a_normal,
						SUM(IF($agent_state_notinit, 1, 0)) AS a_not_init,
						SUM(tagent_counters.mm_normal) AS m_normal,
						SUM(tagent_counters.mm_critical) AS m_critical,
						SUM(tagent_counters.mm_warning) AS m_warning,
						SUM(tagent_counters.mm_unknown) AS m_unknown,
						SUM(tagent_counters.mm_not_init) AS m_not_init,
						SUM(tagent_counters.mm_total) AS m_total
					FROM tagent_custom_data tcd
					INNER JOIN tagent_custom_fields tcf
						ON tcd.id_field = tcf.id_field
					INNER JOIN (
						SELECT ta.id_agente,
							ta.total_count AS c_m_total,
							SUM( IF(tae.estado = 0, 1, 0) ) AS mm_normal,
							SUM( IF(tae.estado = 1, 1, 0) ) AS mm_critical,
							SUM( IF(tae.estado = 2, 1, 0) ) AS mm_warning,
							SUM( IF(tae.estado = 3, 1, 0) ) AS mm_unknown,
							SUM( IF(tae.estado = 4 OR tae.estado = 5, 1, 0) ) AS mm_not_init,
							COUNT(tam.id_agente_modulo) AS mm_total
						FROM tagente ta
						LEFT JOIN tagent_secondary_group tasg
							ON ta.id_agente = tasg.id_agent
						INNER JOIN tagente_modulo tam
							ON ta.id_agente = tam.id_agente
						INNER JOIN tagente_estado tae
							ON tam.id_agente = tae.id_agente
							AND tam.id_agente_modulo = tae.id_agente_modulo
						WHERE ta.disabled = 0
							AND tam.disabled = 0
							%s
							%s
							%s
							%s
						GROUP by ta.id_agente
							%s
					) AS tagent_counters
						ON tcd.id_agent = tagent_counters.id_agente
					INNER JOIN tagente ta
						ON ta.id_agente = tagent_counters.id_agente
					WHERE tcf.name = '%s'
						AND tcd.description <> ''
						%s
					GROUP BY tcd.description",
                    $groups_and,
                    $and_status,
                    $and_module_search,
                    $and_module_status,
                    $empty_agents_count,
                    $custom_field_name,
                    $custom_data_and
                );

                $result_meta[$server_data['id']] = db_get_all_rows_sql($query);

                $query_data = sprintf(
                    "SELECT
						tcd.description,
						ta.id_agente,
						%d AS id_server,
						(CASE
							WHEN ta.critical_count > 0
								THEN 1
							WHEN ta.critical_count = 0
								AND ta.warning_count > 0
								THEN 2
							WHEN ta.critical_count = 0
								AND ta.warning_count = 0
								AND ta.unknown_count > 0
								THEN 3
							WHEN ta.critical_count = 0
								AND ta.warning_count = 0
								AND ta.unknown_count = 0
								AND ta.notinit_count <> ta.total_count
								THEN 0
							WHEN ta.total_count = ta.notinit_count
								THEN 5
							ELSE 0
						END) AS `status`,
						ta.critical_count,
						ta.warning_count,
						ta.unknown_count,
						ta.notinit_count,
						ta.normal_count,
						ta.total_count
					FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					INNER JOIN tagente_modulo tam
						ON ta.id_agente = tam.id_agente
					INNER JOIN tagente_estado tae
						ON tam.id_agente = tae.id_agente
						AND tam.id_agente_modulo = tae.id_agente_modulo
					INNER JOIN tagent_custom_data tcd
						ON tcd.id_agent = ta.id_agente
					INNER JOIN tagent_custom_fields tcf
						ON tcd.id_field = tcf.id_field
					WHERE ta.disabled = 0
						AND tcf.name = '%s'
						AND tcd.description <> ''
						AND tam.disabled = 0
						%s
						%s
						%s
						%s
						%s
						GROUP BY ta.id_agente
					",
                    $server_data['id'],
                    $custom_field_name,
                    $custom_data_and,
                    $groups_and,
                    $and_status,
                    $and_module_search,
                    $and_module_status
                );

                $node_result = db_get_all_rows_sql($query_data);

                if (empty($node_result)) {
                    $node_result = [];
                }

                $data = array_merge($data, $node_result);
                // Restore connection to root node.
                metaconsole_restore_db();
            }
        }

        $final_result = [];
        $array_data = [];

        if (isset($result_meta) && is_array($result_meta)) {
            // Initialize counters.
            $final_result['counters_total'] = [
                't_m_normal'   => 0,
                't_m_critical' => 0,
                't_m_warning'  => 0,
                't_m_unknown'  => 0,
                't_m_not_init' => 0,
                't_m_alerts'   => 0,
                't_m_total'    => 0,
                't_a_critical' => 0,
                't_a_warning'  => 0,
                't_a_unknown'  => 0,
                't_a_normal'   => 0,
                't_a_not_init' => 0,
                't_a_agents'   => 0,
            ];
            foreach ($result_meta as $k => $nodo) {
                if (isset($nodo) && is_array($nodo)) {
                    foreach ($nodo as $key => $value) {
                        // Sum counters total.
                        $final_result['counters_total']['t_m_normal'] += $value['m_normal'];
                        $final_result['counters_total']['t_m_critical'] += $value['m_critical'];
                        $final_result['counters_total']['t_m_warning'] += $value['m_warning'];
                        $final_result['counters_total']['t_m_unknown'] += $value['m_unknown'];
                        $final_result['counters_total']['t_m_not_init'] += $value['m_not_init'];
                        $final_result['counters_total']['t_m_alerts'] += $value['m_alerts'];
                        $final_result['counters_total']['t_m_total'] += $value['m_total'];
                        $final_result['counters_total']['t_a_critical'] += $value['a_critical'];
                        $final_result['counters_total']['t_a_warning'] += $value['a_warning'];
                        $final_result['counters_total']['t_a_unknown'] += $value['a_unknown'];
                        $final_result['counters_total']['t_a_normal'] += $value['a_normal'];
                        $final_result['counters_total']['t_a_not_init'] += $value['a_not_init'];
                        $final_result['counters_total']['t_a_agents'] += $value['a_agents'];

                        // Sum counters for data.
                        $array_data[$value['name_data']]['m_normal'] += $value['m_normal'];
                        $array_data[$value['name_data']]['m_critical'] += $value['m_critical'];
                        $array_data[$value['name_data']]['m_warning'] += $value['m_warning'];
                        $array_data[$value['name_data']]['m_unknown'] += $value['m_unknown'];
                        $array_data[$value['name_data']]['m_not_init'] += $value['m_not_init'];
                        $array_data[$value['name_data']]['m_alerts'] += $value['m_alerts'];
                        $array_data[$value['name_data']]['m_total'] += $value['m_total'];
                        $array_data[$value['name_data']]['a_critical'] += $value['a_critical'];
                        $array_data[$value['name_data']]['a_warning'] += $value['a_warning'];
                        $array_data[$value['name_data']]['a_unknown'] += $value['a_unknown'];
                        $array_data[$value['name_data']]['a_normal'] += $value['a_normal'];
                        $array_data[$value['name_data']]['a_not_init'] += $value['a_not_init'];
                        $array_data[$value['name_data']]['a_agents'] += $value['a_agents'];
                    }
                }
            }

            $final_result['counters_name'] = $array_data;
        }

        $final_result['indexed_descriptions'] = $data;
    } else {
        // TODO.
        $final_result = false;
    }

    return $final_result;
}


function get_filters_custom_fields_view($id=0, $for_select=false, $name='')
{
    // Filter group and check ACL groups.
    $groups_and = '';
    if (!users_can_manage_group_all()) {
        $user_groups = array_keys(users_get_groups(false, 'AR', false));
        $id_groups = implode(', ', $user_groups);
        $groups_and = " AND (group_search IN ($id_groups)) ";
    }

    if ($for_select) {
        $query = 'SELECT id, `name` FROM tagent_custom_fields_filter WHERE 1=1'.$groups_and;
        $rs = db_get_all_rows_sql($query);
        if (isset($rs) && is_array($rs)) {
            foreach ($rs as $key => $value) {
                $result[$value['id']] = $value['name'];
            }
        } else {
            $result = false;
        }
    } else {
        $query = 'SELECT * FROM tagent_custom_fields_filter WHERE 1=1'.$groups_and;

        if ($id) {
            $query .= ' AND id = '.$id;
        }

        if ($name) {
            $query .= " AND `name` = '".$name."'";
        }

        $result = db_get_all_rows_sql($query);
    }

    return $result;
}


function get_group_filter_custom_field_view($id)
{
    if (isset($id)) {
        $res = db_get_row_filter('tagent_custom_fields_filter', ['id' => $id]);
        return $res;
    }

    return false;
}


/**
 * Function for print counters agents or modules.
 *
 * @param array  $status_array Array need value, image, title, color, counter.
 * @param string $id_form      Id form default value ''.
 * @param string $id_input     Id input default value ''.
 *
 * @return array Return html print div container counters.
 */
function print_counters_cfv(
    array $status_array,
    string $id_form='',
    string $id_input=''
) {
    $html_result = '<form class = "cfv_status_agent" id="'.$id_form.'">';
    foreach ($status_array as $key => $value) {
        $checked = ($value['checked'] === 1) ? 'checked=true' : '';
        $disabled = ($value['counter'] === 0) ? 'disabled=true' : '';

        $html_result .= '<input id="lists_'.$id_input.'['.$key.']" '.$checked.' '.$disabled.' type="checkbox" name="lists_'.$id_input.'['.$key.']" />';
        $html_result .= '<label for="lists_'.$id_input.'['.$key.']" style="background-color:'.$value['color'].';">';
        $html_result .= html_print_image(
            $value['image'],
            true,
            ['title' => $value['title']]
        );
        $html_result .= $value['counter'];
        $html_result .= '</label>';
    }

    $html_result .= '</form>';
    return $html_result;
}


/**
 * Function for export a csv file from Custom Fields View
 *
 * @param array $filters       Status counters for agents and modules.
 * @param array $id_status     Agent status.
 * @param array $module_status Module status.
 *
 * @return array Returns the data that will be saved in the csv file
 */
function export_custom_fields_csv($filters, $id_status, $module_status)
{
    $data = agent_counters_custom_fields($filters);
    $indexed_descriptions = $data['indexed_descriptions'];

    // Table temporary for save array in table
    // by order and search custom_field data.
    $table_temporary = 'CREATE TEMPORARY TABLE temp_custom_fields (
        id_server int(10),
        id_agent int(10),
        name_custom_fields varchar(2048),
        critical_count int,
        warning_count int,
        unknown_count int,
        notinit_count int,
        normal_count int,
        total_count int,
        `status` int(2),
        KEY `data_index_temp_1` (`id_server`, `id_agent`)
    )';
    db_process_sql($table_temporary);

    // Insert values array in table temporary.
    $values_insert = [];
    foreach ($indexed_descriptions as $key => $value) {
        $values_insert[] = '('.$value['id_server'].', '.$value['id_agente'].", '".$value['description']."', '".$value['critical_count']."', '".$value['warning_count']."', '".$value['unknown_count']."', '".$value['notinit_count']."', '".$value['normal_count']."', '".$value['total_count']."', ".$value['status'].')';
    }

    $values_insert_implode = implode(',', $values_insert);
    $query_insert = 'INSERT INTO temp_custom_fields VALUES '.$values_insert_implode;
    db_process_sql($query_insert);

    // Search for status module.
    $status_agent_search = '';
    if (isset($id_status) === true && is_array($id_status) === true) {
        if (in_array(-1, $id_status) === false) {
            if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $id_status) === false) {
                $status_agent_search = ' AND temp.status IN ('.implode(',', $id_status).')';
            } else {
                // Not normal statuses.
                $status_agent_search = ' AND temp.status IN (1,2,3,4,5)';
            }
        }
    }

    // Search for status module.
    $status_module_search = '';
    if (isset($module_status) === true && is_array($module_status) === true) {
        if (in_array(-1, $module_status) === false) {
            if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $module_status) === false) {
                if (count($module_status) > 0) {
                    $status_module_search = ' AND ( ';
                    foreach ($module_status as $key => $value) {
                        $status_module_search .= ($key != 0) ? ' OR (' : ' (';
                        switch ($value) {
                            default:
                            case AGENT_STATUS_NORMAL:
                                $status_module_search .= ' temp.normal_count > 0) ';
                            break;
                            case AGENT_STATUS_CRITICAL:
                                $status_module_search .= ' temp.critical_count > 0) ';
                            break;

                            case AGENT_STATUS_WARNING:
                                $status_module_search .= ' temp.warning_count > 0) ';
                            break;

                            case AGENT_STATUS_UNKNOWN:
                                $status_module_search .= ' temp.unknown_count > 0) ';
                            break;

                            case AGENT_STATUS_NOT_INIT:
                                $status_module_search .= ' temp.notinit_count > 0) ';
                            break;
                        }
                    }

                    $status_module_search .= ' ) ';
                }
            } else {
                // Not normal.
                $status_module_search = ' AND ( temp.critical_count > 0 OR temp.warning_count > 0 OR temp.unknown_count > 0 AND temp.notinit_count > 0 )';
            }
        }
    }

    // Query all fields result.
    $query = sprintf(
        'SELECT
        temp.name_custom_fields,
        tma.alias,
        tma.direccion,
        tma.server_name,
        temp.status
    FROM tmetaconsole_agent tma
    INNER JOIN temp_custom_fields temp
        ON temp.id_agent = tma.id_tagente
        AND temp.id_server = tma.id_tmetaconsole_setup
    WHERE tma.disabled = 0
    %s
    %s
    ',
        $status_agent_search,
        $status_module_search
    );

    $result = db_get_all_rows_sql($query);
    return $result;
}
