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
 * @subpackage Forecast
 */


/**
 * Create a prediction based on module data with least square method (linear regression)
 *
 * @param int Module id.
 * @param int Period of the module data.
 * @param int Period of the prediction or false to use it in prediction_date function (see below).
 * @param int Maximun value using this function for prediction_date.
 * @param int Minimun value using this function for prediction_date.
 * @param bool Result data for CSV file exportation.
 *
 * @return array Void array or prediction of the module data.
 */
function forecast_projection_graph(
    $module_id,
    $period=SECONDS_2MONTHS,
    $prediction_period=false,
    $max_value=false,
    $min_value=false,
    $csv=false,
    $server_name=''
) {
    global $config;

    $max_exec_time = ini_get('max_execution_time');

    if ($max_exec_time !== false) {
        $max_exec_time = (int) $max_exec_time;
    }

    $begin_time = time();

    $params = [
        'agent_module_id' => $module_id,
        'period'          => $period,
        'return_data'     => 1,
        'projection'      => true,
    ];

    if (is_metaconsole()) {
        $id_meta = metaconsole_get_id_server($server_name);
        $server  = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $module_data = grafico_modulo_sparse($params);

    if (is_metaconsole()) {
        metaconsole_restore_db();
    }

    if (empty($module_data)) {
        return [];
    }
    // Prevents bad behaviour over image error
    else if (!is_array($module_data) and preg_match('/^<img(.)*$/', $module_data)) {
        return;
    }

    // Data initialization
    $sum_obs        = 0;
    $sum_xi         = 0;
    $sum_yi         = 0;
    $sum_xi_yi      = 0;
    $sum_xi2        = 0;
    $sum_yi2        = 0;
    $sum_diff_dates = 0;
    $last_timestamp = get_system_time();
    $agent_interval = SECONDS_5MINUTES;
    $cont           = 1;
    $data           = [];
    // $table->data = array();
    // Creates data for calculation
    if (is_array($module_data) || is_object($module_data)) {
        foreach ($module_data['sum1']['data'] as $key => $row) {
            if ($row[0] == '') {
                continue;
            }

            $row[0] = ($row[0] / 1000);

            $data[0] = '';
            $data[1] = $cont;
            $data[2] = date($config['date_format'], $row[0]);
            $data[3] = $row[0];
            $data[4] = $row[1];
            $data[5] = ($row[0] * $row[1]);
            $data[6] = ($row[0] * $row[0]);
            $data[7] = ($row[1] * $row[1]);
            if ($cont == 1) {
                $data[8] = 0;
            } else {
                $data[8] = ($row[0] - $last_timestamp);
            }

            $sum_obs        = ($sum_obs + $cont);
            $sum_xi         = ($sum_xi + $row[0]);
            $sum_yi         = ($sum_yi + $row[1]);
            $sum_xi_yi      = ($sum_xi_yi + $data[5]);
            $sum_xi2        = ($sum_xi2 + $data[6]);
            $sum_yi2        = ($sum_yi2 + $data[7]);
            $sum_diff_dates = ($sum_diff_dates + $data[8]);
            $last_timestamp = $row[0];
            $cont++;
        }
    }

    $cont--;

    // Calculation over data above:
    // 1. Calculation of linear correlation coefficient...
    // 1.1 Average for X: Sum(Xi)/Obs
    // 1.2 Average for Y: Sum(Yi)/Obs
    // 2. Covariance between vars
    // 3.1  Standard deviation for X: sqrt((Sum(Xi²)/Obs) - (avg X)²)
    // 3.2 Standard deviation for Y: sqrt((Sum(Yi²)/Obs) - (avg Y)²)
    // Linear correlation coefficient:
    // Agent interval could be zero, 300 is the predefined
    if ($sum_obs == 0) {
        $agent_interval = SECONDS_5MINUTES;
    } else {
        $agent_interval = ($sum_diff_dates / $sum_obs);
    }

    // Could be a inverse correlation coefficient
    // if $linear_coef < 0.0
    // if $linear_coef >= -1.0 and $linear_coef <= -0.8999
    // Function variables have an inverse linear relathionship!
    // else
    // Function variables don't have an inverse linear relathionship!
    // Could be a direct correlation coefficient
    // else
    // if ($linear_coef >= 0.8999 and $linear_coef <= 1.0) {
    // Function variables have a direct linear relathionship!
    // else
    // Function variables don't have a direct linear relathionship!
    // 2. Calculation of linear regresion...
    $b_num = (($cont * $sum_xi_yi) - ($sum_xi * $sum_yi));
    $b_den = (($cont * $sum_xi2) - ($sum_xi * $sum_xi));
    if ($b_den == 0) {
        return;
    }

    $b = ($b_num / $b_den);

    $a_num = (($sum_yi) - ($b * $sum_xi));

    if ($cont != 0) {
        $a = ($a_num / $cont);
    } else {
        $a = 0;
    }

    // Data inicialization
    $output_data = [];
    if ($prediction_period != false) {
        $limit_timestamp = ($last_timestamp + $prediction_period);
    }

    $current_ts = $last_timestamp;
    $in_range = true;
    $time_format_2 = '';

    $temp_range = $period;
    if ($period < $prediction_period) {
        $temp_range = $prediction_period;
    }

    if ($temp_range <= SECONDS_6HOURS) {
        $time_format = 'H:i:s';
    } else if ($temp_range < SECONDS_1DAY) {
        $time_format = 'H:i';
    } else if ($temp_range < SECONDS_15DAYS) {
        $time_format = 'M d';
        $time_format_2 = 'H\h';
    } else if ($temp_range <= SECONDS_1MONTH) {
        $time_format = 'M d';
        $time_format_2 = 'H\h';
    } else {
        $time_format = 'M d';
    }

    // Aplying linear regression to module data in order to do the prediction
    $idx = 0;
    // Create data in graph format like
    while ($in_range) {
        $now = time();

        // Check that exec time is not greater than half max exec server time
        if ($max_exec_time != false) {
            if (($begin_time + ($max_exec_time / 2)) < $now) {
                return false;
            }
        }

        $timestamp_f = ($current_ts * 1000);

        if ($csv) {
            $output_data[$idx]['date'] = $current_ts;
            $output_data[$idx]['data'] = ($a + ($b * $current_ts));
        } else {
            $output_data[$idx][0] = $timestamp_f;
            $output_data[$idx][1] = ($a + ($b * $current_ts));
        }

        // Using this function for prediction_date
        if ($prediction_period == false) {
            // These statements stop the prediction when interval is greater than 2 years
            if (($current_ts - $last_timestamp) >= 94608000
                || $max_value == $min_value
            ) {
                return false;
            }

            // Found it
            if (($max_value >= $output_data[$idx][1])
                && ($min_value <= $output_data[$idx][0])
            ) {
                return ($current_ts + ($sum_diff_dates * $agent_interval));
            }
        } else if ($current_ts > $limit_timestamp) {
            $in_range = false;
        }

        $current_ts = ($current_ts + $agent_interval);
        $idx++;
    }

    return $output_data;
}


/**
 * Return a date when the date interval is reached
 *
 * @param int Module id.
 * @param int Given data period to make the prediction
 * @param int Max value in the interval.
 * @param int Min value in the interval.
 *
 * @return mixed timestamp with the prediction date or false
 */
function forecast_prediction_date(
    $module_id,
    $period=SECONDS_2MONTHS,
    $max_value=0,
    $min_value=0,
    $server_name=''
) {
    // Checks interval
    if ($min_value > $max_value) {
        return false;
    }

    return forecast_projection_graph($module_id, $period, false, $max_value, $min_value, false, $server_name);
}
