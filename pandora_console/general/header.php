<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require_once ("include/functions_messages.php");

//First column (logo)
echo '<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px;" border="0"><tr><td>';

echo '<div id="pandora_logo_header"></div>';

// First column (identifier)
echo '<td width="20%"><img src="images/user_'.((is_user_admin ($config["id_user"]) == 1) ? 'suit' : 'green' ).'.png" class="bot" alt="user" />&nbsp;'.'<a href="index.php?sec=usuarios&amp;sec2=operation/users/user_edit" class="white">'.__('You are').' [<b>'.$config["id_user"].'</b>]</a> ';
$msg_cnt = get_message_count ($config["id_user"]);
if ($msg_cnt > 0) {
	echo '<div id="dialog_messages" style="display: none"></div>';
	
	require_css_file ('dialog');
	require_jquery_file ('ui.core');
	require_jquery_file ('ui.dialog');
	echo '<a href="ajax.php?page=operation/messages/message" id="show_messages_dialog">';
	print_image ("images/email.png", false,
		array ("title" => __('You have %d unread message(s)', $msg_cnt),
			"id" => "yougotmail",
			"class" => "bot"));
	echo '</a>';
}

//First column, second row (logout button)
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?bye=bye"><img src="images/log-out.png" alt="logout" class="bot" />&nbsp;'. __('Logout').'</a>';

// Second column (link to main page)
echo '</td><td width="20%">';
echo '<a class="white_bold" href="index.php?sec=main"><img src="images/information.png" alt="info" class="bot" />&nbsp;'.__('General information').'</a>';

//Second column, second row (System up/down)
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60">';
$servers["all"] = (int) get_db_value ('COUNT(id_server)','tserver');
$servers["up"] = (int) check_server_status ();
$servers["down"] = $servers["all"] - $servers["up"];
if ($servers["up"] == 0) {
	//All Servers down or no servers at all
	echo '<img src="images/cross.png" alt="cross" class="bot" />&nbsp;'.__('All systems').': '.__('Down');
} elseif ($servers["down"] != 0) {
	//Some servers down
	echo '<img src="images/error.png" alt="error" class="bot" />&nbsp;'.$servers["down"].' '.__('servers down');
} else {
	//All servers up
    	echo '<img src="images/ok.png" alt="ok" class="bot" />&nbsp;'.__('All systems').': '.__('Ready');
}
unset ($servers); // Since this is the header, we don't like to trickle down variables. 
echo "</a>";


// Third column
// Autorefresh
echo '</td><td width="20%">';
$ignored_params = array ('agent_config' => false, 'code' => false);
if ($config["refr"]) {
	$ignored_params['refr'] = 0;
	echo '<a id="autorefresh" class="white_grey_bold" href="'.get_url_refresh ($ignored_params).'"><img src="images/page_refresh.png" class="bot" alt="lightning" />&nbsp;'. __('Autorefresh');
	echo ' (<span id="refrcounter">'.date ("i:s", $config["refr"]).'</span>)';
	echo '</a>';
} else {	
	$ignored_params['refr'] = '';
	echo '<a id="autorefresh" class="white_bold" href="'.get_url_refresh ($ignored_params).'"><img src="images/page_refresh.png" class="bot" alt="lightning" />&nbsp;'.__('Autorefresh').'</a>';
	$values = array ('5' => '5 '.__('seconds'),
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
	print_select ($values, 'ref', '', '', __('Select'), '0', false, false, false);
	unset ($values);
	echo '</span>';
}

//Events
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr=5"><img src="images/lightning_go.png" alt="lightning_go" class="bot" />&nbsp;'.__('Events').'</a>';

// Styled text
echo '</td><td width="20%"><div id="head_r"><span id="logo_text1">Pandora</span> <span id="logo_text2">FMS</span></div>';

/* Enterprise support */
if (file_exists (ENTERPRISE_DIR."/load_enterprise.php")) 
	echo '<div id="logo_text3">Enterprise</div>';
else
	echo '<div id="logo_text3">OpenSource</div>';
	
echo '</td></tr></table>';

require_jquery_file ('countdown');
?>

<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
<?php if ($msg_cnt > 0): ?>
	$("#yougotmail").pulsate ();
<?php endif; ?>
<?php if ($config["refr"]): ?>
	t = new Date();
	t.setTime (t.getTime () + <?php echo $config["refr"] * 1000; ?>);
	$("#refrcounter").countdown ({until: t, 
		layout: '%M%nn%M:%S%nn%S',
		labels: ['', '', '', '', '', '', ''],
		onExpiry: function () {
				$(this).text ("...");
			}
		});
<?php else: ?>
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
<?php endif; ?>
});
/* ]]> */
</script>
