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
global $vconsoles_write;
global $vconsoles_manage;
check_login();
ui_require_css_file('firts_task');

ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('There are no visual console defined yet.'),
    ]
);
if ($vconsoles_write || $vconsoles_manage) {
    ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/firts_task/icono_grande_visualconsole.png', true, ['title' => __('Visual Console')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Visual Console'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                '%s allows users to create visual maps on which each user is able to create his or her '.'own monitoring map. The new visual console editor is much more practical, although the prior '."visual console editor had its advantages. On the new visual console, we've been successful in "."imitating the sensation and touch of a drawing application like GIMP. We've also simplified the "."editor by dividing it into several subject-divided tabs named 'Data', 'Preview', 'Wizard', 'List of "."Elements' and 'Editor'. The items the %s Visual Map was designed to handle are "."'static images', 'percentage bars', 'module graphs' and 'simple values'.",
                get_product_name(),
                get_product_name()
            );
            ?>
   </p>
        <form action="index.php?sec=network&amp;sec2=godmode/reporting/visual_console_builder" method="post">
            <?php html_print_input_hidden('edit_layout', 1); ?>
            <input type="submit" class="button_task" value="<?php echo __('Create Visual Console'); ?>" />
        </form>
    </div>
</div>
    <?php
}
