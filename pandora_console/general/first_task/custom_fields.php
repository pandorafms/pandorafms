<?php
/**
 * Custom fields first task.
 *
 * @category   Custom Fields.
 * @package    Pandora FMS
 * @subpackage Opensource
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

// Load globals.
global $config;
check_login();
ui_require_css_file('first_task');
?>
<?php
ui_print_info_message(['no_close' => true, 'message' => __('There are no custom fields defined yet.') ]);
?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_reconserver.png', true, ['title' => __('Custom Fields')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Custom Fields'); ?></h3><p id="description_task">
            <?php
            echo __(
                "Custom fields are an easy way to personalized agent's information.
		 You're able to create custom fields by klicking on 'Administration' -> 'Manage monitoring' -> 'Manage custom fields'. "
            );
            ?>
        </p>
        <form action="index.php?sec=gagente&sec2=godmode/agentes/configure_field" method="post">
            <?php
            html_print_div(
                [
                    'class'   => 'action-buttons',
                    'content' => html_print_submit_button(
                        __('Create Custom Fields'),
                        'button_task',
                        false,
                        [ 'icon' => 'next' ],
                        true
                    ),
                ]
            );
            ?>
            <input type="submit" class="button_task" value="<?php echo __('Create Custom Fields'); ?>" />
        </form>
    </div>
</div>