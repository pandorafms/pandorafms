<?php
/**
 * Pandora FMS- https://pandorafms.com.
 * ==================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the  GNU Lesser General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

global $config;

if ((bool) is_ajax() === true) {
    include_once $config['homedir'].'/include/class/Prd.class.php';

    $getResource = (bool) get_parameter('getResource', false);
    $exportPrd = (bool) get_parameter('exportPrd', false);

    $prd = new Prd();

    if ($getResource === true) {
        $type = (string) get_parameter('type', '');
        $result = false;

        $check = $prd->getOnePrdData($type);
        if (empty($check) === false) {
            switch ($type) {
                case 'visual_console':
                    $result = html_print_label_input_block(
                        __('Visual console'),
                        io_safe_output(
                            html_print_select_from_sql(
                                'SELECT id, name FROM tlayout',
                                'select_value',
                                '',
                                '',
                                '',
                                0,
                                true,
                                false,
                                true,
                                false,
                                false,
                                false,
                                GENERIC_SIZE_TEXT,
                                'w40p',
                            ),
                        )
                    );
                break;

                default:
                    // TODO.
                break;
            }
        }

        echo $result;
        return;
    }

    if ($exportPrd === true) {
        $type = (string) get_parameter('type', '');
        $value = (int) get_parameter('value', 0);
        $name = (string) get_parameter('name', '');

        $prd->exportPrd($type, $value, $name);
    }
}
