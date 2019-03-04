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
 * @param integer $top    Number of hosts to show.
 * @param boolean $talker Talker (true) or listetener (false).
 * @param integer $start  Utimestamp of start time.
 * @param integer $end    Utimestamp of end time.
 *
 * @return array With requested data.
 */
function network_matrix_get_top($top, $talker, $start, $end)
{
    $field_to_group = ($talker === true) ? 'source' : 'destination';
    $sql = sprintf(
        'SELECT SUM(bytes) sum_bytes, SUM(pkts) sum_pkts, %s host
        FROM tnetwork_matrix
        WHERE utimestamp > %d AND utimestamp < %d
        GROUP BY %s
        ORDER BY sum_bytes DESC
        LIMIT %d',
        $field_to_group,
        $start,
        $end,
        $field_to_group,
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
