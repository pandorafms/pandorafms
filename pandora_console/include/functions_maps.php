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
 * @subpackage Maps
 */


function maps_save_map($values)
{
    $result_add = db_process_sql_insert('tmap', $values);
    return $result_add;
}


function maps_get_maps($filter)
{
    return db_get_all_rows_filter('tmap', $filter);
}


function maps_get_subtype_string($subtype)
{
    switch ($subtype) {
        case MAP_SUBTYPE_TOPOLOGY:
        return __('Topology');

            break;
        case MAP_SUBTYPE_POLICIES:
        return __('Policies');

            break;
        case MAP_SUBTYPE_GROUPS:
        return __('Groups');

            break;
        case MAP_SUBTYPE_RADIAL_DYNAMIC:
        return __('Dynamic');

            break;
        default:
        return __('Unknown');
            break;
    }
}


function maps_duplicate_map($id)
{
    global $config;
    $map = db_get_all_rows_sql('SELECT * FROM tmap WHERE id = '.$id);
    $result = false;
    $map = $map[0];
    if (!empty($map)) {
        $map_names = db_get_row_sql("SELECT name FROM tmap WHERE name LIKE '".$map['name']."%'");
        $index = 0;
        foreach ($map_names as $map_name) {
            $index++;
        }

        $new_name = __('Copy of ').$map['name'];
        $result = db_process_sql_insert(
            'tmap',
            [
                'id_group'           => $map['id_group'],
                'id_user'            => $config['id_user'],
                'type'               => $map['type'],
                'subtype'            => $map['subtype'],
                'name'               => $new_name,
                'description'        => $map['description'],
                'width'              => $map['width'],
                'height'             => $map['height'],
                'center_x'           => $map['center_x'],
                'center_y'           => $map['center_y'],
                'background'         => $map['background'],
                'background_options' => $map['background_options'],
                'source_period'      => $map['source_period'],
                'source'             => $map['source'],
                'source_data'        => $map['source_data'],
                'generation_method'  => $map['generation_method'],
                'filter'             => $map['filter'],
            ]
        );
    }

    if ($result) {
        $map_items = db_get_all_rows_sql('SELECT * FROM titem WHERE id_map = '.$id);
        maps_duplicate_items_map($result, $map_items);
    }

    return (int) $result;
}


function maps_duplicate_items_map($id, $map_items)
{
    if (empty($map_items)) {
        return;
    }

    foreach ($map_items as $item) {
        $copy_items = [
            'id_map'      => $id,
            'x'           => $item['x'],
            'y'           => $item['y'],
            'z'           => $item['z'],
            'deleted'     => $item['deleted'],
            'type'        => $item['type'],
            'refresh'     => $item['refresh'],
            'source'      => $item['source'],
            'source_data' => $item['source_data'],
            'options'     => $item['options'],
            'style'       => $item['style'],
        ];
        $result_copy_item = db_process_sql_insert('titem', $copy_items);
        if ($result_copy_item) {
            $item_relations = db_get_all_rows_sql('SELECT * FROM trel_item WHERE id = '.$item['id'].' AND deleted = 0');
            if ($item['id'] == $item_relations['parent_id']) {
                $copy_item_relations = [
                    'id_parent'   => $result_copy_item,
                    'id_child'    => $item_relations['id_child'],
                    'parent_type' => $item_relations['parent_type'],
                    'child_type'  => $item_relations['child_type'],
                    'id_item'     => $item_relations['id_item'],
                    'deleted'     => $item_relations['deleted'],
                ];
            } else {
                $copy_item_relations = [
                    'id_parent'   => $item_relations['id_parent'],
                    'id_child'    => $result_copy_item,
                    'parent_type' => $item_relations['parent_type'],
                    'child_type'  => $item_relations['child_type'],
                    'id_item'     => $item_relations['id_item'],
                    'deleted'     => $item_relations['deleted'],
                ];
            }

            db_process_sql_insert('trel_item', $copy_item_relations);
        }
    }
}


function maps_delete_map($id)
{
    $where = 'id='.$id;
    $result = db_process_sql_delete('tmap', $where);
    return (int) $result;
}


function maps_get_count_nodes($id)
{
    $result = db_get_sql('SELECT COUNT(*) FROM titem WHERE id_map = '.$id);
    return (int) $result;
}


function maps_update_map($id, $values)
{
    $where = 'id='.$id;
    $result = db_process_sql_update('tmap', $values, $where);
    return (int) $result;
}


function maps_add_node($values)
{
    $result_add_node = db_process_sql_insert('titem', $values);
    return $result_add_node;
}


function maps_add_node_relationship($values)
{
    $result_add_node_rel = db_process_sql_insert('trel_item', $values);
    return $result_add_node_rel;
}


function run_graphviz($filename_map, $filename_dot, $layout, $graph)
{
    switch (PHP_OS) {
        case 'WIN32':
        case 'WINNT':
        case 'Windows':
            $filename_plain = sys_get_temp_dir().'\\plain.txt';
        break;

        default:
            $filename_plain = sys_get_temp_dir().'/plain.txt';
        break;
    }

    file_put_contents($filename_dot, $graph);
    file_put_contents($filename_dot, $graph);

    $cmd = $layout.' -Tcmapx -o'.$filename_map.' -Tplain -o'.$filename_plain.' '.$filename_dot;

    system($cmd);

    if (file_exists($filename_map)) {
        unlink($filename_map);
    }

    if (file_exists($filename_dot)) {
        unlink($filename_dot);
    }

    return $filename_plain;
}


function open_graph($size_x=50, $size_y=25)
{
    $size = '';

    $size = $size_x.','.$size_y;

    // BEWARE: graphwiz DONT use single ('), you need double (").
    $head = 'graph vmwaremap { labeljust=l; margin=0; ';
    $head .= 'ratio=fill;';
    $head .= 'root=0;';
    $head .= 'rankdir=LR;';
    $head .= 'size="'.$size.'";';

    return $head;
}


function create_node($node, $font_size=10)
{
    // Set node status.
    if (isset($node['status'])) {
        switch ($node['status']) {
            case AGENT_MODULE_STATUS_NORMAL:
                $status_color = COL_NORMAL;
                // Normal monitor.
            break;

            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                $status_color = COL_CRITICAL;
                // Critical monitor.
            break;

            case AGENT_MODULE_STATUS_WARNING:
                $status_color = COL_WARNING;
                // Warning monitor.
            break;

            case AGENT_STATUS_ALERT_FIRED:
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            case AGENT_MODULE_STATUS_WARNING_ALERT:
                $status_color = COL_ALERTFIRED;
                // Alert fired.
            break;

            case AGENT_MODULE_STATUS_NOT_INIT:
                $status_color = COL_NOTINIT;
                // Not init.
            break;

            default:
                $status_color = COL_UNKNOWN;
                // Unknown monitor.
            break;
        }

        $status_color = 'color="'.$status_color.'",';
    } else {
        $status_color = '';
    }

    // Short name.
    if (isset($node['nombre'])) {
        $name = io_safe_output(strtolower($node['nombre']));
        if (strlen($name) > 16) {
            $name = substr($name, 0, 16).'...';
        }
    }

    // Set node icon.
    if (isset($node['image'])) {
        if (file_exists($node['image'])) {
            $img_node = $node['image'];
        } else {
            $img_node = null;
        }
    } else {
        $img_node = null;
    }

    $result = $node['id_node'].' [ '.$status_color.' fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.html_print_image($img_node, true, false, false, true).'</TD></TR>
	 <TR><TD>'.$name.'</TD></TR></TABLE>>,
	 shape="doublecircle",
	 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$node['id'].'"];';

    return $result;
}


/**
 * Returns an edge definition.
 *
 * @param string $head Origin.
 * @param string $tail Target.
 *
 * @return string Edge str.
 */
function create_edge($head, $tail)
{
    // Token edgeURL allows node navigation.
    $edge = $head.' -- '.$tail.'[color="#BDBDBD", headclip=false, tailclip=false];'."\n";

    return $edge;
}


// Closes a graph definition
function close_graph()
{
    return '}';
}


function loadfile_map($file='', $graph)
{
    global $config;

    $networkmap_nodes = [];

    $relations = [];

    $other_file = file($file);
    $graph = explode(']', $graph);

    $ids = [];
    foreach ($graph as $node) {
        $line = str_replace("\n", ' ', $node);
        if (preg_match('/([0-9]+) \[.*tooltip.*id_agent=([0-9]+)/', $line, $match) != 0) {
            $ids[$match[1]] = ['id_agent' => $match[2]];
        }
    }

    foreach ($other_file as $key => $line) {
        $line = preg_replace('/[ ]+/', ' ', $line);

        $data = [];

        if (preg_match('/^node.*$/', $line) != 0) {
            $items = explode(' ', $line);
            $node_id = $items[1];
            $node_x = ($items[2] * 100);
            // 200 is for show more big
            $node_y = ($height_map - $items[3] * 100);
            // 200 is for show more big
            $data['id'] = $node_id;
            $data['image'] = '';
            $data['width'] = 10;
            $data['height'] = 10;
            $data['id_agent'] = 0;

            if (preg_match('/<img src=\"([^\"]*)\"/', $line, $match) == 1) {
                $image = $match[1];
                $data['image'] = $config['homeurl'].'/'.$image;
                $size = getimagesize($config['homeurl'].'/'.$image);
                $data['image_width'] = $size[0];
                $data['image_height'] = $size[1];
                if ($ids[$node_id]['id_agent'] == '') {
                    $data['id_agent'] = 0;
                    $data['label'] = get_product_name();
                    $data['color'] = COL_UNKNOWN;
                } else {
                    $data['id_agent'] = $ids[$node_id]['id_agent'];
                    $data['label'] = io_safe_output(agents_get_alias($data['id_agent']));

                    $status = agents_get_status($data['id_agent']);

                    switch ($status) {
                        case 0:
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

                    $data['color'] = $status_color;
                }
            }

            $data['x'] = $node_x;
            $data['y'] = $node_y;

            $networkmap_nodes['nodes'][] = $data;
        } else if (preg_match('/^edge.*$/', $line) != 0) {
            $items = explode(' ', $line);
            $line_orig = $items[2];
            $line_dest = $items[1];

            $networkmap_nodes['arrows'][] = [
                'orig' => $line_orig,
                'dest' => $line_dest,
            ];
        }
    }

    return $networkmap_nodes;
}
