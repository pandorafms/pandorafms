<?php

// Allow Grafana proxy
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, X-Grafana-Org-Id, X-Grafana-NoCache, X-DS-Authorization');

// Get all request headers
$headers = apache_request_headers();

$result_array = [];

// Check if user and password has been sent
if ($headers['Authorization']) {
    // Get all POST data sent
    $payload = json_decode(file_get_contents('php://input'), true);

    include_once '../../include/config.php';

    global $config;

    include_once $config['homedir'].'/include/functions_config.php';
    include_once $config['homedir'].'/include/functions.php';

    list($user, $password) = explode(':', base64_decode($headers['Authorization']));

    // Check user login
    $user_in_db = process_user_login($user, $password, true);

    if ($user_in_db !== false) {
        // Check user ACL
        if (check_acl($user_in_db, 0, 'AR')) {
            include_once $config['homedir'].'/include/functions_db.php';

            // If search is for groups
            if ($payload['type'] == 'group') {
                // Include group ALL
                $result_array[] = [
                    'value' => 0,
                    'text'  => 'All',
                ];

                // Get groups that match the search
                $sql = 'SELECT nombre, id_grupo id FROM tgrupo WHERE LOWER(nombre) LIKE LOWER("%'.io_safe_input($payload['search']).'%")';

                // If search is for agents
            } else if ($payload['type'] == 'agent') {
                // Get agents that match the search
                $sql = 'SELECT a.alias nombre, a.id_agente id FROM tagente a, tgrupo g WHERE a.disabled = 0 AND a.id_grupo = g.id_grupo AND LOWER(a.alias) LIKE LOWER("%'.io_safe_input($payload['search']).'%")';

                // If search group is not all, add extra filter
                if ($payload['extra'] != 0) {
                    $sql .= ' AND g.id_grupo = "'.io_safe_input($payload['extra']).'"';
                }

                // If search is for modules
            } else if ($payload['type'] == 'module') {
                // Get modules that match the search (not string)
                $sql = 'SELECT m.nombre nombre, m.id_agente_modulo id FROM tagente_modulo m, tagente a, ttipo_modulo t WHERE m.disabled = 0 AND m.id_agente = a.id_agente AND t.id_tipo = m.id_tipo_modulo AND a.id_agente = "'.io_safe_input($payload['extra']).'" AND LOWER(m.nombre) LIKE LOWER("%'.io_safe_input($payload['search']).'%") AND t.nombre NOT LIKE "%string"';
            }

            // Run query
            $sql_results = db_get_all_rows_sql($sql);

            foreach ($sql_results as $sql_result) {
                // If search is for groups, only add those with permissions
                if ($payload['type'] == 'group') {
                    if (check_acl($user_in_db, $sql_result['id'], 'AR')) {
                        $result_array[] = [
                            'value' => $sql_result['id'],
                            'text'  => io_safe_output($sql_result['nombre']),
                        ];
                    }
                } else {
                    $result_array[] = [
                        'value' => $sql_result['id'],
                        'text'  => io_safe_output($sql_result['nombre']),
                    ];
                }
            }
        }
    }
}

$result = json_encode($result_array, JSON_UNESCAPED_UNICODE);

echo $result;
