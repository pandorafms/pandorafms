<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function menu() {
	?>
	<div id="top_menu">
		<div id="menu">
			<a href="index.php?page=tactical"><img class="icon_menu" alt="<?php echo __('Dashboard');?>" title="<?php echo __('Dashboard');?>" src="../images/house.png" /></a>
			<a href="index.php?page=agents"><img class="icon_menu" alt="<?php echo __('Agents');?>" title="<?php echo __('Agents');?>" src="../images/bricks.png" /></a>
			<a href=""><img class="icon_menu" alt="<?php echo __('Events');?>" title="<?php echo __('Events');?>" src="../images/lightning_go.png" /></a>
			<a href="index.php?page=alerts"><img class="icon_menu" alt="<?php echo __('Alerts');?>" title="<?php echo __('Alerts');?>" src="../images/bell.png" /></a>
			<a href="index.php?page=groups"><img class="icon_menu" alt="<?php echo __('Groups');?>" title="<?php echo __('Groups');?>" src="../images/world.png" /></a>
			<a href="index.php?page=servers"><img class="icon_menu" alt="<?php echo __('Servers');?>" title="<?php echo __('Servers');?>" src="../images/god5.png" /></a>
			<a href=""><img class="icon_menu" alt="<?php echo __('Reports');?>" title="<?php echo __('Reports');?>" src="../images/reporting.png" /></a>
			<a href="index.php?action=logout"><img class="icon_menu" alt="<?php echo __('Logout');?>" title="<?php echo __('Logout');?>" src="../images/log-out.png" /></a>
		</div>
		<div id="down_button">
			<a class="button_menu" id="button_menu_down" href="javascript: toggleMenu();"><img src="images/down.png" /></a>
			<a class="button_menu" id="button_menu_up" href="javascript: toggleMenu();"><img src="images/up.png" /></a>
		</div>
	</div>
	<div id="margin_bottom_menu"></div>
	<script type="text/javascript">
	function toggleMenu() {
		$("#top_menu #menu").slideToggle("normal");
		 $(".button_menu").toggle();
	}
	</script>
	<?php
}
?>