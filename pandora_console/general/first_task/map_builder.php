<?php
/**
 * Map builder First Task.
 *
 * @category   Topology maps
 * @package    Pandora FMS
 * @subpackage Visual consoles
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
global $config;
global $vconsoles_write;
global $vconsoles_manage;
check_login();
ui_require_css_file('first_task');

if ($vconsoles_write || $vconsoles_manage) {
    ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_visualconsole.png', true, ['title' => __('Visual Console')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Visual Consoles'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                '%s allows users to create visual maps on which each user is able to create his or her '.'own monitoring map. The new visual console editor is much more practical, although the prior '."visual console editor had its advantages. On the new visual console, we've been successful in "."imitating the sensation and touch of a drawing application like GIMP. We've also simplified the "."editor by dividing it into several subject-divided tabs named 'Data', 'Preview', 'Wizard', 'List of "."Elements' and 'Editor'. The items the %s Visual Map was designed to handle are "."'static images', 'percentage bars', 'module graphs' and 'simple values'.",
                get_product_name(),
                get_product_name()
            );
            ?>
   </p>
        <form action="index.php?sec=network&amp;sec2=godmode/reporting/visual_console_builder" method="post">
        <?php
        html_print_input_hidden('edit_layout', 1);
        html_print_action_buttons(
            html_print_submit_button(
                __('Create a Visual Console'),
                'button_task',
                false,
                ['icon' => 'wand'],
                true
            )
        );
        ?>
        </form>
    </div>
</div>
    <?php
}
