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

//First column (logo)
echo '<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px;" border="0"><tr><td>';

/* CUSTOM BRANDING STARTS HERE */
/* TODO: Put the branding in it's own file, variables or database 
 Yes, put here your corporate logo instead pandora_logo_head.png
 The style specifies width and height so that oversized images get resized.
 Optimally your logo would be this size.
*/
echo '<a href="index.php"><img src="images/pandora_logo_head.png" alt="logo" style="border-width:0px; width:140px; height:60px;" /></a>';
/* CUSTOM BRANDING ENDS HERE */


// Margin to logo
echo '</td><td width="20">&nbsp;</td>';

// First column (identifier)
echo '<td width="20%"><img src="images/user_'.((dame_admin ($_SESSION["id_usuario"]) == 1) ? 'suit' : 'green' ).'.png" class="bot">&nbsp;'.'<a class="white">'.__('You are').' [<b>'.$_SESSION["id_usuario"].'</b>]</a>';

//First column, second row (logout button)
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?bye=bye"><img src="images/lock.png" class="bot">&nbsp;'. __('Logout').'</a>';

// Second column (link to main page)
echo '</td><td width="20%">';
echo '<a class="white_bold" href="index.php?sec=main"><img src="images/information.png" class="bot">&nbsp;'.__('General information').'</a>';

//Second column, second row (System up/down)
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60">';
$servers["all"] = (int) get_db_value ('COUNT(id_server)','tserver');
$servers["up"] = (int) check_server_status ();
$servers["down"] = $servers["all"] - $servers["up"];
if ($servers["up"] == 0) {
	//All Servers down or no servers at all
	echo '<img src="images/cross.png" class="bot" />&nbsp;'.__('All systems').': '.__('Down');
} elseif ($servers["down"] != 0) {
	//Some servers down
	echo '<img src="images/error.png" class="bot" />&nbsp;'.$servers["down"].' '.__('servers down');
} else {
	//All servers up
    	echo '<img src="images/ok.png" class="bot" />&nbsp;'.__('All systems').': '.__('Ready');
}
unset ($servers); // Since this is the header, we don't like to trickle down variables. 
echo "</a>";


// Third column
// Autorefresh
echo '</td><td width="20%">';
$refr = (int) get_parameter ("refr");
if ($refr) {
	echo '<a id="autorefresh" class="white_grey_bold" href="'.((substr ($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : 'index.php?' ).'&refr=0"><img src="images/page_lightning.png" class="bot" />&nbsp;'. __('Autorefresh');
	echo ' (<span id="refr">'.date ("i:s", $refr).'</span>)';
	echo '</a>';
} else {
	echo '<a id="autorefresh" class="white_bold" href="'.((substr ($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : "index.php?" ).(count($_GET)?"&":"?").'refr="><img src="images/page_lightning.png" class="bot" />&nbsp;'.__('Autorefresh').'</a>';
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
	echo '</span>';
}

//Events
echo '<br /><br />';
echo '<a class="white_bold" href="index.php?sec=eventos&sec2=operation/events/events&refr=5"><img src="images/lightning_go.png" class="bot" />&nbsp;'.__('Events').'</a>';

// Styled text
echo '</td><td width="20%"><div id="head_r"><span id="logo_text1">Pandora</span> <span id="logo_text2">FMS</span></div></td></tr></table>';
?>

<script type="text/javascript" src="include/javascript/jquery.countdown.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
<?php if ($refr): ?>
	t = new Date();
	t.setTime (t.getTime () + <?php echo $refr * 1000; ?>);
	$("#refr").countdown ({until: t, 
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
</script>
