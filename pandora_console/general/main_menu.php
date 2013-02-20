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


if (! isset ($config["id_user"])) {
	require ("general/login_page.php");
	exit ();
}

// Menu container prepared to autohide menu
echo '<div id="menu_container">';

echo '<div class="tit bg">:: '.__('Operation').' ::</div>';
require ("operation/menu.php");

//Check all enterprise ACL used in godmenu items to print menu headers
if (check_acl ($config['id_user'], 0, "AW") ||
	check_acl ($config['id_user'], 0, "PM") ||
	check_acl ($config['id_user'], 0, "LM") ||
	check_acl ($config['id_user'], 0, "UM") ||
	check_acl ($config['id_user'], 0, "LW") ||
	check_acl ($config['id_user'], 0, "IW") ||
	check_acl ($config['id_user'], 0, "EW") ||
	check_acl ($config['id_user'], 0, "DW")) {
	
	echo '<div class="tit bg3">:: '.__('Administration').' ::</div>';
}

require ("godmode/menu.php");

require ("links_menu.php");

echo '</div>'; //menu_container

ui_require_jquery_file ('cookie');
?>
<script type="text/javascript" language="javascript">
/* <![CDATA[ */
$(document).ready( function() {
	$("img.toggle").click (function () {
		$(this).siblings ("ul").toggle ();
		//In case the links gets activated, we don't want to follow link
		return false;
	});
	
	$('#menu_container').hover (handlerIn, handlerOut);
	var openTime = 0;
	var handsIn = 0;
	
	function handlerIn() {
		handsIn = 1;
		if(openTime == 0) {
			$('#menu_container').animate({"left": "+=130px"}, 200);
			openTime = new Date().getTime();
			
			// Close in 1 second if is not closed manually
			setTimeout(function(){
				if(openTime > 0 && handsIn == 0) {
					$('#menu_container').animate({"left": "-=130px"}, 100);
					openTime = 0;
				}
			}, 1000);
		}
	}
	
	function handlerOut() {
		handsIn = 0;
		var openedTime = new Date().getTime() - openTime;
		
		if(openedTime > 1000) {
			$('#menu_container').animate({"left": "-=130px"}, 100);
			openTime = 0;
		}
	}
});
/* ]]> */
</script>