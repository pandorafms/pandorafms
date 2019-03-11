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
 *
 * @return array With requested data.
 */
function network_matrix_get_top(
    $top,
    $talker,
    $start,
    $end,
    $ip_filter='',
    $order_by_bytes=true
) {
    $field_to_group = ($talker === true) ? 'source' : 'destination';
    $field_to_order = ($order_by_bytes === true) ? 'sum_bytes' : 'sum_pkts';
    $filter_sql = '';
    if (!empty($ip_filter)) {
        $filter_field = ($talker === true) ? 'destination' : 'source';
        $filter_sql = sprintf('AND %s="%s"', $filter_field, $ip_filter);
    }

    $sql = sprintf(
        'SELECT SUM(bytes) sum_bytes, SUM(pkts) sum_pkts, %s host
        FROM tnetwork_matrix
        WHERE utimestamp > %d AND utimestamp < %d
        %s
        GROUP BY %s
        ORDER BY %s DESC
        LIMIT %d',
        $field_to_group,
        $start,
        $end,
        $filter_sql,
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
function network_get_report_actions($network)
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
