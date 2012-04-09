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

require_once('lib/functions.php');

if (is_ajax ()) {
	global $config;
	
	check_login ();
	
	if (! check_acl ($config["id_user"], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access event viewer");
		require ("general/noaccess.php");
		return;
	}
	
	require_once('lib/functions.ajax.php');
	
	$get_packages_online = (bool) get_parameter('get_packages_online');
	$download_package = (bool) get_parameter('download_package');
	$check_download_package = (bool) get_parameter('check_download_package');
	$install_package = (bool) get_parameter('install_package');
	$check_install_package = (bool) get_parameter('check_install_package');
	
	if ($get_packages_online)
		update_pandora_get_packages_online_ajax();
	if ($download_package)
		update_pandora_download_package();
	if ($check_download_package)
		update_pandora_check_download_package();
	if ($install_package)
		update_pandora_install_package();
	if ($check_install_package)
		update_pandora_check_install_package();
	
	return;
}

function update_pandora_administration() {
	global $config;
	global $conf_update_pandora;
	
	check_login ();
	
	if (! check_acl ($config["id_user"], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access event viewer");
		require ("general/noaccess.php");
		return;
	}
	
	if (!update_pandora_check_installation()) {
		ui_print_error_message(__('First execution of Update Pandora'));
		update_pandora_installation();
	}
	
	$conf_update_pandora = update_pandora_get_conf();
	
	echo "<h3>" . __('Downloaded Packages') . "</h3>";
	$list_downloaded_packages = update_pandora_get_list_downloaded_packages('administration');
	$table = null;
	$table->width = '80%';
	$table->size = array('80%', '50px');
	$table->head = array(__('Packages'), __('Action'));
	$table->align = array('left', 'center');
	$table->data = array();
	foreach ($list_downloaded_packages as $package) {
		$actions = '';
		if (!isset($package['empty'])) {
			if (!$package['current']) {
				$actions = '<a href="javascript: ajax_start_install_package(\'' . $package['name'] . '\');">' .
					html_print_image('images/b_white.png', true, array('alt'=>
						__('Install this version'), 'title' => __('Install this version'))) .
					'</a>';
			}
			else {
				$actions = '<a href="javascript: ajax_start_install_package(\'' . $package['name'] . '\');">' .
					html_print_image('images/b_yellow.png', true, array('alt'=>
						__('Reinstall this version'), 'title' => __('Reinstall this version'))) .
					'</a>';
			}
		}
		$table->data[] = array($package['name'], $actions);
	}
	html_print_table($table);
	
	echo "<h3>" . __('Online Package') . "</h3>";
	
	echo '<table id="online_packages" class="databox" width="80%" cellspacing="4" cellpadding="4" border="0" style="">';
	echo '<thead><tr>
			<th class="header c0" scope="col">' . __('Package') . '</th>
			<th class="header c1" scope="col">' . __('Action') . '</th>
		</tr></thead>';
	echo '<tbody>
			<tr id="online_packages-0" class="spinner_row" style="">
				<td id="online_packages-0-0" style=" text-align:left; width:80%;">' .
				__('Get list online Package') . " " . html_print_image('images/spinner.gif', true) . 
				'</td>
				<td id="online_packages-0-1" style=" text-align:center; width:50px;"></td>
			</tr>
		</tbody>';
	echo '</table>';
	
	
	?>
	<div id="dialog_download" title="<?php echo __('Process packge'); ?>"
		style="display:none; -ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)'; filter: alpha(opacity=50);">
		<div style="position:absolute; top:20%; text-align: center; left:0%; right:0%; width:600px;">
			<?php
			echo '<h2 id="title_downloading_update_pandora">' . __('Downloading <span class="package_name">package</span> in progress') . " ";
			html_print_image('images/spinner.gif');
			echo '</h2>';
			echo '<h2 style="display: none;" id="title_installing_update_pandora">' . __('Installing <span class="package_name">package</span> in progress') . " ";
			html_print_image('images/spinner.gif');
			echo '</h2>';
			echo '<h2 style="display: none;" id="title_installed_update_pandora">' . __('Installed <span class="package_name">package</span> in progress') . '</h2>';
			echo '<h2 style="display: none;" id="title_error_update_pandora">' . __('Fail download <span class="package_name">package</span>') . '</h2>';
			echo '<br /><br />';
			echo "<div id='progress_bar_img'>";
				echo progress_bar(0, 300, 20, 0 . '%', 1, false, "#00ff00");
			echo "</div>";
			
			echo "<div style='padding-top: 20px; display: none;' id='info_text'>
					<b>Size:</b> 666/666 kbytes <b>Speed:</b> 666 bytes/second
				</div>";
			
			?>
			<div id="button_close_download" style="display: none; position: absolute; top:280px; right:43%;">	  
				<?php
				html_print_submit_button(__("Close"), 'hide_download_dialog', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');
				?>  
			</div>
		 </div> 
	</div>
	<?php
	
	ui_require_css_file ('dialog');
	ui_require_jquery_file ('ui.core');
	ui_require_jquery_file ('ui.dialog');
	
	update_pandora_print_javascript_admin();
}
?>
