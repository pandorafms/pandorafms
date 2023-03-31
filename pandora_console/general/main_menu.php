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
echo sprintf('<div id="menu_full" class="menu_full_%s">', $menuTypeClass);

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
        ).'<div id="button_collapse" class="button_'.$menuTypeClass.'" style="cursor: pointer"></div>',
    ]
);

$display_classic = '';
$display_collapsed = 'display: none;';
if ($menuTypeClass === 'collapsed') {
    $display_classic = 'display: none;';
    $display_collapsed = '';
}


// Tabs.
echo '<div id="menu_tabs">';
// Tabs classic.
echo '<ul class="tabs_ul" style="'.$display_classic.'">';
echo '<li id="tab_display" class="tabs_li"><span>'.__('Operation').'</span></a></li>';
echo '<li id="tab_management" class="tabs_li"><span>'.__('Management').'</span></a></li>';
echo '</ul>';
echo '<div class="div_border_line" style="'.$display_classic.'"><div id="tab_line_1" class="border_line"></div><div id="tab_line_2" class="border_line"></div></div>';
// Tabs collapse.
echo '<div class="tabs_collapsed" style="'.$display_collapsed.'">';
echo '<div class="tabs_collapsed_container">';
echo '<div id="tab_collapsed_display" class="tabs_collapsed_div" title="'.__('Operation').'"><div class="tabs_collapsed_display"></div></div>';
echo '<div id="tab_collapsed_management" class="tabs_collapsed_div" title="'.__('Management').'"><div class="tabs_collapsed_management"></div></div>';
echo '</div></div>';

echo '</div>';

echo '<div id="div_display">';
require 'operation/menu.php';
echo '</div>';
echo '<div id="div_management">';
require 'godmode/menu.php';
echo '</div>';
echo '</div>';
?>
<script type="text/javascript">
    $(document).ready(function() {
        menuActionButtonResizing();
        const menuTypeClass = '<?php echo $menuTypeClass; ?>';
        if (menuTypeClass === 'classic' && menuTypeClass !== localStorage.getItem('menuType')) {
            localStorage.setItem('menuType', 'classic');
        }
        const tab = '<?php echo $tab_active; ?>';

        if (tab === 'management') {
            $('#tab_line_2').addClass('tabs_selected');
            $('#div_display').css('display', 'none');
            $('#div_management').css('display', 'block');
            $('#tab_display').addClass('head_tab_unselected').removeClass('head_tab_selected');
            $('#tab_management').addClass('head_tab_selected').removeClass('head_tab_unselected');
            $('#tab_collapsed_display').children().first().removeClass('tabs_collapsed_display');
            $('#tab_collapsed_display').children().first().addClass('tabs_collapsed_oval');
        } else {
            $('#tab_line_1').addClass('tabs_selected');
            $('#tab_management').addClass('head_tab_unselected').removeClass('head_tab_selected');
            $('#tab_display').addClass('head_tab_selected').removeClass('head_tab_unselected');
            $('#tab_collapsed_management').children().first().removeClass('tabs_collapsed_management');
            $('#tab_collapsed_management').children().first().addClass('tabs_collapsed_oval');
        }

        $('#tab_display,#tab_collapsed_display').click(function() {
            $('#tab_line_1').addClass('tabs_selected');
            $('#tab_line_2').removeClass('tabs_selected');
            $('#div_management').css('display', 'none');
            $('#div_display').css('display', 'block');
            $('#tab_management').addClass('head_tab_unselected').removeClass('head_tab_selected');
            $('#tab_display').addClass('head_tab_selected').removeClass('head_tab_unselected');
            $('#tab_collapsed_management').children().first().removeClass('tabs_collapsed_management');
            $('#tab_collapsed_management').children().first().addClass('tabs_collapsed_oval');
            $('#tab_collapsed_display').children().first().removeClass('tabs_collapsed_oval');
            $('#tab_collapsed_display').children().first().addClass('tabs_collapsed_display');
        });

        $('#tab_management,#tab_collapsed_management').click(function() {
            $('#tab_line_2').addClass('tabs_selected');
            $('#tab_line_1').removeClass('tabs_selected');
            $('#div_display').css('display', 'none');
            $('#div_management').css('display', 'block');
            $('#tab_display').addClass('head_tab_unselected').removeClass('head_tab_selected');
            $('#tab_management').addClass('head_tab_selected').removeClass('head_tab_unselected');
            $('#tab_collapsed_display').children().first().removeClass('tabs_collapsed_display');
            $('#tab_collapsed_display').children().first().addClass('tabs_collapsed_oval');
            $('#tab_collapsed_management').children().first().removeClass('tabs_collapsed_oval');
            $('#tab_collapsed_management').children().first().addClass('tabs_collapsed_management');
        });

        $('#button_collapse').click(function() {
            if ($('#menu_full').hasClass('menu_full_classic')) {
                localStorage.setItem("menuType", "collapsed");
                $('ul.submenu').css('left', '80px');
                var menuType_val = localStorage.getItem("menuType");
                $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data: {
                        menuType: menuType_val,
                        page: "include/functions_menu"
                    },
                    dataType: "json"
                });
                $('.tabs_ul').hide();
                $('.div_border_line').hide();
                $('.tabs_collapsed').show();

                $(".title_menu_classic").children('div[class*=icon_]').each(function() {
                    $(this).removeClass('w15p').addClass('w100p');
                });

                $(".title_menu_classic").children('div[class*=arrow_]').each(function() {
                    $(this).hide();
                });

                $(".title_menu_classic").children('span').each(function() {
                    $(this).hide();
                });

                $('ul.submenu').css('position', 'fixed');
                $('ul.submenu').css('left', '60px');

                $('li.selected').each(function() {
                    $(`#sub${this.id}`).hide();
                })
            } else if ($('#menu_full').hasClass('menu_full_collapsed')) {
                localStorage.setItem("menuType", "classic");
                $('ul.submenu').css('left', '280px');
                var menuType_val = localStorage.getItem("menuType");
                $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data: {
                        menuType: menuType_val,
                        page: "include/functions_menu"
                    },
                    dataType: "json"
                });
                $('.tabs_ul').show();
                $('.div_border_line').show();
                $('.tabs_collapsed').hide();

                $(".title_menu_classic").children('div[class*=icon_]').each(function() {
                    $(this).removeClass('w100p').addClass('w15p');
                });

                $(".title_menu_classic").children('div[class*=arrow_]').each(function() {
                    $(this).show();
                });

                $(".title_menu_classic").children('span').each(function() {
                    $(this).show();
                });

                $('ul.submenu').css('position', '');
                $('ul.submenu').css('left', '80px');

                $('li.selected').each(function() {
                    $(`#sub${this.id}`).show();
                })
            }

            $('.logo_full').toggle();
            $('.logo_icon').toggle();
            $('#menu_full').toggleClass('menu_full_classic menu_full_collapsed');
            $('#button_collapse').toggleClass('button_classic button_collapsed');
            $('div#page').toggleClass('page_classic page_collapsed');
            $('#header_table').toggleClass('header_table_classic header_table_collapsed');
            $('li.menu_icon').toggleClass("no_hidden_menu menu_icon_collapsed");
            menuActionButtonResizing();
        });

        const id_selected = '<?php echo $menu1_selected; ?>';
        if (id_selected != '') {
            var menuType_val = localStorage.getItem("menuType");
            const closedMenuId = localStorage.getItem("closedMenuId");
            if (menuType_val === 'classic' &&
                (closedMenuId === '' || `icon_${id_selected}` !== closedMenuId)
            ) {
                $(`ul#subicon_${id_selected}`).show();
                // Arrow.
                $(`#icon_${id_selected}`).children().first().children().last().removeClass('arrow_menu_down');
                $(`#icon_${id_selected}`).children().first().children().last().addClass('arrow_menu_up');
            }

            // Span.
            $(`#icon_${id_selected}`).children().first().children().eq(1).addClass('span_selected');

            const id_selected2 = '<?php echo $menu2_selected; ?>';
            if (id_selected2 != '') {
                if ($(`#sub${id_selected2}`).length > 0) {
                    $(`#sub${id_selected2}`).show();
                    // Arrow.
                    $(`#${id_selected2}`).children().first().children().last().removeClass('arrow_menu_down');
                    $(`#${id_selected2}`).children().first().children().last().addClass('arrow_menu_up');
                    // Span.
                    $(`#${id_selected2}`).children().first().children().first().addClass('span_selected');
                    // Vertical line.
                    $(`.sub_subMenu.selected`).prepend(`<div class="element_submenu_selected left_3"></div>`);
                } else {
                    $(`#${id_selected2}`).addClass('submenu_selected_no_submenu');
                    $(`#${id_selected2}`).children().first().children().first().css('color', '#fff');
                    // Vertical line.
                    $(`#${id_selected2}`).prepend(`<div class="element_submenu_selected"></div>`);
                }
            }
        }


        var click_display = "<?php echo $config['click_display']; ?>";

        $('.menu_icon').mouseenter(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (!click_display && menuType_val === 'collapsed') {
                table_hover = $(this);
                handsIn = 1;
                openTime = new Date().getTime();
                $("ul#sub" + table_hover[0].id).show();
                get_menu_items(table_hover);
                if (typeof(table_noHover) != 'undefined') {
                    if ("ul#sub" + table_hover[0].id != "ul#sub" + table_noHover[0].id) {
                        $("ul#sub" + table_noHover[0].id).hide();
                    }
                }
            }
        }).mouseleave(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (!click_display && menuType_val === 'collapsed') {
                table_noHover = $(this);
                handsIn = 0;
                setTimeout(function() {
                    opened = new Date().getTime() - openTime;
                    if (opened > 2500 && handsIn == 0) {
                        openTime = 4000;
                        $("ul#sub" + table_noHover[0].id).hide();
                    }
                }, 2500);
            }
        });

        $('.has_submenu').mouseenter(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (!click_display && menuType_val === 'collapsed') {
                table_hover2 = $(this);
                handsIn2 = 1;
                openTime2 = new Date().getTime();
                $("#sub" + table_hover2[0].id).show();
                if (typeof(table_noHover2) != 'undefined') {
                    if ("ul#sub" + table_hover2[0].id != "ul#sub" + table_noHover2[0].id) {
                        $("ul#sub" + table_noHover2[0].id).hide();
                    }
                }
            }
        }).mouseleave(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (!click_display && menuType_val === 'collapsed') {
                table_noHover2 = table_hover2;
                handsIn2 = 0;
                setTimeout(function() {
                    opened = new Date().getTime() - openTime2;
                    if (opened >= 3000 && handsIn2 == 0) {
                        openTime2 = 4000;
                        $("ul#sub" + table_hover2[0].id).hide();
                    }
                }, 3500);
            }
        });

        $('#container').click(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (!click_display && menuType_val === 'collapsed') {

                openTime = 4000;
                if (typeof(table_hover) != 'undefined') {
                    $("ul#sub" + table_hover[0].id).hide();
                }

                if (typeof(table_hover2) != 'undefined') {
                    $("ul#sub" + table_hover2[0].id).hide();
                }
            }
        });

        $('.title_menu_classic').click(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (click_display || (!click_display && menuType_val === 'classic')) {
                const table_hover = $(this).parent();
                const id = table_hover[0].id;
                const classes = $(`#${id}`).attr('class');

                if (id === 'icon_about') {
                    return;
                }

                var menuType_val = localStorage.getItem("menuType");
                const closedMenuId = localStorage.getItem("closedMenuId");

                if (classes.includes('selected') === true
                    && (closedMenuId === '' || closedMenuId !== id)
                ) {
                    if (menuType_val === 'collapsed' && $(`ul#sub${id}`).is(':hidden')) {
                        $(`ul#sub${id}`).show();
                        get_menu_items(table_hover);
                    } else {
                        $(`#${id}`).removeClass('selected');
                        $(`ul#sub${id}`).hide();

                        const liSelected = $(`ul#sub${id}`).find('.selected');
                        if (liSelected.length > 0) {
                            localStorage.setItem("closedMenuId", id);
                        }
                        // Arrow.
                        table_hover.children().first().children().last().removeClass('arrow_menu_up');
                        table_hover.children().first().children().last().addClass('arrow_menu_down');
                        // Span.
                        table_hover.children().first().children().eq(1).removeClass('span_selected');
                    }
                } else {
                    if (menuType_val === 'collapsed') {
                        // hide all submenus.
                        $('ul[id^=sub]').hide();
                        $(`ul#sub${id}`).show();
                        // Unselect all.
                        $(`li[id^=icon_]`).removeClass('selected');
                        $(`#${id}`).addClass('selected');
                        get_menu_items(table_hover);
                    } else {
                        $(`ul#sub${id}`).show();
                        $(`#${id}`).addClass('selected');

                        const liSelected = $(`ul#sub${id}`).find('.selected');
                        if (liSelected.length > 0) {
                            localStorage.setItem("closedMenuId", '');
                        }

                        // Arrow.
                        $(this).children().last().removeClass('arrow_menu_down');
                        $(this).children().last().addClass('arrow_menu_up');
                        // Span.
                        $(this).children().eq(1).addClass('span_selected');
                    }
                }
            }
        });

        $('.has_submenu').click(function() {
            var menuType_val = localStorage.getItem("menuType");
            if (click_display || (!click_display && menuType_val === 'classic')) {
                const table_hover2 = $(this);
                const id = table_hover2[0].id;
                const classes = $(`#${id}`).attr('class');

                if (classes.includes('submenu_selected') === true) {
                    $(`#${id}`).removeClass('submenu_selected');
                    $(`#${id}`).addClass('submenu_not_selected');
                    $(`#sub${id}`).hide();
                    // Arrow.
                    table_hover2.children().first().children().last().removeClass('arrow_menu_up');
                    table_hover2.children().first().children().last().addClass('arrow_menu_down');
                    // Span.
                    table_hover2.children().first().children().first().removeClass('span_selected');
                } else {
                    $(`#${id}`).removeClass('submenu_not_selected');
                    $(`#${id}`).addClass('submenu_selected');
                    $(`#sub${id}`).show();
                    // Arrow.
                    table_hover2.children().first().children().last().removeClass('arrow_menu_down');
                    table_hover2.children().first().children().last().addClass('arrow_menu_up');
                    // Span.
                    table_hover2.children().first().children().first().addClass('span_selected');
                }
            }
        });

        $('.sub_subMenu').click(function(event) {
            event.stopPropagation();
        });

        /**
         * Get the menu items to be positioned.
         *
         * @param string item It is the selector of the current element.
         *
         * @return Add the top position in a inline style.
         */
        function get_menu_items(item) {
            var item_height = parseInt(item.css('min-height'));
            var id_submenu = item.attr('id');
            var index = item.index();

            var top_submenu = menu_calculate_top(index, item_height);
            top_submenu = top_submenu + 'px';
            $('#' + id_submenu + ' ul.submenu').css('position', 'fixed');
            $('#' + id_submenu + ' ul.submenu').css('top', top_submenu);
            $('#' + id_submenu + ' ul.submenu').css('left', '60px');
        }


        /**
         * Positionate the submenu elements. Add a negative top.
         *
         * @param int index It is the position of li.menu_icon in the ul.
         * @param int item_height It is the height of a menu item (35).
         *
         * @return (int) The position (in px).
         */
        function menu_calculate_top(index, item_height) {
            const height_position = index * item_height;
            const height_logo = $('.logo_green').outerHeight(true);
            const height_tabs = $('#menu_tabs').outerHeight(true);
            const padding_menu = parseInt($('.godmode').css('padding-top'));

            return height_logo + height_tabs + padding_menu + height_position;
        }
    });
</script>