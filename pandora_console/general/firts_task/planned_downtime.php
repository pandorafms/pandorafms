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
global $config;
check_login();
ui_require_css_file('firts_task');
?>
<?php ui_print_info_message(['no_close' => true, 'message' => __('There are no planned downtime defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/firts_task/icono_grande_visualconsole.png', true, ['title' => __('Planned Downtime')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Planned Downtime'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                "%s contains a scheduled downtime management system.
						This system was designed to deactivate alerts during specific intervals whenever there is down time by deactivating the agent.
						If an agent is deactivated, it doesn't gather information. During down time, down-time intervals aren't taken into
						account for most metrics or report types, because agents don't contain any data within those intervals.",
                get_product_name()
            );
            ?>
      </p>
        <form action="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor" method="post">
            <input type="submit" class="button_task" value="<?php echo __('Create Planned Downtime'); ?>" />
        </form>
    </div>
</div>
