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
<?php
ui_print_info_message(['no_close' => true, 'message' => __('There are no network map defined yet.') ]);
$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);
$networkmap_types = networkmap_get_types($strict_user);
?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/icono_grande_reconserver.png', true, ['title' => __('Network Map')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Create Network Map'); ?></h3><p id="description_task"> 
            <?php
            echo __(
                'There is also an open-source version of the network map. 
								This functionality allows to graphically display the nodes and relationships, agents, modules and groups available to the user. 
								There are three types of network maps:
			'
            );
                        echo '<li>'.__('Topology Map').'</li>
			<li>'.__('Group Map').'</li>
			<li>'.__('Radial Map (User without strict user)').'</li>
			<li>'.__('Dinamic Map').'</li>
			<li>'.__('Policy Map (Only Enterprise version)').'</li>';
            ?>
    </p>
        <form id="networkmap_action" method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap&action=create">
            <?php
            echo html_print_select($networkmap_types, 'tab', 'topology', '', '', 0);
                    html_print_input_hidden('add_networkmap', 1);
            ?>
            
            <input type="submit" class="button_task" value="<?php echo __('Create Network Map'); ?>" />
        </form>
    </div>
</div>
