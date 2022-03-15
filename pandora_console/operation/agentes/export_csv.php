<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';
require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/functions_modules.php';
require_once '../../include/functions_agents.php';

$config['id_user'] = $_SESSION['id_usuario'];
if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    include '../../general/noaccess.php';
    return;
}

if (isset($_GET['agentmodule']) && isset($_GET['agent'])) {
    $id_agentmodule = $_GET['agentmodule'];
    $id_agent = $_GET['agent'];
    $agentmodule_name = modules_get_agentmodule_name($id_agentmodule);
    if (! check_acl($config['id_user'], agents_get_agent_group($id_agent), 'AR')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Agent Export Data'
        );
        include '../../general/noaccess.php';
        exit;
    }

    $now = date('Y/m/d H:i:s');

    // Show contentype header
    header('Content-type: text/txt');
    header('Content-Disposition: attachment; filename="pandora_export_'.$agentmodule_name.'.txt"');

    if (isset($_GET['from_date'])) {
        $from_date = $_GET['from_date'];
    } else {
        $from_date = $now;
    }

    if (isset($_GET['to_date'])) {
        $to_date = $_GET['to_date'];
    } else {
        $to_date = $now;
    }

    // Convert to unix date
    $from_date = date('U', strtotime($from_date));
    $to_date = date('U', strtotime($to_date));

    // Make the query
    $sql1 = "
		SELECT *
		FROM tdatos
		WHERE id_agente = $id_agent
			AND id_agente_modulo = $id_agentmodule";
    $tipo = modules_get_moduletype_name(modules_get_agentmodule_type($id_agentmodule));
    if ($tipo == 'generic_data_string') {
        $sql1 = "
			SELECT *
			FROM tagente_datos_string
			WHERE utimestamp > $from_date AND utimestamp < $to_date
				AND id_agente_modulo = $id_agentmodule
			ORDER BY utimestamp DESC";
    } else {
        $sql1 = "
			SELECT *
			FROM tagente_datos
			WHERE utimestamp > $from_date AND utimestamp < $to_date
				AND id_agente_modulo = $id_agentmodule
			ORDER BY utimestamp DESC";
    }

    $result1 = db_get_all_rows_sql($sql1, true);
    if ($result1 === false) {
        $result1 = [];
    }

    // Render data
    foreach ($result1 as $row) {
        echo $agentmodule_name;
        echo ',';
        echo $row['datos'];
        echo ',';
        echo $row['utimestamp'];
        echo chr(13);
    }
}
