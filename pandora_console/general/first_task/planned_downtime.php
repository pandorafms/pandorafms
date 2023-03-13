<?php
/**
 * Planned downtimes view
 *
 * @category   Community
 * @package    Pandora FMS
 * @subpackage Tools
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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
check_login();
ui_require_css_file('first_task');
?>
<?php ui_print_info_message(['no_close' => true, 'message' => __('There are no scheduled downtime defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_visualconsole.png', true, ['title' => __('Scehduled Downtime')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Scheduled Downtime'); ?></h3><p id="description_task">
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
        <form action="index.php?sec=extensions&amp;sec2=godmode/agentes/planned_downtime.editor" method="post">
            <?php
            html_print_submit_button(
                __('Create Scheduled Downtime'),
                'button_task'
            );
            ?>
        </form>
    </div>
</div>
