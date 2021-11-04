<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
ui_require_css_file('first_task');
?>
<?php ui_print_info_message(['no_close' => true, 'message' => __('There are no tags defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_gestiondetags.png', true, ['title' => __('Tags')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Tags'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                "Access to modules can be configured by a tagging system.
								Tags are configured on the system and are assigned to the chosen modules.
								A user's access can therefore be restricted to modules with certain tags."
            );
            ?>
        </p>
        <form action="index.php?sec=gmodules&sec2=godmode/tag/edit_tag&action=new" method="post">
            <input type="submit" class="button_task" value="<?php echo __('Create Tags'); ?>" />
        </form>
    </div>
</div>
