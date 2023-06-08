<?php
/**
 * Combined graph
 *
 * @category   Combined graph
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

// Begin.
global $config;

if (is_ajax()) {
    $search_agents = (bool) get_parameter('search_agents');

    if ($search_agents) {
        include_once 'include/functions_agents.php';

        $id_agent = (int) get_parameter('id_agent');
        $string = (string) get_parameter('q');
        // Q is what autocomplete plugin gives.
        $id_group = (int) get_parameter('id_group');

        $filter = [];
        $filter[] = '(nombre LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
        $filter['id_grupo'] = $id_group;

        $agents = agents_get_agents($filter, ['nombre', 'direccion']);
        if ($agents === false) {
            return;
        }

        foreach ($agents as $agent) {
            echo $agent['nombre'].'|'.$agent['direccion']."\n";
        }

        return;
    }

    return;
}

check_login();

if (! check_acl($config['id_user'], 0, 'RW')
    && ! check_acl($config['id_user'], 0, 'RM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access graph builder'
    );
    include 'general/noaccess.php';
    exit;
}

unset($name);

$add_module = (bool) get_parameter('add_module', false);
$delete_module = (bool) get_parameter('delete_module', false);
$edit_graph = (bool) get_parameter('edit_graph', false);
$active_tab = get_parameter('tab', 'main');
$add_graph = (bool) get_parameter('add_graph', false);
$update_graph = (bool) get_parameter('update_graph', false);
$change_weight = (bool) get_parameter('change_weight', false);
$change_label = (bool) get_parameter('change_label', false);
$id_graph = (int) get_parameter('id', 0);

if ($id_graph > 0) {
    $graph_group = db_get_value('id_group', 'tgraph', 'id_graph', $id_graph);
    if (!check_acl_restricted_all($config['id_user'], $graph_group, 'RW')
        && !check_acl_restricted_all($config['id_user'], $graph_group, 'RM')
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access graph builder'
        );
        include 'general/noaccess.php';
        exit;
    }
}

if ($id_graph !== 0) {
    $sql = "SELECT * FROM tgraph 
	WHERE (private = 0 OR (private = 1 AND id_user = '".$config['id_user']."'))
	AND id_graph = ".$id_graph;
    $control = db_process_sql($sql);
    if (!$control) {
        header('Location: index.php?sec=reporting&sec2=godmode/reporting/graphs');
    }
}


if ($add_graph === true) {
    $name = get_parameter_post('name');
    $description = get_parameter_post('description');
    $module_number = get_parameter_post('module_number');
    $idGroup = get_parameter_post('graph_id_group');
    $stacked = get_parameter('stacked', 0);
    $period = get_parameter_post('period');
    $threshold = get_parameter('threshold');
    $percentil = get_parameter('percentil', 0);
    $summatory_series = get_parameter('summatory_series', 0);
    $average_series = get_parameter('average_series', 0);
    $modules_series = get_parameter('modules_series', 0);
    $fullscale = get_parameter('fullscale', 0);

    if ($threshold == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD) {
        $stacked = $threshold;
    }

    // Create graph.
    $values = [
        'id_user'          => $config['id_user'],
        'name'             => $name,
        'description'      => $description,
        'period'           => $period,
        'private'          => 0,
        'id_group'         => $idGroup,
        'stacked'          => $stacked,
        'percentil'        => $percentil,
        'summatory_series' => $summatory_series,
        'average_series'   => $average_series,
        'modules_series'   => $modules_series,
        'fullscale'        => $fullscale,
    ];

    if (trim($name) != '') {
        $id_graph = db_process_sql_insert('tgraph', $values);
        $auditMessage = ($id_graph !== false) ? sprintf('Create graph #%s', $id_graph) : 'Fail try to create graph';
        db_pandora_audit(
            AUDIT_LOG_REPORT_MANAGEMENT,
            $auditMessage
        );
    } else {
        $id_graph = false;
    }

    if (!$id_graph) {
        $edit_graph = false;
    }
}

if ($update_graph) {
    $id_graph = get_parameter('id');
    $name = get_parameter('name');
    $id_group = get_parameter('graph_id_group');
    $description = get_parameter('description');
    $period = get_parameter('period');
    $stacked = get_parameter('stacked');
    $percentil = get_parameter('percentil');
    $summatory_series = get_parameter('summatory_series');
    $average_series = get_parameter('average_series');
    $modules_series = get_parameter('modules_series');
    $alerts = get_parameter('alerts');
    $threshold = get_parameter('threshold');
    $fullscale = get_parameter('fullscale');

    if ($threshold == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD) {
        $stacked = $threshold;
    }

    if (empty(trim($name)) === false) {
        $success = db_process_sql_update(
            'tgraph',
            [
                'name'             => $name,
                'id_group'         => $id_group,
                'description'      => $description,
                'width'            => $width,
                'height'           => $height,
                'period'           => $period,
                'stacked'          => $stacked,
                'percentil'        => $percentil,
                'summatory_series' => $summatory_series,
                'average_series'   => $average_series,
                'modules_series'   => $modules_series,
                'fullscale'        => $fullscale,
            ],
            ['id_graph' => $id_graph]
        );

        $auditMessage = ($success !== false) ? 'Update graph' : 'Fail try to update graph';
        db_pandora_audit(
            AUDIT_LOG_REPORT_MANAGEMENT,
            sprintf(
                '%s #%s',
                $auditMessage,
                $id_graph
            )
        );
    } else {
        $success = false;
    }
}


function add_quotes($item)
{
    return "'$item'";
}


if ($add_module === true) {
    $id_graph = get_parameter('id');
    $id_modules = explode(',', get_parameter('id_modules'));
    $id_agents = explode(',', get_parameter('id_agents'));
    $weight = get_parameter('weight');

    // Id modules has double entities conversion.
    // Safe output remove all entities.
    io_safe_output_array($id_modules, '');

    $id_modules = array_map(
        function ($mod) {
            return io_safe_input($mod);
        },
        $id_modules
    );

    $id_agent_modules = db_get_all_rows_sql(
        'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente IN ('.implode(',', $id_agents).") AND nombre IN ('".implode("','", $id_modules)."')"
    );

    if (count($id_agent_modules) > 0 && $id_agent_modules != '') {
        $order = db_get_row_sql("SELECT `field_order` from tgraph_source WHERE id_graph=$id_graph ORDER BY `field_order` DESC");

        $order = $order['field_order'];
        foreach ($id_agent_modules as $id_agent_module) {
            $order++;
            $result = db_process_sql_insert('tgraph_source', ['id_graph' => $id_graph, 'id_agent_module' => $id_agent_module['id_agente_modulo'], 'weight' => $weight, 'field_order' => $order]);
        }
    } else {
        $result = false;
    }
}

if ($delete_module === true) {
    $id_graph = get_parameter('id');

    $deleteGraph = get_parameter('delete');
    $order_val = db_get_value('field_order', 'tgraph_source', 'id_gs', $deleteGraph);
    $result = db_process_sql_delete('tgraph_source', ['id_gs' => $deleteGraph]);
    db_process_sql('UPDATE tgraph_source SET field_order=field_order-1 WHERE id_graph='.$id_graph.' AND field_order>'.$order_val);
}

if ($change_weight === true) {
    $weight = get_parameter('weight');
    $id_gs = get_parameter('graph');
    db_process_sql_update(
        'tgraph_source',
        ['weight' => $weight],
        ['id_gs' => $id_gs]
    );
}

if ($change_label) {
    $label = get_parameter('label');
    $id_gs = get_parameter('graph');
    db_process_sql_update(
        'tgraph_source',
        ['label' => $label],
        ['id_gs' => $id_gs]
    );
}

if ($edit_graph === true) {
    $buttons = [
        'graph_list'   => [
            'active' => false,
            'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs">'.html_print_image(
                'images/logs@svg.svg',
                true,
                [
                    'title' => __('Graph list'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ],
        'main'         => [
            'active' => false,
            'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=main&edit_graph=1&id='.$id_graph.'">'.html_print_image(
                'images/graph@svg.svg',
                true,
                [
                    'title' => __('Main data'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ],
        'graph_editor' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&edit_graph=1&id='.$id_graph.'">'.html_print_image(
                'images/builder@svg.svg',
                true,
                [
                    'title' => __('Graph editor'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ],
        'view'         => [
            'active' => false,
            'text'   => '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.$id_graph.'">'.html_print_image(
                'images/enable.svg',
                true,
                [
                    'title' => __('View graph'),
                    'class' => 'main_menu_icon invert_filter',

                ]
            ).'</a>',
        ],
    ];

    $buttons[$active_tab]['active'] = true;

    $graphInTgraph = db_get_row_sql('SELECT name FROM tgraph WHERE id_graph = '.$id_graph);
    $name = $graphInTgraph['name'];
} else {
    $buttons = [];
}

$head = __('Graph builder');

if (isset($name) === true) {
    $head .= ' &raquo; '.$name;
}

// Header.
$tab = get_parameter('tab');
switch ($tab) {
    case 'graph_editor':
        $headerHelp = '';
    break;

    case 'main':
    default:
        $headerHelp = 'graph_builder';
    break;
}

// Header.
ui_print_standard_header(
    $head,
    'images/chart.png',
    false,
    $headerHelp,
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom graphs'),
        ],
    ]
);

if ($add_graph) {
    ui_print_result_message(
        $id_graph,
        __('Graph stored successfully'),
        __('There was a problem storing Graph')
    );
}

if ($add_module) {
    ui_print_result_message(
        $result,
        __('Module added successfully'),
        __('There was a problem adding Module')
    );
}

if ($update_graph) {
    ui_print_result_message(
        $success,
        __('Update the graph'),
        __('Bad update the graph')
    );
}

if ($delete_module) {
    ui_print_result_message(
        $result,
        __('Graph deleted successfully'),
        __('There was a problem deleting Graph')
    );
}

// Parse CHUNK information into showable information.
// Split id to get all parameters.
if ($delete_module === false) {
    if (isset($_POST['period']) === true) {
        $period = $_POST['period'];
    }

    if ((isset($chunkdata) === true) && (empty($chunkdata) === false)) {
        $module_array = [];
        $weight_array = [];
        $agent_array = [];
        $chunk1 = [];
        $chunk1 = explode('|', $chunkdata);
        $modules = '';
        $weights = '';
        $chunkCount = count($chunk1);
        for ($a = 0; $a < $chunkCount; $a++) {
            $chunk2[$a] = [];
            $chunk2[$a] = explode(',', $chunk1[$a]);
            if (strpos($modules, $chunk2[$a][1]) == 0) {
                // Skip dupes
                $module_array[] = $chunk2[$a][1];
                $agent_array[] = $chunk2[$a][0];
                $weight_array[] = $chunk2[$a][2];
                if ($modules != '') {
                    $modules = $modules.','.$chunk2[$a][1];
                } else {
                    $modules = $chunk2[$a][1];
                }

                if ($weights != '') {
                    $weights = $weights.','.$chunk2[$a][2];
                } else {
                    $weights = $chunk2[$a][2];
                }
            }
        }
    }
}

switch ($active_tab) {
    case 'main':
        include_once 'godmode/reporting/graph_builder.main.php';
    break;

    case 'graph_editor':
        include_once 'godmode/reporting/graph_builder.graph_editor.php';
    break;

    default:
        // Nothing to do.
    break;
}
