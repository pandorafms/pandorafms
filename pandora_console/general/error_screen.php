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
require_once __DIR__.'/../include/functions.php';
require_once __DIR__.'/../include/functions_html.php';
require_once __DIR__.'/../include/functions_ui.php';
require_once __DIR__.'/../include/functions_io.php';
require_once __DIR__.'/../include/functions_extensions.php';

global $config;
$config['homedir'] = realpath(__DIR__.'/../');

echo '<html>';
ob_start('ui_process_page_head');
echo '<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">';
echo '</head>'."\n";

require_once __DIR__.'/../include/functions_themes.php';
ob_start('ui_process_page_body');

// At this point, $login_screen is set with the error type desired.
require __DIR__.'/login_page.php';

?>
</body>
</html>
