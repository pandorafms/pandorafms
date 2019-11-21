<?php
/**
 * Library for networkmaps in Pandora FMS
 *
 * @category   Library
 * @package    Pandora FMS
 * @subpackage NetworkMap
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

// Begin.
require_once 'functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_networkmap.php');

// Check if a node descends from a given node
function networkmap_is_descendant($node, $ascendant, $parents)
{
    if (! isset($parents[$node])) {
        return false;
    }

    if ($node == $ascendant) {
        return true;
    }

    return networkmap_is_descendant($parents[$node], $ascendant, $parents);
}


function networkmap_print_jsdata($graph, $js_tags=true)
{
    if ($js_tags) {
        echo "<script type='text/javascript'>";

        if (empty($graph)) {
            echo "var graph = null;\n";
            return;
        } else {
            echo "var graph = \n";
        }
    }

    echo "{\n";
    echo "'nodes' : \n";
    echo "[\n";
    $first = true;
    foreach ($graph['nodes'] as $id => $node) {
        if (!$first) {
            echo ",\n";
        }

        $first = false;

        echo "{
			'id' : ".$id.",
			'name' : '".$node['label']."',
			'url' : '".$node['url']."',
			'tooltip' : '".$node['tooltip']."',
			'default_tooltip' : 1,
			'tooltip_content' : ' ".html_print_image('images/spinner.gif', true)."',
			'color' : '".$node['color']."'}\n";
    }

    echo "],\n";

    echo "'links' : \n";
    echo "[\n";
    $first = true;
    foreach ($graph['lines'] as $line) {
        if (!$first) {
            echo ",\n";
        }

        $first = false;

        echo "{
			'source' : ".$line['source'].",
			'target' : ".$line['target']."}\n";
    }

    echo "]\n";

    echo "}\n";

    if ($js_tags) {
        echo ";\n";
        echo '</script>';
    }
}


function networkmap_generate_hash(
    $pandora_name,
    $group=0,
    $simple=0,
    $font_size=12,
    $layout='radial',
    $nooverlap=0,
    $zoom=1,
    $ranksep=2.5,
    $center=0,
    $regen=1,
    $pure=0,
    $id_networkmap=0,
    $show_snmp_modules=0,
    $cut_names=true,
    $relative=false,
    $text_filter=''
) {
    $graph = networkmap_generate_dot(
        $pandora_name,
        $group,
        $simple,
        $font_size,
        $layout,
        $nooverlap,
        $zoom,
        $ranksep,
        $center,
        $regen,
        $pure,
        $id_networkmap,
        $show_snmp_modules,
        $cut_names,
        $relative,
        $text_filter,
        false,
        null,
        false,
        $strict_user
    );

    $return = [];
    if (!empty($graph)) {
        $graph = str_replace("\r", "\n", $graph);
        $graph = str_replace("\n", ' ', $graph);

        // Removed the head
        preg_match('/graph networkmap {(.*)}/', $graph, $matches);
        $graph = $matches[1];

        // Get the lines and nodes
        $tokens = preg_split('/; /', $graph);
        foreach ($tokens as $token) {
            if (empty($token)) {
                continue;
            }

            // Ignore the head rests.
            if (preg_match('/(.+)\s*\[(.*)\]/', $token) != 0) {
                $items[] = $token;
            }
        }

        $lines = $nodes = [];
        foreach ($items as $item) {
            $matches = null;
            preg_match('/(.+)\s*\[(.*)\]/', $item, $matches);
            if (empty($matches)) {
                continue;
            }

            $id_item = trim($matches[1]);
            $content_item = trim($matches[2]);

            // Check if is a edge or node
            if (strstr($id_item, '--') !== false) {
                // edge
                $lines[$id_item] = $content_item;
            } else {
                // node
                $id_item = (int) $id_item;
                $nodes[$id_item] = $content_item;
            }
        }

        foreach ($nodes as $key => $node) {
            if ($key != 0) {
                // Get label
                $matches = null;
                preg_match('/label=(.*),/', $node, $matches);
                $label = $matches[1];
                $matches = null;
                preg_match(
                    '/\<TR\>\<TD\>(.*?)\<\/TD\>\<\/TR\>/',
                    $label,
                    $matches
                );
                $label = str_replace($matches[0], '', $label);
                $matches = null;
                preg_match(
                    '/\<TR\>\<TD\>(.*?)\<\/TD\>\<\/TR\>/',
                    $label,
                    $matches
                );
                $label = $matches[1];

                // Get color
                $matches = null;
                preg_match('/color="([^"]*)/', $node, $matches);
                $color = $matches[1];

                // Get tooltip
                $matches = null;
                preg_match('/tooltip="([^"]*)/', $node, $matches);
                $tooltip = $matches[1];

                // Get URL
                $matches = null;
                preg_match('/URL="([^"]*)/', $node, $matches);
                $url = $matches[1];

                $return['nodes'][$key]['label'] = $label;
                $return['nodes'][$key]['color'] = $color;
                $return['nodes'][$key]['tooltip'] = $tooltip;
                $return['nodes'][$key]['url'] = $url;
            } else {
                // Get tooltip
                $matches = null;
                preg_match('/tooltip="([^"]*)/', $node, $matches);
                $tooltip = $matches[1];

                // Get URL
                $matches = null;
                preg_match('/URL="([^"]*)/', $node, $matches);
                $url = $matches[1];

                $return['nodes'][$key]['label'] = 'Pandora FMS';
                $return['nodes'][$key]['color'] = '#7EBE3F';
                $return['nodes'][$key]['tooltip'] = $tooltip;
                $return['nodes'][$key]['url'] = $url;
            }
        }

        ksort($return['nodes']);

        foreach ($lines as $key => $line) {
            $data = [];

            $points = explode(' -- ', $key);
            $data['source'] = (int) $points[0];
            $data['target'] = (int) $points[1];
            $return['lines'][] = $data;
        }
    }

    return $return;
}


function networkmap_generate_dot(
    $pandora_name,
    $group=0,
    $simple=0,
    $font_size=12,
    $layout='radial',
    $nooverlap=0,
    $zoom=1,
    $ranksep=2.5,
    $center=0,
    $regen=1,
    $pure=0,
    $id_networkmap=0,
    $show_snmp_modules=0,
    $cut_names=true,
    $relative=false,
    $text_filter='',
    $ip_mask=null,
    $dont_show_subgroups=false,
    $strict_user=false,
    $size_canvas=null,
    $old_mode=false,
    $map_filter=[]
) {
    global $config;
    $nooverlap = 1;

    $parents = [];
    $orphans = [];

    $filter = [];
    $filter['disabled'] = 0;

    if (!empty($text_filter)) {
        $filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$text_filter.'%")';
    }

    /*
     * Select data origin.
     *   group
     *   discovery task
     *      - Cloud
     *      - Application
     *      - Standar or custom
     *   network/mask
     */

    if ($group >= 0 && empty($ip_mask)) {
        if ($dont_show_subgroups) {
            $filter['id_grupo'] = $group;
        } else {
            $childrens = groups_get_childrens($group, null, true);
            if (!empty($childrens)) {
                $childrens = array_keys($childrens);

                $filter['id_grupo'] = $childrens;
                $filter['id_grupo'][] = $group;
            } else {
                $filter['id_grupo'] = $group;
            }
        }

        // Order by id_parent ascendant for to avoid the bugs
        // because the first agents to process in the next
        // foreach loop are without parent (id_parent = 0)
        // Get agents data
        $agents = agents_get_agents(
            $filter,
            [
                'id_grupo',
                'nombre',
                'id_os',
                'id_parent',
                'id_agente',
                'normal_count',
                'warning_count',
                'critical_count',
                'unknown_count',
                'total_count',
                'notinit_count',
            ],
            'AR',
            [
                'field' => 'id_parent',
                'order' => 'ASC',
            ]
        );
    } else if ($group == -666) {
        $agents = false;
    } else if (!empty($ip_mask)) {
        $agents = networkmap_get_nodes_from_ip_mask(
            $ip_mask
        );
    } else {
        $agents = agents_get_agents(
            $filter,
            [
                'id_grupo',
                'nombre',
                'id_os',
                'id_parent',
                'id_agente',
                'normal_count',
                'warning_count',
                'critical_count',
                'unknown_count',
                'total_count',
                'notinit_count',
            ],
            'AR',
            [
                'field' => 'id_parent',
                'order' => 'ASC',
            ]
        );
    }

    if ($agents === false) {
        // return false;
        $agents = [];
    }

    // Open Graph
    $graph = networkmap_open_graph(
        $layout,
        $nooverlap,
        $pure,
        $zoom,
        $ranksep,
        $font_size,
        $size_canvas,
        $map_filter
    );

    // Parse agents
    $nodes = [];

    // Add node refs
    $node_ref = [];
    $modules_node_ref = [];

    $node_count = 0;

    foreach ($agents as $agent) {
        $node_count++;

        $node_ref[$agent['id_agente']] = $node_count;

        $agent['id_node'] = $node_count;
        $agent['type'] = 'agent';

        // Add node
        $nodes[$node_count] = $agent;

        $filter = [];
        $filter['disabled'] = 0;

        // Get agent modules data
        $modules = agents_get_modules($agent['id_agente'], '*', $filter, true, true);
        if ($modules === false) {
            $modules = [];
        }

        // Parse modules
        foreach ($modules as $key => $module) {
            $node_count ++;
            $modules_node_ref[$module['id_agente_modulo']] = $node_count;
            $module['id_node'] = $node_count;
            $module['type'] = 'module';

            // Try to get the interface name
            if (preg_match('/(.+)_ifOperStatus$/', (string) $module['nombre'], $matches)) {
                if ($matches[1]) {
                        $module['nombre'] = $matches[1];

                        // Save node parent information to define edges later
                        $parents[$node_count] = $module['parent'] = $agent['id_node'];

                        // Add node
                        $nodes[$node_count] = $module;
                }
            } else {
                $sql_a = sprintf(
                    'SELECT id
                    FROM tmodule_relationship
                    WHERE module_a = %d AND type = "direct"',
                    $module['id_agente_modulo']
                );
                $sql_b = sprintf(
                    'SELECT id
                    FROM tmodule_relationship
                    WHERE module_b = %d AND type = "direct"',
                    $module['id_agente_modulo']
                );
                $have_relations_a = db_get_value_sql($sql_a);
                $have_relations_b = db_get_value_sql($sql_b);

                if ($have_relations_a || $have_relations_b) {
                    // Save node parent information to define edges later.
                    $parents[$node_count] = $module['parent'] = $agent['id_node'];

                    // Add node.
                    $nodes[$node_count] = $module;
                }
            }
        }
    }

    foreach ($modules_node_ref as $id_module => $node_count) {
        $module_type = modules_get_agentmodule_type($id_module);
    }

    // Addded the relationship of parents of agents
    foreach ($agents as $agent) {
        if ($agent['id_parent'] != '0' && array_key_exists($agent['id_parent'], $node_ref)) {
            $parents[$node_ref[$agent['id_agente']]] = $node_ref[$agent['id_parent']];
        } else {
            $orphans[$node_ref[$agent['id_agente']]] = 1;
        }
    }

    // Create a central node if orphan nodes exist
    if (count($orphans) || empty($nodes)) {
        $graph .= networkmap_create_pandora_node($pandora_name, $font_size, $simple, $stats);
    }

    // Define edges for orphan nodes
    foreach (array_keys($orphans) as $node) {
        $graph .= networkmap_create_edge('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
    }

    // Create void statistics array
    $stats = [];
    /*
        $count = 0;
        $group_nodes = 10;
        $graph .= networkmap_create_transparent_node($count);
        foreach (array_keys($orphans) as $node) {
        if ($group_nodes == 0) {
            $count++;
            $graph .= networkmap_create_transparent_node($count);

            $group_nodes = 10;
        }

        $graph .= networkmap_create_transparent_edge(
            'transp_'.$count,
            $node
        );

        $group_nodes--;
        }
    */

    // Create nodes
    foreach ($nodes as $node_id => $node) {
        if ($center > 0 && ! networkmap_is_descendant($node_id, $center, $parents)) {
            unset($parents[$node_id]);
            unset($orphans[$node_id]);
            unset($nodes[$node_id]);
            continue;
        }

        switch ($node['type']) {
            case 'agent':
                $graph .= networkmap_create_agent_node(
                    $node,
                    $simple,
                    $font_size,
                    $cut_names,
                    $relative
                )."\n\t\t";
                $stats['agents'][] = $node['id_agente'];
            break;

            case 'module':
                $graph .= networkmap_create_module_node(
                    $node,
                    $simple,
                    $font_size
                )."\n\t\t";
                $stats['modules'][] = $node['id_agente_modulo'];
            break;
        }
    }

    // Define edges
    foreach ($parents as $node => $parent_id) {
        // Verify that the parent is in the graph
        if (isset($nodes[$parent_id])) {
            $graph .= networkmap_create_edge(
                $parent_id,
                $node,
                $layout,
                $nooverlap,
                $pure,
                $zoom,
                $ranksep,
                $simple,
                $regen,
                $font_size,
                $group,
                'operation/agentes/networkmap',
                'topology',
                $id_networkmap
            );
        } else {
            $orphans[$node] = 1;
        }
    }

    // Define edges for the module interfaces relations
    // Get the remote_snmp_proc relations
    $relations = modules_get_relations();

    if ($relations === false) {
        $relations = [];
    }

    foreach ($relations as $key => $relation) {
        $module_a = $relation['module_a'];
        $agent_a = modules_get_agentmodule_agent($module_a);
        $module_b = $relation['module_b'];
        $agent_b = modules_get_agentmodule_agent($module_b);

        if (isset($modules_node_ref[$module_a])
            && isset($modules_node_ref[$module_b])
        ) {
            $graph .= networkmap_create_edge(
                $modules_node_ref[$module_a],
                $modules_node_ref[$module_b],
                $layout,
                $nooverlap,
                $pure,
                $zoom,
                $ranksep,
                $simple,
                $regen,
                $font_size,
                $group,
                'operation/agentes/networkmap',
                'topology',
                $id_networkmap
            );
        } else if (isset($node_ref[$agent_a])
            && isset($modules_node_ref[$module_b])
        ) {
            $graph .= networkmap_create_edge(
                $node_ref[$agent_a],
                $modules_node_ref[$module_b],
                $layout,
                $nooverlap,
                $pure,
                $zoom,
                $ranksep,
                $simple,
                $regen,
                $font_size,
                $group,
                'operation/agentes/networkmap',
                'topology',
                $id_networkmap
            );
        } else if (isset($node_ref[$agent_b])
            && isset($modules_node_ref[$module_a])
        ) {
            $graph .= networkmap_create_edge(
                $node_ref[$agent_b],
                $modules_node_ref[$module_a],
                $layout,
                $nooverlap,
                $pure,
                $zoom,
                $ranksep,
                $simple,
                $regen,
                $font_size,
                $group,
                'operation/agentes/networkmap',
                'topology',
                $id_networkmap
            );
        } else if (isset($node_ref[$agent_a])
            && isset($node_ref[$agent_b])
        ) {
            $graph .= networkmap_create_edge(
                $node_ref[$agent_a],
                $node_ref[$agent_b],
                $layout,
                $nooverlap,
                $pure,
                $zoom,
                $ranksep,
                $simple,
                $regen,
                $font_size,
                $group,
                'operation/agentes/networkmap',
                'topology',
                $id_networkmap
            );
        }
    }

    // Close graph
    $graph .= networkmap_close_graph();
    return $graph;
}


/**
 * Returns an edge definition.
 *
 * @param mixed   $head          Head.
 * @param mixed   $tail          Tail.
 * @param string  $layout        Layout.
 * @param string  $nooverlap     Nooverlap.
 * @param integer $pure          Pure.
 * @param float   $zoom          Zoom.
 * @param float   $ranksep       Ranksep.
 * @param integer $simple        Simple.
 * @param integer $regen         Regen.
 * @param integer $font_size     Font_size.
 * @param integer $group         Group.
 * @param string  $sec2          Sec2.
 * @param string  $tab           Tab.
 * @param integer $id_networkmap Id_networkmap.
 *
 * @return string Dot string.
 */
function networkmap_create_edge(
    $head,
    $tail,
    $layout,
    $nooverlap,
    $pure,
    $zoom,
    $ranksep,
    $simple,
    $regen,
    $font_size,
    $group,
    $sec2='operation/agentes/networkmap',
    $tab='topology',
    $id_networkmap=0
) {
    if (defined('METACONSOLE')) {
        $url = '';
    } else {
        $url = 'index.php?sec=estado&sec2='.$sec2.'&tab='.$tab.'&';
        $url .= 'recenter_networkmap=1&center='.$head.'&';
        $url .= 'layout='.$layout.'&nooverlap='.$nooverlap.'&';
        $url .= 'pure='.$pure.'&zoom='.$zoom.'&ranksep='.$ranksep.'&';
        $url .= 'simple='.$simple.'&regen=1&font_size='.$font_size.'&';
        $url .= 'group='.$group.'&id_networkmap='.$id_networkmap;
    }

    // Option edgeURL allows node navigation.
    $edge = "\n".$head.' -- '.$tail;
    $edge .= '[len='.$ranksep.', color="#BDBDBD", headclip=false, tailclip=false, edgeURL=""];';
    $edge .= "\n";

    return $edge;
}


function networkmap_create_transparent_edge($head, $tail)
{
    // edgeURL allows node navigation
    $edge = "\n".$head.' -- '.$tail.'[color="#00000000", headclip=false, tailclip=false, edgeURL=""];'."\n";

    return $edge;
}


// Returns a node definition
function networkmap_create_agent_node($agent, $simple=0, $font_size=10, $cut_names=true, $relative=false, $metaconsole=false, $id_server=null, $strict_user=false)
{
    global $config;
    global $hack_networkmap_mobile;

    if ($strict_user) {
        include_once $config['homedir'].'/include/functions_tags.php';
        $acltags = tags_get_user_groups_and_tags($config['id_user'], 'AR', $strict_user);

        $agent_filter = ['id' => $agent['id_agente']];
        $strict_data['normal_count'] = (int) groups_get_normal_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $strict_data['warning_count'] = (int) groups_get_warning_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $strict_data['critical_count'] = (int) groups_get_critical_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $strict_data['unknown_count'] = (int) groups_get_unknown_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $strict_data['notinit_count'] = (int) groups_get_not_init_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $strict_data['total_count'] = (int) groups_get_total_monitors($agent['id_grupo'], $agent_filter, [], $strict_user, $acltags);
        $status = agents_get_status_from_counts($strict_data);
    } else {
        $status = agents_get_status_from_counts($agent);
    }

    if (defined('METACONSOLE')) {
        $server_data = db_get_row(
            'tmetaconsole_setup',
            'id',
            $agent['id_server']
        );
    }

    if (empty($server_data)) {
        $server_name = '';
        $server_id = '';
        $url_hash = '';
        $console_url = '';
    } else {
        $server_name = $server_data['server_name'];
        $server_id = $server_data['id'];
        $console_url = $server_data['server_url'].'/';
        $url_hash = metaconsole_get_servers_url_hash($server_data);
    }

    // Set node status
    switch ($status) {
        case AGENT_STATUS_NORMAL:
            $status_color = COL_NORMAL;
        break;

        case AGENT_STATUS_CRITICAL:
            $status_color = COL_CRITICAL;
        break;

        case AGENT_STATUS_WARNING:
            $status_color = COL_WARNING;
        break;

        case AGENT_STATUS_ALERT_FIRED:
            $status_color = COL_ALERTFIRED;
        break;

        // Juanma (05/05/2014) Fix: Correct color for not init agents!
        case AGENT_STATUS_NOT_INIT:
            $status_color = COL_NOTINIT;
        break;

        default:
            // Unknown monitor
            $status_color = COL_UNKNOWN;
        break;
    }

    // Short name
    $name = io_safe_output($agent['nombre']);
    if ((strlen($name) > 16) && ($cut_names)) {
        $name = ui_print_truncate_text($name, 16, false, true, false);
    }

    if ($simple == 0) {
        if ($hack_networkmap_mobile) {
            $img_node = ui_print_os_icon($agent['id_os'], false, true, true, true, true, true);

            $img_node = $config['homedir'].'/'.$img_node;
            $img_node = '<img src="'.$img_node.'" />';
        } else {
            // Set node icon
            $img_node = ui_print_os_icon($agent['id_os'], false, true, true, true, true, $relative);
            $img_node = str_replace($config['homeurl'].'/', '', $img_node);
            $img_node = str_replace($config['homeurl'], '', $img_node);

            if (defined('METACONSOLE')) {
                $img_node = str_replace('../../', '', $img_node);
            }

            if ($relative) {
                $img_node = html_print_image($img_node, true, false, false, true);
            } else {
                $img_node = html_print_image($img_node, true, false, false, false);
            }
        }

        if (defined('METACONSOLE')) {
            if (can_user_access_node()) {
                $url = ui_meta_get_url_console_child(
                    $id_server,
                    'estado',
                    'operation/agentes/ver_agente&id_agente='.$agent['id_agente']
                );
            } else {
                $url = '';
            }

            $url_tooltip = '../../ajax.php?'.'page=operation/agentes/ver_agente&'.'get_agent_status_tooltip=1&'.'id_agent='.$agent['id_agente'].'&'.'metaconsole=1&'.'id_server='.$agent['id_server'];
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'];
            $url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'];
        }

        $node = "\n".$agent['id_node'].' [ parent="'.$agent['id_parent'].'", color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
		 <TR><TD>'.io_safe_input($name).'</TD></TR></TABLE>>,
		 shape="doublecircle", URL="'.$url.'",
		 tooltip="'.$url_tooltip.'"];'."\n";
    } else {
        $ajax_prefix = '';
        $meta_params = '';

        if (defined('METACONSOLE')) {
            $ajax_prefix = '../../';
            $meta_params = '&metaconsole=1&id_server='.$id_server;
        }

        if (can_user_access_node()) {
            $url_node_link = ', URL="'.$console_url.'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].$url_hash.'"';
        } else {
            $url_node_link = '';
        }

        $node = $agent['id_node'].' [ parent="'.$agent['id_parent'].'", color="'.$status_color.'", fontsize='.$font_size.', shape="doublecircle"'.$url_node_link.', style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="'.$ajax_prefix.'ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].$meta_params.'"];'."\n";
    }

    return $node;
}


// Returns a module node definition
function networkmap_create_module_node($module, $simple=0, $font_size=10, $metaconsole=false, $id_server=null)
{
    global $config;
    global $hack_networkmap_mobile;

    if (isset($module['status'])) {
        $status = $module['status'];
    } else {
        $status = modules_get_agentmodule_status(
            $module['id_agente_modulo'],
            false,
            $metaconsole,
            $id_server
        );
    }

    // Set node status
    switch ($status) {
        case AGENT_MODULE_STATUS_NORMAL:
            $status_color = COL_NORMAL;
            // Normal monitor
        break;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
            $status_color = COL_CRITICAL;
            // Critical monitor
        break;

        case AGENT_MODULE_STATUS_WARNING:
            $status_color = COL_WARNING;
            // Warning monitor
        break;

        case AGENT_STATUS_ALERT_FIRED:
            $status_color = COL_ALERTFIRED;
            // Alert fired
        break;

        default:
            $status_color = COL_UNKNOWN;
            // Unknown monitor
        break;
    }

    if ($hack_networkmap_mobile) {
        $img_node = ui_print_moduletype_icon($module['id_tipo_modulo'], true, true, false, true);

        $img_node = $config['homedir'].'/'.$img_node;
        $img_node = '<img src="'.$img_node.'" />';
    } else {
        $img_node = ui_print_moduletype_icon($module['id_tipo_modulo'], true, true, false);
    }

    if ($simple == 0) {
        if (defined('METACONSOLE')) {
            $url = '';
            $url_tooltip = '../../ajax.php?'.'page=operation/agentes/ver_agente&'.'get_agentmodule_status_tooltip=1&'.'id_module='.$module['id_agente_modulo'].'&metaconsole=1'.'&id_server='.$module['id_server'];
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
            $url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
        }

        $node = $module['id_node'].' [ id_agent="'.$module['id_agente'].'", color="'.$status_color.'", fontsize='.$font_size.', style="filled", '.'fixedsize=true, width=0.30, height=0.30, '.'label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
			<TR><TD>'.io_safe_output($module['nombre']).'</TD></TR></TABLE>>,
			shape="circle", URL="'.$url.'",
			tooltip="'.$url_tooltip.'"];';
    } else {
        if (defined('METACONSOLE')) {
            $url = 'TODO';
            $url_tooltip = '../../ajax.php?page=operation/agentes/ver_agente'.'&get_agentmodule_status_tooltip=1'.'&id_module='.$module['id_agente_modulo'].'&metaconsole=1'.'&id_server='.$module['id_server'];
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
            $url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
        }

        $node = $module['id_node'].' [ '.'id_agent="'.$module['id_agente'].'", '.'color="'.$status_color.'", '.'fontsize='.$font_size.', '.'shape="circle", '.'URL="'.$url.'", '.'style="filled", '.'fixedsize=true, '.'width=0.20, '.'height=0.20, '.'label="", '.'tooltip="'.$url_tooltip.'"'.'];';
    }

    return $node;
}


// Returns the definition of the central module
function networkmap_create_pandora_node($name, $font_size=10, $simple=0, $stats=[])
{
    global $hack_networkmap_mobile;
    global $config;

    // $stats_json = base64_encode(json_encode($stats));
    $summary = [];
    if (isset($stats['policies'])) {
            $summary['policies'] = count($stats['policies']);
    }

    if (isset($stats['groups'])) {
        // TODO: GET STATUS OF THE GROUPS AND ADD IT TO SUMMARY
        $summary['groups'] = count($stats['groups']);
    }

    if (isset($stats['agents'])) {
        // TODO: GET STATUS OF THE AGENTS AND ADD IT TO SUMMARY
        $summary['agents'] = count($stats['agents']);
    }

    if (isset($stats['modules'])) {
        // TODO: GET STATUS OF THE MODULES AND ADD IT TO SUMMARY
        $summary['modules'] = count($stats['modules']);
    }

    $stats_json = base64_encode(json_encode($summary));

    $img_src = ui_get_logo_to_center_networkmap();
    if (defined('METACONSOLE')) {
        $url_tooltip = '../../ajax.php?'.'page=include/ajax/networkmap.ajax&'.'action=get_networkmap_summary&'.'stats='.$stats_json.'&'.'metaconsole=1';
        $url = '';
        $color = '#052938';
    } else {
        $url_tooltip = 'ajax.php?page=include/ajax/networkmap.ajax&action=get_networkmap_summary&stats='.$stats_json.'", URL="index.php?sec=estado&sec2=operation/agentes/group_view';
        $url = 'index.php?sec=estado&sec2=operation/agentes/group_view';
        $color = '#373737';
    }

    if ($hack_networkmap_mobile) {
        $img = '<TR><TD>'."<img src='".$config['homedir'].'/'.ui_get_logo_to_center_networkmap()."' />".'</TD></TR>';
    } else {
        $image = html_print_image(ui_get_logo_to_center_networkmap(), true, false, false, true);
        // $image = str_replace('"',"'",$image);
        $img = '<TR><TD>'.$image.'</TD></TR>';
    }

    $name = "<TR><TD BGCOLOR='#FFFFFF'>".$name.'</TD></TR>';
    $label = "<TABLE BORDER='0'>".$img.$name.'</TABLE>';
    if ($simple == 1) {
        $label = '';
    }

    $node = '0 [ color="'.$color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.8, height=0.6, label=<'.$label.'>,
		shape="ellipse", tooltip="'.$url_tooltip.'", URL="'.$url.'" ];';

    return $node;
}


function networkmap_create_transparent_node($count=0)
{
    $node = 'transp_'.$count.' [ color="#00000000", style="filled", fixedsize=true, width=0.8, height=0.6, label=<>,
		shape="ellipse"];';

    return $node;
}


// Opens a graph definition
function networkmap_open_graph(
    $layout,
    $nooverlap,
    $pure,
    $zoom,
    $rank_sep,
    $font_size,
    $size_canvas,
    $map_filter=[]
) {
    global $config;

    $overlap = 'compress';

    if (isset($config['networkmap_max_width'])) {
        $size_x = ($config['networkmap_max_width'] / 100);
        $size_y = ($size_x * 0.8);
    } else {
        $size_x = 8;
        $size_y = 5.4;
        $size = '';
    }

    if ($zoom > 0) {
        $size_x *= $zoom;
        $size_y *= $zoom;
    }

    $size = $size_x.','.$size_y;

    if (!is_null($size_canvas)) {
        $size = ($size_canvas['x'] / 100).','.($size_canvas['y'] / 100);
    }

    // Graphviz custom values
    if (isset($map_filter['node_sep'])) {
        $node_sep = $map_filter['node_sep'];
    } else {
        $node_sep = 0.1;
    }

    if (isset($map_filter['rank_sep'])) {
        $rank_sep = $map_filter['rank_sep'];
    } else {
        if ($layout == 'radial') {
            $rank_sep = 1.0;
        } else {
            $rank_sep = 0.5;
        }
    }

    if (isset($map_filter['mindist'])) {
        $mindist = $map_filter['mindist'];
    } else {
        $mindist = 1.0;
    }

    if (isset($map_filter['kval'])) {
        $kval = $map_filter['kval'];
    } else {
        $kval = 0.1;
    }

    // BEWARE: graphwiz DONT use single ('), you need double (")
    $head = 'graph networkmap { dpi=100; bgcolor="transparent"; labeljust=l; margin=0; pad="0.75,0.75";';
    if ($nooverlap != '') {
        $head .= 'overlap="false";';
        $head .= 'outputorder=first;';
    }

    if ($layout == 'flat' || $layout == 'spring1' || $layout == 'spring2') {
        if ($nooverlap != '') {
            $head .= 'overlap="scalexy";';
        }

        if ($layout == 'flat') {
            $head .= "ranksep=\"$rank_sep\";";
        }

        if ($layout == 'spring2') {
            $head .= "K=\"$kval\";";
        }
    }

    if ($layout == 'radial') {
        $head .= "ranksep=\"$rank_sep\";";
    }

    if ($layout == 'circular') {
        $head .= "mindist=\"$mindist\";";
    }

    $head .= 'ratio="fill";';
    $head .= 'root=0;';
    $head .= "nodesep=\"$node_sep\";";
    $head .= "size=\"$size\";";

    $head .= "\n";

    return $head;
}


// Closes a graph definition
function networkmap_close_graph()
{
    return '}';
}


// Returns the filter used to achieve the desired layout
function networkmap_get_filter($layout)
{
    switch ($layout) {
        case 'flat':
        return 'dot';

            break;
        case 'radial':
        return 'twopi';

            break;
        case 'circular':
        return 'circo';

            break;
        case 'spring1':
        return 'neato';

            break;
        case 'spring2':
        return 'fdp';

            break;
        default:
        return 'twopi';
            break;
    }
}


/**
 * Get a network map report.
 *
 * @param int Networkmap id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 * @param bool Get only the map if is of the user ( $config['id_user'])
 *
 * @return Networkmap with the given id. False if not available or readable.
 */
function networkmap_get_networkmap($id_networkmap, $filter=false, $fields=false, $check_user=true)
{
    global $config;

    $id_networkmap = safe_int($id_networkmap);
    if (empty($id_networkmap)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_networkmap'] = $id_networkmap;

    if ($check_user) {
        // If hte user has admin flag don't filter by user
        $user_info = users_get_user_by_id($config['id_user']);

        if (!$user_info['is_admin']) {
            // $filter['id_user'] = $config['id_user'];
        }
    }

    $networkmap = db_get_row_filter('tnetwork_map', $filter, $fields);

    return $networkmap;
}


/**
 * Get a user networkmaps.
 *
 * @param int Networkmap id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Networkmap with the given id. False if not available or readable.
 */
function networkmap_get_networkmaps(
    $id_user=null,
    $type=null,
    $optgrouped=true,
    $strict_user=false
) {
    global $config;

    if (empty($id_user)) {
        $id_user = $config['id_user'];
    }

    // Configure filters
    $where = [];
    $where['type'] = MAP_TYPE_NETWORKMAP;
    $where['id_group'] = array_keys(users_get_groups($id_user));
    if (!empty($type)) {
        $where['subtype'] = $type;
    }

    $where['order'][0]['field'] = 'type';
    $where['order'][0]['order'] = 'DESC';
    $where['order'][1]['field'] = 'name';
    $where['order'][1]['order'] = 'ASC';

    $networkmaps_raw = db_get_all_rows_filter('tmap', $where);
    if (empty($networkmaps_raw)) {
        return [];
    }

    $networkmaps = [];
    foreach ($networkmaps_raw as $networkmapitem) {
        if ($optgrouped) {
            $networkmaps[$networkmapitem['id']] = [
                'name'     => $networkmapitem['name'],
                'optgroup' => networkmap_type_to_str_type($networkmapitem['subtype']),
            ];
        } else {
            $networkmaps[$networkmapitem['id']] = $networkmapitem['name'];
        }
    }

    return $networkmaps;
}


function networkmap_type_to_str_type($type)
{
    switch ($type) {
        case MAP_SUBTYPE_GROUPS:
        return __('Groups');

            break;
        case MAP_SUBTYPE_POLICIES:
        return __('Policies');

            break;
        case MAP_SUBTYPE_RADIAL_DYNAMIC:
        return __('Radial dynamic');

            break;
        case MAP_SUBTYPE_TOPOLOGY:
        return __('Topology');

            break;
    }
}


/**
 * Get different networkmaps types for creation.
 *
 * @return array Networkmap diferent types.
 */
function networkmap_get_types($strict_user=false)
{
    $networkmap_types = [];

    $is_enterprise = enterprise_include_once('include/functions_policies.php');

    $networkmap_types['topology'] = __('Create a new topology map');
    $networkmap_types['groups'] = __('Create a new group map');
    $networkmap_types['dynamic'] = __('Create a new dynamic map');
    if (!$strict_user) {
        $networkmap_types['radial_dynamic'] = __('Create a new radial dynamic map');
    }

    if (($is_enterprise !== ENTERPRISE_NOT_HOOK) && (!$strict_user)) {
        $enterprise_types = enterprise_hook('policies_get_networkmap_types');

        $networkmap_types = array_merge($networkmap_types, $enterprise_types);
    }

    return $networkmap_types;
}


/**
 * Retrieve agent list matching desired network.
 *
 * @param string $ip_mask Networks.
 * @param array  $fields  Extra fields.
 *
 * @return array Of agents.
 */
function networkmap_get_nodes_from_ip_mask(
    $ip_mask,
    $return_ids_only=false
) {
    $list_ip_masks = explode(',', $ip_mask);

    if (empty($list_ip_masks)) {
        return [];
    }

    $agents = [];
    foreach ($list_ip_masks as $subnet) {
        $net = explode('/', $subnet);

        // Calculate real network address. Avoid user bad input.
        $mask = ~((1 << (32 - $net[1])) - 1);
        $network = long2ip(ip2long($net[0]) & $mask);

        $sql = sprintf(
            'SELECT *
            FROM `tagente`
            INNER JOIN 
                (SELECT DISTINCT `id_agent` FROM 
                    (SELECT `id_agente` AS "id_agent", `direccion` AS "ip"
                    FROM `tagente` 
                    UNION
                    SELECT ag.`id_agent`, a.`ip`
                    FROM `taddress_agent` ag 
                    INNER JOIN `taddress` a 
                        ON ag.id_a=a.id_a
                    ) t_tmp
                WHERE (-1 << %d) & INET_ATON(t_tmp.ip) = INET_ATON("%s")
                ) t_res
                ON t_res.`id_agent` = `tagente`.`id_agente`',
            (32 - $net[1]),
            $network
        );

        $subnet_agents = db_get_all_rows_sql($sql);

        if ($subnet_agents !== false) {
            $agents = array_merge($agents, $subnet_agents);
        }
    }

    if ($return_ids_only === false) {
        $agents = array_reduce(
            $agents,
            function ($carry, $item) {
                $carry[$item['id_agente']] = $item;
                return $carry;
            },
            []
        );
    } else {
        $agents = array_reduce(
            $agents,
            function ($carry, $item) {
                $carry[$item['id_agente']] = $item['id_agente'];
                return $carry;
            },
            []
        );
    }

    return $agents;
}


function modules_get_all_interfaces($id_agent)
{
    $return = [];

    $fields = $fields_param;
    $modules = db_get_all_rows_filter(
        'tagente_modulo',
        ['id_agente' => $id_agent]
    );

    if (empty($modules)) {
        $modules = [];
    }

    foreach ($modules as $module) {
        if (preg_match('/(.+)_ifOperStatus$/', (string) $module['nombre'], $matches)) {
            if ($matches[1]) {
                $return[] = $module;
            }
        }
    }

    return $return;
}


function networkmap_delete_networkmap($id=0)
{
    if (enterprise_installed()) {
        // Relations
        $result = delete_relations($id);

        // Nodes
        $result = delete_nodes($id);
    }

    // Map
    $result = db_process_sql_delete('tmap', ['id' => $id]);

    return $result;
}


function networkmap_delete_nodes($id_map)
{
    return db_process_sql_delete('titem', ['id_map' => $id_map]);
}


function get_networkmaps($id)
{
    $groups = array_keys(users_get_groups(null, 'IW'));

    $filter = [];
    $filter['id_group'] = $groups;
    $filter['id'] = '<>'.$id;
    $networkmaps = db_get_all_rows_filter('tmap', $filter);
    if ($networkmaps === false) {
        $networkmaps = [];
    }

    $return = [];
    $return[0] = __('None');
    foreach ($networkmaps as $networkmap) {
        $return[$networkmap['id']] = $networkmap['name'];
    }

    return $return;
}


/**
 * Translates node (nodes_and_relations) into JS node.
 *
 * @param array   $node                    Node.
 * @param integer $count                   Count.
 * @param integer $count_item_holding_area Count_item_holding_area.
 * @param boolean $simulated               Simulated.
 *
 * @return array JS nodes.
 */
function networkmap_db_node_to_js_node(
    $node,
    &$count,
    &$count_item_holding_area,
    $simulated=false
) {
    global $config;

    $networkmap = db_get_row('tmap', 'id', $node['id_map']);

    $networkmap['filter'] = json_decode($networkmap['filter'], true);

    // Hardcoded
    $networkmap['filter']['holding_area'] = [
        500,
        500,
    ];

    // 40 = DEFAULT NODE RADIUS
    // 30 = for to align
    $holding_area_max_y = ($networkmap['height'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][1] + 10 * 40);

    $item = [];
    $item['id'] = $count;

    if (enterprise_installed() && $simulated === false) {
        enterprise_include_once('include/functions_networkmap.php');
        $item['id_db'] = $node['id_in_db'];
    } else {
        $item['id_db'] = (int) $node['id'];
    }

    if ((int) $node['type'] == 0) {
        $item['type'] = 0;
        $item['id_agent'] = (int) $node['source_data'];
        $item['id_module'] = '';
    } else if ((int) $node['type'] == 1) {
        $item['type'] = 1;
        $item['id_agent'] = (int) $node['style']['id_agent'];
        $item['id_module'] = (int) $node['source_data'];
    } else {
        $item['type'] = 3;
    }

    $item['fixed'] = true;
    $item['x'] = (int) $node['x'];
    $item['y'] = (int) $node['y'];
    $item['px'] = (int) $node['x'];
    $item['py'] = (int) $node['y'];
    $item['z'] = (int) $node['z'];
    $item['state'] = $node['state'];
    $item['deleted'] = $node['deleted'];
    if ($item['state'] == 'holding_area') {
        // 40 = DEFAULT NODE RADIUS
        // 30 = for to align
        $holding_area_x = ($networkmap['width'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][0] + ($count_item_holding_area % 11) * 40);
        $holding_area_y = ($networkmap['height'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][1] + (int) (($count_item_holding_area / 11)) * 40);

        if ($holding_area_max_y <= $holding_area_y) {
            $holding_area_y = $holding_area_max_y;
        }

        $item['x'] = $holding_area_x;
        $item['y'] = $holding_area_y;

        // Increment for the next node in holding area
        $count_item_holding_area++;
    }

    $item['image_url'] = '';
    $item['image_width'] = 0;
    $item['image_height'] = 0;
    if (!empty($node['style']['image'])) {
        $item['image_url'] = html_print_image(
            $node['style']['image'],
            true,
            false,
            true
        );
        $image_size = getimagesize(
            $config['homedir'].'/'.$node['style']['image']
        );
        $item['image_width'] = (int) $image_size[0];
        $item['image_height'] = (int) $image_size[1];
    }

    $item['raw_text'] = $node['style']['label'];
    $item['text'] = io_safe_output($node['style']['label']);
    $item['shape'] = $node['style']['shape'];

    switch ($node['type']) {
        case 0:
            $color = get_status_color_networkmap($node['source_data']);
        break;

        default:
            // Old code
            if ($node['source_data'] == -1) {
                $color = '#364D1F';
            } else if ($node['source_data'] == -2) {
                $color = '#364D1F';
            } else {
                $color = get_status_color_networkmap($node['source_data']);
            }
        break;
    }

    $item['color'] = $color;
    $item['map_id'] = 0;
    if (isset($node['id_map'])) {
        $item['map_id'] = $node['id_map'];
    }

    if (!isset($node['style']['id_networkmap']) || $node['style']['id_networkmap'] == '' || $node['style']['id_networkmap'] == 0) {
        $item['networkmap_id'] = 0;
    } else {
        $item['networkmap_id'] = $node['style']['id_networkmap'];
    }

    $count++;

    return $item;
}


function get_status_color_networkmap($id, $color=true)
{
    // $status = agents_get_status($id);
    $agent_data = db_get_row_sql('SELECT * FROM tagente WHERE id_agente = '.$id);

    if ($agent_data === false) {
        return COL_UNKNOWN;
    }

    $status = agents_get_status_from_counts($agent_data);

    if (!$color) {
        return $status;
    }

    if ($agent_data['fired_count'] > 0) {
        return COL_ALERTFIRED;
    }

    // Select node color by checking status.
    switch ($status) {
        case AGENT_MODULE_STATUS_NORMAL:
        return COL_NORMAL;

        case AGENT_MODULE_STATUS_NOT_INIT:
        return COL_NOTINIT;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return COL_CRITICAL;

        case AGENT_MODULE_STATUS_WARNING:
        return COL_WARNING;

        case AGENT_MODULE_STATUS_UNKNOWN:
        default:
        return COL_UNKNOWN;
    }

    return COL_UNKNOWN;
}


function networkmap_clean_relations_for_js(&$relations)
{
    do {
        $cleaned = true;

        foreach ($relations as $key => $relation) {
            if ($relation['id_parent_source_data'] == $relation['id_child_source_data']) {
                if (($relation['child_type'] != 3) && $relation['parent_type'] != 3) {
                    $cleaned = false;

                    if ($relation['parent_type'] == 1) {
                        $to_find = $relation['id_parent_source_data'];
                        $to_replace = $relation['id_child_source_data'];
                    } else if ($relation['child_type'] == 1) {
                        $to_find = $relation['id_child_source_data'];
                        $to_replace = $relation['id_parent_source_data'];
                    }

                    // Replace and erase the links
                    foreach ($relations as $key2 => $relation2) {
                        if ($relation2['id_parent_source_data'] == $to_find) {
                            $relations[$key2]['id_parent_source_data'] = $to_replace;
                        } else if ($relation2['id_child_source_data'] == $to_find) {
                            $relations[$key2]['id_child_source_data'] = $to_replace;
                        }
                    }

                    unset($relations[$key]);

                    break;
                }
            }
        }
    } while (!$cleaned);
}


/**
 * Transform networkmap relations into js links.
 *
 * @param array   $relations   Relations.
 * @param array   $nodes_graph Nodes_graph.
 * @param boolean $simulated   Simulated.
 *
 * @return array JS relations.
 */
function networkmap_links_to_js_links(
    $relations,
    $nodes_graph,
    $simulated=false
) {
    $return = [];

    if (enterprise_installed() && $simulated === false) {
        enterprise_include_once('include/functions_networkmap.php');
    }

    $count = 0;
    foreach ($relations as $key => $relation) {
        if (($relation['parent_type'] == NODE_MODULE)
            && ($relation['child_type'] == NODE_MODULE)
        ) {
            $id_target_agent = agents_get_agent_id_by_module_id(
                $relation['id_parent_source_data']
            );
            $id_source_agent = agents_get_agent_id_by_module_id(
                $relation['id_child_source_data']
            );
            $id_target_module = $relation['id_parent_source_data'];
            $id_source_module = $relation['id_child_source_data'];
        } else if (($relation['parent_type'] == NODE_MODULE)
            && ($relation['child_type'] == NODE_AGENT)
        ) {
            $id_target_agent = agents_get_agent_id_by_module_id(
                $relation['id_parent_source_data']
            );
            $id_target_module = $relation['id_parent_source_data'];
            $id_source_agent = $relation['id_child_source_data'];
        } else if (($relation['parent_type'] == NODE_AGENT)
            && ($relation['child_type'] == NODE_MODULE)
        ) {
            $id_target_agent = $relation['id_parent_source_data'];
            $id_source_module = $relation['id_child_source_data'];
            $id_source_agent = agents_get_agent_id_by_module_id(
                $relation['id_child_source_data']
            );
        } else {
            $id_target_agent = $relation['id_parent_source_data'];
            $id_source_agent = $relation['id_child_source_data'];
        }

        $item = [];
        $item['id'] = $count;
        $count++;
        if (enterprise_installed() && $simulated === false) {
            $item['id_db'] = get_relation_id($relation);
        } else {
            $item['id_db'] = $key;
        }

        $item['arrow_start'] = '';
        $item['arrow_end'] = '';
        $item['status_start'] = '';
        $item['status_end'] = '';
        $item['id_module_start'] = 0;
        $item['id_agent_start'] = (int) $id_source_agent;
        $item['id_module_end'] = 0;
        $item['id_agent_end'] = (int) $id_target_agent;
        $item['link_color'] = '#999';
        $item['target'] = -1;
        $item['source'] = -1;
        $item['deleted'] = $relation['deleted'];

        if (enterprise_installed() && $simulated === false) {
            $target_and_source = [];
            $target_and_source = get_id_target_and_source_in_db($relation);
            $item['target_id_db'] = (int) $target_and_source['target'];
            $item['source_id_db'] = (int) $target_and_source['source'];
        } else {
            if (($relation['parent_type'] == NODE_MODULE) && ($relation['child_type'] == NODE_MODULE)) {
                $item['target_id_db'] = $id_target_agent;
                $item['source_id_db'] = $id_source_agent;
            } else if (($relation['parent_type'] == NODE_AGENT) && ($relation['child_type'] == NODE_AGENT)) {
                $item['target_id_db'] = (int) $relation['id_parent_source_data'];
                $item['source_id_db'] = $id_source_agent;
            } else {
                $item['target_id_db'] = (int) $relation['id_parent_source_data'];
                $item['source_id_db'] = (int) $relation['id_child_source_data'];
            }
        }

        $item['text_end'] = '';
        $item['text_start'] = '';

        if ($relation['parent_type'] == 1) {
            $item['arrow_end'] = 'module';
            $item['status_end'] = modules_get_agentmodule_status((int) $id_target_module, false, false, null);
            $item['id_module_end'] = (int) $id_target_module;
            $text_end = modules_get_agentmodule_name((int) $id_target_module);
            if (preg_match('/(.+)_ifOperStatus$/', (string) $text_end, $matches)) {
                if ($matches[1]) {
                    // It's ok to safe_output as it inlo goint to be user into the map line
                    $item['text_end'] = io_safe_output($matches[1]);
                }
            }
        }

        if ($relation['child_type'] == NODE_MODULE) {
            $item['arrow_start'] = 'module';
            $item['status_start'] = modules_get_agentmodule_status((int) $id_source_module, false, false, null);
            $item['id_module_start'] = (int) $id_source_module;
            $text_start = modules_get_agentmodule_name((int) $id_source_module);
            if (preg_match('/(.+)_ifOperStatus$/', (string) $text_start, $matches)) {
                if ($matches[1]) {
                    // It's ok to safe_output as it inlo goint to be user into the map line
                    $item['text_start'] = io_safe_output($matches[1]);
                }
            }
        }

        $agent = 0;
        $agent2 = 0;
        $control1 = false;
        $control2 = false;

        if (($relation['parent_type'] == NODE_MODULE) && ($relation['child_type'] == NODE_MODULE)) {
            if (($item['status_start'] == AGENT_MODULE_STATUS_CRITICAL_BAD) || ($item['status_end'] == AGENT_MODULE_STATUS_CRITICAL_BAD)) {
                $item['link_color'] = '#e63c52';
            } else if (($item['status_start'] == AGENT_MODULE_STATUS_WARNING) || ($item['status_end'] == AGENT_MODULE_STATUS_WARNING)) {
                $item['link_color'] = '#f3b200';
            }

            $agent = agents_get_agent_id_by_module_id(
                $relation['id_parent_source_data']
            );
            $agent2 = agents_get_agent_id_by_module_id(
                $relation['id_child_source_data']
            );
            foreach ($nodes_graph as $key2 => $node) {
                if (isset($node['id_agent'])) {
                    if ($node['id_agent'] == $agent) {
                        $agent = $node['id_db'];
                        $control1 = true;
                    }

                    if ($node['id_agent'] == $agent2) {
                        $agent2 = $node['id_db'];
                        $control2 = true;
                    }

                    if ($control1 && $control2) {
                        break;
                    }
                }
            }
        } else if ($relation['child_type'] == NODE_MODULE) {
            if ($item['status_start'] == AGENT_MODULE_STATUS_CRITICAL_BAD) {
                $item['link_color'] = '#e63c52';
            } else if ($item['status_start'] == AGENT_MODULE_STATUS_WARNING) {
                $item['link_color'] = '#f3b200';
            }

            $agent2 = agents_get_agent_id_by_module_id(
                $relation['id_child_source_data']
            );
            foreach ($nodes_graph as $key2 => $node) {
                if (isset($node['id_agent'])) {
                    if ($node['id_agent'] == $relation['id_parent_source_data']) {
                        $agent = $node['id_db'];
                        $control1 = true;
                    }

                    if ($node['id_agent'] == $agent2) {
                        $agent2 = $node['id_db'];
                        $control2 = true;
                    }

                    if ($control1 && $control2) {
                        break;
                    }
                }
            }
        } else if ($relation['parent_type'] == NODE_MODULE) {
            if ($item['status_end'] == AGENT_MODULE_STATUS_CRITICAL_BAD) {
                $item['link_color'] = '#e63c52';
            } else if ($item['status_end'] == AGENT_MODULE_STATUS_WARNING) {
                $item['link_color'] = '#f3b200';
            }

            $agent = agents_get_agent_id_by_module_id(
                $relation['id_parent_source_data']
            );
            foreach ($nodes_graph as $key2 => $node) {
                if (isset($node['id_agent'])) {
                    if ($node['id_agent'] == $agent) {
                        $agent = $node['id_db'];
                        $control1 = true;
                    }

                    if ($node['id_agent'] == $relation['id_child_source_data']) {
                        $agent2 = $node['id_db'];
                        $control2 = true;
                    }

                    if ($control1 && $control2) {
                        break;
                    }
                }
            }
        } else if (($relation['parent_type'] == NODE_PANDORA)
            && ($relation['child_type'] == NODE_PANDORA)
        ) {
            foreach ($nodes_graph as $key2 => $node) {
                if ($relation['id_parent'] == $node['id_db']) {
                    $agent = $node['id_db'];
                }
            }

            foreach ($nodes_graph as $key2 => $node) {
                if ($relation['id_child'] == $node['id_db']) {
                    $agent2 = $node['id_db'];
                }
            }
        } else if (($relation['parent_type'] == NODE_PANDORA)
            || ($relation['child_type'] == NODE_PANDORA)
        ) {
            if ($relation['parent_type'] == NODE_PANDORA) {
                foreach ($nodes_graph as $key2 => $node) {
                    if ($relation['id_parent'] == $node['id_db']) {
                        $agent = $node['id_db'];
                    } else if ($node['id_agent'] == $relation['id_child_source_data']) {
                        $agent2 = $node['id_db'];
                    }
                }
            } else if ($relation['child_type'] == NODE_PANDORA) {
                foreach ($nodes_graph as $key2 => $node) {
                    if ($relation['id_child'] == $node['id_db']) {
                        $agent2 = $node['id_db'];
                    } else if ($node['id_agent'] == $relation['id_parent_source_data']) {
                        $agent = $node['id_db'];
                    }
                }
            }
        } else {
            foreach ($nodes_graph as $key2 => $node) {
                if (isset($node['id_agent'])) {
                    if ($node['id_agent'] == $relation['id_parent_source_data']) {
                        $agent = $node['id_db'];
                    } else if ($node['id_agent'] == $relation['id_child_source_data']) {
                        $agent2 = $node['id_db'];
                    }
                }
            }
        }

        foreach ($nodes_graph as $node) {
            if ($node['id_db'] == $agent) {
                $item['target'] = $node['id'];
            } else if ($node['id_db'] == $agent2) {
                $item['source'] = $node['id'];
            }
        }

        if ((($item['target'] == -1) || ($item['source'] == -1))
            && $relation['parent_type'] == NODE_MODULE
            && $relation['child_type'] == NODE_MODULE
        ) {
            continue;
        }

        $return[] = $item;
    }

    return $return;
}


function get_status_color_module_networkmap($id_agente_modulo)
{
    $status = modules_get_agentmodule_status($id_agente_modulo);

    // Set node status
    switch ($status) {
        case 0:
            // At the moment the networkmap enterprise does not show the
            // alerts.
        case AGENT_MODULE_STATUS_NORMAL_ALERT:
            $status_color = COL_NORMAL;
            // Normal monitor
        break;

        case 1:
            $status_color = COL_CRITICAL;
            // Critical monitor
        break;

        case 2:
            $status_color = COL_WARNING;
            // Warning monitor
        break;

        case 4:
            $status_color = COL_ALERTFIRED;
            // Alert fired
        break;

        default:
            $status_color = COL_UNKNOWN;
            // Unknown monitor
        break;
    }

    return $status_color;
}


function duplicate_networkmap($id)
{
    $return = true;

    $values = db_get_row('tmap', 'id', $id);
    unset($values['id']);
    $free_name = false;
    $values['name'] = io_safe_input(__('Copy of ')).$values['name'];
    $count = 1;
    while (!$free_name) {
        $exist = db_get_row_filter('tmap', ['name' => $values['name']]);
        if ($exist === false) {
            $free_name = true;
        } else {
            $values['name'] = $values['name'].io_safe_input(' '.$count);
        }
    }

    $correct_or_id = db_process_sql_insert('tmap', $values);
    if ($correct_or_id === false) {
        $return = false;
    } else {
        if (enterprise_installed()) {
            $new_id = $correct_or_id;
            duplicate_map_insert_nodes_and_relations($id, $new_id);
        }
    }

    if ($return) {
        return true;
    } else {
        // Clean DB.
        if (enterprise_installed()) {
            // Relations
            delete_relations($new_id);

            // Nodes
            delete_nodes($new_id);
        }

        db_process_sql_delete('tmap', ['id' => $new_id]);

        return false;
    }
}


function clean_duplicate_links($relations)
{
    if (enterprise_installed()) {
        enterprise_include_once('include/functions_networkmap.php');
    }

    $segregation_links = [];
    $index = 0;
    $index2 = 0;
    $index3 = 0;
    $index4 = 0;
    foreach ($relations as $rel) {
        if (($rel['parent_type'] == 0) && ($rel['child_type'] == 0)) {
            $segregation_links['aa'][$index] = $rel;
            $index++;
        } else if (($rel['parent_type'] == 1) && ($rel['child_type'] == 1)) {
            $segregation_links['mm'][$index2] = $rel;
            $index2++;
        } else if (($rel['parent_type'] == 3) && ($rel['child_type'] == 3)) {
            $segregation_links['ff'][$index4] = $rel;
            $index4++;
        } else {
            $segregation_links['am'][$index3] = $rel;
            $index3++;
        }
    }

    $final_links = [];

    // ----------------------------------------------------------------
    // --------------------- Clean duplicate links --------------------
    // ----------------------------------------------------------------
    $duplicated = false;
    $index_to_del = 0;
    $index = 0;
    if (isset($segregation_links['aa']) === true
        && is_array($segregation_links['aa']) === true
    ) {
        foreach ($segregation_links['aa'] as $link) {
            foreach ($segregation_links['aa'] as $link2) {
                if ($link['id_parent'] == $link2['id_child']
                    && $link['id_child'] == $link2['id_parent']
                ) {
                    if (enterprise_installed()) {
                        delete_link($segregation_links['aa'][$index_to_del]);
                    }

                    unset($segregation_links['aa'][$index_to_del]);
                }

                $index_to_del++;
            }

            $final_links['aa'][$index] = $link;
            $index++;

            $duplicated = false;
            $index_to_del = 0;
        }
    }

    $duplicated = false;
    $index_to_del = 0;
    $index2 = 0;
    if (isset($segregation_links['mm']) === true
        && is_array($segregation_links['mm']) === true
    ) {
        foreach ($segregation_links['mm'] as $link) {
            foreach ($segregation_links['mm'] as $link2) {
                if ($link['id_parent'] == $link2['id_child']
                    && $link['id_child'] == $link2['id_parent']
                ) {
                    if (enterprise_installed()) {
                        delete_link($segregation_links['mm'][$index_to_del]);
                    }
                }

                $index_to_del++;
            }

            $final_links['mm'][$index2] = $link;
            $index2++;

            $duplicated = false;
            $index_to_del = 0;
        }
    }

    $duplicated = false;
    $index_to_del = 0;
    $index3 = 0;

    if (isset($segregation_links['ff']) === true
        && is_array($segregation_links['ff']) === true
    ) {
        foreach ($segregation_links['ff'] as $link) {
            foreach ($segregation_links['ff'] as $link2) {
                if ($link['id_parent'] == $link2['id_child']
                    && $link['id_child'] == $link2['id_parent']
                ) {
                    if (enterprise_installed()) {
                        delete_link($segregation_links['ff'][$index_to_del]);
                    }

                    unset($segregation_links['ff'][$index_to_del]);
                }

                $index_to_del++;
            }

            $final_links['ff'][$index3] = $link;
            $index3++;

            $duplicated = false;
            $index_to_del = 0;
        }
    }

    $final_links['am'] = $segregation_links['am'];

    /*
        ----------------------------------------------------------------
        ----------------- AA, AM and MM links management ---------------
        ------------------ Priority: -----------------------------------
        -------------------- 1 -> MM (module - module) -----------------
        -------------------- 2 -> AM (agent - module) ------------------
        -------------------- 3 -> AA (agent - agent) -------------------
        ----------------------------------------------------------------
    */

    $final_links2 = [];
    $index = 0;
    $l3_link = [];
    $agent1 = 0;
    $agent2 = 0;

    if (isset($final_links['mm']) === true
        && is_array($final_links['mm']) === true
    ) {
        foreach ($final_links['mm'] as $rel_mm) {
            $module_parent = $rel_mm['id_parent_source_data'];
            $module_children = $rel_mm['id_child_source_data'];
            $agent1 = (int) agents_get_agent_id_by_module_id($module_parent);
            $agent2 = (int) agents_get_agent_id_by_module_id($module_children);
            foreach ($final_links['aa'] as $key => $rel_aa) {
                $l3_link = $rel_aa;
                $id_p_source_data = (int) $rel_aa['id_parent_source_data'];
                $id_c_source_data = (int) $rel_aa['id_child_source_data'];
                if ((($id_p_source_data == $agent1)
                    && ($id_c_source_data == $agent2))
                    || (($id_p_source_data == $agent2)
                    && ($id_c_source_data == $agent1))
                ) {
                    if (enterprise_installed()) {
                        delete_link($final_links['aa'][$key]);
                    }

                    unset($final_links['aa'][$key]);
                }
            }
        }
    }

    $final_links2['aa'] = $final_links['aa'];
    $final_links2['mm'] = $final_links['mm'];
    $final_links2['am'] = $final_links['am'];
    $final_links2['ff'] = $final_links['ff'];

    $same_m = [];
    $index = 0;
    if (isset($final_links2['am']) === true
        && is_array($final_links2['am']) === true
    ) {
        foreach ($final_links2['am'] as $rel_am) {
            foreach ($final_links2['am'] as $rel_am2) {
                if (($rel_am['id_child_source_data'] == $rel_am2['id_child_source_data'])
                    && ($rel_am['id_parent_source_data'] != $rel_am2['id_parent_source_data'])
                ) {
                    $same_m[$index]['rel'] = $rel_am2;
                    $same_m[$index]['agent_parent'] = $rel_am['id_parent_source_data'];
                    $index++;
                }
            }
        }
    }

    $final_links3 = [];
    $index = 0;
    $l3_link = [];
    $have_l3 = false;
    if (isset($final_links2['aa']) === true
        && is_array($final_links2['aa']) === true
    ) {
        foreach ($final_links2['aa'] as $key => $rel_aa) {
            $l3_link = $rel_aa;
            foreach ($same_m as $rel_am) {
                if ((($rel_aa['id_parent_source_data'] == $rel_am['parent']['id_parent_source_data'])
                    && ($rel_aa['id_child_source_data'] == $rel_am['rel']['id_parent_source_data']))
                    || (($rel_aa['id_child_source_data'] == $rel_am['parent']['id_parent_source_data'])
                    && ($rel_aa['id_parent_source_data'] == $rel_am['rel']['id_parent_source_data']))
                ) {
                    if (enterprise_installed()) {
                        delete_link($final_links2['aa'][$key]);
                    }

                    unset($final_links2['aa'][$key]);
                }
            }
        }
    }

    $final_links3['aa'] = $final_links2['aa'];
    $final_links3['mm'] = $segregation_links['mm'];
    $final_links3['am'] = $segregation_links['am'];
    $final_links3['ff'] = $final_links2['ff'];

    $cleaned_links = [];
    if (isset($final_links3['aa']) === true
        && is_array($final_links3['aa']) === true
    ) {
        foreach ($final_links3['aa'] as $link) {
            $cleaned_links[] = $link;
        }
    }

    if (isset($final_links3['am']) === true
        && is_array($final_links3['am']) === true
    ) {
        foreach ($final_links3['am'] as $link) {
            $cleaned_links[] = $link;
        }
    }

    if (isset($final_links3['mm']) === true
        && is_array($final_links3['mm']) === true
    ) {
        foreach ($final_links3['mm'] as $link) {
            $cleaned_links[] = $link;
        }
    }

    if (isset($final_links3['ff']) === true
        && is_array($final_links3['ff']) === true
    ) {
        foreach ($final_links3['ff'] as $link) {
            $cleaned_links[] = $link;
        }
    }

    return $cleaned_links;
}


function migrate_older_open_maps($id)
{
    global $config;

    $old_networkmap = db_get_row_filter(
        'tnetwork_map',
        ['id_networkmap' => $id]
    );

    $map_values = [];
    $map_values['id_group'] = $old_networkmap['id_group'];
    $map_values['id_user'] = $old_networkmap['id_user'];
    $map_values['type'] = 0;
    $map_values['subtype'] = 0;
    $map_values['name'] = $old_networkmap['name'];

    $new_map_filter = [];
    $new_map_filter['dont_show_subgroups'] = $old_networkmap['dont_show_subgroups'];
    $new_map_filter['node_radius'] = 40;
    $new_map_filter['x_offs'] = 0;
    $new_map_filter['y_offs'] = 0;
    $new_map_filter['z_dash'] = '0.5';
    $new_map_filter['node_sep'] = '0.1';
    $new_map_filter['rank_sep'] = 1;
    $new_map_filter['mindist'] = 1;
    $new_map_filter['kval'] = '0.1';
    $map_values['filter'] = json_encode($new_map_filter);

    $map_values['description'] = 'Mapa open migrado';
    $map_values['width'] = 4000;
    $map_values['height'] = 4000;
    $map_values['center_x'] = 2000;
    $map_values['center_y'] = 2000;
    $map_values['background'] = '';
    $map_values['background_options'] = 0;
    $map_values['source_period'] = 60;
    $map_values['source'] = 0;
    $map_values['source_data'] = $old_networkmap['id_group'];
    $map_values['generation_method'] = 3;

    $map_values['generated'] = 0;

    $id_new_map = db_process_sql_insert('tmap', $map_values);

    if (!$id_new_map) {
        return false;
    }

    return true;
}


/**
 * Load cluetip required files and JS.
 *
 * @return void
 */
function networkmap_load_cluetip()
{
    ui_require_css_file('cluetip', 'include/styles/js/');

    ?>
<script language="javascript" type="text/javascript">
    $(document).ready (function () {
        // TODO: Implement the jquery tooltip functionality everywhere
        // and remove the cluetip code.
        $("area[title!='<?php echo 'Pandora FMS'; ?>']")
            .each(function (index, element) {
                // Store the title.
                // The title stores the url into a data property
                $(element).data('uri', $(element).prop('title'));
            })
            .tooltip({
                track: true,
                content: '<?php html_print_image('images/spinner.gif'); ?>',
                open: function (evt, ui) {
                    var elem = $(this);
                    var uri = elem.data('uri');
                    
                    if (typeof uri != 'undefined' && uri.length > 0) {
                        var jqXHR = $.ajax(uri).done(function(data) {
                            elem.tooltip('option', 'content', data);
                        });
                        // Store the connection handler
                        elem.data('jqXHR', jqXHR);
                    }
                    
                    $(".ui-tooltip>.ui-tooltip-content:not(.cluetip-default)")
                        .addClass("cluetip-default");
                },
                close: function (evt, ui) {
                    var elem = $(this);
                    var jqXHR = elem.data('jqXHR');
                    
                    // Close the connection handler
                    if (typeof jqXHR != 'undefined')
                        jqXHR.abort();
                }
            });
    });
</script>
    <?php
}
