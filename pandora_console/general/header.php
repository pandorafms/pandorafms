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

require_once ("include/functions_messages.php");
require_once ('include/functions_servers.php');

// Check permissions

// Global errors/warnings checking.
config_check();

?>
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px; margin-top: 0px; height: 100%" border="0">
	<tr>
		<td style="width:90%;">
			<a href="index.php?sec=main">
				<?php
					$custom_logo = 'images/custom_logo/' . $config['custom_logo'];
					
					if (!defined ('PANDORA_ENTERPRISE')) {
						$logo_title = 'Pandora FMS Opensource';
						$custom_logo = 'images/custom_logo/pandora_logo_head_3.png';
					}
					else {
						if (file_exists(ENTERPRISE_DIR . '/' . $custom_logo)) {
							$custom_logo = ENTERPRISE_DIR . '/' . $custom_logo;
						}
						$logo_title = 'Pandora FMS Enterprise';
					}
					
					echo html_print_image($custom_logo, true,
						array("alt" => $logo_title, "border" => '0'));
				?>
			</a>
		</td>
		<td style="min-width:200px;">
			<?php
				$table = new stdClass();
				$table->id = "header_table";
				$table->class = "none";
				$table->cellpadding = 0;
				$table->cellspacing = 0;
				$table->head = array ();
				$table->data = array ();
				$table->style[0] =
					$table->style['clippy'] =
					$table->style[1] =
					$table->style[3] =
					$table->style[4] =
					$table->style[5] =
					$table->style[6] =
					$table->style[8] =
					$table->style[9] =
					$table->style['qr'] =
					'width: 22px; text-align:center; height: 22px; padding-right: 9px;padding-left: 9px;';
				$table->style[7] = 'width: 20px; padding-right: 9px;';
				$table->style['searchbar'] = 'width: 180px; min-width: 180px;';
				$table->style[11] = 'padding-left: 10px; padding-right: 5px;width: 16px;';
				$table->width = "100%";
				$table->styleTable = 'margin: auto; margin-top: 0px;';
				$table->rowclass[0] = '';
				$table->data[0][11] = ui_print_help_tip (__("Blank characters are used as AND conditions"), true);
				// Search bar
				$search_bar = '<form method="get" style="display: inline;" name="quicksearch" action="">';
				if (!isset($config['search_keywords'])) {
					$search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
				}
				else {
					if (strlen($config['search_keywords']) == 0)
						$search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
					else
						$search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = false; </script>';
				}
				
				$search_bar .= '<input type="text" id="keywords" name="keywords"';
				if (!isset($config['search_keywords']))
					$search_bar .= "value='" . __("Enter keywords to search") . "'";
				else if (strlen($config['search_keywords']) == 0)
					$search_bar .= "value='" . __("Enter keywords to search") . "'";
				else
					$search_bar .= "value='" . $config['search_keywords'] . "'";
				
				$search_bar .= 'onfocus="javascript: if (fieldKeyWordEmpty) $(\'#keywords\').val(\'\');"
					onkeyup="javascript: fieldKeyWordEmpty = false;"
					style="margin-top:5px;" class="search_input" />';
				
				//$search_bar .= 'onClick="javascript: document.quicksearch.submit()"';
				
				$search_bar .= "<input type='hidden' name='head_search_keywords' value='abc' />";
				$search_bar .= '</form>';
				
				$table->data[0]['searchbar'] = $search_bar;
				
				// Servers check
				$servers = array();
				$servers["all"] = (int) db_get_value ('COUNT(id_server)','tserver');
				$servers["up"] = (int) servers_check_status ();
				$servers["down"] = $servers["all"] - $servers["up"];
				if ($servers["up"] == 0) {
					//All Servers down or no servers at all
					$servers_check_img = html_print_image("images/header_down.png", true, array("alt" => 'cross', "class" => 'bot', 'title' => __('All systems').': '.__('Down')));
				}
				elseif ($servers["down"] != 0) {
					//Some servers down
					$servers_check_img = html_print_image("images/header_warning.png", true, array("alt" => 'error', "class" => 'bot', 'title' => $servers["down"].' '.__('servers down')));
				}
				else {
					//All servers up
					$servers_check_img = html_print_image("images/header_ready.png", true, array("alt" => 'ok', "class" => 'bot', 'title' => __('All systems').': '.__('Ready')));
				}
				unset ($servers); // Since this is the header, we don't like to trickle down variables.
				
				$servers_link_open = '<a class="white" href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server&amp;refr=60">';
				$servers_link_close = '</a>';
				
				if ($config['show_qr_code_header'] == 0){
					$show_qr_code_header = 'display: none;';
				}
				else {
					$show_qr_code_header = 'display: inline;';
				}
				
				$table->data[0]['qr'] =
					'<div style="' . $show_qr_code_header . '" id="qr_code_container" style="">' .
					'<a href="javascript: show_dialog_qrcode();">' .
					html_print_image(
						"images/qrcode_icon.png",
						true,
						array("alt" => __('QR Code of the page'),
							'title' => __('QR Code of the page'))) .
					'</a>' .
					'</div>';
				
				echo "<div style='display: none;' id='qrcode_container' title='" . __('QR code of the page') . "'>";
				echo "<div id='qrcode_container_image'></div>";
				echo "</div>";
				?>
				<script type='text/javascript'>
					$(document).ready(function() {
						$( "#qrcode_container" ).dialog({
							autoOpen: false,
							modal: true
						});
					});
				</script>
				<?php
				
				if ($config['tutorial_mode'] !== 'expert') {
					$table->data[0]['clippy'] = 
						'<a href="javascript: show_clippy();">' .
							html_print_image(
								"images/clippy_icon.png",
								true,
								array("id" => 'clippy',
									"class" => 'clippy',
									"alt" => __('Pandora FMS assistant'),
									'title' => __('Pandora FMS assistant'))) .
						'</a>';
				}
				
				
				$table->data[0][0] = $servers_link_open .
					$servers_check_img . $servers_link_close;
				
				
				
				
				//======= Autorefresh code =============================
				$autorefresh_txt = '';
				$autorefresh_additional = '';
				
				$ignored_params = array ('agent_config' => false, 'code' => false);
				
				if (!isset($_GET['sec2'])) {
					$_GET['sec2'] = '';
				}
				if (!isset($_GET['refr'])) {
					$_GET['refr'] = null;
				}
				
				if ($config['autorefresh_white_list'] !== null && array_search($_GET['sec2'], $config['autorefresh_white_list']) !== false) {
					$autorefresh_img = html_print_image("images/header_refresh.png", true, array("class" => 'bot', "alt" => 'lightning', 'title' => __('Configure autorefresh')));
					
					if ($_GET['refr']) {
						$autorefresh_txt .= ' (<span id="refrcounter">'.date ("i:s", $config["refr"]).'</span>)';
					}
					
					$ignored_params['refr'] = '';
					$values = get_refresh_time_array();
					$autorefresh_additional = '<span id="combo_refr" style="display: none;">';
					$autorefresh_additional .= html_print_select ($values, 'ref', '', '', __('Select'), '0', true, false, false);
					$autorefresh_additional .= '</span>';
					unset ($values);
					
					$autorefresh_link_open_img =
						'<a class="white autorefresh" href="' . ui_get_url_refresh ($ignored_params) . '">';
					
					if ($_GET['refr']) {
						$autorefresh_link_open_txt =
							'<a class="white autorefresh autorefresh_txt" href="' . ui_get_url_refresh ($ignored_params) . '">';
					}
					else {
						$autorefresh_link_open_txt = '<a>';
					}
					
					$autorefresh_link_close = '</a>';
				}
				else {
					$autorefresh_img = html_print_image("images/header_refresh_disabled.png", true, array("class" => 'bot autorefresh_disabled', "alt" => 'lightning', 'title' => __('Disabled autorefresh')));
					
					$ignored_params['refr'] = false;
					
					$autorefresh_link_open_img = '';
					$autorefresh_link_open_txt = '';
					$autorefresh_link_close = '';
				}
				
				$table->data[0][1] = $autorefresh_link_open_img . $autorefresh_img . $autorefresh_link_close;
				$table->data[0][2] = $autorefresh_link_open_txt . $autorefresh_txt . $autorefresh_link_close . $autorefresh_additional;
				//======================================================
				
				
				
				$pandora_management = check_acl($config['id_user'], 0, "PM");
				
				if ($config["alert_cnt"] > 0) {
					echo '<div id="alert_messages" style="display: none"></div>';
					
					$maintenance_link = 'javascript:';
					$maintenance_title = __("System alerts detected - Please fix as soon as possible");
					$maintenance_class = $maintenance_id = 'show_systemalert_dialog white';
					
					$maintenance_link_open_txt = 
						'<a href="' . $maintenance_link . '" title="' . $maintenance_title . '" class="' . $maintenance_class . '" id="show_systemalert_dialog">';
					$maintenance_link_open_img = 
						'<a href="' . $maintenance_link . '" title="' . $maintenance_title . '" class="' . $maintenance_class . '">';
					$maintenance_link_close = '</a>';
					if (!$pandora_management) {
						$maintenance_img = '';
					}
					else {
						$maintenance_img = $maintenance_link_open_img .
							html_print_image("images/header_yellow.png",
								true, array(
									"title" => __('You have %d warning(s)',
									$config["alert_cnt"]),
									"id" => "yougotalert",
									"class" => "bot")) . $maintenance_link_close;
					}
				}
				else {
					if (!$pandora_management) {
						$maintenance_img = '';
					}
					else {
						$maintenance_img = html_print_image ("images/header_ready.png", true, array ("title" => __('There are not warnings'), "id" => "yougotalert", "class" => "bot"));
					}
				}
				
				$table->data[0][3] = $maintenance_img;
				
				// Main help icon
				$table->data[0][4] = '<a href="#" class="modalpopup" id="helpmodal">'.html_print_image("images/header_help.png",
					true, array(
						"title" => __('Main help'),
						"id" => "helpmodal",
						"class" => "modalpopup")).'</a>';
				
				// Logout
				$table->data[0][5] = '<a class="white" href="' . ui_get_full_url('index.php?bye=bye') . '">';
				$table->data[0][5] .= html_print_image("images/header_logout.png", true, array("alt" => __('Logout'), "class" => 'bot', "title" => __('Logout')));
				$table->data[0][5] .= '</a>';
				
				// User
				if (is_user_admin ($config["id_user"]) == 1)
					$table->data[0][6] = html_print_image("images/header_user_admin.png" , true, array("title" => __('Edit my user'), "class" => 'bot', "alt" => 'user'));
				else
					$table->data[0][6] = html_print_image("images/header_user.png" , true, array("title" => __('Edit my user'), "class" => 'bot', "alt" => 'user'));
				
				$table->data[0][6] = '<a href="index.php?sec=workspace&sec2=operation/users/user_edit">' . $table->data[0][6] . '</a>';
				
				$table->data[0][7] = '<a href="index.php?sec=workspace&amp;sec2=operation/users/user_edit" class="white_bold"> (' . $config["id_user"] . ')</a>';
				
				// Chat messages
				$table->data[0][8] = "<span id='icon_new_messages_chat' style='display: none;'>";
				$table->data[0][8] .= "<a href='index.php?sec=workspace&sec2=operation/users/webchat'>";
				$table->data[0][8] .= html_print_image('images/header_chat.png', true, array("title" => __('New chat message')));
				$table->data[0][8] .= "</a>";
				$table->data[0][8] .= "</span>";
				
				// Messages
				$msg_cnt = messages_get_count ($config["id_user"]);
				if ($msg_cnt > 0) {
					echo '<div id="dialog_messages" style="display: none"></div>';
					
					$table->data[0][9] = '<a href="ajax.php?page=operation/messages/message_list" title="' . __("Message overview") . '" id="show_messages_dialog">';
					$table->data[0][9] .= html_print_image ("images/header_email.png", true, array ("title" => __('You have %d unread message(s)', $msg_cnt), "id" => "yougotmail", "class" => "bot", 'style' => 'width:24px;'));
					$table->data[0][9] .= '</a>';
				}



				html_print_table($table);
				
				unset($table);
			?>
		</td>
		<!--
		<td style="text-align:center">
			<?php
				echo "<a href='index.php?sec=main'>";
				if (isset($config["custom_logo"])) {
					echo html_print_image("images/custom_logo/" . $config["custom_logo"], true, array("height" => '60', "width" => '139', "alt" => 'Logo'));
				}
				echo "</a>";
			?>
		</td>
		-->
	</tr>
</table>

<script type="text/javascript">
	/* <![CDATA[ */
	
	<?php
	$config_fixed_header = false;
	if (isset($config['fixed_header'])) {
		$config_fixed_header = $config['fixed_header'];
	}
	?>
	
	var fixed_header = <?php echo json_encode((bool)$config_fixed_header); ?>;
	
	var new_chat = <?php echo (int)$_SESSION['new_chat'];?>;
	$(document).ready (function () {
		
		if (fixed_header) {
			$('div#head').addClass('fixed_header');
			$('div#page')
				.css('padding-top', $('div#head').innerHeight() + 'px')
				.css('position', 'relative');
		}
		
		check_new_chats_icon('icon_new_messages_chat');
		
		/* Temporal fix to hide graphics when ui_dialog are displayed */
		$("#yougotalert").click(function () { 
			$("#agent_access").css("display", "none");
		});
		$("#ui_close_dialog_titlebar").click(function () {
			$("#agent_access").css("display","");
		});
		
		function blinkmail(){
			$("#yougotmail").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkmail);
		}
		function blinkalert(){
			$("#yougotalert").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkalert);
		}
		function blinkpubli(){
			$(".publienterprise").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkpubli);
		}
		<?php
		if ($msg_cnt > 0) {
		?>
			blinkmail();
		<?php
		}
		?>
		
		
		<?php
		if ($config["alert_cnt"] > 0) {
		?>
			blinkalert();
		<?php
		}
		?>
			blinkpubli();

		<?php
		if ($_GET["refr"]) {
			$_get_refr = strip_tags($_GET["refr"]);
		?>
			refr_time = parseInt("<?php echo $_get_refr; ?>");
			if (isNaN(refr_time)) {
				refr_time = 0;
			}
			
			t = new Date();
			t.setTime (t.getTime () +
				parseInt(<?php echo $config["refr"] * 1000; ?>));
			$("#refrcounter").countdown ({until: t, 
				layout: '%M%nn%M:%S%nn%S',
				labels: ['', '', '', '', '', '', ''],
				onExpiry: function () {
						href = $("a.autorefresh").attr ("href");
						href = href + refr_time;
						$(document).attr ("location", href);
					}
				});
		<?php
		}
		?>
		
		$("a.autorefresh").click (function () {
			$("a.autorefresh_txt").toggle ();
			$("#combo_refr").toggle ();
			$("#combo_refr").css('padding-right', '9px');
			$("select#ref").change (function () {
				href = $("a.autorefresh").attr ("href");
				$(document).attr ("location", href + this.value);
			});
			
			return false;
		});
	});
/* ]]> */
</script>
