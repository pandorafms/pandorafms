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

check_login();

ui_require_css_file ('firts_task');
?>
<?php 

ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no HA clusters defined yet.') ) );
?>

<div class="new_task_cluster">
	<div class="image_task_cluster">
		<?php echo html_print_image('images/firts_task/icono-cluster-activo.png', true, array("title" => __('Clusters')));?>
	</div>
	<div class="text_task_cluster">
		<h3> <?php echo __('PANDORA FMS DB CLUSTER'); ?></h3>
		<p id="description_task"> <?php
    
    echo __('A cluster is a group of devices that provide the same service in high availability.').'<br><br>';
    
    echo __('Depending on how they provide that service, we can find two types:').'<br><br>';
    
    echo __('<b>Clusters to balance the service load</b>: these are  active - active (A/A)  mode clusters. It means that all the nodes (or machines that compose it) are working. They must be working because if one stops working, it will overload the others.').'<br><br>';
    
    echo __('<b>Clusters to guarantee service</b>: these are active - passive (A/P) mode clusters. It means that one of the nodes (or machines that make up the cluster) will be running (primary) and another won\'t (secondary). When the primary goes down, the secondary must take over and give the service instead. Although many of the elements of this cluster are active-passive, it will also have active elements in both of them that indicate that the passive node is "online", so that in the case of a service failure in the master, the active node collects this information.');
    
    ?></p>
		
		<?php
			if(check_acl ($config['id_user'], 0, "AW")) {
				echo "<div id='create_master_window'></div>";
		?>
			<input style="margin-bottom:20px;" onclick="show_create_ha_cluster();" type="submit" class="button_task" value="<?php echo __('Create Cluster'); ?>" />
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
					title: 'Agregar nodo maestro',
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
