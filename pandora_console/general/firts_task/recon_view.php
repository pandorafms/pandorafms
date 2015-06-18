<?php
global $config;
check_login ();
ui_require_css_file ('firts_task');
?>

<div class="new_task">
	<div>
		<div class="title_task"> <?php echo __('There are no recon task defined yet'); ?> </div>
	</div>
	<div>
		<div class="image_task">
			<?php echo html_print_image('images/icono_grande_reconserver.png', true, array("alt" => __('Recon server')));?>
		</div>
		<div class="text_task">
			<h3> <?php echo __('Create Recon Task'); ?></h3>
			<p id="description_task"> <?php echo __('The Recon Task definition of Pandora FMS is used to find new elements in the network. 
			If it detects any item, it will add that item to the monitoring, and if that item it is already being monitored, then it will 
			ignore it or will update its information.There are three types of detection: Based on <strong id="fuerte"> ICMP </strong>(pings), 
			<strong id="fuerte">SNMP</strong> (detecting the topology of networks and their interfaces), and other <strong id="fuerte"> customized </strong>
			type. You can define your own customized recon script.'); ?></p>
			<form action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create" method="post">
				<button class="button_task" > <?php echo __('Create Recon Task'); ?></button>
			</form>
		</div>
	</div>
</div>
