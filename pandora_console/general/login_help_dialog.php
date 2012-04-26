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

/**
 * @package General
 */

global $config;

if (is_ajax()) {
	$skip_login_help = get_parameter('skip_login_help', 0);

	// Updates config['skip_login_help_dialog'] in order to don't show login help message
	if ($skip_login_help) {
		if (isset ($config['skip_login_help_dialog']))
			$result_config = db_process_sql_update('tconfig', array("value" => 1), array("token" => "skip_login_help_dialog"));		
		else 
			$result_config = db_process_sql_insert ('tconfig', array ("value" => 1, "token" => "skip_login_help_dialog"));
	}
		
	return;
}

// Prints help dialog information

if ($license_fail == 1)
	return;

echo '<div id="login_help_dialog" title="' . __('Welcome to Pandora FMS') . '" style="">';

	echo '<div style="position:absolute; top:30px; left: 10px; text-align: left; right:0%; height:70px; width:560px; margin: 0 auto; border: 1px solid #FFF; line-height: 19px;">';
		echo '<span style="font-size: 15px;">' . __('If this is your first time with Pandora FMS, we propose you a few links to learn more about Pandora FMS. Monitoring could be overhelm, but take your time to learn how to use the power of Pandora!') . '</span>';
	echo '</div>';	
	
	echo '<div style="position:absolute; top:110px; text-align: center; left:0%; right:0%; height:210px; width:580px; margin: 0 auto; border: 1px solid #FFF">';
		echo '<table cellspacing=0 cellpadding=0 style="border:1px solid #FFF; width:100%; height: 100%">';
		echo '<tr>';
			echo '<td style="border:1px solid #FFF; width:50%; height: 50%;">';
				echo '<div style="position: relative; float: left; width:40%;">';
				echo html_print_image('images/noaccess.png', true, array("alt" => __('Online help'), "border" => 0));
				echo '</div>';
				echo '<div style="position:relative; margin: 0 auto; float: right; width:60%; height: 60px; top: 20px; text-align: left;">';
				echo  '<a href="' . ui_get_full_url(false) . 'general/pandora_help.php?id=main_help" target="_blank" style="text-decoration:none; text-shadow: 0 2px 2px #9D9999;" onmouseover="this.style.textDecoration=\'underline\';" onmouseout="this.style.textDecoration=\'none\';"><span style="font-size: 14px;">' . __('Online help') . '</span></a>';
				echo '</div>';
			echo '</td>';
			echo '<td style="border:1px solid #FFF">';
				echo '<div style="position: relative; float: left; width:40%;">';
				echo html_print_image('images/noaccess.png', true, array("alt" => __('Support'), "border" => 0));
				echo '</div>';
				echo '<div style="position:relative; margin: 0 auto; float: right; width:60%; height: 60px; top: 20px; text-align: left;">';
				echo '<a href="http://openideas.info/smf/" target="_blank" style="text-decoration:none; text-shadow: 0 2px 2px #9D9999;" onmouseover="this.style.textDecoration=\'underline\';" onmouseout="this.style.textDecoration=\'none\';"><span style="font-size: 14px;">' . __('Support') . ' / ' . __('Forums') . '</span></a>';
				echo '</div>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';			
			echo '<td style="border:1px solid #FFF; width:50%; height: 50%">';
				echo '<div style="position: relative; float: left; width:40%;">';
				echo html_print_image('images/noaccess.png', true, array("alt" => __('Enterprise version'), "border" => 0));
				echo '</div>';
				echo '<div style="position:relative; margin: 0 auto; float: right; width:60%; height: 60px; top: 20px; text-align: left;">';
				echo '<a href="http://pandorafms.com/" target="_blank" style="text-decoration:none; text-shadow: 0 2px 2px #9D9999;" onmouseover="this.style.textDecoration=\'underline\';" onmouseout="this.style.textDecoration=\'none\';"><span style="font-size: 14px;">' . __('Enterprise version') . '</span></a>';
				echo '</div>';
			echo '</td>';
			echo '<td style="border:1px solid #FFF">';
				echo '<div style="position: relative; float: left; width:40%;">';
				echo html_print_image('images/noaccess.png', true, array("alt" => __('Documentation'), "border" => 0));
				echo '</div>';
				echo '<div style="position:relative; margin: 0 auto; float: right; width:60%; height: 60px; top: 20px; text-align: left;">';
				echo '<a href="http://pandorafms.org/index.php?lng=en&sec=project&sec2=documentation" target="_blank" style="text-decoration:none; text-shadow: 0 2px 2px #9D9999;" onmouseover="this.style.textDecoration=\'underline\';" onmouseout="this.style.textDecoration=\'none\';"><span style="font-size: 14px;">' . __('Documentation') . '</span></a>';
				echo '</div>';
			echo '</td>';
		echo '</tr>';
		echo '</table>';
	echo '</div>';	

	echo '<div style="position:absolute; margin: 0 auto; top: 340px; right: 10px; border: 1px solid #FFF; width: 570px">';	
		echo '<div style="float: left; margin-top: 3px; margin-left: 0px; width: 80%; text-align: left;">';
			html_print_checkbox('skip_login_help', 1, false, false, false, 'cursor: \'pointer\'');
			echo '&nbsp;<span style="font-size: 12px;">' .__("Click here to don't show again this message") . '</span>';
		echo '</div>';
		echo '<div style="float: right; width: 20%;">';
		html_print_submit_button("Ok", 'hide-login-help', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');  
		echo '</div>';
	echo '</div>';
	
echo '</div>';

ui_require_css_file ('dialog');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('ui.dialog');
ui_require_jquery_file ('ui.draggable');

?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {			

	$("#login_help_dialog").dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 400,
				width: 600,
				overlay: {
							opacity: 0.5,
							background: "black"
						},
				bgiframe: jQuery.browser.msie
	});
		
	
	$("#submit-hide-login-help").click (function () {
		
		$("#login_help_dialog" ).dialog('close');
		
		var skip_login_help = $("#checkbox-skip_login_help").attr('checked');
		
		// Update config['skip_login_help_dialog'] to don't display more this message
		if (skip_login_help == true) {
			jQuery.get ("ajax.php",
			{"page": "general/login_help_dialog",
			 "skip_login_help": 1},
			function (data) {}
			);
		}
		
	});
		
});

/* ]]> */
</script>
