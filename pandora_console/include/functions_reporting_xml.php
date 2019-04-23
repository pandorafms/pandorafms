<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2017 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
require_once __DIR__.'/functions_modules.php';
require_once __DIR__.'/functions_events.php';
require_once __DIR__.'/functions_groups.php';
require_once __DIR__.'/functions_netflow.php';
enterprise_include_once('include/functions_metaconsole.php');


function reporting_xml_get_report($report, $filename, $return=false)
{
    // ------- Removed the unused fields ------------------------------------
    unset($report['header']);
    unset($report['first_page']);
    unset($report['footer']);
    unset($report['custom_font']);
    unset($report['id_template']);
    unset($report['id_group_edit']);
    unset($report['metaconsole']);
    unset($report['private']);
    unset($report['custom_logo']);
    // ----------------------------------------------------------------------
    // change agent name.
    if (count($report['contents']) > 0) {
        for ($i = 0; $i < count($report['contents']); $i++) {
            $aux = explode('-', $report['contents'][$i]['subtitle']);
            $report['contents'][$i]['subtitle'] = db_get_value('alias', 'tagente', 'nombre', $report['contents'][$i]['agent_name']).' -'.$aux[1];
        }
    }

    $xml = null;
    $xml = array2XML($report, 'report', $xml);
    $xml = preg_replace('/(<[^>]+>)(<[^>]+>)(<[^>]+>)/', "$1\n$2\n$3", $xml);
    $xml = preg_replace('/(<[^>]+>)(<[^>]+>)/', "$1\n$2", $xml);

    // Return if is marked to return.
    if ($return) {
        return $xml;
    }

    // Download if marked to download.
    if ($filename !== false) {
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'.xml"');
    }

    // Clean the output buffer.
    ob_clean();

    echo $xml;
}
