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
<?php ui_print_info_message(['no_close' => true, 'message' => __('There are no SNMP filter defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_reconserver.png', true, ['title' => __('SNMP Filter')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create SNMP Filter'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                "Some systems receive a high number of traps. 
				We're only interested in monitoring a tiny percentage of them. From Pandora FMS versions 3.2 and above, 
				it's possible to filter the traps that the server obtains in order to avoid straining the application unnecessarily.
				In order to define different filters, please go to 'Administration' -> 'Manage SNMP Console' and 'SNMP Filters'. 
				One trap which is going to run in conjunction with any of them - just the ones for the server are going to get ruled out automatically. "
            );
            ?>
        </p>
        <form action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter=-1" method="post">
            <input type="submit" class="button_task" value="<?php echo __('Create SNMP Filter'); ?>" />
        </form>
    </div>
</div>
