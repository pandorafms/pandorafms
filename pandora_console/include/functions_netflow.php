<?php

/**
 * Netflow functions
 *
 * @package    Functons.
 * @subpackage Netflow functions.
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

require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/include/functions_network.php';
enterprise_include_once(
    $config['homedir'].'/enterprise/include/pdf_translator.php'
);
enterprise_include_once(
    $config['homedir'].'/enterprise/include/functions_metaconsole.php'
);

define('NETFLOW_RES_LOWD', 6);
define('NETFLOW_RES_MEDD', 12);
define('NETFLOW_RES_HID', 24);
define('NETFLOW_RES_ULTRAD', 30);
define('NETFLOW_RES_HOURLY', 'hourly');
define('NETFLOW_RES_DAILY', 'daily');

// Date format for nfdump.
global $nfdump_date_format;
$nfdump_date_format = 'Y/m/d.H:i:s';

// Array to hold the hostnames.
$hostnames = [];


/**
 * Selects all netflow filters (array (id_name => id_name)) or filters filtered
 *
 * @param mixed $filter Array with filter conditions to retrieve filters or
 *      false.
 *
 * @return array List of all filters.
 */
function netflow_get_filters($filter=false)
{
    if ($filter === false) {
        $filters = db_get_all_rows_in_table('tnetflow_filter', 'id_name');
    } else {
        $filters = db_get_all_rows_filter('tnetflow_filter', $filter);
    }

    $return = [];
    if ($filters === false) {
        return $return;
    }

    foreach ($filters as $filter) {
        $return[$filter['id_name']] = $filter['id_name'];
    }

    return $return;
}


/**
 * Selects all netflow reports (array (id_name => id_name)) or filters filtered
 *
 * @param mixed $filter Array with filter conditions to retrieve filters or
 *      false.
 *
 * @return array List of all filters.
 */
function netflow_get_reports($filter=false)
{
    if ($filter === false) {
        $filters = db_get_all_rows_in_table('tnetflow_report', 'id_name');
    } else {
        $filters = db_get_all_rows_filter('tnetflow_report', $filter);
    }

    $return = [];
    if ($filters === false) {
        return $return;
    }

    foreach ($filters as $filter) {
        $return[$filter['id_name']] = $filter['id_name'];
    }

    return $return;
}


/**
 * Check if a filter owns to a certain group.
 *
 * @param integer $id_sg Id group to check.
 *
 * @return boolean True if user manages that group.
 */
function netflow_check_filter_group($id_sg)
{
    global $config;

    $id_group = db_get_value('id_group', 'tnetflow_filter', 'id_sg', $id_sg);
    $own_info = get_user_info($config['id_user']);
    // Get group list that user has access.
    $groups_user = users_get_groups($config['id_user'], 'IW', $own_info['is_admin'], true);
    $groups_id = [];
    $has_permission = false;

    foreach ($groups_user as $key => $groups) {
        if ($groups['id_grupo'] == $id_group) {
            return true;
        }
    }

    return false;
}


/**
 * Get a filter.
 *
 * @param integer $id_sg  Filter id to be fetched.
 * @param mixed   $filter Extra filter.
 * @param mixed   $fields Fields to be fetched.
 *
 * @return array A netflow filter matching id and filter.
 */
function netflow_filter_get_filter($id_sg, $filter=false, $fields=false)
{
    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_sg'] = (int) $id_sg;

    return db_get_row_filter('tnetflow_filter', $filter, $fields);
}


/**
 * Compare two flows according to the 'data' column.
 *
 * @param array $a First flow.
 * @param array $b Second flow.
 *
 * @return Result of the comparison.
 */
function compare_flows($a, $b)
{
    return $a['data'] < $b['data'];
}


/**
 * Sort netflow data according to the 'data' column.
 *
 * @param array $netflow_data Netflow data array.
 *
 * @return void (Array passed by reference)
 */
function sort_netflow_data(&$netflow_data)
{
    usort($netflow_data, 'compare_flows');
}


/**
 * Show a table with netflow statistics.
 *
 * @param array  $data       Statistic data.
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @param string $aggregate  Aggregate field.
 *
 * @return string HTML statistics table.
 */
function netflow_stat_table($data, $start_date, $end_date, $aggregate)
{
    global $nfdump_date_format;

    $start_date = date($nfdump_date_format, $start_date);
    $end_date = date($nfdump_date_format, $end_date);
    $values = [];
    $table->width = '40%';
    $table->cellspacing = 0;
    $table->class = 'databox';
    $table->data = [];
    $j = 0;
    $x = 0;

    $table->head = [];
    $table->head[0] = '<b>'.netflow_format_aggregate($aggregate).'</b>';
    $table->head[1] = '<b>'.__('Value').'</b>';
    $table->style[0] = 'padding: 6px;';
    $table->style[1] = 'padding: 6px;';

    while (isset($data[$j])) {
        $agg = $data[$j]['agg'];
        if (!isset($values[$agg])) {
            $values[$agg] = $data[$j]['data'];
        } else {
            $values[$agg] += $data[$j]['data'];
        }

        $table->data[$x][0] = $agg;
        $table->data[$x][1] = network_format_bytes($data[$j]['data']);

        $j++;
        $x++;
    }

    return html_print_table($table, true);
}


/**
 * Show a table with netflow data.
 *
 * @param array  $data       Netflow data.
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @param string $aggregate  Aggregate field.
 *
 * @return string HTML data table.
 */
function netflow_data_table($data, $start_date, $end_date, $aggregate)
{
    global $nfdump_date_format;

    $period = ($end_date - $start_date);
    $start_date = date($nfdump_date_format, $start_date);
    $end_date = date($nfdump_date_format, $end_date);

    // Set the format.
    if ($period <= SECONDS_6HOURS) {
        $time_format = 'H:i:s';
    } else if ($period < SECONDS_1DAY) {
        $time_format = 'H:i';
    } else if ($period < SECONDS_15DAYS) {
        $time_format = 'M d H:i';
    } else if ($period < SECONDS_1MONTH) {
        $time_format = 'M d H\h';
    } else {
        $time_format = 'M d H\h';
    }

    $values = [];
    $table->size = ['100%'];
    $table->class = 'databox';
    $table->cellspacing = 0;
    $table->data = [];

    $table->head = [];
    $table->head[0] = '<b>'.__('Timestamp').'</b>';
    $table->style[0] = 'padding: 4px';

    $j = 0;
    $source_index = [];
    $source_count = 0;

    if (isset($data['sources'])) {
        foreach ($data['sources'] as $source => $null) {
            $table->style[($j + 1)] = 'padding: 4px';
            $table->align[($j + 1)] = 'right';
            $table->headstyle[($j + 1)] = 'text-align: right;';
            $table->head[($j + 1)] = $source;
            $source_index[$j] = $source;
            $source_count++;
            $j++;
        }
    } else {
        $table->style[1] = 'padding: 4px;';
    }

    // No aggregates.
    if ($source_count == 0) {
        $table->head[1] = __('Data');
        $table->align[1] = 'right';
        $i = 0;

        foreach ($data as $timestamp => $value) {
            $table->data[$i][0] = date($time_format, $timestamp);
            $table->data[$i][1] = network_format_bytes($value['data']);
            $i++;
        }
    }

    // Aggregates.
    else {
        $i = 0;
        foreach ($data['data'] as $timestamp => $values) {
            $table->data[$i][0] = date($time_format, $timestamp);
            for ($j = 0; $j < $source_count; $j++) {
                $table->data[$i][($j + 1)] = network_format_bytes(
                    $values[$source_index[$j]]
                );
            }

            $i++;
        }
    }

    return html_print_table($table, true);
}


/**
 * Show a table with a traffic summary.
 *
 * @param array $data Summary data.
 *
 * @return string HTML summary table.
 */
function netflow_summary_table($data)
{
    global $nfdump_date_format;

    $values = [];
    $table->size = ['50%'];
    $table->cellspacing = 0;
    $table->class = 'databox';
    $table->data = [];

    $table->style[0] = 'font-weight: bold; padding: 6px';
    $table->style[1] = 'padding: 6px';

    $row = [];
    $row[] = __('Total flows');
    $row[] = format_for_graph($data['totalflows'], 2);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Total bytes');
    $row[] = network_format_bytes($data['totalbytes']);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Total packets');
    $row[] = format_for_graph($data['totalpackets'], 2);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Average bits per second');
    $row[] = network_format_bytes($data['avgbps']);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Average packets per second');
    $row[] = format_for_graph($data['avgpps'], 2);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Average bytes per packet');
    $row[] = format_for_graph($data['avgbpp'], 2);
    $table->data[] = $row;

    $html = html_print_table($table, true);

    return $html;
}


/**
 * Returns 1 if the given address is a network address.
 *
 * @param string $address Host or network address.
 *
 * @return 1 if the address is a network address, 0 otherwise.
 */
function netflow_is_net($address)
{
    if (strpos($address, '/') !== false) {
        return 1;
    }

    return 0;
}


/**
 * Returns netflow data for the given period in an array.
 *
 * @param string  $start_date         Period start date.
 * @param string  $end_date           Period end date.
 * @param mixed   $interval_length    Resolution points or hourly or daily.
 * @param string  $filter             Netflow filter.
 * @param string  $aggregate          Aggregate field.
 * @param integer $max                Maximum number of aggregates.
 * @param boolean $absolute           True to give the absolute data and false
 *      to get troughput.
 * @param string  $connection_name    Node name when data is get in meta.
 * @param boolean $address_resolution True to resolve ips to hostnames.
 *
 * @return array An array with netflow stats.
 */
function netflow_get_data(
    $start_date,
    $end_date,
    $interval_length,
    $filter,
    $aggregate,
    $max,
    $absolute,
    $connection_name='',
    $address_resolution=false
) {
    global $nfdump_date_format;
    global $config;

    // Requesting remote data.
    if (defined('METACONSOLE') && $connection_name != '') {
        $data = metaconsole_call_remote_api(
            $connection_name,
            'netflow_get_data',
            "$start_date|$end_date|$interval_length|".base64_encode(json_encode($filter))."|$aggregate|$max|1".(int) $address_resolution
        );
        return json_decode($data, true);
    }

    if ($start_date > $end_date) {
        return [];
    }

    // Calculate the number of intervals.
    $multiplier_time = ($end_date - $start_date);
    switch ($interval_length) {
        case NETFLOW_RES_LOWD:
        case NETFLOW_RES_MEDD:
        case NETFLOW_RES_HID:
        case NETFLOW_RES_ULTRAD:
            $multiplier_time = ceil(($end_date - $start_date) / $interval_length);
        break;

        case NETFLOW_RES_HOURLY:
            $multiplier_time = SECONDS_1HOUR;
        break;

        case NETFLOW_RES_DAILY:
            $multiplier_time = SECONDS_1DAY;
        break;

        default:
            $multiplier_time = ($end_date - $start_date);
        break;
    }

    // Recalculate to not pass of netflow_max_resolution.
    if ($config['netflow_max_resolution'] > 0
        && (($end_date - $start_date) / $multiplier_time) > 50
    ) {
        $multiplier_time = ceil(
            (($end_date - $start_date) / $config['netflow_max_resolution'])
        );
    }

    // Put all points into an array.
    $intervals = [($start_date - $multiplier_time)];
    while ((end($intervals) < $end_date) === true) {
        $intervals[] = (end($intervals) + $multiplier_time);
    }

    if (end($intervals) != $end_date) {
        $intervals[] = $end_date;
    }

    // If there is aggregation calculate the top n.
    $values['data'] = [];
    $values['sources'] = [];

    // Get the command to call nfdump.
    $command = netflow_get_command($filter);

    // Suppress the header line and the statistics at the bottom and configure
    // piped output.
    $command .= ' -q -o csv';

    // Call nfdump.
    $agg_command = $command." -n $max -s $aggregate/bytes -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
    exec($agg_command, $string);

    // Remove the first line.
    $string[0] = '';

    // Parse aggregates.
    foreach ($string as $line) {
        if ($line == '') {
            continue;
        }

        $val = explode(',', $line);
        $values['sources'][$val[4]] = 1;
    }

    // Update the filter.
    switch ($aggregate) {
        default:
        case 'srcip':
            $extra_filter = 'ip_src';
        break;
        case 'srcport':
            $extra_filter = 'src_port';
        break;

        case 'dstip':
            $extra_filter = 'ip_dst';
        break;

        case 'dstport':
            $extra_filter = 'dst_port';
        break;
    }

    if (isset($filter[$extra_filter]) && $filter[$extra_filter] != '') {
        $filter[$extra_filter] .= ',';
    }

    $filter[$extra_filter] = implode(
        ',',
        array_keys($values['sources'])
    );

    // Address resolution start.
    $get_hostnames = false;
    if ($address_resolution && ($aggregate == 'srcip' || $aggregate == 'dstip')) {
        $get_hostnames = true;
        global $hostnames;

        $sources = [];
        foreach ($values['sources'] as $source => $value) {
            if (!isset($hostnames[$source])) {
                $hostname = gethostbyaddr($source);
                if ($hostname !== false) {
                    $hostnames[$source] = $hostname;
                    $source = $hostname;
                }
            } else {
                $source = $hostnames[$source];
            }

            $sources[$source] = $value;
        }

        $values['sources'] = $sources;
    }

    // Address resolution end.
    foreach ($intervals as $k => $time) {
        $interval_start = $time;
        if (!isset($intervals[($k + 1)])) {
            continue;
        }

        $interval_end = $intervals[($k + 1)];

        // Set default values.
        foreach ($values['sources'] as $source => $discard) {
            $values['data'][$interval_end][$source] = 0;
        }

        $data = netflow_get_stats(
            $interval_start,
            $interval_end,
            $filter,
            $aggregate,
            $max,
            $absolute,
            $connection_name
        );

        foreach ($data as $line) {
            // Address resolution start.
            if ($get_hostnames) {
                if (!isset($hostnames[$line['agg']])) {
                    $hostname = false;
                    // Trying to get something like an IP from the description.
                    if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $line['agg'], $matches)
                        || preg_match(
                            "/(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:?)|\2))(?4){5}((?4){2}|(25[0-5]|
                            (2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})/i",
                            $line['agg'],
                            $matches
                        )
                    ) {
                        if ($matches[0]) {
                            $hostname = gethostbyaddr($line['agg']);
                        }
                    }

                    if ($hostname !== false) {
                        $hostnames[$line['agg']] = $hostname;
                        $line['agg'] = $hostname;
                    }
                } else {
                    $line['agg'] = $hostnames[$line['agg']];
                }
            }

            // Address resolution end.
            if (! isset($values['sources'][$line['agg']])) {
                continue;
            }

            $values['data'][$interval_end][$line['agg']] = $line['data'];
        }
    }

    if (empty($values['data'])) {
        return [];
    }

    return $values;
}


/**
 * Returns netflow stats for the given period in an array.
 *
 * @param string  $start_date         Period start date.
 * @param string  $end_date           Period end date.
 * @param string  $filter             Netflow filter.
 * @param string  $aggregate          Aggregate field.
 * @param integer $max                Maximum number of aggregates.
 * @param boolean $absolute           True to give the absolute data and false to get
 *                troughput.
 * @param string  $connection_name    Node name when data is get in meta.
 * @param boolean $address_resolution True to resolve ips to hostnames.
 *
 * @return array With netflow stats.
 */
function netflow_get_stats(
    $start_date,
    $end_date,
    $filter,
    $aggregate,
    $max,
    $absolute=true,
    $connection_name='',
    $address_resolution=false
) {
    global $config, $nfdump_date_format;

    // Requesting remote data.
    if (defined('METACONSOLE') && $connection_name != '') {
        $data = metaconsole_call_remote_api($connection_name, 'netflow_get_stats', "$start_date|$end_date|".base64_encode(json_encode($filter))."|$aggregate|$max|$absolute|".(int) $address_resolution);
        return json_decode($data, true);
    }

    // Get the command to call nfdump.
    $command = netflow_get_command($filter);

    // Execute nfdump.
    $command .= " -o csv -q -n $max -s $aggregate/bytes -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
    exec($command, $string);

    if (! is_array($string)) {
        return [];
    }

    // Remove the first line.
    $string[0] = '';

    $i = 0;
    $values = [];
    $interval_length = ($end_date - $start_date);
    foreach ($string as $line) {
        if ($line == '') {
            continue;
        }

        $val = explode(',', $line);

        $values[$i]['date'] = $val[0];
        $values[$i]['time'] = $val[1];

        // Create field to sort array.
        $datetime = $val[0];
        $end_date = strtotime($datetime);
        $values[$i]['datetime'] = $end_date;
        // Address resolution start.
        if ($address_resolution && ($aggregate == 'srcip' || $aggregate == 'dstip')) {
            global $hostnames;

            if (!isset($hostnames[$val[4]])) {
                $hostname = gethostbyaddr($val[4]);
                if ($hostname !== false) {
                    $hostnames[$val[4]] = $hostname;
                    $val[4] = $hostname;
                }
            } else {
                $val[4] = $hostnames[$val[4]];
            }
        }

        // Address resolution end.
        $values[$i]['agg'] = $val[4];

        if (! isset($val[9])) {
            return [];
        }

        $values[$i]['data'] = $val[9];
        if (!$absolute) {
            $values[$i]['data'] = ($values[$i]['data'] / $interval_length);
        }

        $i++;
    }

    sort_netflow_data($values);

    return $values;
}


/**
 * Returns a traffic summary for the given period in an array.
 *
 * @param string $start_date      Period start date.
 * @param string $end_date        Period end date.
 * @param string $filter          Netflow filter.
 * @param string $connection_name Node name when data is get in meta.
 *
 * @return array With netflow summary data.
 */
function netflow_get_summary($start_date, $end_date, $filter, $connection_name='')
{
    global $nfdump_date_format;
    global $config;

    // Requesting remote data.
    if (defined('METACONSOLE') && $connection_name != '') {
        $data = metaconsole_call_remote_api($connection_name, 'netflow_get_summary', "$start_date|$end_date|".base64_encode(json_encode($filter)));
        return json_decode($data, true);
    }

    // Get the command to call nfdump.
    $command = netflow_get_command($filter);

    // Execute nfdump.
    $command .= ' -o csv -n 1 -s srcip/bytes -t '.date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
    exec($command, $string);

    if (! is_array($string) || ! isset($string[5])) {
        return [];
    }

    // Read the summary.
    $summary = explode(',', $string[5]);
    if (! isset($summary[5])) {
        return [];
    }

    $values['totalflows'] = $summary[0];
    $values['totalbytes'] = $summary[1];
    $values['totalpackets'] = $summary[2];
    $values['avgbps'] = $summary[3];
    $values['avgpps'] = $summary[4];
    $values['avgbpp'] = $summary[5];

    return $values;
}


/**
 * Returns a traffic record for the given period in an array.
 *
 * @param string  $start_date         Period start date.
 * @param string  $end_date           Period end date.
 * @param string  $filter             Netflow filter.
 * @param integer $max                Maximum number of elements.
 * @param boolean $address_resolution True to resolve ips to hostnames.
 *
 * @return array With netflow record data.
 */
function netflow_get_record(
    $start_date,
    $end_date,
    $filter,
    $max,
    $address_resolution=false
) {
    global $nfdump_date_format;
    global $config;

    // Get the command to call nfdump.
    $command = netflow_get_command($filter);

    // Execute nfdump.
    $command .= " -q -o csv -n $max -s record/bytes -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
    exec($command, $result);

    if (! is_array($result)) {
        return [];
    }

    $values = [];
    foreach ($result as $key => $line) {
        $data = [];

        $items = explode(',', $line);

        $data['time_start'] = $items[0];
        $data['time_end'] = $items[1];
        $data['duration'] = ($items[2] / 1000);
        $data['source_address'] = $items[3];
        $data['destination_address'] = $items[4];
        $data['source_port'] = $items[5];
        $data['destination_port'] = $items[6];
        $data['protocol'] = $items[7];
        $data['data'] = $items[12];

        $values[] = $data;
    }

    // Address resolution start.
    if ($address_resolution) {
        global $hostnames;

        for ($i = 0; $i < count($values); $i++) {
            if (!isset($hostnames[$values[$i]['source_address']])) {
                $hostname = gethostbyaddr($values[$i]['source_address']);
                if ($hostname !== false) {
                    $hostnames[$values[$i]['source_address']] = $hostname;
                    $values[$i]['source_address'] = $hostname;
                }
            } else {
                $values[$i]['source_address'] = $hostnames[$values[$i]['source_address']];
            }

            if (!isset($hostnames[$values[$i]['destination_address']])) {
                $hostname = gethostbyaddr($values[$i]['destination_address']);
                if ($hostname !== false) {
                    $hostnames[$values[$i]['destination_address']] = $hostname;
                    $values[$i]['destination_address'] = $hostname;
                }
            } else {
                $values[$i]['destination_address'] = $hostnames[$values[$i]['destination_address']];
            }
        }
    }

    // Address resolution end.
    return $values;
}


/**
 * Returns the command needed to run nfdump for the given filter.
 *
 * @param array $filter Netflow filter.
 *
 * @return string Command to run.
 */
function netflow_get_command($filter)
{
    global $config;

    // Build command.
    $command = io_safe_output($config['netflow_nfdump']).' -N';

    // Netflow data path.
    if (isset($config['netflow_path']) && $config['netflow_path'] != '') {
        $command .= ' -R. -M '.$config['netflow_path'];
    }

    // Filter options.
    $command .= netflow_get_filter_arguments($filter);

    return $command;
}


/**
 * Returns the nfdump command line arguments that match the given filter.
 *
 * @param array $filter Netflow filter.
 *
 * @return string Command line argument string.
 */
function netflow_get_filter_arguments($filter)
{
    // Advanced filter.
    $filter_args = '';
    if ($filter['advanced_filter'] != '') {
        $filter_args = preg_replace('/["\r\n]/', '', io_safe_output($filter['advanced_filter']));
        return ' "('.$filter_args.')"';
    }

    if ($filter['router_ip'] != '') {
        $filter_args .= ' "(router ip '.$filter['router_ip'].')';
    }

    // Normal filter.
    if ($filter['ip_dst'] != '') {
        $filter_args .= ' "(';
        $val_ipdst = explode(',', io_safe_output($filter['ip_dst']));
        for ($i = 0; $i < count($val_ipdst); $i++) {
            if ($i > 0) {
                $filter_args .= ' or ';
            }

            if (netflow_is_net($val_ipdst[$i]) == 0) {
                $filter_args .= 'dst ip '.$val_ipdst[$i];
            } else {
                $filter_args .= 'dst net '.$val_ipdst[$i];
            }
        }

        $filter_args .= ')';
    }

    if ($filter['ip_src'] != '') {
        if ($filter_args == '') {
            $filter_args .= ' "(';
        } else {
            $filter_args .= ' and (';
        }

        $val_ipsrc = explode(',', io_safe_output($filter['ip_src']));
        for ($i = 0; $i < count($val_ipsrc); $i++) {
            if ($i > 0) {
                $filter_args .= ' or ';
            }

            if (netflow_is_net($val_ipsrc[$i]) == 0) {
                $filter_args .= 'src ip '.$val_ipsrc[$i];
            } else {
                $filter_args .= 'src net '.$val_ipsrc[$i];
            }
        }

        $filter_args .= ')';
    }

    if ($filter['dst_port'] != '') {
        if ($filter_args == '') {
            $filter_args .= ' "(';
        } else {
            $filter_args .= ' and (';
        }

        $val_dstport = explode(',', io_safe_output($filter['dst_port']));
        for ($i = 0; $i < count($val_dstport); $i++) {
            if ($i > 0) {
                $filter_args .= ' or ';
            }

            $filter_args .= 'dst port '.$val_dstport[$i];
        }

        $filter_args .= ')';
    }

    if ($filter['src_port'] != '') {
        if ($filter_args == '') {
            $filter_args .= ' "(';
        } else {
            $filter_args .= ' and (';
        }

        $val_srcport = explode(',', io_safe_output($filter['src_port']));
        for ($i = 0; $i < count($val_srcport); $i++) {
            if ($i > 0) {
                $filter_args .= ' or ';
            }

            $filter_args .= 'src port '.$val_srcport[$i];
        }

        $filter_args .= ')';
    }

    if (isset($filter['proto']) && $filter['proto'] != '') {
        if ($filter_args == '') {
            $filter_args .= ' "(';
        } else {
            $filter_args .= ' and (';
        }

        $val_proto = explode(',', io_safe_output($filter['proto']));
        for ($i = 0; $i < count($val_proto); $i++) {
            if ($i > 0) {
                $filter_args .= ' or ';
            }

            $filter_args .= 'proto '.$val_proto[$i];
        }

        $filter_args .= ')';
    }

    if ($filter_args != '') {
        $filter_args .= '"';
    }

    return $filter_args;
}


/**
 * Get the types of netflow charts.
 *
 * @return array of types.
 */
function netflow_get_chart_types()
{
    return [
        'netflow_area'          => __('Area graph'),
        'netflow_pie_summatory' => __('Pie graph and Summary table'),
        'netflow_statistics'    => __('Statistics table'),
        'netflow_data'          => __('Data table'),
        'netflow_mesh'          => __('Circular mesh'),
        'netflow_host_treemap'  => __('Host detailed traffic'),
    ];
}


/**
 * Draw a netflow report item.
 *
 * @param string  $start_date         Period start date.
 * @param string  $end_date           Period end date.
 * @param mixed   $interval_length    Resolution points or hourly or daily.
 * @param string  $type               Chart type.
 * @param array   $filter             Netflow filter.
 * @param integer $max_aggregates     Maximum number of aggregates.
 * @param string  $connection_name    Node name when data is get in meta.
 * @param string  $output             Output format. Only HTML, PDF and XML
 *      are supported.
 * @param boolean $address_resolution True to resolve ips to hostnames.
 *
 * @return string The netflow report in the appropriate format.
 */
function netflow_draw_item(
    $start_date,
    $end_date,
    $interval_length,
    $type,
    $filter,
    $max_aggregates,
    $connection_name='',
    $output='HTML',
    $address_resolution=false
) {
    $aggregate = $filter['aggregate'];
    $interval = ($end_date - $start_date);
    if (defined('METACONSOLE')) {
        $width = 950;
    } else {
        $width = 850;
    }

    $height = 320;

    // Process item.
    switch ($type) {
        case '0':
        case 'netflow_area':
            $data = netflow_get_data(
                $start_date,
                $end_date,
                $interval_length,
                $filter,
                $aggregate,
                $max_aggregates,
                false,
                $connection_name,
                $address_resolution
            );
            if (empty($data)) {
                break;
            }

            if ($output == 'HTML' || $output == 'PDF') {
                $html .= graph_netflow_aggregate_area(
                    $data,
                    $interval,
                    $width,
                    $height,
                    ($output === 'HTML') ? 1 : 2,
                    ($output === 'HTML'),
                    $end_date
                );
                return $html;
            } else if ($output == 'XML') {
                $xml .= '<aggregate>'.$aggregate."</aggregate>\n";
                $xml .= '<resolution>'.$interval_length."</resolution>\n";
                $xml .= netflow_aggregate_area_xml($data);
                return $xml;
            }
        break;

        case '2':
        case 'netflow_data':
            $data = netflow_get_data(
                $start_date,
                $end_date,
                $interval_length,
                $filter,
                $aggregate,
                $max_aggregates,
                true,
                $connection_name,
                $address_resolution
            );
            if (empty($data)) {
                break;
            }

            if ($output == 'HTML' || $output == 'PDF') {
                $html .= "<div style='width: 100%; overflow: auto;'>";
                $html .= netflow_data_table($data, $start_date, $end_date, $aggregate);
                $html .= '</div>';

                return $html;
            } else if ($output == 'XML') {
                $xml .= '<aggregate>'.$aggregate."</aggregate>\n";
                $xml .= '<resolution>'.$interval_length."</resolution>\n";
                // Same as netflow_aggregate_area_xml.
                $xml .= netflow_aggregate_area_xml($data);
                return $xml;
            }
        break;

        case '3':
        case 'netflow_statistics':
            $data = netflow_get_stats(
                $start_date,
                $end_date,
                $filter,
                $aggregate,
                $max_aggregates,
                true,
                $connection_name,
                $address_resolution
            );
            if (empty($data)) {
                break;
            }

            if ($output == 'HTML' || $output == 'PDF') {
                $html = netflow_stat_table($data, $start_date, $end_date, $aggregate);
                return $html;
            } else if ($output == 'XML') {
                return netflow_stat_xml($data);
            }
        break;

        case '4':
        case 'netflow_summary':
            $data_summary = netflow_get_summary(
                $start_date,
                $end_date,
                $filter,
                $connection_name
            );
            if (empty($data_summary)) {
                break;
            }

            if ($output == 'HTML' || $output == 'PDF') {
                return netflow_summary_table($data_summary);
            } else if ($output == 'XML') {
                return netflow_summary_xml($data_summary);
            }
        break;

        case '1':
        case 'netflow_pie':
            $data_pie = netflow_get_stats(
                $start_date,
                $end_date,
                $filter,
                $aggregate,
                $max_aggregates,
                true,
                $connection_name,
                $address_resolution
            );
            if (empty($data_pie)) {
                break;
            }

            if ($output == 'HTML') {
                return $html;
            } else if ($output == 'PDF') {
                return $html;
            } else if ($output == 'XML') {
                return $xml;
            }
        break;

        case 'netflow_pie_summatory':
            $data_summary = netflow_get_summary(
                $start_date,
                $end_date,
                $filter,
                $connection_name
            );
            if (empty($data_summary)) {
                break;
            }

            $data_pie = netflow_get_stats(
                $start_date,
                $end_date,
                $filter,
                $aggregate,
                $max_aggregates,
                true,
                $connection_name,
                $address_resolution
            );
            if (empty($data_pie)) {
                break;
            }

            switch ($output) {
                case 'HTML':
                    $html = '<table>';
                    $html .= '<tr>';
                    $html .= '<td>';
                    $html .= netflow_summary_table($data_summary);
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= graph_netflow_aggregate_pie($data_pie, netflow_format_aggregate($aggregate));
                    $html .= '</td>';
                    $html .= '</tr>';
                    $html .= '</table>';
                return $html;

                case 'XML':
                return netflow_summary_xml($data_summary);

                default:
                    // Nothing to do.
                break;
            }
        break;

        case 'netflow_mesh':
            $netflow_data = netflow_get_record(
                $start_date,
                $end_date,
                $filter,
                $max_aggregates,
                $address_resolution
            );

            switch ($aggregate) {
                case 'srcport':
                case 'dstport':
                    $source_type = 'source_port';
                    $destination_type = 'destination_port';
                break;

                default:
                case 'dstip':
                case 'srcip':
                    $source_type = 'source_address';
                    $destination_type = 'destination_address';
                break;
            }

            $data = [];
            $data['elements'] = [];
            $data['matrix'] = [];
            foreach ($netflow_data as $record) {
                if (!in_array($record[$source_type], $data['elements'])) {
                    $data['elements'][] = $record[$source_type];
                    $data['matrix'][] = [];
                }

                if (!in_array($record[$destination_type], $data['elements'])) {
                    $data['elements'][] = $record[$destination_type];
                    $data['matrix'][] = [];
                }
            }

            for ($i = 0; $i < count($data['matrix']); $i++) {
                $data['matrix'][$i] = array_fill(0, count($data['matrix']), 0);
            }

            foreach ($netflow_data as $record) {
                $source_key = array_search($record[$source_type], $data['elements']);
                $destination_key = array_search($record[$destination_type], $data['elements']);
                if ($source_key !== false && $destination_key !== false) {
                    $data['matrix'][$source_key][$destination_key] += $record['data'];
                }
            }

            $html = '<div style="text-align:center;">';
            $html .= graph_netflow_circular_mesh($data, 700);
            $html .= '</div>';
        return $html;

        case 'netflow_host_treemap':
            $netflow_data = netflow_get_record($start_date, $end_date, $filter, $max_aggregates, $address_resolution);

            switch ($aggregate) {
                case 'srcip':
                case 'srcport':
                    $address_type = 'source_address';
                    $port_type = 'source_port';
                    $type = __('Sent');
                break;

                default:
                case 'dstip':
                case 'dstport':
                    $address_type = 'destination_address';
                    $port_type = 'destination_port';
                    $type = __('Received');
                break;
            }

            $data_aux = [];
            foreach ($netflow_data as $record) {
                $address = $record[$address_type];
                $port = $record[$port_type];

                if (!isset($data_aux[$address])) {
                    $data_aux[$address] = [];
                }

                if (!isset($data_aux[$address][$port])) {
                    $data_aux[$address][$port] = 0;
                }

                $data_aux[$address][$port] += $record['data'];
            }

            $id = -1;

            $data = [];

            if (! empty($netflow_data)) {
                $data['name'] = __('Host detailed traffic').': '.$type;
                $data['children'] = [];

                foreach ($data_aux as $address => $ports) {
                    $children = [];
                    $children['id'] = $id++;
                    $children['name'] = $address;
                    $children['children'] = [];
                    foreach ($ports as $port => $value) {
                        $children_data = [];
                        $children_data['id'] = $id++;
                        $children_data['name'] = $port;
                        $children_data['value'] = $value;
                        $children_data['tooltip_content'] = $port.': <b>'.network_format_bytes($value).'</b>';
                        $children['children'][] = $children_data;
                    }

                    $data['children'][] = $children;
                }
            }
        return graph_netflow_host_traffic($data, 'auto', 400);

        default:
            // Nothing to do.
        break;
    }

    if ($output == 'HTML' || $output == 'PDF') {
        return graph_nodata_image(300, 110, 'data');
    }
}


/**
 * Render an aggregated area chart as an XML.
 *
 * @param array $data Netflow data.
 *
 * @return void XML is echoed.
 */
function netflow_aggregate_area_xml($data)
{
    // Print source information.
    if (isset($data['sources'])) {
        echo "<aggregates>\n";
        foreach ($data['sources'] as $source => $discard) {
            echo '<aggregate>'.$source."</aggregate>\n";
        }

        echo "</aggregates>\n";

        // Print flow information.
        echo "<flows>\n";
        foreach ($data['data'] as $timestamp => $flow) {
            echo "<flow>\n";
            echo '  <timestamp>'.$timestamp."</timestamp>\n";
            echo "  <aggregates>\n";
            foreach ($flow as $source => $data) {
                echo "    <aggregate>$source</aggregate>\n";
                echo '    <data>'.$data."</data>\n";
            }

            echo "  </aggregates>\n";
            echo "</flow>\n";
        }

        echo "</flows>\n";
    } else {
        echo "<flows>\n";
        foreach ($data as $timestamp => $flow) {
            echo "<flow>\n";
            echo '  <timestamp>'.$timestamp."</timestamp>\n";
            echo '  <data>'.$flow['data']."</data>\n";
            echo "</flow>\n";
        }

        echo "</flows>\n";
    }
}


/**
 * Render a pie chart as an XML.
 *
 * @param array $data Netflow data.
 *
 * @return void XML is echoed.
 */
function netflow_aggregate_pie_xml($data)
{
    // Calculate total.
    $total = 0;
    foreach ($data as $flow) {
        $total += $flow['data'];
    }

    if ($total == 0) {
        return;
    }

    // Print percentages.
    echo "<pie>\n";
    foreach ($data as $flow) {
        echo '<aggregate>'.$flow['agg']."</aggregate>\n";
        echo '<data>'.format_numeric((100 * $flow['data'] / $total), 2)."%</data>\n";
    }

    echo "</pie>\n";
}


/**
 * Render a stats table as an XML.
 *
 * @param array $data Netflow data.
 *
 * @return string Wiht XML data.
 */
function netflow_stat_xml($data)
{
    // Print stats.
    $xml .= "<stats>\n";
    foreach ($data as $flow) {
        $xml .= '<aggregate>'.$flow['agg']."</aggregate>\n";
        $xml .= '<data>'.$flow['data']."</data>\n";
    }

    $xml .= "</stats>\n";

    return $xml;
}


/**
 * Render a summary table as an XML.
 *
 * @param array $data Netflow data.
 *
 * @return string Wiht XML data.
 */
function netflow_summary_xml($data)
{
    // Print summary
    $xml = "<summary>\n";
    $xml .= '  <total_flows>'.$data['totalflows']."</total_flows>\n";
    $xml .= '  <total_bytes>'.$data['totalbytes']."</total_bytes>\n";
    $xml .= '  <total_packets>'.$data['totalbytes']."</total_packets>\n";
    $xml .= '  <average_bps>'.$data['avgbps']."</average_bps>\n";
    $xml .= '  <average_pps>'.$data['avgpps']."</average_pps>\n";
    $xml .= '  <average_bpp>'.$data['avgpps']."</average_bpp>\n";
    $xml .= "</summary>\n";

    return $xml;
}


/**
 * Return a string describing the given aggregate.
 *
 * @param string $aggregate Netflow aggregate.
 *
 * @return string With formatted aggregate.
 */
function netflow_format_aggregate($aggregate)
{
    switch ($aggregate) {
        case 'dstport':
        return __('Dst port');

        case 'dstip':
        return __('Dst IP');

        case 'srcip':
        return __('Src IP');

        case 'srcport':
        return __('Src port');

        default:
        return '';
    }
}


/**
 * Check the nfdump binary for compatibility.
 *
 * @param string $nfdump_binary Nfdump binary full path.
 *
 * @return integer 1 if the binary does not exist or is not executable, 2 if a
 *         version older than 1.6.8 is installed or the version cannot be
 *         determined, 0 otherwise.
 */
function netflow_check_nfdump_binary($nfdump_binary)
{
    // Check that the binary exists and is executable.
    if (! is_executable($nfdump_binary)) {
        return 1;
    }

    // Check at least version 1.6.8.
    $output = '';
    $rc = -1;
    exec($nfdump_binary.' -V', $output, $rc);
    if ($rc != 0) {
        return 2;
    }

    $matches = [];
    foreach ($output as $line) {
        if (preg_match('/Version:[^\d]+(\d+)\.(\d+)\.(\d+)/', $line, $matches) === 1) {
            if ($matches[1] < 1) {
                return 2;
            }

            if ($matches[2] < 6) {
                return 2;
            }

            if ($matches[3] < 8) {
                return 2;
            }

            return 0;
        }
    }

    return 2;
}


/**
 * Get the netflow datas to build a netflow explorer data structure.
 *
 * @param integer $max        Number of result displayed.
 * @param string  $top_action Action to do (listeners,talkers,tcp or udp).
 * @param integer $start_date In utimestamp.
 * @param integer $end_date   In utimestamp.
 * @param string  $filter     Ip to filter.
 * @param string  $order      Select one of bytes,pkts,flow.
 *
 * @return array With data (host, sum_bytes, sum_pkts and sum_flows).
 */
function netflow_get_top_summary(
    $max,
    $top_action,
    $start_date,
    $end_date,
    $filter='',
    $order='bytes'
) {
    global $nfdump_date_format;
    $netflow_filter = [];
    $sort = '';
    switch ($top_action) {
        case 'listeners':
            if (empty(!$filter)) {
                $netflow_filter['ip_src'] = $filter;
            }

            $sort = 'dstip';
        break;

        case 'talkers':
            if (empty(!$filter)) {
                $netflow_filter['ip_dst'] = $filter;
            }

            $sort = 'srcip';
        break;

        case 'tcp':
        case 'udp':
            $netflow_filter['proto'] = $top_action;
            $sort = 'port';
            if (empty(!$filter)) {
                $netflow_filter['advanced_filter'] = sprintf(
                    '((dst port %s) or (src port %s)) and (proto %s)',
                    $filter,
                    $filter,
                    $top_action
                );
                // Display ips when filter is set in port.
                $sort = 'ip';
            }
        break;

        default:
        return [];
    }

    $command = netflow_get_command($netflow_filter);

    // Execute nfdump.
    $order_text = '';
    switch ($order) {
        case 'flows':
            $order_text = 'flows';
        break;

        case 'pkts':
            $order_text = 'packets';
        break;

        case 'bytes':
        default:
            $order_text = 'bytes';
        break;
    }

    $command .= " -q -o csv -n $max -s $sort/$order_text -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
    exec($command, $result);

    if (! is_array($result)) {
        return [];
    }

    // Remove first line (avoiding slow array_shift).
    $result = array_reverse($result);
    array_pop($result);
    $result = array_reverse($result);

    $top_info = [];
    foreach ($result as $line) {
        if (empty($line)) {
            continue;
        }

        $data = explode(',', $line);
        if (!isset($data[9])) {
            continue;
        }

        $top_info[(string) $data[4]] = [
            'host'      => $data[4],
            'sum_bytes' => $data[9],
            'sum_pkts'  => $data[7],
            'sum_flows' => $data[5],
            'pct_bytes' => $data[10],
            'pct_pkts'  => $data[8],
            'pct_flows' => $data[6],
        ];
    }

    return $top_info;
}


/**
 * Check the netflow version and print an error message if there is not correct.
 *
 * @return boolean True if version check is correct.
 */
function netflow_print_check_version_error()
{
    global $config;

    switch (netflow_check_nfdump_binary($config['netflow_nfdump'])) {
        case 0:
        return true;

        case 1:
            ui_print_error_message(
                __('nfdump binary (%s) not found!', $config['netflow_nfdump'])
            );
        return false;

        case 2:
        default:
            ui_print_error_message(
                __('Make sure nfdump version 1.6.8 or newer is installed!')
            );
        return false;
    }
}


/**
 * Returns the array for netflow resolution select.
 *
 * @return array With all values.
 */
function netflow_resolution_select_params()
{
    return [
        NETFLOW_RES_LOWD   => __('Low'),
        NETFLOW_RES_MEDD   => __('Medium'),
        NETFLOW_RES_HID    => __('High'),
        NETFLOW_RES_ULTRAD => __('Ultra High'),
        NETFLOW_RES_HOURLY => __('Hourly'),
        NETFLOW_RES_DAILY  => __('Daily'),
    ];
}


/**
 * Get the resolution name.
 *
 * @param mixed $value Type.
 *
 * @return string Translated name. Unknown for unrecognized resolution names.
 */
function netflow_get_resolution_name($value)
{
    $resolutions = netflow_resolution_select_params();
    return (isset($resolutions[$value])) ? $resolutions[$value] : __('Unknown');
}


/**
 * Report formatted subtitle.
 *
 * @param string $aggreagate Aggregate by param.
 * @param string $resolution Netfow live view resolution.
 * @param string $type       Type of view.
 *
 * @return string HTML with formatted subtitle.
 */
function netflow_generate_subtitle_report($aggregate, $resolution, $type)
{
    $subt = __(
        'Agregate by %s',
        netflow_format_aggregate($aggregate)
    );

    // Display the resolution only in required reports.
    if (in_array($type, ['netflow_area', 'netflow_data']) === true) {
        $subt .= ' - ';
        $subt .= __(
            'Resolution %s',
            netflow_get_resolution_name($resolution)
        );
    }

    return $subt;
}
