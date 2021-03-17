<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
function menu()
{
    global $config;

    $enterpriseHook = enterprise_include('mobile/include/functions_web.php');

    ?>
    <div id="top_menu">
        <div id="menu">
            <a href="index.php?page=tactical"><img class="icon_menu" alt="<?php echo __('Dashboard'); ?>" title="<?php echo __('Dashboard'); ?>" src="../images/house.png" /></a>
            <a href="index.php?page=agents"><img class="icon_menu" alt="<?php echo __('Agents'); ?>" title="<?php echo __('Agents'); ?>" src="../images/bricks.png" /></a>
            <a href="index.php?page=monitor"><img class="icon_menu" alt="<?php echo __('Monitor'); ?>" title="<?php echo __('Monitor'); ?>" src="../images/data.png" /></a>
            <a href="index.php?page=events"><img class="icon_menu" alt="<?php echo __('Events'); ?>" title="<?php echo __('Events'); ?>" src="../images/lightning_go.png" /></a>
            <a href="index.php?page=alerts"><img class="icon_menu" alt="<?php echo __('Alerts'); ?>" title="<?php echo __('Alerts'); ?>" src="../images/bell.png" /></a>
            <a href="index.php?page=groups"><img class="icon_menu" alt="<?php echo __('Groups'); ?>" title="<?php echo __('Groups'); ?>" src="../images/world.png" /></a>
            <a href="index.php?page=servers"><img class="icon_menu" alt="<?php echo __('Servers'); ?>" title="<?php echo __('Servers'); ?>" src="../images/god5.png" /></a>
            <?php
            if ($enterpriseHook !== ENTERPRISE_NOT_HOOK) {
                menuEnterprise();
            }
            ?>
            <a href="index.php?action=logout"><img class="icon_menu" alt="<?php echo __('Logout'); ?>" title="<?php echo __('Logout'); ?>" src="<?php echo 'images/header_logout.png'; ?>" /></a>
        </div>
        <div id="down_button">
            <a class="button_menu" id="button_menu_down" href="javascript: toggleMenu();"><img id="img_boton_menu" width="20" height="20" src="<?php echo 'images/down.png'; ?>" /></a>
        </div>
    </div>
    <script type="text/javascript">
        var open = 0;
    
        function toggleMenu() {
            if (document.getElementById) {
                var div = document.getElementById('menu');
                var boton_up = document.getElementById('button_menu_up');
                var boton_down = document.getElementById('button_menu_down');
                var boton_img = document.getElementById('img_boton_menu');

                if (open == 0) {
                    boton_img.src = 'images/up.png';
                    div.style.display = 'block';
//                    boton_up.style.display = 'block';
//                    boton_down.style.display = 'none';
                    open = 1;
                }
                else {
                    open = 0;
                    boton_img.src = 'images/down.png';
                    div.style.display = 'none';
//                    boton_down.style.display = 'block';
//                    boton_up.style.display = 'none';
                }
            }
        }
    </script>
    <?php
}


function footer()
{
    global $pandora_version, $build_version;

    if (isset($_SERVER['REQUEST_TIME'])) {
        $time = $_SERVER['REQUEST_TIME'];
    } else {
        $time = get_system_time();
    }
    ?>
    <div id="footer" style="background: url('../images/pandora.ico.gif') no-repeat left #000;">
        <?php
        echo sprintf(__('Pandora FMS %s - Build %s', $pandora_version, $build_version)).'<br />';
        echo __('Generated at').' '.ui_print_timestamp($time, true, ['prominent' => 'timestamp']);
        ?>
    </div>
    <?php
}

