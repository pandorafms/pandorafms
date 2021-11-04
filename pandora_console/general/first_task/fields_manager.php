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
<?php ui_print_info_message(['no_close' => true, 'message' => __('There are no custom fields defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_reconserver.png', true, ['title' => __('Fields Manager')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Fields Manager'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                "Custom fields are an easy way to personalized agent's information.
				You're able to create custom fields by klicking on 'Administration' -> 'Manage monitoring' -> 'Manage custom fields'. "
            );
            ?>
        </p>
        <form action="index.php?sec=gagente&sec2=godmode/agentes/configure_field" method="post">
            <input type="submit" class="button_task" value="<?php echo __('Create Fields '); ?>" />
        </form>
    </div>
</div>
