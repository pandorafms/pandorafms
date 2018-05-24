<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access HA cluster");
	require ("general/noaccess.php");
	exit;
}

ui_require_css_file ('firts_task');
?>
<?php 

ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no HA clusters defined yet.') ) );
?>

<div class="new_task_cluster">
	<div class="image_task_cluster">
		<?php echo html_print_image('images/firts_task/slave-mode.png', true, array("title" => __('Clusters')));?>
	</div>
	<div class="text_task_cluster">
		<h3> <?php echo __('PANDORA FMS DB CLUSTER'); ?></h3>
		<p id="description_task"> <?php
    
    echo __('With Pandora FMS Enterprise you can add high availability to your Pandora FMS installation by adding redundant MySQL servers').'<br><br>';
    
    echo __('Click on "add new node" to start transforming your Pandora FMS DB Cluster into a Pandora FMS DB Cluster.').'<br><br>';
    
    ?></p>
		
		<?php
			if(check_acl ($config['id_user'], 0, "AW")) {
				echo "<div id='create_master_window'></div>";
		?>
			<input style="margin-bottom:20px;" onclick="show_create_ha_cluster();" type="submit" class="button_task" value="<?php echo __('Add new node'); ?>" />
		<?php
			}
		?>
	</div>
</div>
<script type="text/javascript">
	function show_create_ha_cluster() {
		var params = {};
		params["dialog_master"] = 1;
		params["page"] = "enterprise/include/ajax/HA_cluster.ajax";
		jQuery.ajax ({
			data: params,
			dataType: "html",
			type: "POST",
			url: "ajax.php",
			success: function (data) {
				$("#create_master_window").dialog ({
					title: '<?php echo __("Add master node");?>',
					resizable: true,
					draggable: true,
					modal: true,
					close: function() {
						
					},
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 800,
					height: 600
				}).empty()
				.html(data)
				.show ();
			}
		});
	}
</script>
