<?php

/**
 * Lateral Main Menu.
 *
 * @category   Main Menu.
 * @package    Pandora FMS.
 * @subpackage OpenSource.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
if (isset($config['id_user']) === false) {
    include 'general/login_page.php';
    exit();
}

require_once 'include/functions_menu.php';

// Global variable. Do not delete.
$tab_active = '';


// Start of full lateral menu.
echo sprintf('<div id="menu_full" class="menu_full_%s">', 'classic');

$url_logo = ui_get_full_url('index.php');
if (is_reporting_console_node() === true) {
    $url_logo = 'index.php?logged=1&sec=discovery&sec2=godmode/servers/discovery&wiz=tasklist';
}

// Header logo.
html_print_div(
    [
        'class'   => 'logo_green',
        'content' => html_print_anchor(
            [
                'href'    => $url_logo,
                'content' => html_print_header_logo_image(
                    $menuCollapsed,
                    true
                ),
            ],
            true
        ).html_print_image(
            '/images/menu/contraer.svg',
            true
        ),
    ]
);

// Tabs.
echo '<div id="menu_tabs" class="tabs">';
echo '<ul class="tabs_ul">';
echo '<li id="tab_display" class="tabs_li"><span>'.__('Display').'</span></a></li>';
echo '<li id="tab_management" class="tabs_li"><span>'.__('Management').'</span></a></li>';
echo '</ul><div class="div_border_line"><div id="tab_line_1" class="border_line"></div><div id="tab_line_2" class="border_line"></div></div></div>';

echo '<div id="div_display">';
require 'operation/menu.php';
echo '</div>';
echo '<div id="div_management" style="display: none;">';
require 'godmode/menu.php';
echo '</div>';

echo '</div>';
?>
<script type="text/javascript">
    $(document).ready(function() {
        const tab = '<?php echo $tab_active; ?>';
        if (tab === 'management') {
            $('#tab_line_2').addClass('tabs_selected');
            $('#div_display').css('display', 'none');
            $('#div_management').css('display', 'block');
        } else {
            $('#tab_line_1').addClass('tabs_selected');
        }

        $('#tab_display').click(function() {
            $('#tab_line_1').addClass('tabs_selected');
            $('#tab_line_2').removeClass('tabs_selected');
            $('#div_management').css('display', 'none');
            $('#div_display').css('display', 'block');
        });

        $('#tab_management').click(function() {
            $('#tab_line_2').addClass('tabs_selected');
            $('#tab_line_1').removeClass('tabs_selected');
            $('#div_display').css('display', 'none');
            $('#div_management').css('display', 'block');
        })

        var click_display = "<?php echo $config['click_display']; ?>";

        $('.title_menu_classic').click(function() {
            if (typeof(table_hover) != 'undefined') {
                $("ul#sub" + table_hover[0].id).hide();
                // Arrow.
                table_hover.children().first().children().last().removeClass('arrow_menu_up');
                table_hover.children().first().children().last().addClass('arrow_menu_down');
                // Span.
                table_hover.children().first().children().eq(1).removeClass('span_selected');
                if (table_hover[0].id == $(this).parent()[0].id) {
                    table_hover = undefined;
                    return;
                }
            }

            table_hover = $(this).parent();
            handsIn = 1;
            $("ul#sub" + table_hover[0].id).show();
            // Arrow.
            $(this).children().last().removeClass('arrow_menu_down');
            $(this).children().last().addClass('arrow_menu_up');
            // Span.
            $(this).children().eq(1).addClass('span_selected');
        });

        $('.has_submenu').click(function() {
            if (typeof(table_hover2) != 'undefined') {
                $("#sub" + table_hover2[0].id).hide();
                // Arrow.
                table_hover2.children().first().children().last().removeClass('arrow_menu_up');
                table_hover2.children().first().children().last().addClass('arrow_menu_down');
                // Span.
                table_hover2.children().first().children().first().removeClass('span_selected');
                if (table_hover2[0].id == $(this)[0].id) {
                    table_hover2 = undefined;
                    return;
                }
            }

            table_hover2 = $(this);
            handsIn2 = 1;
            $("#sub" + table_hover2[0].id).show();
            // Arrow.
            table_hover2.children().first().children().last().removeClass('arrow_menu_down');
            table_hover2.children().first().children().last().addClass('arrow_menu_up');
            // Span.
            table_hover2.children().first().children().first().addClass('span_selected');
        });
    });
</script>