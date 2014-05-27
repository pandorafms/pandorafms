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

$autohidden_menu = 0;

if (isset ($config["autohidden_menu"]) && $config["autohidden_menu"]) {
	$autohidden_menu = 1;
}

$menu_container_id = '';
if ($autohidden_menu) {
	$menu_container_id = 'menu_container';
}

// Menu container prepared to autohide menu
echo '<div id="' . $menu_container_id . '">';

echo '<div class="tit bg titop">:: '.__('Operation').' ::</div>';
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

var autohidden_menu = <?php echo $autohidden_menu; ?>;

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
			show_menu();
			openTime = new Date().getTime();
			
			// Close in 1 second if is not closed manually
			setTimeout(function() {
				if(openTime > 0 && handsIn == 0) {
					hide_menu();
					openTime = 0;
				}
			}, 1000);
		}
	}
	
	function handlerOut() {
		handsIn = 0;
		var openedTime = new Date().getTime() - openTime;
		
		if(openedTime > 1000) {
			hide_menu();
			openTime = 0;
		}
	}
	
	function show_menu () {
		$('#menu_container').animate({"left": "+=140px"}, 200);
		show_menu_pretty();
	}
	
	function show_menu_pretty() {
		$('div.menu ul li').css('background-position', '');
		$('ul.submenu li a, li.menu_icon a, li.links a').css('visibility', '');
		$('.titop').css('color', 'white');
		$('.bg3').css('color', 'white');
		$('.bg4').css('color', 'white');
	}
	
	function hide_menu () {
		$('#menu_container').animate({"left": "-=140px"}, 100);
		hide_menu_pretty();
	}
	
	function hide_menu_pretty() {
		$('div.menu li').css('background-position', '140px 3px');
		$('ul.submenu li a, li.menu_icon a, li.links a').css('visibility', 'hidden');
		$('.titop').css('color', $('.titop').css('background-color'));
		$('.bg3').css('color', $('.bg3').css('background-color'));
		$('.bg4').css('color', $('.bg4').css('background-color'));
	}
	
	if (autohidden_menu) {
		$('#main').css('margin-left', '40px');
		hide_menu_pretty();
	}
});
/* ]]> */
</script>
