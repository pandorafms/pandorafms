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




// Load global vars
require_once ("include/config.php");

check_login ();

echo "<h2>".__('Users defined in Pandora')." &raquo; ".__('User activity statistics')."</h2>";

print_image ("reporting/fgraph.php?tipo=user_activity", false, array ("border" => 0));
?>
