<?php
/**
 * Network explorer
 *
 * @package    Include.
 * @subpackage Network functions.
 *
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
    $cell = '<div class="flex_center">';
    $cell .= $title;
    $cell .= html_print_link_with_params(
        'images/arrow@svg.svg',
        array_merge($hidden_data, ['order_by' => $order]),
        'image',
        'rotate: 270deg; width: 20px; margin-top: 4px;'.(($selected === $order) ? '' : 'opacity: 0.5')
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
                'node_radius'     => 40,
                'node_sep'        => 7,
                'node_separation' => 5,
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
        'width'  => 40,
        'height' => 40,
        'status' => 0,
    ];
}
