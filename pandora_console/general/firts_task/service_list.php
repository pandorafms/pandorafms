<?php
global $config;
check_login ();
ui_require_css_file ('firts_task');
?>
<?php ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no services defined yet.') ) ); ?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_servicios.png', true, array("alt" => __('Recon server')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Services'); ?></h3>
		<p id="description_task"> <?php echo __("A service is a way to group your IT resources based on their functionalities. 
					A service could be e.g. your official website, your CRM system, your support application, or even your printers.
					 Services are logical groups which can include hosts, routers, switches, firewalls, CRMs, ERPs, websites and numerous other services. 
					 By the following example, you're able to see more clearly what a service is:
						A chip manufacturer sells computers by its website all around the world. 
						His company consists of three big departments: A management, an on-line shop and support."); ?></p>
		<form action="index.php?sec=estado&sec2=enterprise/godmode/services/services.service&action=new_service" method="post">
			<input type="submit" class="button_task" value="<?php echo __('Create Services'); ?>" />
		</form>
	</div>
</div>
