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

// Login check
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_visual_map.php');

print_pandora_visual_map ($visualConsole['id']);

require_javascript_file ('wz_jsgraphics');
require_javascript_file ('pandora_visual_console');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	draw_lines (lines, 'layout_map');
});
/* ]]> */
</script>