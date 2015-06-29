<?php
global $config;
check_login ();
ui_require_css_file ('firts_task');
?>
<?php ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no planned downtime defined yet.') ) ); ?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_visualconsole.png', true, array("title" => __('Planned Downtime')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Planned Downtime'); ?></h3>
		<p id="description_task"> <?php echo __("Pandora FMS contains a scheduled downtime management system. 
						This system was designed to deactivate the alerts in the intervals whenever there is down time by deactivating the agent.
						If an agent is deactivated, it doesn't collect information. In a down time, the down-time intervals aren't taken into 
						account for most of the metrics or types of reports, because the agents don't contain any data within those intervals. "); ?></p>
		<form action="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor" method="post">
			<input type="submit" class="button_task" value="<?php echo __('Create Planned Downtime'); ?>" />
		</form>
	</div>
</div>
