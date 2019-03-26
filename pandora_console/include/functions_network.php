<?php
/**
 * Network explorer
 *
 * @package    Include.
 * @subpackage Network functions.
 *
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// Write here requires and definitions.


/**
 * Get the tnetwok_matrix summatory data.
 *
 * @param integer $top            Number of hosts to show.
 * @param boolean $talker         Talker (true) or listetener (false).
 * @param integer $start          Utimestamp of start time.
 * @param integer $end            Utimestamp of end time.
 * @param string  $ip_filter      Ip to filter.
 * @param boolean $order_by_bytes True by top by bytes. False by packets.
 * @param array   $host_filter    Host filter array.
 *
 * @return array With requested data.
 */
function network_matrix_get_top(
    $top,
    $talker,
    $start,
    $end,
    $ip_filter='',
    $order_by_bytes=true,
    $host_filter=[]
) {
    $field_to_group = ($talker === true) ? 'source' : 'destination';
    $field_to_order = ($order_by_bytes === true) ? 'sum_bytes' : 'sum_pkts';
    $filter_sql = '';
    if (!empty($ip_filter)) {
        $filter_field = ($talker === true) ? 'destination' : 'source';
        $filter_sql = sprintf('AND %s="%s"', $filter_field, $ip_filter);
    }

    $host_filter_sql = '';
    if (!empty($host_filter)) {
        $host_filter_sql = sprintf(
            ' AND %s IN ("%s")',
            $field_to_group,
            implode('","', $host_filter)
        );
    }

    $sql = sprintf(
        'SELECT SUM(bytes) sum_bytes, SUM(pkts) sum_pkts, %s host
        FROM tnetwork_matrix
        WHERE utimestamp > %d AND utimestamp < %d
        %s
        %s
        GROUP BY %s
        ORDER BY %s DESC
        LIMIT %d',
        $field_to_group,
        $start,
        $end,
        $filter_sql,
        $host_filter_sql,
        $field_to_group,
        $field_to_order,
        $top
    );

    $data = db_get_all_rows_sql($sql);

    return ($data !== false) ? $data : [];
}


/**
 * Get the possible actions on networking.
 *
 * @param boolean $network True if network. False if netflow.
 *
 * @return array With the actions to print in a select.
 */
function network_get_report_actions($network=true)
{
    $common_actions = [
        'listeners' => __('Top listeners'),
        'talkers'   => __('Top talkers'),
    ];

    if ($network) {
        return $common_actions;
    }

    return array_merge(
        $common_actions,
        [
            'tcp' => __('Top TCP protocols'),
            'udp' => __('Top UDP protocols'),
        ]
    );
}


/**
 * Print the header of the network
 *
 * @param string $title       Title of header.
 * @param string $order       Current ordering.
 * @param string $selected    Selected order.
 * @param array  $hidden_data All the data to hide into the button.
 *
 * @return string With HTML data.
 */
function network_print_explorer_header(
    $title,
    $order,
    $selected,
    $hidden_data
) {
    $cell = '<div style="display: flex; align-items: center;">';
    $cell .= $title;
    $cell .= html_print_link_with_params(
        'images/arrow-down-white.png',
        array_merge($hidden_data, ['order_by' => $order]),
        'image',
        ($selected === $order) ? 'opacity: 0.5' : ''
    );
    $cell .= '</div>';

    return $cell;
}


/**
 * Alias for format_for_graph to print bytes.
 *
 * @param integer $value Value to parse like bytes.
 *
 * @return string Number parsed.
 */
function network_format_bytes($value)
{
    if (!isset($value)) {
        $value = 0;
    }

    $value = (int) $value;

    return format_for_graph(
        $value,
        2,
        '.',
        ',',
        1024,
        'B'
    );
}


/**
 * Build netflow data structure to network map.
 *
 * @param integer $start  Time in timestamp format.
 * @param integer $end    Time in timestamp format.
 * @param integer $top    Max data to show.
 * @param boolean $talker True to get top tolkers. False for listeners.
 *
 * @return array With map structure.
 */
function network_build_map_data($start, $end, $top, $talker)
{
    $data = network_matrix_get_top($top, $talker, $start, $end);

    $hosts = array_map(
        function ($elem) {
            return $elem['host'];
        },
        $data
    );
    $inverse_hosts = array_flip($hosts);

    $nodes = array_map(
        function ($elem) {
            return network_init_node_map($elem);
        },
        $hosts
    );

    $relations = [];
    $orphan_relations = [];
    foreach ($hosts as $host) {
        $host_top = network_matrix_get_top(
            $top,
            !$talker,
            $start,
            $end,
            $host,
            true,
            $hosts
        );
        foreach ($host_top as $sd) {
            $src_index = $inverse_hosts[$host];
            $dst_index = $inverse_hosts[$sd['host']];
            if (isset($src_index) === false || isset($dst_index) === false) {
                continue;
            }

            network_init_relation_map(
                $relations,
                $src_index,
                $dst_index,
                network_format_bytes($sd['sum_bytes'])
            );
        }

        // Put the orphans on Other node.
        if (empty($host_top)) {
            $other_id = (end($inverse_hosts) + 1);
            // TODOS: Add the data.
            network_init_relation_map(
                $orphan_relations,
                $other_id,
                $inverse_hosts[$host]
            );
        }
    }

    // Put the Others node and their relations.
    if (empty($orphan_relations) === false) {
        $nodes[] = network_init_node_map(__('Others'));
        $relations = array_merge($relations, $orphan_relations);
    }

    return network_general_map_configuration($nodes, $relations);
}


/**
 * Return the array to pass to constructor to NetworkMap.
 *
 * @param array $nodes     Nodes data structure.
 * @param array $relations Relations data structure.
 *
 * @return array To be passed to NetworMap class.
 */
function network_general_map_configuration($nodes, $relations)
{
    return [
        'nodes'           => $nodes,
        'relations'       => $relations,
        'pure'            => 1,
        'no_pandora_node' => 1,
        'no_popup'        => 1,
        'map_options'     => [
            'generation_method' => LAYOUT_SPRING1,
            'map_filter'        => [
                'node_radius' => 40,
                'node_sep'    => 7,
            ],
        ],
    ];
}


/**
 * Added a relation to relations array
 *
 * @param array   $relations Relations array (passed by reference).
 * @param integer $parent    Parent id (numeric).
 * @param integer $child     Child id (numeric).
 * @param string  $text      Text to show at the end of edge (optional).
 *
 * @return void Relations will be modified (passed by reference).
 */
function network_init_relation_map(&$relations, $parent, $child, $text='')
{
    $index = $parent.'-'.$child;
    $relations[$index] = [
        'id_parent'   => $parent,
        'parent_type' => NODE_GENERIC,
        'child_type'  => NODE_GENERIC,
        'id_child'    => $child,
        'link_color'  => '#82B92E',
    ];

    if (empty($text) === false) {
        $relations[$index]['text_start'] = $text;
    }
}


/**
 * Initialize a node structure to NetworkMap class.
 *
 * @param string $name Node name.
 *
 * @return array Node data structure.
 */
function network_init_node_map($name)
{
    return [
        'name'   => $name,
        'type'   => NODE_GENERIC,
        'width'  => 20,
        'height' => 20,
        'status' => '#82B92E',
    ];
}
