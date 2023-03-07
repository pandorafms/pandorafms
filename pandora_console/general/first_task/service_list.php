<?php
/**
 * Services first task.
 *
 * @category   Topology maps
 * @package    Pandora FMS
 * @subpackage Services
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

check_login();
ui_require_css_file('first_task');
?>
<?php if ((bool) $agent_w === true) { ?>
    <div class="new_task">
        <div class="image_task">
            <?php echo html_print_image('images/item-service.svg', true, ['title' => __('Services'), 'class' => 'w120px']); ?>
        </div>
        <div class="text_task">
            <h3> <?php echo __('Services'); ?></h3>
            <p id="description_task"> 
                <?php
                echo __(
                    "A service is a way to group your IT resources based on their functionalities. 
						A service could be e.g. your official website, your CRM system, your support application, or even your printers.
						 Services are logical groups which can include hosts, routers, switches, firewalls, CRMs, ERPs, websites and numerous other services. 
						 By the following example, you're able to see more clearly what a service is:
							A chip manufacturer sells computers by its website all around the world. 
							His company consists of three big departments: A management, an on-line shop and support."
                );
                ?>
            </p>
            <form action="index.php?sec=estado&sec2=enterprise/godmode/services/services.service&action=new_service" method="post">
                <?php
                html_print_action_buttons(
                    html_print_submit_button(
                        __('Create a service'),
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
