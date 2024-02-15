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
    $deleteFile = (bool) get_parameter('deleteFile', false);

    $prd = new Prd();

    if ($getResource === true) {
        $type = (string) get_parameter('type', '');
        $result = false;

        $data = $prd->getOnePrdData($type);
        if (empty($data) === false) {
            $sql = sprintf(
                'SELECT %s FROM %s',
                reset($data['items']['value']).', '.reset($data['items']['show']),
                $data['items']['table']
            );
            $result = html_print_label_input_block(
                $data['label'],
                io_safe_output(
                    html_print_select_from_sql(
                        $sql,
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
                        'w90p',
                    ),
                ),
                [
                    'div_style' => 'display: flex; flex-direction: column; width: 50%',
                    'div_id'    => 'resource_type',
                ],
            );
        }

        echo $result;
        return;
    }

    if ($exportPrd === true) {
        $type = (string) get_parameter('type', '');
        $value = (int) get_parameter('value', 0);
        $name = (string) get_parameter('name', '');
        $filename = (string) get_parameter('filename', '');

        try {
            $data = $prd->exportPrd($type, $value, $name);
        } catch (\Exception $e) {
            $data = '';
        }

        $return = [];

        if (empty($data) === false) {
            $filename_download = date('YmdHis').'-'.$type.'-'.$name.'.prd';
            $file = $config['attachment_store'].'/'.$filename;

            $file_pointer = fopen($file, 'a');
            if ($file_pointer !== false) {
                $write = fwrite($file_pointer, $data);

                if ($write === false) {
                    $return['error'] = -2;
                    unlink($config['attachment_store'].'/'.$filename);
                } else {
                    $return['name'] = $filename;
                    $return['name_download'] = $filename_download;
                }

                fclose($file_pointer);
            } else {
                $return['error'] = -1;
            }
        }

        echo json_encode($return);

        return;
    }

    if ($deleteFile === true) {
        $filename = (string) get_parameter('filename', '');

        unlink($config['attachment_store'].'/'.$filename);
    }
}
