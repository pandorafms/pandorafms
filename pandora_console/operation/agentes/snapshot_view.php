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



// Global & session management
require_once ('../../include/config.php');
require_once ('../../include/auth/mysql.php');

if (! isset($_SESSION['id_usuario'])) {
        session_start();
        session_write_close();
}

require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_ui.php');

check_login ();

$user_language = get_user_language ($config['id_user']);
if (file_exists ('../../include/languages/'.$user_language.'.mo')) {
        $l10n = new gettext_reader (new CachedFileReader ('../../include/languages/'.$user_language.'.mo'));
        $l10n->load_tables();
}

$id = get_parameter('id');
$label = get_parameter ("label");

// TODO - Put ACL here

// Parsing the refresh before sending any header
$refresh = (int) get_parameter ("refr", -1);
if ($refresh > 0) {
        $query = ui_get_url_refresh (false);
        echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
}
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pandora FMS Snapshot data view for module (<?php echo $label; ?>)</title>
<body style='background:#000; color: #ccc;'>

<?php

$row = db_get_row_sql("SELECT * FROM tagente_estado WHERE id_agente_modulo = $id");

echo "<h2>";
echo __("Current data at");
echo " ";
echo $row["timestamp"];
echo "</h2>";
$datos = io_safe_output($row["datos"]);
$datos = preg_replace ('/\n/i','<br>',$datos);
$datos =  preg_replace ('/\s/i','&nbsp;',$datos);
echo "<div style='padding: 10px; font-size: 14px; line-height: 16px; font-family: mono; text-align: left'>";
echo $datos;
echo "</div>";

