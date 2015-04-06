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

//echo '<div class="tit bg titop">:: '.__('Operation').' ::</div>';
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
	
	//echo '<div class="tit bg3">:: '.__('Administration').' ::</div>';
}

require ("godmode/menu.php");

//require ("links_menu.php");

echo '</div>'; //menu_container

ui_require_jquery_file ('cookie');

$config_fixed_menu = false;
if (isset($config['fixed_menu'])) {
	$config_fixed_menu = $config['fixed_menu'];
}
$config_fixed_header = false;
if (isset($config['fixed_header'])) {
	$config_fixed_header = $config['fixed_header'];
}
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */
/*
var autohidden_menu = <?php echo $autohidden_menu; ?>;
var fixed_menu = <?php echo json_encode((bool)$config_fixed_menu); ?>;
var fixed_header = <?php echo json_encode((bool)$config_fixed_header); ?>;
var id_user = "<?php echo $config['id_user']; ?>";
var cookie_name = id_user + '-pandora_menu_state';
var cookie_name_encoded = btoa(cookie_name);

var menuState = $.cookie(cookie_name_encoded);
if (!menuState) {
	menuState = {};
}
else {
	menuState = JSON.parse(menuState);
	open_submenus();
}

function open_submenus () {
	$.each(menuState, function (index, value) {
		if (value)
			$('div.menu>ul>li#' + index + '>ul').show();
	});
	$('div.menu>ul>li.selected>ul').removeClass('invisible');
}


function close_submenus () {
	$.each(menuState, function (index, value) {
		if (value)
			$('div.menu>ul>li#' + index + '>ul').hide();
	});
	$('div.menu>ul>li.selected>ul').addClass('invisible');
}

$(document).ready( function() {

	$("img.toggle").click (function (e) {
		//In case the links gets activated, we don't want to follow link
		e.preventDefault();
		
		var menuItem = $(this).parent();
		var submenu = menuItem.children("ul");

		if (submenu.is(":visible")) {
			submenu.slideUp();
			
			if (typeof menuState[menuItem.attr('id')] != 'undefined')
				delete menuState[menuItem.attr('id')];
		}
		else {
			submenu.slideDown();

			menuState[menuItem.attr('id')] = 1;
		}
		
		$.cookie(cookie_name_encoded, JSON.stringify(menuState), {expires: 7});
	});
	
	if (fixed_menu) {
		$('div#menu')
			.css('position', 'fixed')
			.css('z-index', '9000')
			.css('left', '0')
			.css('top', $('div#head').innerHeight() + 'px')
			.css('height', '100%')
			.css('overflow', 'hidden')
			.hover(function (e) {
				if (!autohidden_menu) {
					$(this).css('overflow', 'auto').children('div').css('width', 'auto');
				}
			}, function (e) {
				if (!autohidden_menu) {
					$(this).css('overflow', 'hidden').children('div').css('width', '100%');
				}
			})
			.children('div')
				.css('margin-bottom', $('div#head').innerHeight() + 'px');

		if (!fixed_header) {
			$(window).scroll(function () {
				if ($(this).scrollTop() <= $('div#head').innerHeight()) {
					$('div#menu').css('top', $('div#head').innerHeight() - $(this).scrollTop() + 'px' );
				} else {
					$('div#menu').css('top', '0');
				}
			});
		}
	}

	if (autohidden_menu) {

		$('#menu_container').hover (handlerIn, handlerOut);
		var openTime = 0;
		var handsIn = 0;

		$('#main').css('margin-left', '50px');
		hide_menu_pretty();
		
		function handlerIn() {
			handsIn = 1;
			if (openTime == 0) {
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
			
			if (openedTime > 1000) {
				hide_menu();
				openTime = 0;
			}
		}
		
		function show_menu () {
			$('#menu_container').animate({"left": "+=80px"}, 200, function () {
				if (fixed_menu) {
					$('#menu_container').parent().css('overflow', 'auto');
				}
			});
			show_menu_pretty();
		}
		
		function show_menu_pretty() {
			open_submenus();
			$('div.menu ul li').css('background-position', '');
			$('ul.submenu li a, li.menu_icon a, li.links a, div.menu>ul>li>img.toggle').show();
			$('.titop').css('color', 'white');
			$('.bg3').css('color', 'white');
			$('.bg4').css('color', 'white');
		}
		
		function hide_menu () {
			if (fixed_menu) {
				$('#menu_container').parent().css('overflow', 'hidden');
			}
			$('#menu_container').animate({"left": "-=80px"}, 100);
			hide_menu_pretty();
		}
		
		function hide_menu_pretty() {
			close_submenus();
			$('div.menu li').css('background-position', '85px 5px');
			$('ul.submenu li a, li.menu_icon a, li.links a, div.menu>ul>li>img.toggle').hide();
			$('.titop').css('color', $('.titop').css('background-color'));
			$('.bg3').css('color', $('.bg3').css('background-color'));
			$('.bg4').css('color', $('.bg4').css('background-color'));
		}
	}
});
/* ]]> */
</script>

<script type="text/javascript">
	openTime = 0;
	openTime2 = 0;
	handsIn = 0;
	handsIn2 = 0;

	$('.menu_icon').hover(function(){
		table_hover = $(this);
		handsIn = 1;
		openTime = new Date().getTime();
		$("ul#sub"+table_hover[0].id).show();
		if( typeof(table_noHover) != 'undefined')
			if ( "ul#sub"+table_hover[0].id != "ul#sub"+table_noHover[0].id )
				$("ul#sub"+table_noHover[0].id).hide();
	}).mouseleave(function(){
		table_noHover = $(this);
		handsIn = 0;
		setTimeout(function() {
			opened = new Date().getTime() - openTime;
			if(opened > 3000 && handsIn == 0) {
				openTime = 4000;
				$("ul#sub"+table_hover[0].id).hide();
			}
		}, 3500);
	});
	
	
-	$('.has_submenu').hover(function(){
		table_hover2 = $(this);
		handsIn2 = 1;
		openTime2 = new Date().getTime();
		$("#sub"+table_hover2[0].id).show();
		if( typeof(table_noHover2) != 'undefined')
			if ( "ul#sub"+table_hover2[0].id != "ul#sub"+table_noHover2[0].id )
				$("ul#sub"+table_noHover2[0].id).hide();
	}).mouseout(function(){
		table_noHover2 = table_hover2;
		handsIn2 = 0;
		setTimeout(function() {
		opened = new Date().getTime() - openTime2;
			if(opened >= 3000 && handsIn2 == 0) {
				openTime2 = 4000;
				$("ul#sub"+table_hover2[0].id).hide();
			}
		}, 3500);
	});
	
	$(document).ready(function(){
		$('#page').click(function(){
			openTime = 4000;
			if( typeof(table_hover) != 'undefined')
				$("ul#sub"+table_hover[0].id).hide();
			if( typeof(table_hover2) != 'undefined')
				$("ul#sub"+table_hover2[0].id).hide();
		});
	});
	
	

</script>
