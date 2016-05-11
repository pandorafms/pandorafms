<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Real start

require_once ("../../include/config.php");

// Set root on homedir, as defined in setup
chdir ($config["homedir"]);

session_start ();
ob_start ();
/* Enterprise support */
if (file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
	include_once (ENTERPRISE_DIR . "/load_enterprise.php");
}

if (file_exists (ENTERPRISE_DIR . "/include/functions_login.php")) {
	include_once (ENTERPRISE_DIR . "/include/functions_login.php");
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');
// Enterprise main
enterprise_include ('index.php');

require ('include/functions_visual_map.php');

$hash = get_parameter ('hash');
$id_layout = (int) get_parameter ('id_layout');
$graph_javascript = (bool) get_parameter('graph_javascript');
$config["id_user"] = get_parameter ('id_user');

$myhash = md5($config["dbpass"].$id_layout. $config["id_user"]);

// Check input hash
if ($myhash != $hash) {
	exit;
}

$refr = (int) get_parameter ('refr', 0);
$layout = db_get_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ($config['homedir'] . "/general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

if (!isset($config['pure']))
	$config['pure'] = 0;

// Render map
$options = array();
echo '<div style="width: 95%; background: white; margin: 20px auto 20px auto; box-shadow: 10px 10px 5px #000;">';
echo "<h1>" . $layout_name . "</h1>";

visual_map_print_visual_map ($id_layout, true, true, null, null, '../../', true, $graph_javascript);

$values = array ();
$values[5] = human_time_description_raw (5);
$values[30] = human_time_description_raw (30);
$values[SECONDS_1MINUTE] = human_time_description_raw(SECONDS_1MINUTE);
$values[SECONDS_2MINUTES] = human_time_description_raw(SECONDS_2MINUTES);
$values[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
$values[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
$values[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);

$table->width = '90%';
$table->data = array ();
$table->style = array ();
$table->style[2] = 'text-align: center';
$table->data[0][0] = __('Autorefresh time');

$table->data[0][1] = html_print_select ($values, 'refr', $refr, '', 'N/A', 0, true, false, false);
$table->data[0][2] = html_print_submit_button (__('Refresh'), '', false, 'class="sub next"', true);
$table->data[0][2] .= html_print_input_hidden ('vc_refr', $config["vc_refr"], true);
$table->data[0][3] .= '<a href="javascript: show_dialog_qrcode();">' .
	'<img src="../../images/qrcode_icon.jpg"/>' .
	'</a>';

echo '<div style="height:30px">&nbsp;</div>';

if ($refr > 0) {
	echo '<div id="countdown"><br /></div>';
}

echo '<div style="height:30px">&nbsp;</div>';

echo "<div style='display: none;' id='qrcode_container' title='" . __('QR code of the page') . "'>";
echo "<div id='qrcode_container_image'></div>";
echo "</div>";

echo '<form method="post">';
html_print_input_hidden ('pure', $config["pure"]);
html_print_input_hidden ('id', $id_layout);
html_print_table ($table);
echo '</form>';
echo '</div>';


ui_require_jquery_file ('countdown');
ui_require_css_file ('countdown');



ui_require_javascript_file ('wz_jsgraphics');
ui_require_javascript_file ('pandora_visual_console');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$(document).ready(function() {
		$( "#qrcode_container" ).dialog({
			autoOpen: false,
			modal: true
		});
	});
	
	$("#refr").change(function () {
		$("#hidden-vc_refr").val($("#refr option:selected").val());
	});
	
	<?php
	if ($refr > 0) {
	?>
		t = new Date();
		t.setTime (t.getTime() + <?php echo $refr * 1000; ?>);
		$("#countdown").countdown(
			{
				until: t,
				format: 'MS',
				description: '<?php echo __('Until refresh'); ?>',
				onExpiry: function () {
						href = "<?php echo ui_get_full_url();?>";
						$exists_refr = href.indexOf("refr");
						if ($exists_refr == -1) {
							href = href + "&refr=<?php echo $refr;?>";
						}
						$(document).attr ("location", href);
					}
			}
		);
	
	<?php
	}
	?>
});

/* ]]> */
</script>
