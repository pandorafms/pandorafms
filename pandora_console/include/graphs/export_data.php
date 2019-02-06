<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't use authentication methods.
// This file only uses data retrieved in a request.
require_once '../../include/config.php';
require_once '../../include/functions.php';

global $config;

$user_language = get_user_language($config['id_user']);
$l10n = null;
if (file_exists('../languages/'.$user_language.'.mo')) {
    $l10n = new gettext_reader(new CachedFileReader('../languages/'.$user_language.'.mo'));
    $l10n->load_tables();
}

// Get data
$type = (string) get_parameter('type', 'csv');

$data = (string) get_parameter('data');
$data = json_decode(io_safe_output($data), true);

$default_filename = 'data_exported - '.date($config['date_format']);
$filename = (string) get_parameter('filename', $default_filename);
$filename = io_safe_output($filename);

// Using first-class functions to avoid namespace conflicts
// Type: 'csv'
/*
    $data = array(
 *   'head' => array(<column>,<column>,...,<column>),
 *   'data' => array(
 *     array(<data>,<data>,...,<data>),
 *     array(<data>,<data>,...,<data>),
 *     ...,
 *     array(<data>,<data>,...,<data>),
 *   )
 * );
 */
$output_csv = function ($data, $filename) {
    $separator = (string) get_parameter('separator', ';');
    $excel_encoding = (bool) get_parameter('excel_encoding', false);

    // CSV Output
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');

    // BOM
    if (!$excel_encoding) {
        print pack('C*', 0xEF, 0xBB, 0xBF);
    }

    // Header
    // Item / data
    foreach ($data as $items) {
        if (!isset($items['head']) || !isset($items['data'])) {
                throw new Exception(__('An error occured exporting the data'));
        }

        $head_line = implode($separator, $items['head']);
        echo $head_line."\n";
        foreach ($items['data'] as $item) {
            $item = str_replace('--> '.__('Selected'), '', $item);
            $line = implode($separator, $item);

            if ($excel_encoding) {
                echo mb_convert_encoding($line, 'UTF-16LE', 'UTF-8')."\n";
            } else {
                echo $line."\n";
            }
        }
    }
};

// Type: 'json'
/*
    $data = array(
 *   array(
 *     'key' => <value>,
 *     'key' => <value>,
 *     ...,
 *     'key' => <value>
 *   ),
 *   array(
 *     'key' => <value>,
 *     'key' => <value>,
 *     ...,
 *     'key' => <value>
 *   ),
 *   ...,
 *   array(
 *     'key' => <value>,
 *     'key' => <value>,
 *     ...,
 *     'key' => <value>
 *   )
 * );
 */
$output_json = function ($data, $filename) {
    // JSON Output
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'.json"');

    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
    } else {
        $json = json_encode($data);
    }

    if ($json !== false) {
        echo $json;
    }
};

try {
    if (!$data) {
        throw new Exception(__('An error occured exporting the data'));
    }

    ob_end_clean();

    switch ($type) {
        case 'json':
            $output_json($data, $filename);
        break;

        case 'csv':
        default:
            $output_csv($data, $filename);
        break;
    }
} catch (Exception $e) {
    die($e->getMessage());
}

exit;
