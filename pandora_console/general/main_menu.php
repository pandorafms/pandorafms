<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


if (! isset ($config["id_user"])) {
	require ("general/login_page.php");
	exit ();
}

echo '<div class="tit bg">:: '.__('Operation').' ::</div>';
require ("operation/menu.php");

echo '<div class="tit bg3">:: '.__('Administration').' ::</div>';
require ("godmode/menu.php");

require ("links_menu.php");

require_jquery_file ('cookie');
?>
<script type="text/javascript" language="javascript">
/* <![CDATA[ */
$(document).ready( function() {
	$("img.toggle").click (function () {
		$(this).siblings ("ul").toggle ();
		//In case the links gets activated, we don't want to follow link
		return false;
	});
});
/* ]]> */
</script>
