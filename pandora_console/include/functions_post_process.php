<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Post Process
 */

// Load global vars
global $config;


function post_process_get_custom_values()
{
    global $config;

    if (!isset($config['post_process_custom_values'])) {
        $return = [];
    } else {
        $return = json_decode(
            io_safe_output($config['post_process_custom_values']),
            true
        );
    }

    if (empty($return)) {
        $return = [];
    }

    return $return;
}


function post_process_add_custom_value($text, $value)
{
    global $config;

    $value = (string) $value;

    $post_process_custom_values = post_process_get_custom_values();

    $post_process_custom_values[$value] = $text;

    $new_conf = json_encode($post_process_custom_values);
    $return = config_update_value(
        'post_process_custom_values',
        $new_conf
    );

    if ($return) {
        $config['post_process_custom_values'] = $new_conf;

        return true;
    } else {
        return false;
    }
}


function post_process_delete_custom_value($value)
{
    global $config;

    $post_process_custom_values = post_process_get_custom_values();

    unset($post_process_custom_values[$value]);

    $new_conf = json_encode($post_process_custom_values);
    $return = config_update_value(
        'post_process_custom_values',
        $new_conf
    );

    if ($return) {
        $config['post_process_custom_values'] = $new_conf;

        return true;
    } else {
        return false;
    }
}
