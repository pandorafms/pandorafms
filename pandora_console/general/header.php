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


$msg_cnt = 0;
$alert_cnt = 0;
$config["alert_cnt"] = 0;
$_SESSION["alert_msg"] = "";

// Check permissions

// Global errors/warnings checking.
config_check();

?>
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px; margin-top: 0px; height: 100%" border="0">
	<tr>
		<td style="width:90%;">
			<a href="index.php?sec=main">
				<?php
				if (!defined ('PANDORA_ENTERPRISE')) {
					echo html_print_image('images/pandora_header_logo.png', true, array("alt" => 'Pandora FMS Opensource', "border" => '0'));
				}
				else {
					echo html_print_image('images/pandora_header_logo_enterprise.png', true, array("alt" => 'Pandora FMS Enterprise', "border" => '0'));
				}
				?>
			</a>
		</td>
		<td style="min-width:200px;">
			<?php
				$table->class = "none";
				$table->cellpadding = 0;
				$table->cellspacing = 0;
				$table->head = array ();
				$table->data = array ();
				$table->style[0] = $table->style[1] = $table->style[3] = $table->style[4] = $table->style[5] = $table->style[6] = $table->style[9] = 'width: 22px; text-align:center; height: 22px; padding-right: 9px;';
				$table->style[7] = 'width: 20px; padding-right: 9px;';
				$table->style['searchbar'] = 'width: 180px; min-width: 180px;';
				$table->style[11] = 'padding-left: 10px; padding-right: 5px;width: 16px;';
				$table->width = "100%";
				$table->styleTable = 'margin: auto; margin-top: 0px;';
				$table->rowclass[0] = '';

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
				
				$table->data[0][0] = $servers_link_open . $servers_check_img . $servers_link_close;
				
				// Autorefresh
				$autorefresh_img = html_print_image("images/header_refresh.png", true, array("class" => 'bot', "alt" => 'lightning', 'title' => __('Configure autorefresh')));
				$autorefresh_txt = '';
				$autorefresh_additional = '';
				
				$ignored_params = array ('agent_config' => false, 'code' => false);
				if ($config['enable_refr']) {
					$ignored_params['refr'] = 0;
					$autorefresh_txt .= ' (<span id="refrcounter">'.date ("i:s", $config["refr"]).'</span>)';
				}
				else {
					if (!isset($_GET['sec2'])) 
						$_GET['sec2'] = '';
					
					if (($config['refr']) && (($_GET['sec2'] == 'operation/agentes/tactical') || ($_GET['sec2'] == 'operation/agentes/estado_agente') ||
						($_GET['sec2'] == 'operation/agentes/group_view') || ($_GET['sec2'] == 'operation/events/events') || 
						($_GET['sec2'] == 'enterprise/dashboard/main_dashboard'))) {
						
							$autorefresh_txt .= ' (<span id="refrcounter">'.date ("i:s", $config["refr"]).'</span>)';
					}
					else {
						$ignored_params['refr'] = '';
						$values = array (
							'5' => __('5 seconds'),
							'10' => __('10 seconds'),
							'15' => __('15 seconds'),
							'30' => __('30 seconds'),
							(string)SECONDS_1MINUTE => __('1 minute'),
							(string)SECONDS_2MINUTES => __('2 minutes'),
							(string)SECONDS_5MINUTES => __('5 minutes'),
							(string)SECONDS_15MINUTES => __('15 minutes'),
							(string)SECONDS_30MINUTES => __('30 minutes'),
							(string)SECONDS_1HOUR => __('1 hour'));
						$autorefresh_additional = '<span id="combo_refr" style="display: none">';
						$autorefresh_additional .= html_print_select ($values, 'ref', '', '', __('Select'), '0', true, false, false);
						$autorefresh_additional .= '</span>';
						unset ($values);
					}
				}
			
				$autorefresh_link_open_img = '<a class="white autorefresh" href="' . ui_get_url_refresh ($ignored_params) . '">'; 
				$autorefresh_link_open_txt = '<a class="white autorefresh autorefresh_txt" href="' . ui_get_url_refresh ($ignored_params) . '">'; 
				$autorefresh_link_close = '</a>';
				
				$table->data[0][1] = $autorefresh_link_open_img . $autorefresh_img . $autorefresh_link_close;
				$table->data[0][2] .= $autorefresh_link_open_txt . $autorefresh_txt . $autorefresh_link_close . $autorefresh_additional;
				
				if ($config["alert_cnt"] > 0) {
					echo '<div id="alert_messages" style="display: none"></div>';
					ui_require_css_file ('dialog');
					
					$maintenance_link = 'javascript:';
					$maintenance_title = __("System alerts detected - Please fix as soon as possible");
					$maintenance_class = $maintenance_id = 'show_systemalert_dialog white';
					
					$maintenance_link_open_txt =  '<a href="' . $maintenance_link . '" title="' . $maintenance_title . '" class="' . $maintenance_class . '" id="show_systemalert_dialog">'; 
					$maintenance_link_open_img =  '<a href="' . $maintenance_link . '" title="' . $maintenance_title . '" class="' . $maintenance_class . '">'; 
					$maintenance_link_close =  '</a>'; 
					$maintenance_img = $maintenance_link_open_img . html_print_image ("images/header_warning.png", true, array ("title" => __('You have %d warning(s)', $config["alert_cnt"]), "id" => "yougotalert", "class" => "bot")) . $maintenance_link_close;
					$maintenance_txt = $maintenance_link_open_txt . $maintenance_txt . $maintenance_link_close;
				}
				else {
					$maintenance_img = html_print_image ("images/header_ready.png", true, array ("title" => __('There are not warnings'), "id" => "yougotalert", "class" => "bot"));
				}
			
				$table->data[0][3] = $maintenance_img;
				
				// Main help icon
				$table->data[0][4] = ui_print_help_icon ("main_help", true, '', 'images/header_help.png');
				
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
				$table->data[0][8] .= html_print_image('images/header_chat.png', true, array('style' => 'width:22px;', "title" => __('New chat message')));
				$table->data[0][8] .= "</a>";
				$table->data[0][8] .= "</span>";
				
				// Messages
				$msg_cnt = messages_get_count ($config["id_user"]);
				if ($msg_cnt > 0) {
					echo '<div id="dialog_messages" style="display: none"></div>';
					ui_require_css_file ('dialog');
					
					$table->data[0][9] = '<a href="ajax.php?page=operation/messages/message_list" title="' . __("Message overview") . '" id="show_messages_dialog">';
					$table->data[0][9] .= html_print_image ("images/header_email.png", true, array ("title" => __('You have %d unread message(s)', $msg_cnt), "id" => "yougotmail", "class" => "bot", 'style' => 'width:24px;'));
					$table->data[0][9] .= '</a>';
				}
				
				// Search bar
				$search_bar = '<form method="get" style="display: inline;" name="quicksearch" action="">';
				$search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
				$search_bar .= '<input type="text" id="keywords" name="keywords"';
				if (!isset($config['search_keywords']))
					$search_bar .= "value='" . __("Enter keywords to search") . "'";
				else if (strlen($config['search_keywords']) == 0)
					$search_bar .= "value='" . __("Enter keywords to search") . "'";
				else
					$search_bar .= "value='" . $config['search_keywords'] . "'";
					
				$search_bar .= 'onfocus="javascript: if (fieldKeyWordEmpty) $(\'#keywords\').val(\'\');"
						style="margin-top:5px;" class="search_input" />';
						
				//$search_bar .= 'onClick="javascript: document.quicksearch.submit()"';
				
				$search_bar .= "<input type='hidden' name='head_search_keywords' value='abc' />";
				$search_bar .= '</form>';
				
				$table->data[0]['searchbar'] = $search_bar;
				
				$table->data[0][11] = ui_print_help_tip (__("Blank characters are used as AND conditions"), true);
				
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
	var new_chat = <?php echo (int)$_SESSION['new_chat'];?>;
	$(document).ready (function () {
		check_new_chats_icon('icon_new_messages_chat');
		
		/* Temporal fix to hide graphics when ui_dialog are displayed */
		$("#yougotalert").click(function () { 
			$("#agent_access").css("display", "none");	
		});
		$("#ui_close_dialog_titlebar").click(function () {
			$("#agent_access").css("display","");
		});
		
		
		<?php
		if ($msg_cnt > 0) {
		?>
			$("#yougotmail").pulsate ();
		<?php
		}
		?>
		
		
		<?php
		if ($config["alert_cnt"] > 0) {
		?>
			$("#yougotalert").pulsate ();
		<?php
		}
		?>
		
		
		<?php
		if ($config["refr"]) {
		?>
			t = new Date();
			t.setTime (t.getTime () + <?php echo $config["refr"] * 1000; ?>);
			$("#refrcounter").countdown ({until: t, 
				layout: '%M%nn%M:%S%nn%S',
				labels: ['', '', '', '', '', '', ''],
				onExpiry: function () {
						$(this).text ("...");
					}
				});
		<?php
		}
		else {
		?>
			$("a.autorefresh").click (function () {
				$("a.autorefresh_txt").toggle ();
				$("#combo_refr").toggle ();
				$("select#ref").change (function () {
					href = $(this).attr ("href");
					$(document).attr ("location", href + this.value);
				});
				
				return false;
			});
		<?php
		}
		?>
	});
/* ]]> */
</script>
