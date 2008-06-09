<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {
echo "<h2>".$lang_label["users"]." &gt; ";
echo $lang_label["users_statistics"]."</h2>";
echo '<img src="reporting/fgraph.php?tipo=user_activity" border=0>';
}
?>