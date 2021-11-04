<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
require_once '../../include/functions_html.php';

$graph = $_POST['graph'];
$graph_title = $_POST['graph_title'];
$refresh = $_POST['refresh'];

$os = strtolower(PHP_OS);
if (substr($os, 0, 3) === 'win') {
    $os = 'windows';
}

switch ($graph) {
    case 'cpu_load':
        if ($os == 'windows') {
            $data = exec('wmic cpu get loadpercentage|find /I /V "Loadpercentage" | findstr /r "[0-9]" ');
        } else {
            $data = exec("top -bn 2 -d 0.01 | grep '^Cpu' | tail -n 1 | awk '{ print $2+$4+$6 }'");
        }
    break;

    case 'pending_packets':
        $data = exec('ls /var/spool/pandora/data_in/*.data | wc -l');
    break;

    case 'disk_io_wait':
        if ($os == 'windows') {
            $data = exec("vmstat 1 3 | tail -1 | awk '{ print $16 }'");
        } else {
            $data = exec("vmstat 1 3 | tail -1 | awk '{ print $16 }'");
        }
    break;

    case 'mysql_load':
        if ($os == 'windows') {
            $data = exec('(FOR /F "skip=2 tokens=2 delims=\," %P IN (\'typeperf "\\Process(mysqld)\\% processor time" -sc 1\') DO @echo %P)|find /V /I "..."');
        } else {
            $data = exec("ps aux | grep mysqld | grep -v safe | grep -v grep | awk '{ print $3 }'");
        }
    break;

    case 'apache_load':
        if ($os == 'windows') {
            $data = exec('(FOR /F "skip=2 tokens=2 delims=\," %P IN (\'typeperf "\\Process(httpd)\\% processor time" -sc 1\') DO @echo %P)|find /V /I "..."');
        } else {
            $data = exec("ps aux | grep apache2 | grep -v safe | grep -v grep | awk '{ sum+=$3 } END { print sum }'");
        }
    break;

    case 'server_load':
        if ($os == 'windows') {
            $data = exec('(FOR /F "skip=2 tokens=2 delims=\," %P IN (\'typeperf "\\Process(pandora_server)\\% processor time" -sc 1\') DO @echo %P)|find /V /I "..."');
        } else {
            $data = exec("ps aux | grep pandora_server | grep -v grep | awk '{ print $3 }'");
        }
    break;

    case 'snmp_interface':
    case 'snmp_module':
        $snmp_address = get_parameter('snmp_address', '');
        $snmp_community = get_parameter('snmp_community', '');
        $snmp_ver = get_parameter('snmp_ver', '');
        $snmp_oid = get_parameter('snmp_oid', '');
        $snmp3_auth_user = get_parameter('snmp3_auth_user', '');
        $snmp3_security_level = get_parameter('snmp3_security_level', '');
        $snmp3_auth_method = get_parameter('snmp3_auth_method', '');
        $snmp3_auth_pass = get_parameter('snmp3_auth_pass', '');
        $snmp3_privacy_method = get_parameter('snmp3_privacy_method', '');
        $snmp3_privacy_pass = get_parameter('snmp3_privacy_pass', '');

        if (empty($snmp_address) || empty($snmp_oid)) {
            $data = 0;
        } else {
            $data = get_snmpwalk(
                $snmp_address,
                $snmp_ver,
                $snmp_community,
                $snmp3_auth_user,
                $snmp3_security_level,
                $snmp3_auth_method,
                $snmp3_auth_pass,
                $snmp3_privacy_method,
                $snmp3_privacy_pass,
                0,
                $snmp_oid,
                $snmp_port
            );
            $data_index = array_keys($data);
            $graph_title = $data_index[0];
            if (!empty($data)) {
                $data_array = explode(' ', reset($data));
                if (count($data_array) > 1) {
                    $data = $data_array[1];
                }

                // Redefine boolean data
                switch ($data) {
                    case 'up(1)':
                        $data = 1;
                    break;

                    case 'down(0)':
                        $data = 0;
                    break;
                }
            }
        }
    break;

    default:
        $data = 0;
}

if (empty($data)) {
    $data = 0;
}

echo '{
	"label": "'.htmlspecialchars($graph_title, ENT_QUOTES).'",    
	"data": [["'.time().'", '.htmlspecialchars($data, ENT_QUOTES).']]
}';
