<?php
// Allow Grafana proxy.
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, X-Grafana-Org-Id, X-Grafana-NoCache, X-DS-Authorization');

// Get all request headers.
$headers = apache_request_headers();

$result_array = [];

// Check if user and password has been sent.
if ($headers['Authorization']) {
    // Get all POST data sent.
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
            include_once $config['homedir'].'/include/functions_graph.php';

            // Get graph data for each Grafana target
            foreach ($payload['targets'] as $target) {
                $sql_results = [];
                $result_data = [];

                // Decode target data sent by datasource plugin in Grafana
                $target_data = json_decode($target['target'], true);

                if ($target_data['module']) {
                    // Get module name as target if not defined in Grafana.
                    if (!$target_data['target']) {
                        $target_data['target'] = io_safe_output(db_get_value_sql('SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = '.$target_data['module']));
                    }

                    $target_data['interval'] = db_get_value_sql('SELECT module_interval FROM tagente_modulo WHERE id_agente_modulo = '.$target_data['module']);

                    $params = [
                        'agent_module_id' => $target_data['module'],
                        'period'          => (strtotime($payload['range']['to']) - strtotime($payload['range']['from'])),
                        'date'            => strtotime($payload['range']['to']),
                        'return_data'     => 1,
                        'show_unknown'    => true,
                        'fullscale'       => (bool) $target_data['tip'],
                        'time_interval'   => $target_data['interval'],
                    ];

                    // Get all data.
                    $data = grafico_modulo_sparse($params);

                    $unknown_timestamps = [];

                    // Set unknown data as null.
                    foreach ($data['unknown1']['data'] as $d) {
                        if (($d[1] == 1 && !$params['fullscale']) || ($d[1] == 0 && $params['fullscale'])) {
                            $result_data[] = [
                                null,
                                $d[0],
                            ];
                        }

                        $unknown_timestamps[] = $d[0];
                    }

                    // Get each data if not in unknown timestamps
                    foreach ($data['sum1']['data'] as $d) {
                        if ($d[1] != false && !in_array($d[0], $unknown_timestamps)) {
                            $result_data[] = [
                                $d[1],
                                $d[0],
                            ];
                        }
                    }

                    // Sort all data by utimestamp (Grafana needs it).
                    usort(
                        $result_data,
                        function ($a, $b) {
                            return $a[1] > $b[1] ? 1 : -1;
                        }
                    );

                    $rows = [];

                    foreach ($result_data as $k => $v) {
                        if (($result_data[$k][0] !== $result_data[($k - 1)][0]
                            || $result_data[$k][0] !== $result_data[($k + 1)][0])
                            || ($result_data[($k - 1)][0] === null
                            && $result_data[$k][0] !== null
                            && $result_data[$k][1] != (strtotime($payload['range']['to']) * 1000))
                            || ($result_data[($k - 1)][0] === $result_data[$k][0] && $result_data[$k][1] == (strtotime($payload['range']['to']) * 1000))
                        ) {
                            $rows[] = $result_data[$k];
                        }
                    }

                    if (!$params['fullscale']) {
                        $target_data['target'] .= ' (avg)';
                    }

                    // Set all target info and data
                    $result_array[] = [
                        'type'       => 'table',
                        'target'     => $target_data['target'],
                        'refId'      => $target_data['target'],
                        'columns'    => [
                            [
                                'text' => $target_data['target'],
                            ],
                            [
                                'text' => 'Time',
                                'type' => 'time',
                            ],
                        ],
                        'datapoints' => array_values($rows),
                    ];
                }
            }
        }
    }
}

// Numeric data in array must be numeric data in json (not text).
$result = json_encode($result_array, JSON_NUMERIC_CHECK);

echo $result;
