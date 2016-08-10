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

var autohidden_menu = <?php echo $autohidden_menu; ?>;
var fixed_menu = <?php echo json_encode((bool)$config_fixed_menu); ?>;
var fixed_header = <?php echo json_encode((bool)$config_fixed_header); ?>;
var id_user = "<?php echo $config['id_user']; ?>";
var cookie_name = id_user + '-pandora_menu_state';
var cookie_name_encoded = btoa(cookie_name);
var click_display = "<?php echo $config["click_display"]; ?>";

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
	//Daniel maya 02/06/2016 Fixed menu position--INI
	if (fixed_menu) {
		$('div#menu')
			.css('position', 'fixed')
			.css('z-index', '9000')
	}
	//Daniel maya 02/06/2016 Fixed menu position--END
	/*
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
	//Cerrar aqui los comentarios cuando esté el menu terminado
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
	
	function show_menu () {
			$('#menu_container').animate({"left": "+=80px"}, 200, function () {
				if (fixed_menu) {
					$('#menu_container').parent().css('overflow', 'auto');
				}
			});
			//show_menu_pretty();
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
		//hide_menu_pretty();
	}
	
	function hide_menu_pretty() {
		close_submenus();
		$('div.menu li').css('background-position', '85px 5px');
		$('ul.submenu li a, li.menu_icon a, li.links a, div.menu>ul>li>img.toggle').hide();
		$('.titop').css('color', $('.titop').css('background-color'));
		$('.bg3').css('color', $('.bg3').css('background-color'));
		$('.bg4').css('color', $('.bg4').css('background-color'));
	}
	*/
	if (autohidden_menu) {

		//handlerIn, handlerOut);
		//openTime = 0;
		//handsIn = 0;

		//$('#main').css('margin-left', '50px');
		//hide_menu_pretty();
		/*
		$('#menu').hover(function() {
			handsIn = 1;
			if (openTime == 0) {
				show_menu();
				openTime = new Date().getTime();
			}
		}).mouseleave(function() {
			handsIn = 0;
			setTimeout(function() {
				openedTime = new Date().getTime() - openTime;
				if(openedTime > 3000 && handsIn == 0) {
					hide_menu();
					openTime = 0;
				}
			}, 3500);
		});
		*/
		handsInMenu = 0;
		openTimeMenu = 0;
		if(!click_display){
			$('#menu').mouseenter(function() {
				$('div#title_menu').show();
				handsInMenu = 1;
				openTimeMenu = new Date().getTime();
				$('#menu').css('width', '145px');
				$('li.menu_icon').addClass( " no_hidden_menu" );
				$('ul.submenu').css('left', '144px');
			}).mouseleave(function() {
				handsInMenu = 0;
				setTimeout(function() {
					openedMenu = new Date().getTime() - openTimeMenu;
					if(openedMenu > 1000 && handsInMenu == 0) {
						$('#menu').css('width', '45px');
						$('li.menu_icon').removeClass( " no_hidden_menu");
						$('ul.submenu').css('left', '44px');
						$('div#title_menu').hide();
					}
				}, 2500);
			});
		}else{
			$(document).ready(function() {
				$('#menu').on("click", function() {
					$('div#title_menu').show();
					handsInMenu = 1;
					openTimeMenu = new Date().getTime();
					$('#menu').css('width', '145px');
					$('li.menu_icon').addClass( " no_hidden_menu" );
					$('li.menu_icon').find('li').addClass( " no_hidden_menu" );
					$('ul.submenu').css('left', '144px');
				}).mouseleave(function() {
					handsInMenu = 0;
					setTimeout(function() {
						openedMenu = new Date().getTime() - openTimeMenu;
						if(openedMenu > 1000 && handsInMenu == 0) {
							$('#menu').css('width', '45px');
							$('li.menu_icon').removeClass( " no_hidden_menu");
							$('li.menu_icon').find('li').removeClass( " no_hidden_menu" );
							$('ul.submenu').css('left', '44px');
							$('div#title_menu').hide();
						}
					}, 5500);
				});
			});
		}
		/*$('#menu').mouseenter(function() {
			$('div#title_menu').show();
			handsInMenu = 1;
			openTimeMenu = new Date().getTime();
			$('#menu').css('width', '145px');
			$('li.menu_icon').addClass( " no_hidden_menu" );
			$('ul.submenu').css('left', '144px');
		}).mouseleave(function() {
			handsInMenu = 0;
			setTimeout(function() {
				openedMenu = new Date().getTime() - openTimeMenu;
				if(openedMenu > 1000 && handsInMenu == 0) {
					$('#menu').css('width', '45px');
					$('li.menu_icon').removeClass( " no_hidden_menu");
					$('ul.submenu').css('left', '44px');
					$('div#title_menu').hide();
				}
			}, 1500);
		});*/
	}
	else {
		$('div#title_menu').hide();
	}
});
/* ]]> */
</script>

<script type="text/javascript">
	openTime = 0;
	openTime2 = 0;
	handsIn = 0;
	handsIn2 = 0;

	//Daniel maya 02/06/2016 Display menu with click --INI
	if(!click_display){
		//Daniel barbero 10/08/2016 Display menu with click --INI
		if (autohidden_menu) {
		//Daniel barbero 10/08/2016 Display menu with click --END
			$('.menu_icon').mouseenter(function() {
				table_hover = $(this);
				handsIn = 1;
				openTime = new Date().getTime();
				$("ul#sub"+table_hover[0].id).show();
				if( typeof(table_noHover) != 'undefined')
					if ( "ul#sub"+table_hover[0].id != "ul#sub"+table_noHover[0].id )
						$("ul#sub"+table_noHover[0].id).hide();
			}).mouseleave(function() {
				table_noHover = $(this);
				handsIn = 0;
				setTimeout(function() {
					opened = new Date().getTime() - openTime;
					if(opened > 3000 && handsIn == 0) {
						openTime = 4000;
						$("ul#sub"+table_hover[0].id).hide();
					}
				}, 2500);
			});
		//Daniel barbero 10/08/2016 Display menu with click --INI
		} else {
			$('.menu_icon').mouseenter(function() {
				table_hover = $(this);
				handsIn = 1;
				openTime = new Date().getTime();
				$("ul#sub"+table_hover[0].id).show();
				if( typeof(table_noHover) != 'undefined')
					if ( "ul#sub"+table_hover[0].id != "ul#sub"+table_noHover[0].id )
						$("ul#sub"+table_noHover[0].id).hide();
			}).mouseleave(function() {
				table_noHover = $(this);
				handsIn = 0;
				$("ul#sub"+table_hover[0].id).hide();
				/*
				setTimeout(function() {
					opened = new Date().getTime() - openTime;
					if(opened > 3000 && handsIn == 0) {
						openTime = 4000;
						$("ul#sub"+table_hover[0].id).hide();
					}
				}, 2500);
				*/
			});
		}
		//Daniel barbero 10/08/2016 Display menu with click --END
	}else{
		$(document).ready(function() {
			//Daniel barbero 10/08/2016 Display menu with click --INI
			if (autohidden_menu) {
			//Daniel barbero 10/08/2016 Display menu with click --END
				$('.menu_icon').on("click", function() {
					if( typeof(table_hover) != 'undefined'){
						$("ul#sub"+table_hover[0].id).hide();
					}
					table_hover = $(this);
					handsIn = 1;
					openTime = new Date().getTime();
					$("ul#sub"+table_hover[0].id).show();
				}).mouseleave(function() {
					table_noHover = $(this);
					handsIn = 0;
					setTimeout(function() {
						opened = new Date().getTime() - openTime;
						if(opened > 5000 && handsIn == 0) {
							openTime = 6000;
							$("ul#sub"+table_hover[0].id).hide();
						}
					}, 5500);
				});
			//Daniel barbero 10/08/2016 Display menu with click --INI
			} else {
				$('.menu_icon').on("click", function() {
					if( typeof(table_hover) != 'undefined'){
						$("ul#sub"+table_hover[0].id).hide();
					}
					table_hover = $(this);
					handsIn = 1;
					openTime = new Date().getTime();
					$("ul#sub"+table_hover[0].id).show();
				});
			}
			//Daniel barbero 10/08/2016 Display menu with click --END
		});
	}
	//Daniel maya 02/06/2016 Display menu with click --END

-	$('.has_submenu').mouseenter(function() {
		table_hover2 = $(this);
		handsIn2 = 1;
		openTime2 = new Date().getTime();
		$("#sub"+table_hover2[0].id).show();
		if( typeof(table_noHover2) != 'undefined')
			if ( "ul#sub"+table_hover2[0].id != "ul#sub"+table_noHover2[0].id )
				$("ul#sub"+table_noHover2[0].id).hide();
	}).mouseleave(function() {
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
	
	$(document).ready(function() {
		//Daniel maya 02/06/2016 Display menu with click --INI
		if(!click_display){
			$('#container').click(function() {
				openTime = 4000;
				if( typeof(table_hover) != 'undefined')
					$("ul#sub"+table_hover[0].id).hide();
				if( typeof(table_hover2) != 'undefined')
					$("ul#sub"+table_hover2[0].id).hide();
				$('#menu').css('width', '45px');
				$('li.menu_icon').removeClass( " no_hidden_menu");
				$('ul.submenu').css('left', '44px');
				$('div#title_menu').hide();
			});
		}else{
			$('#main').click(function() {
				openTime = 4000;
				if( typeof(table_hover) != 'undefined')
					$("ul#sub"+table_hover[0].id).hide();
				if( typeof(table_hover2) != 'undefined')
					$("ul#sub"+table_hover2[0].id).hide();
				$('#menu').css('width', '45px');
				$('li.menu_icon').removeClass( " no_hidden_menu");
				$('ul.submenu').css('left', '44px');
				$('div#title_menu').hide();
			});
		}
		//Daniel maya 02/06/2016 Display menu with click --END

		$('div.menu>ul>li>ul>li>a').click(function() {
			openTime = 4000;
			if( typeof(table_hover) != 'undefined')
				$("ul#sub"+table_hover[0].id).hide();
			if( typeof(table_hover2) != 'undefined')
				$("ul#sub"+table_hover2[0].id).hide();
			$('#menu').css('width', '45px');
			$('li.menu_icon').removeClass( " no_hidden_menu");
			$('ul.submenu').css('left', '44px');
			$('div#title_menu').hide();
		});		
		$('div.menu>ul>li>ul>li>ul>li>a').click(function() {
			openTime = 4000;
			if( typeof(table_hover) != 'undefined')
				$("ul#sub"+table_hover[0].id).hide();
			if( typeof(table_hover2) != 'undefined')
				$("ul#sub"+table_hover2[0].id).hide();
			$('#menu').css('width', '45px');
			$('li.menu_icon').removeClass( " no_hidden_menu");
			$('ul.submenu').css('left', '44px');
			$('div#title_menu').hide();
		});
	});
	
	

</script>
