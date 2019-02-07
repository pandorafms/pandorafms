<?php

require_once 'include/config.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';
require_once 'include/auth/mysql.php';

$id = get_parameter('get_image');

$sql = 'SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$id;

$result = db_get_sql($sql);

$image = strpos($result, 'data:image');

if ($image === false) {
    echo 0;
} else {
    echo 1;
}
