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
require_once 'functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_discovery.php');
enterprise_include_once('include/functions_metaconsole.php');


/**
 * Check if a node descends from a given node.
 *
 * @param integer $node      Id of our node.
 * @param integer $ascendant Id of ascendant node.
 * @param array   $parents   If has parents.
 *
 * @return boolean True if is descendant.
 */
function networkmap_is_descendant($node, $ascendant, $parents)
{
    if (isset($parents[$node]) === false) {
        return false;
    }

    if ((int) $node === (int) $ascendant) {
        return true;
    }

    return networkmap_is_descendant($parents[$node], $ascendant, $parents);
}


function networkmap_print_jsdata($graph, $js_tags=true)
{
    if ($js_tags === true) {
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
        if ($first === false) {
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
        if ($first === false) {
            echo ",\n";
        }

        $first = false;

        echo "{
			'source' : ".$line['source'].",
			'target' : ".$line['target']."}\n";
    }

    echo "]\n";

    echo "}\n";

    if ($js_tags === true) {
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
        false
    );

    $return = [];
    if (empty($graph) === false) {
        $graph = str_replace("\r", "\n", $graph);
        $graph = str_replace("\n", ' ', $graph);

        // Removed the head.
        preg_match('/graph networkmap {(.*)}/', $graph, $matches);
        $graph = $matches[1];

        // Get the lines and nodes.
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

        $lines = [];
        $nodes = [];
        foreach ($items as $item) {
            $matches = null;
            preg_match('/(.+)\s*\[(.*)\]/', $item, $matches);
            if (empty($matches) === true) {
                continue;
            }

            $id_item = trim($matches[1]);
            $content_item = trim($matches[2]);

            // Check if is a edge or node.
            if (strstr($id_item, '--') !== false) {
                // Edge.
                $lines[$id_item] = $content_item;
            } else {
                // Node.
                $id_item = (int) $id_item;
                $nodes[$id_item] = $content_item;
            }
        }

        foreach ($nodes as $key => $node) {
            if ($key != 0) {
                // Get label.
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

                // Get color.
                $matches = null;
                preg_match('/color="([^"]*)/', $node, $matches);
                $color = $matches[1];

                // Get tooltip.
                $matches = null;
                preg_match('/tooltip="([^"]*)/', $node, $matches);
                $tooltip = $matches[1];

                // Get URL.
                $matches = null;
                preg_match('/URL="([^"]*)/', $node, $matches);
                $url = $matches[1];

                $return['nodes'][$key]['label'] = $label;
                $return['nodes'][$key]['color'] = $color;
                $return['nodes'][$key]['tooltip'] = $tooltip;
                $return['nodes'][$key]['url'] = $url;
            } else {
                // Get tooltip.
                $matches = null;
                preg_match('/tooltip="([^"]*)/', $node, $matches);
                $tooltip = $matches[1];

                // Get URL.
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

    if (empty($text_filter) === false) {
        $filter[] = '(nombre LIKE "%'.$text_filter.'%")';
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
            $childrens = groups_get_children($group, null, true);
            if (empty($childrens) === false) {
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
        // Get agents data.
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
    } else if (empty($ip_mask) === false) {
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
        $agents = [];
    }

    // Open Graph.
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

    // Parse agents.
    $nodes = [];

    // Add node refs.
    $node_ref = [];
    $modules_node_ref = [];

    $node_count = 0;

    foreach ($agents as $agent) {
        $node_count++;

        $node_ref[$agent['id_agente']] = $node_count;

        $agent['id_node'] = $node_count;
        $agent['type'] = 'agent';

        // Add node.
        $nodes[$node_count] = $agent;

        $filter = [];
        $filter['disabled'] = 0;

        // Get agent modules data.
        $modules = agents_get_modules($agent['id_agente'], '*', $filter, true, true);
        if ($modules === false) {
            $modules = [];
        }

        // Parse modules.
        foreach ($modules as $key => $module) {
            $node_count++;
            $modules_node_ref[$module['id_agente_modulo']] = $node_count;
            $module['id_node'] = $node_count;
            $module['type'] = 'module';

            // Try to get the interface name.
            if (preg_match('/(.+)_ifOperStatus$/', (string) $module['nombre'], $matches)) {
                if ($matches[1]) {
                    $module['nombre'] = $matches[1];

                    // Save node parent information to define edges later.
                    $parents[$node_count] = $module['parent'] = $agent['id_node'];

                    // Add node.
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

    // Addded the relationship of parents of agents.
    foreach ($agents as $agent) {
        if ($agent['id_parent'] != '0' && array_key_exists($agent['id_parent'], $node_ref)) {
            $parents[$node_ref[$agent['id_agente']]] = $node_ref[$agent['id_parent']];
        } else {
            $orphans[$node_ref[$agent['id_agente']]] = 1;
        }
    }

    // Create a central node if orphan nodes exist.
    if (count($orphans) > 0 || empty($nodes) === true) {
        $graph .= networkmap_create_pandora_node($pandora_name, $font_size, $simple);
    }

    // Define edges for orphan nodes.
    foreach (array_keys($orphans) as $node) {
        $graph .= networkmap_create_edge('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
    }

    // Create void statistics array.
    $stats = [];

    // Create nodes.
    foreach ($nodes as $node_id => $node) {
        if ($center > 0 && networkmap_is_descendant($node_id, $center, $parents) === false) {
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

            default:
                // Do none.
            break;
        }
    }

    // Define edges.
    foreach ($parents as $node => $parent_id) {
        // Verify that the parent is in the graph.
        if (isset($nodes[$parent_id]) === true) {
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
    // Get the remote_snmp_proc relations.
    $relations = modules_get_relations();

    if ($relations === false) {
        $relations = [];
    }

    foreach ($relations as $relation) {
        $module_a = $relation['module_a'];
        $agent_a = modules_get_agentmodule_agent($module_a);
        $module_b = $relation['module_b'];
        $agent_b = modules_get_agentmodule_agent($module_b);

        if (isset($modules_node_ref[$module_a]) === true && isset($modules_node_ref[$module_b]) === true) {
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
        } else if (isset($node_ref[$agent_a]) === true && isset($modules_node_ref[$module_b]) === true) {
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
        } else if (isset($node_ref[$agent_b]) === true && isset($modules_node_ref[$module_a]) === true) {
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
        } else if (isset($node_ref[$agent_a]) === true && isset($node_ref[$agent_b]) === true) {
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

    // Close graph.
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
    if (is_metaconsole() === true) {
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
    $edge .= '[len='.$ranksep.', color="#BDBDBD", headclip=false, tailclip=false, edgeURL="'.$url.'"];';
    $edge .= "\n";

    return $edge;
}


/**
 * Returns a node definition.
 *
 * @param boolean $agent       Id Agent.
 * @param integer $simple      Iapa.
 * @param integer $font_size   Iapa.
 * @param boolean $cut_names   Iapa.
 * @param boolean $relative    Iapa.
 * @param boolean $metaconsole Iapa.
 * @param integer $id_server   Iapa.
 * @param boolean $strict_user Iapa.
 *
 * @return string
 */
function networkmap_create_agent_node(
    $agent,
    $simple=0,
    $font_size=10,
    $cut_names=true,
    $relative=false,
    $metaconsole=false,
    $id_server=null,
    $strict_user=false
) {
    global $config;
    global $hack_networkmap_mobile;

    if ($strict_user === true) {
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

    if (is_metaconsole() === true) {
        $server_data = db_get_row(
            'tmetaconsole_setup',
            'id',
            $agent['id_server']
        );
    }

    if (empty($server_data) === true) {
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

    // Set node status.
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

        case AGENT_STATUS_NOT_INIT:
            $status_color = COL_NOTINIT;
        break;

        default:
            $status_color = COL_UNKNOWN;
        break;
    }

    // Short name.
    $name = io_safe_output($agent['nombre']);

    if ((strlen($name) > 16) && ($cut_names)) {
        $name = ui_print_truncate_text($name, 16, false, true, false);
    }

    if ((int) $simple === 0) {
        if ($hack_networkmap_mobile) {
            $img_node = ui_print_os_icon($agent['id_os'], false, true, true, true, true, true);

            $img_node = $config['homedir'].'/'.$img_node;
            $img_node = '<img src="'.$img_node.'" />';
        } else {
            // Set node icon.
            $img_node = ui_print_os_icon($agent['id_os'], false, true, true, true, true, $relative);
            $img_node = str_replace($config['homeurl'].'/', '', $img_node);
            $img_node = str_replace($config['homeurl'], '', $img_node);

            if (is_metaconsole() === true) {
                $img_node = str_replace('../../', '', $img_node);
            }

            if ($relative === true) {
                $img_node = html_print_image($img_node, true, false, false, true);
            } else {
                $img_node = html_print_image($img_node, true, false, false, false);
            }
        }

        if (is_metaconsole() === true) {
            if (can_user_access_node()) {
                $url = ui_meta_get_url_console_child(
                    $id_server,
                    'estado',
                    'operation/agentes/ver_agente&id_agente='.$agent['id_agente']
                );
            } else {
                $url = '';
            }

            $url_tooltip = '../../ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'&metaconsole=1&id_server='.$agent['id_server'];
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

        if (is_metaconsole() === true) {
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


/**
 * Returns a module node definition.
 *
 * @param array   $module      Module definition.
 * @param integer $simple      Simple.
 * @param integer $font_size   Font size.
 * @param boolean $metaconsole Metaconsole.
 * @param integer $id_server   Id server.
 *
 * @return string
 */
function networkmap_create_module_node(
    $module,
    $simple=0,
    $font_size=10,
    $metaconsole=false,
    $id_server=null
) {
    global $config;
    global $hack_networkmap_mobile;

    if (isset($module['status']) === true) {
        $status = $module['status'];
    } else {
        $status = modules_get_agentmodule_status(
            $module['id_agente_modulo'],
            false,
            $metaconsole,
            $id_server
        );
    }

    // Set node status.
    switch ($status) {
        case AGENT_MODULE_STATUS_NORMAL:
            // Normal monitor.
            $status_color = COL_NORMAL;
        break;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
            // Critical monitor.
            $status_color = COL_CRITICAL;
        break;

        case AGENT_MODULE_STATUS_WARNING:
            // Warning monitor.
            $status_color = COL_WARNING;
        break;

        case AGENT_STATUS_ALERT_FIRED:
            // Alert fired.
            $status_color = COL_ALERTFIRED;
        break;

        default:
            // Unknown monitor.
            $status_color = COL_UNKNOWN;
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
        if (is_metaconsole() === true) {
            $url = '';
            $url_tooltip = '../../ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'&metaconsole=1&id_server='.$module['id_server'];
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
            $url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
        }

        $node = $module['id_node'].' [ id_agent="'.$module['id_agente'].'", color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
			<TR><TD>'.io_safe_output($module['nombre']).'</TD></TR></TABLE>>,
			shape="circle", URL="'.$url.'",
			tooltip="'.$url_tooltip.'"];';
    } else {
        if (is_metaconsole() === true) {
            $url = 'TODO';
            $url_tooltip = '../../ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'&metaconsole=1&id_server='.$module['id_server'];
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
            $url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
        }

        $node = $module['id_node'].' [ id_agent="'.$module['id_agente'].'", color="'.$status_color.'", fontsize='.$font_size.', shape="circle", URL="'.$url.'", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="'.$url_tooltip.'"];';
    }

    return $node;
}


/**
 * Returns the definition of the central module.
 *
 * @param string  $name      Name.
 * @param integer $font_size Font size.
 * @param integer $simple    Simple.
 * @param array   $stats     Stats.
 *
 * @return string
 */
function networkmap_create_pandora_node(
    $name,
    $font_size=10,
    $simple=0,
    $stats=[]
) {
    global $hack_networkmap_mobile;
    global $config;

    $summary = [];
    if (isset($stats['policies'])) {
        $summary['policies'] = count($stats['policies']);
    }

    if (isset($stats['groups']) === true) {
        // TODO: GET STATUS OF THE GROUPS AND ADD IT TO SUMMARY.
        $summary['groups'] = count($stats['groups']);
    }

    if (isset($stats['agents']) === true) {
        // TODO: GET STATUS OF THE AGENTS AND ADD IT TO SUMMARY.
        $summary['agents'] = count($stats['agents']);
    }

    if (isset($stats['modules']) === true) {
        // TODO: GET STATUS OF THE MODULES AND ADD IT TO SUMMARY.
        $summary['modules'] = count($stats['modules']);
    }

    $stats_json = base64_encode(json_encode($summary));

    $img_src = ui_get_logo_to_center_networkmap();
    if (is_metaconsole() === true) {
        $url_tooltip = '../../ajax.php?page=include/ajax/networkmap.ajax&action=get_networkmap_summary&stats='.$stats_json.'&metaconsole=1';
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


// Opens a graph definition.
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

    if (isset($config['networkmap_max_width']) === true) {
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

    if (empty($size_canvas) === false) {
        $size = ($size_canvas['x'] / 100).','.($size_canvas['y'] / 100);
    }

    // Graphviz custom values.
    if (isset($map_filter['node_sep']) === true) {
        $node_sep = $map_filter['node_sep'];
    } else {
        $node_sep = 0.1;
    }

    if (isset($map_filter['rank_sep']) === true) {
        $rank_sep = $map_filter['rank_sep'];
    } else {
        if ($layout === 'radial') {
            $rank_sep = 1.0;
        } else {
            $rank_sep = 0.5;
        }
    }

    if (isset($map_filter['mindist']) === true) {
        $mindist = $map_filter['mindist'];
    } else {
        $mindist = 1.0;
    }

    if (isset($map_filter['kval']) === true) {
        $kval = $map_filter['kval'];
    } else {
        $kval = 0.1;
    }

    // BEWARE: graphwiz DONT use single ('), you need double (").
    $head = 'graph networkmap { dpi=100; bgcolor="transparent"; labeljust=l; margin=0; pad="0.75,0.75";';
    if (empty($nooverlap) === false) {
        $head .= 'overlap="false";';
        $head .= 'outputorder=first;';
    }

    if ($layout === 'flat' || $layout === 'spring1' || $layout === 'spring2') {
        if (empty($nooverlap) === false) {
            $head .= 'overlap="scalexy";';
        }

        if ($layout == 'spring1' || $layout == 'spring2') {
            $head .= 'sep="'.$node_sep.'";';
        }

        if ($layout === 'flat') {
            $head .= 'ranksep="'.$rank_sep.'";';
        } else if ($layout === 'spring2') {
            $head .= 'K="'.$kval.'";';
        }
    } else if ($layout === 'radial') {
        $head .= 'ranksep="'.$rank_sep.'";';
    } else if ($layout === 'circular') {
        $head .= 'mindist="'.$mindist.'";';
    }

    $head .= 'ratio="fill";';
    $head .= 'root=0;';
    $head .= 'nodesep="'.$node_sep.'";';
    $head .= 'size="'.$size.'";';

    $head .= "\n";

    return $head;
}


/**
 * Closes a graph definition
 *
 * @return string
 */
function networkmap_close_graph()
{
    return '}';
}


/**
 * Returns the filter used to achieve the desired layout
 *
 * @param string $layout Layout.
 *
 * @return string Formatted layout.
 */
function networkmap_get_filter($layout)
{
    switch ($layout) {
        case 'flat':
            $output = 'dot';
        break;

        case 'circular':
            $output = 'circo';
        break;

        case 'spring1':
            $output = 'neato';
        break;

        case 'spring2':
            $output = 'fdp';
        break;

        case 'radial':
        default:
            $output = 'twopi';
        break;
    }

    return $output;
}


/**
 * Get a user networkmaps.
 *
 * @param integer $id_user          Networkmap id to get.
 * @param integer $type             Type.
 * @param boolean $optgrouped       Grouped.
 * @param boolean $return_all_group Return all groups.
 *
 * @return array Networkmap with the given id. False if not available or readable.
 */
function networkmap_get_networkmaps(
    $id_user=null,
    $type=null,
    $optgrouped=true,
    $return_all_group=true
) {
    global $config;

    if (empty($id_user) === true) {
        $id_user = $config['id_user'];
    }

    // Configure filters.
    $where = [];
    $where['type'] = MAP_TYPE_NETWORKMAP;
    $where['id_group'] = array_keys(
        users_get_groups(
            $id_user,
            'AR',
            $return_all_group
        )
    );

    if (empty($type) === false) {
        $where['subtype'] = $type;
    }

    $where['order'][0]['field'] = 'type';
    $where['order'][0]['order'] = 'DESC';
    $where['order'][1]['field'] = 'name';
    $where['order'][1]['order'] = 'ASC';

    if ((bool) is_metaconsole() === true) {
        $servers = metaconsole_get_connection_names();
        foreach ($servers as $server) {
            $connection = metaconsole_get_connection($server);
            if (metaconsole_connect($connection) != NOERR) {
                continue;
            }

            $tmp_maps = db_get_all_rows_filter('tmap', $where);
            if ($tmp_maps !== false) {
                foreach ($tmp_maps as $g) {
                    $g['id_t'] = $g['id'];
                    $g['id'] = $connection['id'].'_'.$g['id'];
                    $g['name'] = $g['name'].' ('.$connection['server_name'].')';
                    $networkmaps_raw[] = $g;
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $networkmaps_raw = db_get_all_rows_filter('tmap', $where);
    }

    if (empty($networkmaps_raw) === true) {
        return [];
    }

    $networkmaps = [];
    foreach ($networkmaps_raw as $networkmapitem) {
        if ($optgrouped === true) {
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


/**
 * Covert Networkmap type to string type
 *
 * @param integer $type Type of network map.
 *
 * @return string
 */
function networkmap_type_to_str_type($type)
{
    switch ($type) {
        case MAP_SUBTYPE_GROUPS:
            $output = __('Groups');
        break;

        case MAP_SUBTYPE_POLICIES:
            $output = __('Policies');
        break;

        case MAP_SUBTYPE_RADIAL_DYNAMIC:
            $output = __('Radial dynamic');
        break;

        case MAP_SUBTYPE_TOPOLOGY:
            $output = __('Topology');
        break;

        default:
            $output = '';
        break;
    }

    return $output;
}


/**
 * Get different networkmaps types for creation.
 *
 * @param boolean $strict_user Defines strict user.
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
    if ($strict_user === false) {
        $networkmap_types['radial_dynamic'] = __('Create a new radial dynamic map');
    }

    if (($is_enterprise !== ENTERPRISE_NOT_HOOK) && ($strict_user === false)) {
        $enterprise_types = enterprise_hook('policies_get_networkmap_types');

        $networkmap_types = array_merge($networkmap_types, $enterprise_types);
    }

    return $networkmap_types;
}


/**
 * Retrieve agent list matching desired network.
 *
 * @param string  $ip_mask         Networks.
 * @param boolean $return_ids_only Retrieve only ids.
 *
 * @return array Of agents.
 */
function networkmap_get_nodes_from_ip_mask(
    $ip_mask,
    $return_ids_only=false,
    $separator=',',
) {
    $list_ip_masks = explode($separator, $ip_mask);

    if (empty($list_ip_masks) === true) {
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


/**
 * Get all interfaces from modules
 *
 * @param integer $id_agent Id agent.
 *
 * @return array
 */
function modules_get_all_interfaces($id_agent)
{
    $return = [];

    $modules = db_get_all_rows_filter(
        'tagente_modulo',
        ['id_agente' => $id_agent]
    );

    if (empty($modules) === false) {
        foreach ($modules as $module) {
            if (preg_match('/(.+)_ifOperStatus$/', (string) $module['nombre'], $matches)) {
                if ($matches[1]) {
                    $return[] = $module;
                }
            }
        }
    }

    return $return;
}


/**
 * Delete network map.
 *
 * @param integer $id Id.
 *
 * @return integer Number of rows affected
 */
function networkmap_delete_networkmap($id=0)
{
    // Relations.
    $result = delete_relations($id);

    // Nodes.
    $result = delete_nodes($id);

    // Map.
    $result = db_process_sql_delete('tmap', ['id' => $id]);

    return $result;
}


/**
 * Delete nodes
 *
 * @param integer $id_map Id Map.
 *
 * @return mixed
 */
function networkmap_delete_nodes($id_map)
{
    return db_process_sql_delete('titem', ['id_map' => $id_map]);
}


/**
 * Delete relations given id_map
 *
 * @param integer $id_map Id map.
 *
 * @return integer result
 */
function networkmap_delete_relations($id_map)
{
    $result = db_process_sql_delete('trel_item', ['id_map' => $id_map]);

    return $result;
}


function get_networkmaps($id)
{
    $groups = array_keys(users_get_groups(null, 'MW'));

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

    // Hardcoded.
    $networkmap['filter']['holding_area'] = [
        500,
        500,
    ];

    // 40 = DEFAULT NODE RADIUS
    // 30 = for to align
    $holding_area_max_y = ($networkmap['height'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][1] + 10 * 40);

    $item = [];
    $item['id'] = $count;

    $item['id_db'] = ($simulated === true) ? (int) $node['id'] : (int) $node['id_in_db'];

    if ((int) $node['type'] === 0) {
        $item['type'] = 0;
        $item['id_agent'] = (int) $node['source_data'];
        $item['id_module'] = '';
    } else if ((int) $node['type'] === 1) {
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
    if ($item['state'] === 'holding_area') {
        // 40 = DEFAULT NODE RADIUS
        // 30 = for to align
        $holding_area_x = ($networkmap['width'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][0] + ($count_item_holding_area % 11) * 40);
        $holding_area_y = ($networkmap['height'] + 30 + 40 * 2 - $networkmap['filter']['holding_area'][1] + (int) (($count_item_holding_area / 11)) * 40);

        if ($holding_area_max_y <= $holding_area_y) {
            $holding_area_y = $holding_area_max_y;
        }

        $item['x'] = $holding_area_x;
        $item['y'] = $holding_area_y;

        // Increment for the next node in holding area.
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
            // Old code.
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
    if (isset($node['id_map']) === true) {
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


/**
 * Get Status color
 *
 * @param integer $id    Id Agent.
 * @param boolean $color If want to be coloured.
 *
 * @return string
 */
function get_status_color_networkmap($id, $color=true)
{
    $agent_data = db_get_row_sql('SELECT * FROM tagente WHERE id_agente = '.$id);

    if ($agent_data === false) {
        return COL_UNKNOWN;
    }

    $status = agents_get_status_from_counts($agent_data);

    if ($color === false) {
        return $status;
    }

    if ($agent_data['fired_count'] > 0) {
        return COL_ALERTFIRED;
    }

    $output = '';
    // Select node color by checking status.
    switch ($status) {
        case AGENT_MODULE_STATUS_NORMAL:
            $output = COL_NORMAL;
        break;

        case AGENT_MODULE_STATUS_NOT_INIT:
            $output = COL_NOTINIT;
        break;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
            $output = COL_CRITICAL;
        break;

        case AGENT_MODULE_STATUS_WARNING:
            $output = COL_WARNING;
        break;

        case AGENT_MODULE_STATUS_UNKNOWN:
        default:
            $output = COL_UNKNOWN;
        break;
    }

    return $output;
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

                    // Replace and erase the links.
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
    } while ($cleaned === false);
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
        $item['id_db'] = ($simulated === true) ? $key : get_relation_id($relation);

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

        if ($simulated === false) {
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
                    // It's ok to safe_output as it inlo goint to be user into the map line.
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

                    if ($control1 === true && $control2 === true) {
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
            foreach ($nodes_graph as $node) {
                if (isset($node['id_agent']) === true) {
                    if ($node['id_agent'] == $relation['id_parent_source_data']) {
                        $agent = $node['id_db'];
                        $control1 = true;
                    }

                    if ($node['id_agent'] == $agent2) {
                        $agent2 = $node['id_db'];
                        $control2 = true;
                    }

                    if ($control1 === true && $control2 === true) {
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
                foreach ($nodes_graph as $node) {
                    if ($relation['id_parent'] == $node['id_db']) {
                        $agent = $node['id_db'];
                    } else if ($node['id_agent'] == $relation['id_child_source_data']) {
                        $agent2 = $node['id_db'];
                    }
                }
            } else if ($relation['child_type'] == NODE_PANDORA) {
                foreach ($nodes_graph as $node) {
                    if ($relation['id_child'] == $node['id_db']) {
                        $agent2 = $node['id_db'];
                    } else if ($node['id_agent'] == $relation['id_parent_source_data']) {
                        $agent = $node['id_db'];
                    }
                }
            }
        } else {
            foreach ($nodes_graph as $node) {
                if (isset($node['id_agent']) === true) {
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

    // Set node status.
    switch ($status) {
        case 0:
            // At the moment the networkmap enterprise does not show the
            // alerts.
        case AGENT_MODULE_STATUS_NORMAL_ALERT:
            $status_color = COL_NORMAL;
            // Normal monitor.
        break;

        case 1:
            $status_color = COL_CRITICAL;
            // Critical monitor.
        break;

        case 2:
            $status_color = COL_WARNING;
            // Warning monitor.
        break;

        case 4:
            $status_color = COL_ALERTFIRED;
            // Alert fired.
        break;

        default:
            $status_color = COL_UNKNOWN;
            // Unknown monitor.
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
    while ($free_name === false) {
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
        $new_id = $correct_or_id;
        duplicate_map_insert_nodes_and_relations($id, $new_id);
    }

    if ($return === true) {
        return true;
    } else {
        // Relations.
        delete_relations($new_id);
        // Nodes.
        delete_nodes($new_id);
        // Clean DB.
        db_process_sql_delete('tmap', ['id' => $new_id]);

        return false;
    }
}


function clean_duplicate_links($relations)
{
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
    if (isset($segregation_links['aa']) === true && is_array($segregation_links['aa']) === true) {
        foreach ($segregation_links['aa'] as $link) {
            foreach ($segregation_links['aa'] as $link2) {
                if ($link['id_parent'] == $link2['id_child'] && $link['id_child'] == $link2['id_parent']) {
                    delete_link($segregation_links['aa'][$index_to_del]);

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
    if (isset($segregation_links['mm']) === true && is_array($segregation_links['mm']) === true) {
        foreach ($segregation_links['mm'] as $link) {
            foreach ($segregation_links['mm'] as $link2) {
                if ($link['id_parent'] == $link2['id_child'] && $link['id_child'] == $link2['id_parent']) {
                    delete_link($segregation_links['mm'][$index_to_del]);
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

    if (isset($segregation_links['ff']) === true && is_array($segregation_links['ff']) === true) {
        foreach ($segregation_links['ff'] as $link) {
            foreach ($segregation_links['ff'] as $link2) {
                if ($link['id_parent'] == $link2['id_child'] && $link['id_child'] == $link2['id_parent']) {
                    delete_link($segregation_links['ff'][$index_to_del]);

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

    if (isset($final_links['mm']) === true && is_array($final_links['mm']) === true) {
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
                    delete_link($final_links['aa'][$key]);

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
    if (isset($final_links2['am']) === true && is_array($final_links2['am']) === true) {
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
                    delete_link($final_links2['aa'][$key]);

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

    if (isset($final_links3['am']) === true && is_array($final_links3['am']) === true) {
        foreach ($final_links3['am'] as $link) {
            $cleaned_links[] = $link;
        }
    }

    if (isset($final_links3['mm']) === true && is_array($final_links3['mm']) === true) {
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
        $(document).ready(function() {
            // TODO: Implement the jquery tooltip functionality everywhere
            // and remove the cluetip code.
            $("area[title!='<?php echo 'Pandora FMS'; ?>']")
                .each(function(index, element) {
                    // Store the title.
                    // The title stores the url into a data property
                    $(element).data('uri', $(element).prop('title'));
                })
                .tooltip({
                    track: true,
                    content: '<?php html_print_image('images/spinner.gif'); ?>',
                    open: function(evt, ui) {
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
                    close: function(evt, ui) {
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


/**
 * Stores nodes and relations into db trel_item and titem.
 * This method also updates the nodes_and_relations array
 * with the id of the inserted data (needed by JS to match
 * the links).
 *
 * @param integer $id                  ID from tmap.
 * @param array   $nodes_and_relations Nodes and relations.
 *
 * @return array Nodes and relations with id link updated.
 */
function save_generate_nodes($id, $nodes_and_relations)
{
    foreach ($nodes_and_relations['nodes'] as $key => $node) {
        $values = [];
        $values['id_map'] = $id;
        $values['x'] = (int) $node['x'];
        $values['y'] = (int) $node['y'];
        $values['type'] = $node['type'];
        $values['source_data'] = $node['source_data'];

        $node_style = json_decode($node['style'], true);

        $style = [];
        if ($node['type'] == 1) {
            $style['id_agent'] = $node['id_agent'];
        } else if ($node['type'] == 0) {
            $style['id_group'] = db_get_value(
                'id_grupo',
                'tagente',
                'id_agente',
                $node['source_data']
            );
        }

        $style['shape'] = $node_style['shape'];
        $style['image'] = $node_style['image'];
        $style['width'] = $node_style['width'];
        $style['height'] = $node_style['height'];
        $style['label'] = $node_style['label'];
        $values['style'] = json_encode($style);

        $id_or_result = db_process_sql_insert('titem', $values);
        $nodes_and_relations['nodes'][$key]['id_db'] = $id_or_result;
    }

    $i = 0;
    foreach ($nodes_and_relations['relations'] as $key => $relation) {
        $i++;

        $parent_source = $relation['id_parent_source_data'];
        $child_source = $relation['id_child_source_data'];

        if ($parent_source === null) {
            $parent_source = 0;
        }

        if ($child_source === null) {
            $child_source = 0;
        }

        $id_or_result = db_process_sql_insert(
            'trel_item',
            [
                'id_map'                => $id,
                'id_parent'             => $relation['id_parent'],
                'id_child'              => $relation['id_child'],
                'id_parent_source_data' => $parent_source,
                'id_child_source_data'  => $child_source,
                'parent_type'           => $relation['parent_type'],
                'child_type'            => $relation['child_type'],
            ]
        );
        $nodes_and_relations['relations'][$key]['id_db'] = $id_or_result;
    }

    // Update networkmap size.
    return $nodes_and_relations;
}


/**
 * Load a networkmap from DB. Do not call this method directly.
 *
 * @param object $nmObject Networkmap class object.
 *
 * @return array Graph loaded.
 */
function networkmap_load_map($nmObject)
{
    global $config;

    $nodes = $nmObject->nodes;
    $relations = $nmObject->relations;

    $graph = [];
    $graph['nodes'] = [];
    $node_mapping = [];
    $i = 0;
    $node_sources = [];
    foreach ($nodes as $k => $node) {
        if ((bool) $node['deleted'] === false) {
            $tmp_node = [];
            $tmp_node['id_map'] = $node['id_map'];
            $tmp_node['id'] = $i;
            $tmp_node['id_db'] = $node['id'];
            $tmp_node['source_data'] = $node['source_data'];
            $tmp_node['type'] = $node['type'];

            $mapping_key = $node['source_data'].'_'.$node['type'];
            if ((int) $node['type'] === NODE_GENERIC) {
                $mapping_key = $node['id'].'_'.$node['type'];
            }

            if ((int) $tmp_node['type'] === NODE_AGENT) {
                $tmp_node['id_agent'] = $tmp_node['source_data'];
                $agent = agents_get_agents(
                    ['id_agente' => $tmp_node['id_agent']],
                    ['*']
                );
                if (is_array($agent) === true) {
                    $node_sources[$mapping_key] = $agent[0];
                    $status = agents_get_status_from_counts(
                        $node_sources[$mapping_key]
                    );
                }
            } else if ((int) $tmp_node['type'] === NODE_MODULE) {
                // Skip NetworkMap module nodes load due a bad previous
                // definition in databases.
                continue;
            } else {
                $tmp_node['id_agent'] = 0;
                $tmp_node['id_module'] = 0;
            }

            $style_node = json_decode($node['style'], true);

            if (is_array($style_node) === true) {
                $style = $style_node;
            } else {
                $style = [];
                $style['shape'] = $style_node['shape'];
                $style['image'] = $style_node['image'];
                $style['width'] = $style_node['width'];
                $style['height'] = $style_node['height'];
                $style['label'] = $style_node['label'];
                $style['color'] = $style_node['color'];
            }

            $style['id_networkmap'] = $style_node['networkmap'];
            $tmp_node['style'] = json_encode($style);

            $tmp_node['x'] = $node['x'];
            $tmp_node['y'] = $node['y'];
            $tmp_node['z'] = $node['z'];
            $tmp_node['color'] = $style['color'];
            $tmp_node['width'] = $style['width'];
            $tmp_node['height'] = $style['height'];
            $tmp_node['text'] = $style['label'];

            if ($tmp_node['type'] == NODE_MODULE) {
                $tmp_node['id_agent'] = $style_node['id_agent'];
            }

            // Fullfill node information, status, id_db...
            $node_sources[$mapping_key]['status'] = $status;
            $node_sources[$mapping_key]['id_db'] = $node['id'];

            // Map node.
            $nmObject->nodeMapping[$i] = $mapping_key;

            // Keep reverse node mapping for relations.
            $node_mapping[$mapping_key] = $i;

            // Add node to graph.
            $graph['nodes'][$i++] = $tmp_node;
        }
    }

    // Update nodes in networkmap object (set sources data).
    $nmObject->setNodes($node_sources);

    $graph['relations'] = [];
    $i = 0;
    if (is_array($relations) === true) {
        foreach ($relations as $rel) {
            $edge = [];
            $edge['id_map'] = $rel['id_map'];
            $edge['id_db'] = $rel['id'];

            // Default.
            $edge['id_parent_agent'] = 0;
            $edge['id_child_agent'] = 0;

            if ($rel['parent_type'] == NODE_AGENT) {
                $edge['id_parent_agent'] = $rel['id_parent_source_data'];
            } else if ($rel['parent_type'] == NODE_MODULE) {
                $edge['id_parent_agent'] = modules_get_agentmodule_agent(
                    $rel['id_parent_source_data']
                );
            }

            if ($rel['child_type'] == NODE_AGENT) {
                $edge['id_child_agent'] = $rel['id_child_source_data'];
            } else if ($rel['child_type'] == NODE_MODULE) {
                $edge['id_child_agent'] = modules_get_agentmodule_agent(
                    $rel['id_child_source_data']
                );
            }

            // Search parent by default search mapping id_source'_'type.
            if ($rel['parent_type'] == NODE_GENERIC) {
                $kp = $rel['id_parent'].'_'.$rel['parent_type'];
            } else {
                $kp = $rel['id_parent_source_data'].'_'.$rel['parent_type'];
                if ($node_mapping[$kp] === null) {
                    // Not found in direct assignment. Search agent.
                    $kp = $edge['id_parent_agent'].'_'.NODE_AGENT;
                }

                if ($node_mapping[$kp] === null) {
                    // Not found in direct assignment. Search module.
                    $kp = $edge['id_parent_agent'].'_'.NODE_MODULE;
                }
            }

            // Set parent.
            $edge['id_parent'] = $node_mapping[$kp];
            $edge['parent_type'] = $rel['parent_type'];
            $edge['id_parent_source_data'] = $rel['id_parent_source_data'];

            // Search child.
            if ($rel['child_type'] == NODE_GENERIC) {
                $kc = $rel['id_child'].'_'.$rel['child_type'];
            } else {
                $kc = $rel['id_child_source_data'].'_'.$rel['child_type'];
                if ($node_mapping[$kc] === null) {
                    // Not found in direct assignment. Search agent.
                    $kc = $edge['id_child_agent'].'_'.NODE_AGENT;
                }

                if ($node_mapping[$kc] === null) {
                    // Not found in direct assignment. Search module.
                    $kc = $edge['id_child_agent'].'_'.NODE_MODULE;
                }
            }

            // Set child.
            $edge['id_child'] = $node_mapping[$kc];
            $edge['child_type'] = $rel['child_type'];
            $edge['id_child_source_data'] = $rel['id_child_source_data'];

            // Both start and end defined and avoided self references.
            if ($edge['id_child'] !== null
                && $edge['id_parent'] !== null
                && $edge['id_parent'] != $edge['id_child']
            ) {
                $graph['relations'][$i++] = $edge;
            }
        }
    }

    $nmObject->setRelations($graph['relations']);

    return $graph;
}


/**
 * Clean duplicate links
 *
 * @param integer $id Id.
 *
 * @return void
 */
function networkmap_clean_duplicate_links($id)
{
    global $config;

    $sql_duplicate_links = 'SELECT *
		FROM trel_item t1
		WHERE t1.deleted = 0 AND t1.id_child IN (
				SELECT t2.id_child
				FROM trel_item t2
				WHERE t1.id != t2.id
					AND t1.id_child = t2.id_child
					AND t1.id_parent = t2.id_parent
					AND t2.id_map = '.$id.')
			AND t1.id_map = '.$id.'
		ORDER BY id_parent, id_child, id_parent_source_data desc, id_child_source_data desc';

    $rows = db_get_all_rows_sql($sql_duplicate_links);
    if (empty($rows) === true) {
        $rows = [];
    }

    $pre_parent = -1;
    $pre_child = -1;
    $pre_parent_source = -1;
    $pre_child_source = -1;
    foreach ($rows as $row) {
        if (($pre_parent === (int) $row['id_parent'])
            && ($pre_child === (int) $row['id_child'])
        ) {
            // Agent <-> Agent.
            if ((int) $row['parent_type'] === 0 && (int) $row['child_type'] === 0) {
                // Delete the duplicate row.
                db_process_sql_delete(
                    'trel_item',
                    ['id' => $row['id']]
                );
            } else {
                // Agent <-> Module or Module <-> Agent or Module <-> Module.
                if ($pre_parent_source === (int) $row['id_parent_source_data']
                    && $pre_child_source === (int) $row['id_child_source_data']
                ) {
                    // Delete the duplicate row.
                    db_process_sql_delete(
                        'trel_item',
                        ['id' => $row['id']]
                    );
                } else {
                    $pre_parent_source = (int) $row['id_parent_source_data'];
                    $pre_child_source = (int) $row['id_child_source_data'];
                }
            }
        } else {
            $pre_parent = (int) $row['id_parent'];
            $pre_child = (int) $row['id_child'];
            if ((int) $row['parent_type'] === 1 || (int) $row['child_type'] === 1) {
                $pre_parent_source = (int) $row['id_parent_source_data'];
                $pre_child_source = (int) $row['id_child_source_data'];
            }
        }
    }

    do {
        db_clean_cache();

        $sql_duplicate_links_parent_as_children = '
			SELECT *
			FROM trel_item t1
			WHERE t1.deleted = 0 AND t1.id_child IN (
				SELECT t2.id_parent
				FROM trel_item t2
				WHERE t1.id_parent = t2.id_child
					AND t1.id_child = t2.id_parent
					AND t2.id_map = '.$id.')
				AND t1.id_map = '.$id.'
			ORDER BY id_parent, id_child';
        $rows = db_get_all_rows_sql($sql_duplicate_links_parent_as_children);

        if (empty($rows) === true) {
            $rows = [];
        }

        $found = false;

        foreach ($rows as $row) {
            foreach ($rows as $row2) {
                if (($row['id'] != $row2['id'])
                    && ($row['id_child'] == $row2['id_parent'])
                    && ($row['id_parent'] == $row2['id_child'])
                    && ($row['parent_type'] == $row2['child_type'])
                    && ($row['child_type'] == $row2['parent_type'])
                ) {
                    // Agent <-> Agent.
                    if ((int) $row2['parent_type'] === 0 && (int) $row2['child_type'] === 0) {
                        db_process_sql_delete(
                            'trel_item',
                            ['id' => $row2['id']]
                        );

                        $found = true;
                        break;
                    } else {
                        // Agent <-> Module or Module <-> Agent or Module <-> Module.
                        if ((int) $row['id_child_source_data'] === (int) $row2['id_parent_source_data']
                            && (int) $row['id_parent_source_data'] === (int) $row2['id_child_source_data']
                        ) {
                            db_process_sql_delete(
                                'trel_item',
                                ['id' => $row2['id']]
                            );

                            $found = true;
                            break;
                        }
                    }
                } else {
                    // Si no son del mismo tipo pero hay un parent_type = 0 y child_type = 0 borrar.
                    if ((int) $row['parent_type'] === 0 && (int) $row['child_type'] === 0) {
                        db_process_sql_delete(
                            'trel_item',
                            ['id' => $row['id']]
                        );

                        $found = true;
                        break;
                    } else if ((int) $row2['parent_type'] === 0 && (int) $row2['child_type'] === 0) {
                        db_process_sql_delete(
                            'trel_item',
                            ['id' => $row2['id']]
                        );

                        $found = true;
                        break;
                    }
                }
            }

            if ($found === true) {
                break;
            }
        }
    } while ($found === true);
}


/**
 * Get node structure.
 *
 * @param integer $id Id.
 *
 * @return array
 */
function get_structure_nodes($id)
{
    $nodes = get_nodes_from_db($id);
    $relations = get_relations_from_db($id);
    $nodes_and_relations = [];

    $nodes_and_relations['nodes'] = [];
    $index_nodes = 0;
    foreach ($nodes as $node) {
        if ((bool) $node['deleted'] === false) {
            $nodes_and_relations['nodes'][$index_nodes]['id_map'] = $node['id_map'];
            $nodes_and_relations['nodes'][$index_nodes]['x'] = $node['x'];
            $nodes_and_relations['nodes'][$index_nodes]['y'] = $node['y'];
            $nodes_and_relations['nodes'][$index_nodes]['source_data'] = $node['source_data'];
            $nodes_and_relations['nodes'][$index_nodes]['type'] = $node['type'];

            $style_node = json_decode($node['style'], true);
            $style = [];
            $style['shape'] = $style_node['shape'];
            $style['image'] = $style_node['image'];
            $style['width'] = $style_node['width'];
            $style['height'] = $style_node['height'];
            $style['label'] = $style_node['label'];
            $style['id_networkmap'] = $style_node['networkmap'];
            $nodes_and_relations['nodes'][$index_nodes]['style'] = json_encode($style);

            if ($node['type'] == 1) {
                $nodes_and_relations['nodes'][$index_nodes]['id_agent'] = $style_node['id_agent'];
            }

            $nodes_and_relations['nodes'][$index_nodes]['id_in_db'] = $node['id'];

            $index_nodes++;
        }
    }

    $nodes_and_relations['relations'] = [];
    $index_relations = 0;
    foreach ($relations as $relation) {
        $nodes_and_relations['relations'][$index_relations]['id_map'] = $relation['id_map'];
        $nodes_and_relations['relations'][$index_relations]['id_parent'] = $relation['id_parent'];
        $nodes_and_relations['relations'][$index_relations]['id_child'] = $relation['id_child'];
        $nodes_and_relations['relations'][$index_relations]['parent_type'] = $relation['parent_type'];
        $nodes_and_relations['relations'][$index_relations]['child_type'] = $relation['child_type'];
        $nodes_and_relations['relations'][$index_relations]['id_parent_source_data'] = $relation['id_parent_source_data'];
        $nodes_and_relations['relations'][$index_relations]['id_child_source_data'] = $relation['id_child_source_data'];

        $index_relations++;
    }

    return $nodes_and_relations;
}


/**
 * Get nodes from database.
 *
 * @param integer $id Id.
 *
 * @return mixed
 */
function get_nodes_from_db($id)
{
    return db_get_all_rows_filter(
        'titem',
        [
            'id_map'  => $id,
            'deleted' => 0,
        ]
    );
}


/**
 * Get relations from database.
 *
 * @param integer $id Id.
 *
 * @return mixed
 */
function get_relations_from_db($id)
{
    return db_get_all_rows_filter(
        'trel_item',
        [
            'id_map'  => $id,
            'deleted' => 0,
        ]
    );
}


/**
 * Delete link.
 *
 * @param integer $link Link.
 *
 * @return mixed
 */
function delete_link($link)
{
    db_process_sql_delete(
        'trel_item',
        [
            'id_map'                => $link['id_map'],
            'id_parent'             => $link['id_parent'],
            'id_child'              => $link['id_child'],
            'id_parent_source_data' => $link['id_parent_source_data'],
            'id_child_source_data'  => $link['id_child_source_data'],
            'parent_type'           => $link['parent_type'],
            'child_type'            => $link['child_type'],
        ]
    );
}


/**
 * Get relation id
 *
 * @param array $rel Relation.
 *
 * @return mixed.
 */
function get_relation_id($rel)
{
    return db_get_value_filter(
        'id',
        'trel_item',
        [
            'id_parent'             => $rel['id_parent'],
            'id_child'              => $rel['id_child'],
            'id_parent_source_data' => $rel['id_parent_source_data'],
            'id_child_source_data'  => $rel['id_child_source_data'],
            'id_map'                => $rel['id_map'],
            'parent_type'           => $rel['parent_type'],
            'deleted'               => 0,
            'child_type'            => $rel['child_type'],
        ]
    );
}


/**
 * Get target id and his source in database.
 *
 * @param array $rel Relation.
 *
 * @return array
 */
function get_id_target_and_source_in_db($rel)
{
    $return = [];

    $return['target'] = db_get_value_filter(
        'id',
        'titem',
        [
            'source_data' => $rel['id_parent_source_data'],
            'type'        => $rel['parent_type'],
        ]
    );

    $return['source'] = db_get_value_filter(
        'id',
        'titem',
        [
            'source_data' => $rel['id_child_source_data'],
            'type'        => $rel['child_type'],
        ]
    );
    return $return;
}


/**
 * Delete link for network map.
 *
 * @param integer $networkmap_id    Id of the networkmap.
 * @param integer $source_module_id Id of source module.
 * @param integer $target_module_id Id of target module.
 * @param integer $id_link          Id of link.
 *
 * @return mixed
 */
function networkmap_delete_link(
    $networkmap_id,
    $source_module_id,
    $target_module_id,
    $id_link
) {
    $flag_delete_level2 = ((int) $source_module_id !== 0 && (int) $target_module_id !== 0);

    $link = db_get_row_filter(
        'trel_item',
        [
            'id_map'  => $networkmap_id,
            'id'      => $id_link,
            'deleted' => 0,
        ]
    );

    $result = db_process_sql_update(
        'trel_item',
        [
            'deleted' => 1,
            'id_map'  => $networkmap_id,
        ],
        ['id' => $id_link]
    );

    if ($result !== false && $flag_delete_level2 === true) {
        db_process_sql_delete('tmodule_relationship', ['module_a' => $link['id_parent_source_data'], 'module_b' => $link['id_child_source_data']]);
        db_process_sql_delete('tmodule_relationship', ['module_a' => $link['id_child_source_data'], 'module_b' => $link['id_parent_source_data']]);
    }

    return $result;
}


/**
 * Erase the node
 *
 * @param integer $id Id of node.
 *
 * @return boolean
 */
function erase_node($id)
{
    $node = db_get_row('titem', 'id', $id['id']);
    if ($node['type'] !== '2') {
        $return = db_process_sql_update(
            'titem',
            ['deleted' => 1],
            [
                'id'     => (int) $node['id'],
                'id_map' => (int) $node['id_map'],
            ]
        );

        db_process_sql_update(
            'trel_item',
            ['deleted' => 1],
            [
                'id_parent' => (int) $node['id'],
                'id_map'    => (int) $node['id_map'],
            ]
        );

        db_process_sql_update(
            'trel_item',
            ['deleted' => 1],
            [
                'id_child' => (int) $node['id'],
                'id_map'   => (int) $node['id_map'],
            ]
        );

        $node_modules = db_get_all_rows_filter(
            'titem',
            [
                'id_map' => $node['id_map'],
                'type'   => 1,
            ]
        );

        foreach ($node_modules as $node_module) {
            $style = json_decode($node_module['style'], true);

            if ($style['id_agent'] == $node['source_data']) {
                db_process_sql_update(
                    'titem',
                    ['deleted' => 1],
                    [
                        'id'     => (int) $node_module['id'],
                        'id_map' => (int) $node_module['id_map'],
                    ]
                );
                db_process_sql_update(
                    'trel_item',
                    ['deleted' => 1],
                    [
                        'id_parent_source_data' => (int) $node_module['source_data'],
                        'id_map'                => (int) $node_module['id_map'],
                    ]
                );
                db_process_sql_update(
                    'trel_item',
                    ['deleted' => 1],
                    [
                        'id_child_source_data' => (int) $node_module['source_data'],
                        'id_map'               => (int) $node_module['id_map'],
                    ]
                );
            }
        }
    } else {
        $return = db_process_sql_delete(
            'titem',
            [
                'id'     => (int) $node['id'],
                'id_map' => (int) $node['id_map'],
            ]
        );

        db_process_sql_delete(
            'trel_item',
            [
                'parent_type' => 2,
                'id_map'      => (int) $node['id_map'],
            ]
        );

        db_process_sql_delete(
            'trel_item',
            [
                'child_type' => 2,
                'id_map'     => (int) $node['id_map'],
            ]
        );
    }

    if ($return === false) {
        return false;
    } else {
        return true;
    }
}


/**
 * Add agent for network map.
 *
 * @param integer $id               Id.
 * @param string  $agent_name_param Agent name.
 * @param integer $x                X axis.
 * @param integer $y                Y axis.
 * @param integer $id_agent_param   Id agent parameter.
 * @param array   $other_values     Array with other values.
 *
 * @return mixed.
 */
function add_agent_networkmap(
    $id,
    $agent_name_param,
    $x,
    $y,
    $id_agent_param=false,
    $other_values=[]
) {
    global $config;

    if ($id_agent_param !== false) {
        $agent_name = agents_get_alias($id_agent_param);

        $id_agent = $id_agent_param;
    } else {
        $id_agent = agents_get_agent_id($agent_name_param);
        $agent_name = $agent_name_param;
    }

    if ($id_agent == false) {
        return false;
    }

    $agent = db_get_row('tagente', 'id_agente', $id_agent);

    $img_node = ui_print_os_icon(
        $agent['id_os'],
        false,
        true,
        true,
        true,
        true,
        true
    );
    $img_node_dir = str_replace(
        $config['homeurl'],
        $config['homedir'],
        $img_node
    );

    $size = getimagesize($img_node_dir);
    $width = $size[0];
    $height = $size[1];

    $data = [];
    $data['id_map'] = $id;
    $data['x'] = $x;
    $data['y'] = $y;
    $data['source_data'] = $id_agent;
    $style = [];
    $style['shape'] = 'circle';
    $style['image'] = $img_node;
    $style['width'] = $width;
    $style['height'] = $height;
    $data['type'] = 0;
    // WORKAROUND FOR THE JSON ENCODE WITH FOR EXAMPLE Ñ OR Á.
    $style['label'] = 'json_encode_crash_with_ut8_chars';

    if (isset($other_values['state']) === true) {
        $data['state'] = $other_values['state'];
    }

    if (isset($other_values['label']) === true) {
        $agent_name = $other_values['label'];
    }

    if (isset($other_values['id_module']) === true) {
        $data['source_data'] = $other_values['id_module'];
        $style['shape'] = 'arrowhead';
    }

    if (isset($other_values['type']) === true) {
        $data['type'] = $other_values['type'];
    }

    if (isset($other_values['refresh']) === true) {
        $data['refresh'] = $other_values['refresh'];
    }

    $data['style'] = json_encode($style);
    $data['style'] = str_replace(
        'json_encode_crash_with_ut8_chars',
        $agent_name,
        $data['style']
    );

    $id_node = db_process_sql_insert('titem', $data);

    $node = db_get_all_rows_filter('titem', ['id' => $id_node]);
    $node = $node[0];

    $networkmap_filter = db_get_value_filter('filter', 'tmap', ['id' => $id]);
    $networkmap_filter = json_decode($networkmap_filter, true);

    $rel = [];
    if ((bool) $networkmap_filter['empty_map'] === false) {
        $index = 0;
        if ((int) $agent['id_parent'] !== 0) {
            $parent = db_get_row('tagente', 'id_agente', $agent['id_parent']);
            $parent_item = db_get_all_rows_filter('titem', ['source_data' => $agent['id_parent'], 'type' => 0]);
            $parent_item = $parent_item[0];

            $values = [];
            $values['id_child'] = $id_node;
            $values['id_parent_source_data'] = $agent['id_parent'];
            $values['id_child_source_data'] = $agent['id_agente'];
            $values['id_parent'] = $parent_item['id'];
            $values['parent_type'] = 0;
            $values['child_type'] = 0;
            $values['id_item'] = 0;
            $values['deleted'] = 0;
            $values['id_map'] = $id;

            $rel[$index]['id_db'] = db_process_sql_insert('trel_item', $values);
            $rel[$index]['id_agent_end'] = $parent['id_agente'];
            $rel[$index]['id_agent_start'] = $agent['id_agente'];
            $rel[$index]['id_module_end'] = 0;
            $rel[$index]['id_module_start'] = 0;
            $rel[$index]['source_in_db'] = $id_node;
            $rel[$index]['target_in_db'] = $parent_item['id'];
            $rel[$index]['arrow_end'] = '';
            $rel[$index]['arrow_start'] = '';
            $rel[$index]['status_end'] = '';
            $rel[$index]['status_start'] = '';
            $rel[$index]['text_end'] = '';
            $rel[$index]['text_start'] = '';
            $index++;

            $childs_of_new_agent = db_get_all_rows_filter('tagente', ['id_parent' => $agent['id_agente']]);

            if (is_array($childs_of_new_agent) === true) {
                foreach ($childs_of_new_agent as $child) {
                    $child_item = db_get_all_rows_filter('titem', ['source_data' => $child['id_agente'], 'type' => 0]);
                    $child_item = $child_item[0];

                    $values = [];
                    $values['id_child'] = $child_item['id'];
                    $values['id_parent'] = $id_node;
                    $values['id_parent_source_data'] = $agent['id_agente'];
                    $values['id_child_source_data'] = $child['id_agente'];
                    $values['parent_type'] = 0;
                    $values['child_type'] = 0;
                    $values['id_item'] = 0;
                    $values['deleted'] = 0;
                    $values['id_map'] = $id;

                    $rel[$index]['id_db'] = db_process_sql_insert('trel_item', $values);
                    $rel[$index]['id_agent_end'] = $agent['id_agente'];
                    $rel[$index]['id_agent_start'] = $child['id_agente'];
                    $rel[$index]['id_module_end'] = 0;
                    $rel[$index]['id_module_start'] = 0;
                    $rel[$index]['source_in_db'] = $child_item['id'];
                    $rel[$index]['target_in_db'] = $id_node;
                    $rel[$index]['arrow_end'] = '';
                    $rel[$index]['arrow_start'] = '';
                    $rel[$index]['status_end'] = '';
                    $rel[$index]['status_start'] = '';
                    $rel[$index]['text_end'] = '';
                    $rel[$index]['text_start'] = '';
                    $index++;
                }
            }
        }
    }

    $return_data = [];
    if ($id_node !== false) {
        $return_data['id_node'] = $id_node;
        $return_data['rel'] = $rel;
        return $return_data;
    } else {
        return false;
    }
}


/**
 * Status color of fictional point.
 *
 * @param integer $id_networkmap Id of network map.
 * @param string  $parent        Parent.
 *
 * @return string
 */
function get_status_color_networkmap_fictional_point($id_networkmap, $parent='')
{
    $last_status = 0;

    if ((int) $id_networkmap !== 0) {
        $agents = db_get_all_rows_filter(
            'titem',
            [
                'id_map'  => $id_networkmap,
                'deleted' => 0,
            ]
        );

        if ((bool) $agents === false) {
            $agents = [];
        }

        $exit = false;
        foreach ($agents as $agent) {
            if ($agent['source_data'] == -1 || $agent['type'] == 2) {
                continue;
            }

            if ($agent['source_data'] == -2) {
                if (empty($parent) === true) {
                    if (is_array($agent) === false) {
                        $option = json_decode($agent, true);
                    }

                    if ($option['networkmap'] == 0) {
                        $status = 0;
                    } else {
                        $status = get_status_color_networkmap($option['networkmap'], true);
                    }
                } else {
                    // TODO Calculate next levels.
                    $status = 0;
                }
            } else {
                $status = get_status_color_networkmap($agent['source_data'], false);
            }

            switch ($status) {
                case AGENT_STATUS_NORMAL:
                    // Normal monitor. Do nothing.
                break;

                case AGENT_STATUS_CRITICAL:
                    // Critical monitor.
                    $last_status = AGENT_STATUS_CRITICAL;
                    $exit = true;
                break;

                case AGENT_STATUS_WARNING:
                    // Warning monitor.
                    $last_status = AGENT_STATUS_WARNING;
                break;

                case AGENT_STATUS_ALERT_FIRED:
                    if ($last_status != AGENT_STATUS_WARNING) {
                        $last_status = AGENT_STATUS_ALERT_FIRED;
                    }
                break;

                default:
                    // Unknown monitor.
                    if (($last_status != AGENT_STATUS_WARNING) && ($last_status != AGENT_STATUS_ALERT_FIRED)) {
                        $last_status = $status;
                    }
                break;
            }

            if ($exit === true) {
                break;
            }
        }
    }

    if (empty($parent) === true) {
        switch ($last_status) {
            case AGENT_STATUS_NORMAL:
                // Normal monitor.
                $status_color = COL_NORMAL;
            break;

            case AGENT_STATUS_CRITICAL:
                // Critical monitor.
                $status_color = COL_CRITICAL;
            break;

            case AGENT_STATUS_WARNING:
                // Warning monitor.
                $status_color = COL_WARNING;
            break;

            case AGENT_STATUS_ALERT_FIRED:
                // Alert fired.
                $status_color = COL_ALERTFIRED;
            break;

            default:
                // Unknown monitor.
                $status_color = COL_UNKNOWN;
            break;
        }

        return $status_color;
    } else {
        return $last_status;
    }
}


/**
 * Refresh the holding area
 *
 * @param integer $id_networkmap Id of network map.
 * @param integer $x             X Axis.
 * @param integer $y             Y Axis.
 *
 * @return array
 */
function networkmap_refresh_holding_area($id_networkmap, $x, $y)
{
    // Retrieve information from target networkmap.
    $networkmap = db_get_row_filter(
        'tmap',
        ['id' => $id_networkmap]
    );

    if ($networkmap === false) {
        return __('Map not found.');
    }

    $networkmap['filter'] = json_decode(
        $networkmap['filter'],
        true
    );

    // Search for missing nodes (and links).
    networkmap_get_new_nodes_and_links($networkmap, $x, $y);

    networkmap_clean_duplicate_links($id_networkmap);

    $rows = db_get_all_rows_filter(
        'titem',
        [
            'id_map'  => $id_networkmap,
            'refresh' => '1',
            'deleted' => 0,
        ]
    );
    if (empty($rows) === true) {
        $rows = [];
    }

    $nodes = [];
    $count = 0;
    $count_item_holding_area = 0;

    foreach ($rows as $row) {
        if (isset($row['type']) === true) {
            if ((int) $row['type'] === 1) {
                continue;
            }
        } else {
            $row['type'] = '';
        }

        $row['state'] = 'holding_area';
        db_process_sql_update(
            'titem',
            ['state' => $row['state']],
            ['id' => $row['id']]
        );

        $row['style'] = json_decode($row['style'], true);
        $row['id_db'] = $row['id'];
        $row['id_in_db'] = $row['id'];
        $node = networkmap_db_node_to_js_node(
            $row,
            $count,
            $count_item_holding_area
        );

        $nodes[$node['id']] = $node;
    }

    // Get all links of actual nodes
    // but in the javascript code filter the links and only add the
    // new links.
    $relations = db_get_all_rows_filter(
        'trel_item',
        [
            'id_map'  => $id_networkmap,
            'deleted' => 0,
        ]
    );

    if ($relations === false) {
        $relations = [];
    }

    networkmap_clean_relations_for_js($relations);

    $links_js = networkmap_links_to_js_links(
        $relations,
        $nodes
    );

    return [
        'nodes' => $nodes,
        'links' => $links_js,
    ];
}


/**
 * Update Node.
 *
 * @param array   $node           Node information.
 * @param integer $holding_area_x X Axis.
 * @param integer $holding_area_y Y Axis.
 *
 * @return array
 */
function update_node($node, $holding_area_x, $holding_area_y)
{
    $return = [];
    $return['correct'] = true;
    $return['state'] = '';
    $values = [];
    $values['x'] = $node['x'];
    $values['y'] = $node['y'];
    $values['refresh'] = 0;

    if ($node['state'] === 'holding_area') {
        $networkmap_node = db_get_row_filter(
            'titem',
            ['id' => $node['id_db']]
        );
        $networkmap = db_get_row_filter(
            'tmap',
            ['id' => $networkmap_node['id_map']]
        );
        $networkmap['filter'] = json_decode($networkmap['filter'], true);
        // Hardcoded.
        $networkmap['filter']['holding_area'] = [
            500,
            500,
        ];

        if (((($holding_area_x + 500) > $node['x']) && (($holding_area_x) < $node['x']))
            && ((($holding_area_y + 500) > $node['y']) && (($holding_area_y) < $node['y']))
        ) {
            // Inside holding area.
            $return['state'] = 'holding_area';
            $values['refresh'] = 1;
        } else {
            // The user move the node out the holding area.
            $return['state'] = '';
        }
    }

    db_process_sql_update(
        'titem',
        $values,
        ['id' => $node['id_db']]
    );

    return $return;
}


/**
 * Add agent node in an option.
 *
 * @param integer $id_networkmap Id of networkmap.
 * @param integer $id_agent      Id Agent.
 * @param integer $x             X Axis.
 * @param integer $y             Y Axis.
 *
 * @return mixed
 */
function add_agent_node_in_option($id_networkmap, $id_agent, $x, $y)
{
    $networkmap = db_get_row_filter(
        'tmap',
        ['id' => $id_networkmap]
    );

    add_agent_networkmap(
        $id_networkmap,
        '',
        // Empty because the function fill with the id.
        $x,
        $y,
        $id_agent
    );

    $networkmap_filter = db_get_value_filter('filter', 'tmap', ['id' => $id_networkmap]);
    $networkmap_filter = json_decode($networkmap_filter, true);

    if (!$networkmap_filter['empty_map']) {
        /*
         * Links Agent-Agent
         */

        $parent = db_get_value_filter(
            'id_parent',
            'tagente',
            ['id_agente' => $id_agent]
        );

        $child_node = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $id_agent,
                'id_map'      => $id_networkmap,
                'deleted'     => 0,
            ]
        );

        $parent_node = [];
        if ($parent) {
            $parent_node = db_get_value_filter(
                'id',
                'titem',
                [
                    'source_data' => $parent,
                    'id_map'      => $id_networkmap,
                    'deleted'     => 0,
                ]
            );
        }

        if (empty($parent_node) === false) {
            if (empty($child_node) === false && empty($parent_node) === false) {
                $exist = db_get_row_filter(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent_source_data' => $parent,
                        'id_child_source_data'  => $id_agent,
                        'parent_type'           => 0,
                        'child_type'            => 0,
                        'deleted'               => 0,
                    ]
                );

                if (empty($exist) === true) {
                    db_process_sql_insert(
                        'trel_item',
                        [
                            'id_map'                => $id_networkmap,
                            'id_parent'             => $parent_node,
                            'id_child'              => $child_node,
                            'id_parent_source_data' => $parent,
                            'id_child_source_data'  => $id_agent,
                            'parent_type'           => 0,
                            'child_type'            => 0,
                        ]
                    );
                }
            }
        } else {
            if (empty($parent_node) === true) {
                $parent_node = db_get_value_filter(
                    'id',
                    'titem',
                    [
                        'source_data' => 0,
                        'id_map'      => $id_networkmap,
                        'deleted'     => 0,
                    ]
                );

                $child_node = db_get_value_filter(
                    'id',
                    'titem',
                    [
                        'source_data' => $id_agent,
                        'id_map'      => $id_networkmap,
                        'deleted'     => 0,
                    ]
                );

                $exist = db_get_row_filter(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent_source_data' => 0,
                        'id_child_source_data'  => $id_agent,
                        'parent_type'           => 0,
                        'child_type'            => 0,
                        'deleted'               => 0,
                    ]
                );

                if ((bool) $exist === false) {
                    db_process_sql_insert(
                        'trel_item',
                        [
                            'id_map'                => $id_networkmap,
                            'id_parent'             => (int) $parent_node,
                            'id_child'              => $child_node,
                            'id_parent_source_data' => 0,
                            'id_child_source_data'  => $id_agent,
                            'parent_type'           => 0,
                            'child_type'            => 0,
                        ]
                    );
                }
            }
        }

        $possible_childrens = db_get_all_rows_sql('SELECT * FROM tagente WHERE id_parent = '.$id_agent);

        if ($possible_childrens !== false) {
            foreach ($possible_childrens as $agent_child) {
                $child_node_aa = db_get_value_filter(
                    'id',
                    'titem',
                    [
                        'source_data' => $agent_child['id_agente'],
                        'id_map'      => $id_networkmap,
                        'deleted'     => 0,
                    ]
                );

                if ($child_node_aa) {
                    $node_id_parent = db_get_value_filter(
                        'id',
                        'titem',
                        [
                            'source_data' => $id_agent,
                            'id_map'      => $id_networkmap,
                            'deleted'     => 0,
                        ]
                    );

                    $exist = db_get_row_filter(
                        'trel_item',
                        [
                            'id_map'                => $id_networkmap,
                            'id_parent_source_data' => $id_agent,
                            'id_child_source_data'  => $agent_child['id_agente'],
                            'parent_type'           => 0,
                            'child_type'            => 0,
                            'deleted'               => 0,
                        ]
                    );

                    if (!$exist) {
                        db_process_sql_insert(
                            'trel_item',
                            [
                                'id_map'                => $id_networkmap,
                                'id_parent'             => $node_id_parent,
                                'id_child'              => $child_node_aa['id'],
                                'id_parent_source_data' => $id_agent,
                                'id_child_source_data'  => $agent_child['id_agente'],
                                'parent_type'           => 0,
                                'child_type'            => 0,
                            ]
                        );
                    }
                }
            }
        }

        /*
            ---------------------------------------------------------------
            ------Links Agent-Module-Module-Agent (interfaces) ------------
            ---------------------------------------------------------------
        */

        $interfaces = modules_get_all_interfaces($id_agent);

        if (empty($interfaces) === true) {
            $interfaces = [];
        }

        foreach ($interfaces as $interface) {
            $style = [];
            $style['id_agent'] = $id_agent;
            $style['shape'] = 'circle';
            $style['image'] = 'images/mod_snmp_proc.png';
            $style['width'] = 50;
            $style['height'] = 16;
            $style['label'] = modules_get_agentmodule_name($interface['id_agente_modulo']);
            $id_int = db_process_sql_insert(
                'titem',
                [
                    'id_map'      => $id_networkmap,
                    'x'           => 666,
                    'y'           => 666,
                    'z'           => 0,
                    'deleted'     => 0,
                    'type'        => 1,
                    'refresh'     => 0,
                    'source'      => 0,
                    'source_data' => $interface['id_agente_modulo'],
                    'style'       => json_encode($style),
                ]
            );

            $relations = modules_get_relations(
                ['id_module' => $interface['id_agente_modulo']]
            );

            if (empty($relations)) {
                $relations = [];
            }

            foreach ($relations as $relation) {
                $interface_a = db_get_value_filter(
                    'nombre',
                    'tagente_modulo',
                    ['id_agente_modulo' => $relation['module_a']]
                );

                $interface_b = db_get_value_filter(
                    'nombre',
                    'tagente_modulo',
                    ['id_agente_modulo' => $relation['module_b']]
                );

                $a_is_interface = false;
                if (preg_match('/(.+)_ifOperStatus$/', (string) $interface_a, $matches)) {
                    if ($matches[1]) {
                            $a_is_interface = true;
                    }
                }

                $b_is_interface = false;
                if (preg_match('/(.+)_ifOperStatus$/', (string) $interface_b, $matches)) {
                    if ($matches[1]) {
                            $b_is_interface = true;
                    }
                }

                if ($a_is_interface && $b_is_interface) {
                    $exist_agent_in_map = false;
                    if ($interface['id_agente_modulo'] == $relation['module_a']) {
                        $exist_agent_in_map = db_get_value_filter(
                            'id',
                            'titem',
                            ['source_data' => modules_get_agentmodule_agent($relation['module_b'])]
                        );

                        $agent_a = $id_agent;
                        $agent_b = modules_get_agentmodule_agent($relation['module_b']);
                    } else {
                        $exist_agent_in_map = db_get_value_filter(
                            'id',
                            'titem',
                            ['source_data' => modules_get_agentmodule_agent($relation['module_a'])]
                        );

                        $agent_a = modules_get_agentmodule_agent($relation['module_a']);
                        $agent_b = $id_agent;
                    }

                    if ($exist_agent_in_map) {
                        $exist = db_get_row_filter(
                            'trel_item',
                            [
                                'id_map'                => $id_networkmap,
                                'id_parent_source_data' => $relation['module_a'],
                                'id_child_source_data'  => $relation['module_b'],
                                'deleted'               => 0,
                            ]
                        );

                        $exist_reverse = db_get_row_filter(
                            'trel_item',
                            [
                                'id_map'                => $id_networkmap,
                                'id_parent_source_data' => $relation['module_b'],
                                'id_child_source_data'  => $relation['module_a'],
                                'deleted'               => 0,
                            ]
                        );

                        if (!$exist && !$exist_reverse) {
                            if ($interface['id_agente_modulo'] == $relation['module_a']) {
                                $id_int2 = db_get_value_filter(
                                    'id',
                                    'titem',
                                    [
                                        'source_data' => $relation['module_b'],
                                        'id_map'      => $id_networkmap,
                                    ]
                                );

                                db_process_sql_insert(
                                    'trel_item',
                                    [
                                        'id_map'                => $id_networkmap,
                                        'id_parent'             => $id_int,
                                        'id_child'              => $id_int2,
                                        'id_parent_source_data' => $relation['module_a'],
                                        'id_child_source_data'  => $relation['module_b'],
                                        'parent_type'           => 1,
                                        'child_type'            => 1,
                                    ]
                                );
                            } else {
                                $id_int2 = db_get_value_filter(
                                    'id',
                                    'titem',
                                    [
                                        'source_data' => $relation['module_a'],
                                        'id_map'      => $id_networkmap,
                                    ]
                                );

                                db_process_sql_insert(
                                    'trel_item',
                                    [
                                        'id_map'                => $id_networkmap,
                                        'id_parent'             => $id_int2,
                                        'id_child'              => $id_int,
                                        'id_parent_source_data' => $relation['module_a'],
                                        'id_child_source_data'  => $relation['module_b'],
                                        'parent_type'           => 1,
                                        'child_type'            => 1,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }

        /*
            ---------------------------------------------------------------
            ------Links Agent-Module-Module-Agent -------------------------
            ---------------------------------------------------------------
        */

        $relations = modules_get_relations(['id_agent' => $id_agent]);
        if ($relations === false) {
            $relations = [];
        }

        foreach ($relations as $key => $relation) {
            $module_a = $relation['module_a'];
            $agent_a = modules_get_agentmodule_agent($module_a);
            $module_b = $relation['module_b'];
            $agent_b = modules_get_agentmodule_agent($module_b);

            $exist = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_a,
                    'id_child_source_data'  => $module_b,
                    'deleted'               => 0,
                ]
            );
            $exist_reverse = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_b,
                    'id_child_source_data'  => $module_a,
                    'deleted'               => 0,
                ]
            );

            $new_relation = false;
            if (empty($exist) === true && empty($exist_reverse) === true) {
                $style = [];
                $style['id_agent'] = $agent_a;
                $style['shape'] = 'circle';
                $style['image'] = 'images/mod_snmp_proc.png';
                $style['width'] = 50;
                $style['height'] = 16;
                $style['label'] = modules_get_agentmodule_name($module_a);
                $id_int1 = db_process_sql_insert(
                    'titem',
                    [
                        'id_map'      => $id_networkmap,
                        'x'           => 666,
                        'y'           => 666,
                        'z'           => 0,
                        'deleted'     => 0,
                        'type'        => 1,
                        'refresh'     => 0,
                        'source'      => 0,
                        'source_data' => $module_a,
                        'style'       => json_encode($style),
                    ]
                );

                $style = [];
                $style['id_agent'] = $agent_b;
                $style['shape'] = 'circle';
                $style['image'] = 'images/mod_snmp_proc.png';
                $style['width'] = 50;
                $style['height'] = 16;
                $style['label'] = modules_get_agentmodule_name($module_b);
                $id_int2 = db_process_sql_insert(
                    'titem',
                    [
                        'id_map'      => $id_networkmap,
                        'x'           => 666,
                        'y'           => 666,
                        'z'           => 0,
                        'deleted'     => 0,
                        'type'        => 1,
                        'refresh'     => 0,
                        'source'      => 0,
                        'source_data' => $module_b,
                        'style'       => json_encode($style),
                    ]
                );

                $ins_mod = db_process_sql_insert(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent'             => $id_int1,
                        'id_child'              => $id_int2,
                        'id_parent_source_data' => $module_a,
                        'id_child_source_data'  => $module_b,
                        'parent_type'           => 1,
                        'child_type'            => 1,
                    ]
                );

                if ($ins_mod) {
                    $new_relation = true;
                }
            }

            if ($new_relation) {
                $old_relation_wit_center = db_get_row_filter(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent_source_data' => 0,
                        'id_child_source_data'  => $id_agent,
                        'deleted'               => 0,
                    ]
                );

                if ($old_relation_wit_center) {
                    db_process_sql_delete(
                        'trel_item',
                        [
                            'id_map' => $id_networkmap,
                            'id'     => $old_relation_wit_center['id'],
                        ]
                    );
                }
            }
        }
    }

    $new_node = db_get_row_sql(
        sprintf(
            'SELECT * FROM titem WHERE id_map = %s AND type = 0 AND source_data = %s',
            $id_networkmap,
            $id_agent
        )
    );

    return $new_node;
}


function networkmap_get_new_nodes_and_links($networkmap, $x, $y)
{
    $id_networkmap = $networkmap['id'];
    $id_recon = $networkmap['source_data'];

    $map_filter = $networkmap['filter'];
    if (is_array($map_filter) === false) {
        $map_filter = json_decode($map_filter, true);
    }

    if ((int) $networkmap['source'] === SOURCE_TASK) {
        $agents = enterprise_hook('get_discovery_agents', [$id_recon, true]);

        $relations_discovery = modules_get_relations(['id_rt' => $id_recon, 'distinct' => true]);
        $array_aux = $relations_discovery;
        $target_aux = $relations_discovery;

        foreach ($relations_discovery as $key => $rel) {
            foreach ($array_aux as $key2 => $rel2) {
                if ($key2 <= $key) {
                    continue;
                }

                if ($rel['module_a'] === $rel2['module_a']) {
                    $agent1 = modules_get_agentmodule_agent($rel['module_b']);
                    $agent2 = modules_get_agentmodule_agent($rel2['module_b']);

                    if ($agent1 === $agent2) {
                        $name1 = modules_get_agentmodule_name($rel['module_b']);
                        $name2 = modules_get_agentmodule_name($rel2['module_b']);
                        if ($name1 == 'Host&#x20;Alive') {
                            unset($target_aux[$key]);
                        } else if ($name2 == 'Host&#x20;Alive') {
                            unset($target_aux[$key2]);
                        }

                        continue;
                    }
                }

                if ($rel['module_b'] === $rel2['module_b']) {
                    $agent1 = modules_get_agentmodule_agent($rel['module_a']);
                    $agent2 = modules_get_agentmodule_agent($rel2['module_a']);

                    if ($agent1 === $agent2) {
                        $name1 = modules_get_agentmodule_name($rel['module_a']);
                        $name2 = modules_get_agentmodule_name($rel2['module_a']);
                        if ($name1 == 'Host&#x20;Alive') {
                            unset($target_aux[$key]);
                        } else if ($name2 == 'Host&#x20;Alive') {
                            unset($target_aux[$key2]);
                        }

                        continue;
                    }
                }

                if ($rel['module_a'] === $rel2['module_b']) {
                    $agent1 = modules_get_agentmodule_agent($rel['module_b']);
                    $agent2 = modules_get_agentmodule_agent($rel2['module_a']);

                    if ($agent1 === $agent2) {
                        $name1 = modules_get_agentmodule_name($rel['module_b']);
                        $name2 = modules_get_agentmodule_name($rel2['module_a']);
                        if ($name1 == 'Host&#x20;Alive') {
                            unset($target_aux[$key]);
                        } else if ($name2 == 'Host&#x20;Alive') {
                            unset($target_aux[$key2]);
                        }

                        continue;
                    }
                }

                if ($rel['module_b'] === $rel2['module_a']) {
                    $agent1 = modules_get_agentmodule_agent($rel['module_a']);
                    $agent2 = modules_get_agentmodule_agent($rel2['module_b']);

                    if ($agent1 === $agent2) {
                        $name1 = modules_get_agentmodule_name($rel['module_a']);
                        $name2 = modules_get_agentmodule_name($rel2['module_b']);
                        if ($name1 == 'Host&#x20;Alive') {
                            unset($target_aux[$key]);
                        } else if ($name2 == 'Host&#x20;Alive') {
                            unset($target_aux[$key2]);
                        }

                        continue;
                    }
                }
            }
        }

        $relations_discovery = $target_aux;

        db_process_sql_delete('trel_item', ['id_map' => $id_networkmap, 'parent_type' => 1, 'child_type' => 1]);

        $id_recon = $id_networkmap;

        // Relations Module <-> Module.
        foreach ($relations_discovery as $key => $relation) {
            $module_a = $relation['module_a'];
            $agent_a = modules_get_agentmodule_agent($module_a);
            $module_b = $relation['module_b'];
            $agent_b = modules_get_agentmodule_agent($module_b);

            $exist = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_a,
                    'id_child_source_data'  => $module_b,
                    'deleted'               => 0,
                ]
            );
            $exist_reverse = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_b,
                    'id_child_source_data'  => $module_a,
                    'deleted'               => 0,
                ]
            );

            if (empty($exist) === true && empty($exist_reverse) === true) {
                $item_a = db_get_value(
                    'id',
                    'titem',
                    'source_data',
                    $agent_a
                );

                $item_b = db_get_value(
                    'id',
                    'titem',
                    'source_data',
                    $agent_b
                );

                db_process_sql_insert(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent'             => $item_a,
                        'id_child'              => $item_b,
                        'id_parent_source_data' => $module_a,
                        'id_child_source_data'  => $module_b,
                        'parent_type'           => 1,
                        'child_type'            => 1,
                    ]
                );
            }
        }
    } else if ((int) $networkmap['source'] === SOURCE_NETWORK) {
        // Network map, based on direct network.
        $agents = networkmap_get_nodes_from_ip_mask(
            $id_recon,
            true
        );
    } else {
        if ($map_filter['dont_show_subgroups'] == 'true' && $map_filter['dont_show_subgroups'] !== 0) {
            // Show only current selected group.
            $filter['id_grupo'] = $networkmap['id_group'];
        } else {
            // Show current group and children.
            $childrens = groups_get_children($networkmap['id_group'], null, true);
            if (empty($childrens) === false) {
                $childrens = array_keys($childrens);

                $filter['id_grupo'] = $childrens;
                $filter['id_grupo'][] = $networkmap['id_group'];
            } else {
                $filter['id_grupo'] = $networkmap['id_group'];
            }
        }

        // Group map.
        $agents = agents_get_agents(
            $filter,
            ['*'],
            'AR',
            [
                'field' => 'id_parent',
                'order' => 'ASC',
            ]
        );

        if (is_array($agents)) {
            // Remap ids.
            $agents = array_reduce(
                $agents,
                function ($carry, $item) {
                    $carry[$item['id_agente']] = $item['id_agente'];
                    return $carry;
                }
            );
        } else {
            $agents = [];
        }
    }

    // At this point $agents contains all agents must be registered in the
    // network map.
    $new_agents = [];
    if (!empty($agents)) {
        $sql = '
        SELECT t1.id_agente
        FROM tagente t1
        WHERE t1.id_agente IN ('.implode(',', $agents).')
            AND t1.disabled = 0
            AND t1.id_agente NOT IN (
                SELECT source_data
                FROM titem
                WHERE id_map = '.$id_networkmap.'
                AND source='.NODE_AGENT.')
        ';
        $new_agents = db_get_all_rows_sql($sql);
    }

    if (!is_array($new_agents)) {
        $new_agents = [];
    }

    // Insert the new nodes.
    foreach ($new_agents as $new_agent) {
        // Agent name parameter is empty because the function fill with the id.
        add_agent_networkmap(
            $id_networkmap,
            '',
            $x,
            $y,
            $new_agent['id_agente'],
            ['refresh' => 1]
        );
    }

    $new_agents_id = [];
    foreach ($new_agents as $id_agent) {
        $new_agents_id[] = $id_agent['id_agente'];
    }

    if (empty($new_agents_id) === true
        || (is_array($networkmap['filter']) === true
        && isset($networkmap['filter']['empty_map']) === true
        && (int) $networkmap['filter']['empty_map'] === 1)
    ) {
        // In empty maps do not calculate links, let the user paint them.
        // Also return if no new agents are detected.
        return;
    }

    // Links.
    $sql = 'SELECT source_data
		FROM titem
		WHERE id_map = '.$id_networkmap.'
			 AND source_data IN ('.implode(',', $new_agents_id).')
			 AND type = 0
			 AND deleted = 0
		GROUP BY source_data';
    $nodes = db_get_all_rows_sql($sql);

    foreach ($nodes as $node) {
        // First the relation parents without l2 interfaces.
        $parent = db_get_value_filter(
            'id_parent',
            'tagente',
            ['id_agente' => $node['source_data']]
        );

        $child_node = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $node['source_data'],
                'id_map'      => $id_networkmap,
                'deleted'     => 0,
            ]
        );
        $parent_node = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $parent,
                'id_map'      => $id_networkmap,
                'deleted'     => 0,
            ]
        );

        if (empty($child_node) === false && empty($parent_node) === false) {
            $exist = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $parent,
                    'id_child_source_data'  => $node['source_data'],
                    'parent_type'           => 0,
                    'child_type'            => 0,
                    'deleted'               => 0,
                ]
            );

            if (empty($exist) === true) {
                db_process_sql_insert(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent'             => $parent_node,
                        'id_child'              => $child_node,
                        'id_parent_source_data' => $parent,
                        'id_child_source_data'  => $node['source_data'],
                        'parent_type'           => 0,
                        'child_type'            => 0,
                    ]
                );
            }
        }

        if (empty($parent_node) === true) {
            $parent_node = db_get_value_filter(
                'id',
                'titem',
                [
                    'source_data' => 0,
                    'id_map'      => $id_networkmap,
                    'deleted'     => 0,
                ]
            );
            $child_node = db_get_value_filter(
                'id',
                'titem',
                [
                    'source_data' => $node['source_data'],
                    'id_map'      => $id_networkmap,
                    'deleted'     => 0,
                ]
            );

            $exist = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => 0,
                    'id_child_source_data'  => $node['source_data'],
                    'parent_type'           => 0,
                    'child_type'            => 0,
                    'deleted'               => 0,
                ]
            );

            if (empty($exist) === true) {
                db_process_sql_insert(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent'             => $parent_node,
                        'id_child'              => $child_node,
                        'id_parent_source_data' => 0,
                        'id_child_source_data'  => $node['source_data'],
                        'parent_type'           => 0,
                        'child_type'            => 0,
                        'deleted'               => 0,
                    ]
                );
            }
        }

        $relations = modules_get_relations(
            [
                'id_agent'   => $node['source_data'],
                'networkmap' => true,
            ]
        );
        if ($relations === false) {
            $relations = [];
        }

        // Relations Module <-> Module.
        foreach ($relations as $key => $relation) {
            $module_a = $relation['module_a'];
            $agent_a = modules_get_agentmodule_agent($module_a);
            $module_b = $relation['module_b'];
            $agent_b = modules_get_agentmodule_agent($module_b);

            $exist = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_a,
                    'id_child_source_data'  => $module_b,
                    'deleted'               => 0,
                ]
            );
            $exist_reverse = db_get_row_filter(
                'trel_item',
                [
                    'id_map'                => $id_networkmap,
                    'id_parent_source_data' => $module_b,
                    'id_child_source_data'  => $module_a,
                    'deleted'               => 0,
                ]
            );

            if (empty($exist) === true && empty($exist_reverse) === true) {
                $item_a = db_get_value(
                    'id',
                    'titem',
                    'source_data',
                    $agent_a
                );

                $item_b = db_get_value(
                    'id',
                    'titem',
                    'source_data',
                    $agent_b
                );

                db_process_sql_insert(
                    'trel_item',
                    [
                        'id_map'                => $id_networkmap,
                        'id_parent'             => $item_a,
                        'id_child'              => $item_b,
                        'id_parent_source_data' => $module_a,
                        'id_child_source_data'  => $module_b,
                        'parent_type'           => 1,
                        'child_type'            => 1,
                    ]
                );
            }
        }

        // Get L2 interface relations.
        $interfaces = modules_get_interfaces(
            $node['source_data'],
            [
                'id_agente',
                'id_agente_modulo',
            ]
        );
        if (empty($interfaces) === true) {
            $interfaces = [];
        }
    }
}


/**
 * Delete nodes with id agent given.
 *
 * @param integer $id_agent Id Agent.
 *
 * @return void
 */
function networkmap_delete_nodes_by_agent($id_agent)
{
    $rows = db_get_all_rows_filter(
        'titem',
        ['source_data' => $id_agent]
    );
    if (empty($rows) === true) {
        $rows = [];
    }

    foreach ($rows as $row) {
        db_process_sql_delete(
            'trel_item',
            ['id_parent' => $row['id']]
        );
        db_process_sql_delete(
            'trel_item',
            ['id_child' => $row['id']]
        );
    }

    db_process_sql_delete(
        'titem',
        ['source_data' => $id_agent]
    );
}


/**
 * Delete a relation.
 *
 * @param integer $id_map Id Map.
 *
 * @return mixed
 */
function delete_relations($id_map)
{
    return db_process_sql_delete('trel_item', ['id_map' => $id_map]);
}


/**
 * Delete a node.
 *
 * @param integer $id_map Id Map.
 *
 * @return mixed
 */
function delete_nodes($id_map)
{
    return db_process_sql_delete('titem', ['id_map' => $id_map]);
}


function duplicate_map_insert_nodes_and_relations($id, $new_id)
{
    $return = true;

    $relations = [];
    $nodes = [];

    $nodes = db_get_all_rows_filter(
        'titem',
        ['id_map' => $id]
    );
    if ($nodes === false) {
        $nodes = [];
    }

    $relations = db_get_all_rows_filter(
        'trel_item',
        [
            'id_map'  => $id,
            'deleted' => 0,
        ]
    );
    if ($relations === false) {
        $relations = [];
    }

    foreach ($nodes as $node) {
        $values = $node;
        unset($values['id']);
        $values['id_map'] = $new_id;
        $result_or_id = db_process_sql_insert('titem', $values);

        if ($result_or_id === false) {
            $return = false;
            break;
        }
    }

    // Get fictional nodes in original map.
    $fictional_nodes = db_get_all_rows_filter(
        'titem',
        [
            'id_map' => $id,
            'type'   => NODE_GENERIC,
        ]
    );

    // Get fictional nodes in new duplicate map.
    $fictional_nodes_new = db_get_all_rows_filter(
        'titem',
        [
            'id_map' => $new_id,
            'type'   => NODE_GENERIC,
        ]
    );

    // Insert the new relations.
    if ($return) {
        foreach ($relations as $relation) {
            $values = [];
            $values['id_parent'] = $relation['id_parent'];
            $values['id_child'] = $relation['id_child'];

            // In fictional nodes, we need its node id (It doesn't have agent id or module id).
            if ($relation['parent_type'] == NODE_GENERIC || $relation['child_type'] == NODE_GENERIC) {
                foreach ($fictional_nodes as $key => $fn) {
                    if ($relation['parent_type'] == NODE_GENERIC && ($relation['id_parent'] == $fn['id'])) {
                        $values['id_parent'] = $fictional_nodes_new[$key]['id'];
                    }

                    if ($relation['child_type'] == NODE_GENERIC && ($relation['id_child'] == $fn['id'])) {
                        $values['id_child'] = $fictional_nodes_new[$key]['id'];
                    }
                }
            }

            $values['id_parent_source_data'] = $relation['id_parent_source_data'];
            $values['id_child_source_data'] = $relation['id_child_source_data'];
            $values['id_map'] = $new_id;
            $values['parent_type'] = $relation['parent_type'];
            $values['child_type'] = $relation['child_type'];
            $result = db_process_sql_insert('trel_item', $values);

            if ($result === false) {
                $return = false;
                break;
            }
        }
    }
}


function migrate_older_networkmap_enterprise($id)
{
    global $config;

    $old_networkmap = db_get_row_filter(
        'tnetworkmap_enterprise',
        ['id' => $id]
    );

    $map_values = [];
    $map_values['id_group'] = $old_networkmap['id_group'];
    $map_values['id_user'] = $config['id_user'];
    $map_values['type'] = 0;
    $map_values['subtype'] = 0;
    $map_values['name'] = $old_networkmap['name'];

    $old_networkmap_options = json_decode($old_networkmap['options'], true);
    $new_map_filter = [];
    $new_map_filter['dont_show_subgroups'] = $old_networkmap_options['dont_show_subgroups'];
    $new_map_filter['node_radius'] = 40;
    $new_map_filter['id_migrate_map'] = $id;
    $map_values['filter'] = json_encode($new_map_filter);

    $map_values['description'] = 'Mapa enterprise migrado';
    $map_values['width'] = 4000;
    $map_values['height'] = 4000;

    if (!isset($old_networkmap_options['center_x'])) {
        $map_values['center_x'] = 2000;
    } else {
        if ($old_networkmap_options['center_x'] == null) {
            $map_values['center_x'] = 2000;
        } else {
            $map_values['center_x'] = $old_networkmap_options['center_x'];
        }
    }

    if (!isset($old_networkmap_options['center_y'])) {
        $map_values['center_y'] = 2000;
    } else {
        if ($old_networkmap_options['center_y'] == null) {
            $map_values['center_y'] = 2000;
        } else {
            $map_values['center_y'] = $old_networkmap_options['center_y'];
        }
    }

    $map_values['background'] = '';
    $map_values['background_options'] = 0;
    if ($old_networkmap_options['refresh_state'] == null) {
        $map_values['source_period'] = 50;
    } else {
        $map_values['source_period'] = $old_networkmap_options['refresh_state'];
    }

    switch ($old_networkmap_options['source_data']) {
        case 'group':
            $map_values['source'] = 0;
            $map_values['source_data'] = $old_networkmap['id_group'];
        break;

        case 'recon_task':
            $map_values['source'] = 1;
            $map_values['source_data'] = $old_networkmap_options['recon_task_id'];
        break;

        case 'ip_mask':
            $map_values['source'] = 2;
            $map_values['source_data'] = $old_networkmap_options['ip_mask'];
        break;

        default:
            // Do none.
        break;
    }

    switch ($old_networkmap_options['method']) {
        case 'twopi':
            $map_values['generation_method'] = LAYOUT_RADIAL;
        break;

        case 'dot':
            $map_values['generation_method'] = LAYOUT_FLAT;
        break;

        case 'circo':
            $map_values['generation_method'] = LAYOUT_CIRCULAR;
        break;

        case 'neato':
            $map_values['generation_method'] = LAYOUT_SPRING1;
        break;

        case 'fdp':
            $map_values['generation_method'] = LAYOUT_SPRING2;
        break;

        default:
            $map_values['generation_method'] = LAYOUT_RADIAL;
        break;
    }

    $map_values['generated'] = 1;

    $id_new_map = db_process_sql_insert('tmap', $map_values);

    if ((bool) $id_new_map === true) {
        $old_nodes = db_get_all_rows_filter(
            'tnetworkmap_enterprise_nodes',
            ['id_networkmap_enterprise' => $old_networkmap['id']]
        );

        $old_nodes_source_data = [];

        foreach ($old_nodes as $key => $old_node) {
            $key = $old_node['id'];
            $old_nodes_source_data[$key]['id_agent'] = $old_node['id_agent'];
            $old_nodes_source_data[$key]['id_module'] = $old_node['id_module'];

            $node_values = [];
            $node_values['id_map'] = $id_new_map;
            $node_values['x'] = $old_node['x'];
            $node_values['y'] = $old_node['y'];
            $node_values['z'] = $old_node['z'];
            $node_values['deleted'] = $old_node['deleted'];
            $node_values['source'] = 0;
            $node_values['options'] = '';

            $old_node_style = json_decode($old_node['options'], true);

            $node_style = [];
            $node_style['shape'] = $old_node_style['shape'];
            $node_style['image'] = $old_node_style['image'];
            $node_style['width'] = $old_node_style['width'];
            $node_style['height'] = $old_node_style['height'];
            $node_style['label'] = $old_node_style['text'];
            $node_style['id_group'] = $map_values['id_group'];

            $node_values['type'] = 0;
            $node_values['source_data'] = $old_node['id_agent'];

            $node_values['style'] = json_encode($node_style);

            db_process_sql_insert('titem', $node_values);
        }

        $old_relations = db_get_all_rows_filter(
            'tnetworkmap_ent_rel_nodes',
            ['id_networkmap_enterprise' => $old_networkmap['id']]
        );

        foreach ($old_relations as $old_relation) {
            $new_item_parent = db_get_row_filter(
                'titem',
                [
                    'source_data' => $old_nodes_source_data[$old_relation['parent']]['id_agent'],
                    'id_map'      => $id_new_map,
                ]
            );

            $new_item_child = db_get_row_filter(
                'titem',
                [
                    'source_data' => $old_nodes_source_data[$old_relation['child']]['id_agent'],
                    'id_map'      => $id_new_map,
                ]
            );

            $relation_values = [];
            $relation_values['id_map'] = $id_new_map;
            $relation_values['deleted'] = $old_relation['deleted'];

            $relation_values['parent_type'] = 0;
            $relation_values['id_parent_source_data'] = $old_nodes_source_data[$old_relation['parent']]['id_agent'];
            $relation_values['id_parent'] = $new_item_parent['id'];

            $relation_values['child_type'] = 0;
            $relation_values['id_child_source_data'] = $old_nodes_source_data[$old_relation['child']]['id_agent'];
            $relation_values['id_child'] = $new_item_child['id'];

            if (isset($relation_values['id_parent_source_data']) === false) {
                $relation_values['id_parent_source_data'] = 0;
            }

            if (isset($relation_values['id_parent']) === false) {
                $relation_values['id_parent'] = 0;
            }

            if (isset($relation_values['id_child_source_data']) === false) {
                $relation_values['id_child_source_data'] = 0;
            }

            if (isset($relation_values['id_child']) === false) {
                $relation_values['id_child'] = 0;
            }

            db_process_sql_insert('trel_item', $relation_values);
        }
    } else {
        return false;
    }

    return true;
}
