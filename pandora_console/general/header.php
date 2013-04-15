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
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px; margin-top: 5px;" border="0">
	<tr>
		<td rowspan="2">
			<a href="index.php?sec=main">
				<?php
				if (!defined ('PANDORA_ENTERPRISE')){
					echo html_print_image('images/pandora_header_logo.png', true, array("alt" => 'Pandora FMS Opensource', "border" => '0'));
				} else {
					echo html_print_image('images/pandora_header_logo_enterprise.png', true, array("alt" => 'Pandora FMS Enterprise', "border" => '0'));
				}
				?>
			</a>
		</td>
		<td width="20%">
			<?php 
				if (is_user_admin ($config["id_user"]) == 1)
					html_print_image("images/user_suit.png" , false, array("class" => 'bot', "alt" => 'user'));
				else
					html_print_image("images/user_green.png" , false, array("class" => 'bot', "alt" => 'user'));
			?>
			<a href="index.php?sec=workspace&amp;sec2=operation/users/user_edit" class="white"> [<b><?php echo $config["id_user"];?></b>]</a>
			<?php
			
			if ($config["metaconsole"] == 0) {
				$msg_cnt = messages_get_count ($config["id_user"]);
				if ($msg_cnt > 0) {
					echo '<div id="dialog_messages" style="display: none"></div>';
					ui_require_css_file ('dialog');
					ui_require_jquery_file ('ui.core');
					ui_require_jquery_file ('ui.dialog');
					echo '<a href="ajax.php?page=operation/messages/message_list" title="' . __("Message overview") . '" id="show_messages_dialog">';
					html_print_image ("images/email.png", false,
					array ("title" => __('You have %d unread message(s)', $msg_cnt), "id" => "yougotmail", "class" => "bot"));
					echo '</a>';
					echo "&nbsp;";
					echo "&nbsp;";
				}
			}
			
			echo "<span id='icon_new_messages_chat' style='display: none;'>";
			echo "<a href='index.php?sec=workspace&sec2=operation/users/webchat'>";
			html_print_image('images/comments.png');
			echo "</a>";
			echo "</span>";
			
			if ($config["alert_cnt"] > 0) {
				echo '<div id="alert_messages" style="display: none"></div>';
				ui_require_css_file ('dialog');
				ui_require_jquery_file ('ui.core');
				ui_require_jquery_file ('ui.dialog');
				
				echo '<a href="ajax.php?page=operation/system_alert" title="'.__("System alerts detected - Please fix as soon as possible").'" id="show_systemalert_dialog">'; 
				html_print_image ("images/error.png", false,
					array ("title" => __('You have %d warning(s)', $config["alert_cnt"]), "id" => "yougotalert", "class" => "bot"));
				echo '</a>';
				echo "&nbsp;";
				echo "&nbsp;";
			}
			
			echo '<a class="white_bold" href="index.php?bye=bye">';
			html_print_image("images/log-out.png", false, array("alt" => __('Logout'), "class" => 'bot', "title" => __('Logout')));
			echo '</a>';
			
			// Main help icon
			echo "&nbsp;";
			echo "&nbsp;";
			echo ui_print_help_icon ("main_help", true);
			if ($config['metaconsole'] == 1) {
				echo "&nbsp;";
				echo "&nbsp;";
				html_print_image("images/application_double.png", false, array("alt" => __('Metaconsole activated'), "class" => 'bot', "title" => __('You are using metaconsole')));
			}
			
			echo '</td>';
		echo '<td width="20%">';
			
			if ($config["metaconsole"] == 0){
				echo '<a class="white_bold" href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server&amp;refr=60">';
				
				$servers["all"] = (int) db_get_value ('COUNT(id_server)','tserver');
				$servers["up"] = (int) servers_check_status ();
				$servers["down"] = $servers["all"] - $servers["up"];
				if ($servers["up"] == 0) {
					//All Servers down or no servers at all
					echo html_print_image("images/cross.png", true, array("alt" => 'cross', "class" => 'bot')) . '&nbsp;'.__('All systems').': '.__('Down');
				}
				elseif ($servers["down"] != 0) {
					//Some servers down
					echo html_print_image("images/error.png", true, array("alt" => 'error', "class" => 'bot')) . '&nbsp;'.$servers["down"].' '.__('servers down');
				}
				else {
					//All servers up
					echo html_print_image("images/ok.png", true, array("alt" => 'ok', "class" => 'bot')) . '&nbsp;'.__('All systems').': '.__('Ready');
				}
				unset ($servers); // Since this is the header, we don't like to trickle down variables.
				echo '</a>';
			}
		
		?>
		</td>
		<td width="20%">
			<?php
			// Autorefresh
			if (!isset($_GET['refr'])) {
				$_GET['refr'] = null;
			}
			
			$ignored_params = array ('agent_config' => false, 'code' => false);
			if ($_GET['refr']) {
				$ignored_params['refr'] = 0;
				echo '<a id="autorefresh" class="white_bold" href="' . ui_get_url_refresh ($ignored_params).'">' . html_print_image("images/page_refresh.png", true, array("class" => 'bot', "alt" => 'lightning')) . '&nbsp;'. __('Autorefresh'); 
				echo ' (<span id="refrcounter">'.date ("i:s", $_GET['refr']).'</span>)';
				echo '</a>';
			}
			else {
				$ignored_params['refr'] = '';
				echo '<a id="autorefresh" class="white_bold" href="' . ui_get_url_refresh ($ignored_params).'">' . html_print_image("images/page_refresh.png", true, array("class" => 'bot', "alt" => 'lightning')) . '&nbsp;'. __('Autorefresh').'</a>'; 
				$values = array (
					'5' => '5 '.__('seconds'),
					'10' => '10 '.__('seconds'),
					'15' => '15 '.__('seconds'),
					'30' => '30 '.__('seconds'),
					'60' => '1 '.__('minute'),
					'120' => '2 '.__('minutes'),
					'300' => '5 '.__('minutes'),
					'900' => '15 '.__('minutes'),
					'1800' => '30 '.__('minutes'),
					'3600' => '1 '.__('hour'));
				echo '<span id="combo_refr" style="display: none">';
				html_print_select ($values, 'ref', '', '', __('Select'), '0', false, false, false);
				unset ($values);
				echo '</span>';
			}
		echo "</td>";
		echo "<td width='20%' rowspan='2'>";
		echo "<a href='index.php?sec=main'>";
		if (isset($config["custom_logo"]))
			echo html_print_image("images/custom_logo/" . $config["custom_logo"], true,array("height" => '60', "width" => '139', "alt" => 'Logo'));
		echo "</a>";
		?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		
		<?php
		if ($config["metaconsole"] == 0) {
		?>
		<form method="get" style="" name="quicksearch" action="">
			<script type="text/javascript">
			var fieldKeyWordEmpty = true;
			</script>
			<input type="text" id="keywords" name="keywords"
				<?php
				if (!isset($config['search_keywords']))
					echo "value='" . __("Enter keywords to search") . "'";
				else if (strlen($config['search_keywords']) == 0)
					echo "value='" . __("Enter keywords to search") . "'";
				else echo "value='" . $config['search_keywords'] . "'";
				?>
				onfocus="javascript: if (fieldKeyWordEmpty) $('#keywords').val('');"
				size="100" style="background: white url('images/lupa_15x15.png') no-repeat right; padding: 0; padding-left:0px; margin: 0; width: 90%; height: 19px; margin-bottom: 5px; margin-left: 2px;" />
			<!-- onClick="javascript: document.quicksearch.submit()" -->
			<input type='hidden' name='head_search_keywords' value='abc' />
			<?php
				ui_print_help_tip (__("Blank characters are used as AND conditions"));
			?>
		</form>
		<?php
		}
		?>
		</td>
		<td>
		<?php
		if ($config["metaconsole"] == 0) {
			echo '<a class="white_bold" href="index.php?sec=eventos&amp;sec2=operation/events/events">' . html_print_image("images/lightning_go.png", true, array("alt" => 'lightning_go', "class" => 'bot')) . '&nbsp;'.__('Events').'</a>';
		}
		?>
		</td>
	</tr>
</table>
<?php
	ui_require_jquery_file ('countdown');
?>

<script type="text/javascript" src="include/javascript/jquery.ui.dialog.js "></script>	
<script type="text/javascript" src="include/javascript/jquery.ui.draggable.js "></script>	
<script type="text/javascript" src="include/javascript/jquery.ui.droppable.js "></script>		
<script type="text/javascript" src="include/javascript/jquery.ui.resizable.js "></script>	
<script type="text/javascript" src="include/javascript/webchat.js "></script>	

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
		if ($_GET['refr']) {
		?>
			t = new Date();
			t.setTime (t.getTime () + <?php echo $_GET['refr'] * 1000; ?>);
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
			$("a#autorefresh").click (function () {
				var a = this;
				
				$(this).hide ().unbind ("click");
				$("#combo_refr").show ();
				$("select#ref").change (function () {
					href = $(a).attr ("href");
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
