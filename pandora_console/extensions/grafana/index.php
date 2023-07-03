<?php

// Allow Grafana proxy
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, X-Grafana-Org-Id, X-Grafana-NoCache, X-DS-Authorization');

// Get all request headers
$headers = apache_request_headers();

// Check if user and password has been sent
if ($headers['Authorization']) {
        $headers['X-DS-Authorization'] = $headers['Authorization'];
}

if ($headers['X-DS-Authorization']) {
        include_once '../../include/config.php';

        global $config;

        include_once $config['homedir'].'/include/functions_config.php';
        include_once $config['homedir'].'/include/functions.php';

        list($user, $password) = explode(':', base64_decode($headers['X-DS-Authorization']));

        // Check user login
        $user_in_db = process_user_login($user, $password, true);

    if ($user_in_db !== false) {
            // Check user ACL
        if (check_acl($user_in_db, 0, 'AR')) {
                $result_array = [
                    'code'    => 200,
                    'message' => 'Access granted',
                ];
        } else {
                $result_array = [
                    'code'    => 403,
                    'message' => 'Access forbidden',
                ];
        }
    } else {
            $result_array = [
                'code'    => 401,
                'message' => 'Unauthorized',
            ];
    }
} else {
        // OPTIONS request automatically works
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $result_array = [
                'code'    => 200,
                'message' => 'Options request accepted',
            ];
    } else {
            $result_array = [
                'code'    => 401,
                'message' => 'Unauthorized',
            ];
    }
}

// Numeric data in array must be numeric data in json (not text)
$result = json_encode($result_array, JSON_NUMERIC_CHECK);

echo $result;
