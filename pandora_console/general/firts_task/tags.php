<?php
global $config;
check_login ();
ui_require_css_file ('firts_task');
?>
<?php ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no tags defined yet.') ) ); ?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_gestiondetags.png', true, array("alt" => __('Recon server')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Tags'); ?></h3>
		<p id="description_task"> <?php echo __("From Pandora FMS versions 5 and above, the access to modules can be configured by a tags system.
								Tags are configured on the system and be assigned to the chosen modules. 
								The user's access can be limited to modules with certain tags in this way. "); ?></p>
		<form action="index.php?sec=gmodules&sec2=godmode/tag/edit_tag&action=new" method="post">
			<input type="submit" class="button_task" value="<?php echo __('Create Tags'); ?>" />
		</form>
	</div>
</div>
