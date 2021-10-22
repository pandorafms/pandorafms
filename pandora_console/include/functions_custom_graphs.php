<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Graphs
 */


/**
 * @global array Contents all var configs for the local instalation.
 */
global $config;

require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_users.php';


function custom_graphs_create(
    $id_modules=[],
    $name='',
    $description='',
    $stacked=CUSTOM_GRAPH_AREA,
    $width=0,
    $height=0,
    $events=0,
    $period=0,
    $private=0,
    $id_group=0,
    $user=false,
    $fullscale=0
) {
    global $config;

    if ($user === false) {
        $user = $config['id_user'];
    }

    $id_graph = db_process_sql_insert(
        'tgraph',
        [
            'id_user'           => $user,
            'name'              => $name,
            'description'       => $description,
            'period'            => $period,
            'width'             => $width,
            'height'            => $height,
            'private'           => $private,
            'events'            => $events,
            'stacked'           => $stacked,
            'id_group'          => $id_group,
            'id_graph_template' => 0,
            'fullscale'         => $fullscale,
        ]
    );

    if (empty($id_graph)) {
        return false;
    } else {
        $result = true;
        foreach ($id_modules as $id_module) {
            $result = db_process_sql_insert(
                'tgraph_source',
                [
                    'id_graph'        => $id_graph,
                    'id_agent_module' => $id_module,
                    'weight'          => 1,
                ]
            );

            if (empty($result)) {
                break;
            }
        }

        if (empty($result)) {
            // Not it is a complete insert the modules. Delete all
            db_process_sql_delete(
                'tgraph_source',
                ['id_graph' => $id_graph]
            );

            db_process_sql_delete(
                'tgraph',
                ['id_graph' => $id_graph]
            );

            return false;
        }

        return $id_graph;
    }
}


/**
 * Get all the custom graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 * @param $returnAllGroup Wheter to return graphs of group All or not.
 * @param $privileges Privileges to check in user group
 *
 * @return array graphs of a an user. Empty array if none.
 */
function custom_graphs_get_user($id_user=0, $only_names=false, $returnAllGroup=true, $privileges='RR')
{
    global $config;

    if (!$id_user) {
        $id_user = $config['id_user'];
    }

    $groups = users_get_groups($id_user, $privileges, $returnAllGroup);
    $all_graphs = [];
    if (is_metaconsole()) {
        $servers = metaconsole_get_connection_names();
        foreach ($servers as $key => $server) {
            $connection = metaconsole_get_connection($server);
            if (metaconsole_connect($connection) != NOERR) {
                continue;
            }

            $tmp_graphs = db_get_all_rows_in_table('tgraph', 'name');
            if ($tmp_graphs !== false) {
                foreach ($tmp_graphs as $g) {
                    $g['id_tgraph'] = $g['id_graph'];
                    $g['id_graph'] = $connection['id'].'|'.$g['id_graph'];
                    $g['name'] = $g['name'].' ('.$connection['server_name'].')';
                    $all_graphs[] = $g;
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $all_graphs = db_get_all_rows_in_table('tgraph', 'name');
    }

    if ($all_graphs === false) {
        return [];
    }

    $graphs = [];
    foreach ($all_graphs as $graph) {
        if (!in_array($graph['id_group'], array_keys($groups))) {
            continue;
        }

        if ($graph['id_user'] != $id_user && $graph['private']) {
            continue;
        }

        if ($graph['id_group'] > 0) {
            if (!isset($groups[$graph['id_group']])) {
                continue;
            }
        }

        if ($only_names) {
            $graphs[$graph['id_graph']] = $graph['name'];
        } else {
            $graphs[$graph['id_graph']] = $graph;
            $id_graph = 'id_graph';
            if ((bool) is_metaconsole() === true) {
                $id_graph = 'id_tgraph';
            }

            $graphsCount = db_get_value_sql(
                'SELECT COUNT(id_gs)
				FROM tgraph_source
				WHERE id_graph = '.$graph[$id_graph]
            );
            $graphs[$graph['id_graph']]['graphs_count'] = $graphsCount;
        }
    }

    return $graphs;
}


function custom_graphs_search($id_group, $search)
{
    if ($id_group != '' && $search != '') {
        $all_graphs = db_get_all_rows_sql('select * from tgraph where id_group = '.$id_group.' AND (name LIKE "%'.$search.'%" OR description LIKE "'.$search.'")');
    } else if ($id_group != '') {
        $all_graphs = db_get_all_rows_sql('select * from tgraph where id_group = '.$id_group.'');
    } else {
        $all_graphs = db_get_all_rows_sql('select * from tgraph where name LIKE "%'.$search.'%" OR description LIKE "'.$search.'"');
    }

    if ($all_graphs === false) {
        return [];
    }

    $graphs = [];
    foreach ($all_graphs as $graph) {
        $graphsCount = db_get_value_sql(
            'SELECT COUNT(id_gs)
                        FROM tgraph_source
                        WHERE id_graph = '.$graph['id_graph'].''
        );
        $graphs[$graph['id_graph']]['id_graph'] = $graph['id_graph'];
        $graphs[$graph['id_graph']]['graphs_count'] = $graphsCount;
        $graphs[$graph['id_graph']]['name'] = $graph['name'];
        $graphs[$graph['id_graph']]['description'] = $graph['description'];
        $graphs[$graph['id_graph']]['id_group'] = $graph['id_group'];
    }

    return $graphs;
}
