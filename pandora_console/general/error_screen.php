<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
include_once("include/functions.php");
include_once("include/functions_html.php");
include_once("include/functions_ui.php");
include_once("include/functions_io.php");
include_once("include/functions_extensions.php");
echo '<html>';
ob_start ('ui_process_page_head');
echo '<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">';
echo '</head>' . "\n";

require_once ("include/functions_themes.php");
ob_start ('ui_process_page_body');

// At this point, $login_screen is setted with the error type desired

require('login_page.php');

?>
</body>
</html>
