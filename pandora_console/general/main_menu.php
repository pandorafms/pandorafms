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
if (! isset($config['id_user'])) {
    include 'general/login_page.php';
    exit();
}

?>
<script type="text/javascript" language="javascript">

$(document).ready(function(){    
    var menuType_value = "<?php echo $config['menu_type']; ?>";

    if (menuType_value == 'classic') {
        $('ul.submenu').css('left', '214px');    
    }
    else{
        $('ul.submenu').css('left', '59px');
    }
});

</script>
<?php
$autohidden_menu = 0;

if (isset($config['autohidden_menu']) && $config['autohidden_menu']) {
    $autohidden_menu = 1;
}

// Menu container prepared to autohide menu
if ($config['menu_type'] == 'classic') {
    echo '<div id="menu_full" class="menu_full_classic">';
} else {
    echo '<div id="menu_full" class="menu_full_collapsed">';
}

$custom_logo = 'images/custom_logo/'.$config['custom_logo'];
$custom_logo_collapsed = 'images/custom_logo/'.$config['custom_logo_collapsed'];

if (!defined('PANDORA_ENTERPRISE')) {
    $logo_title = get_product_name().' Opensource';
    $custom_logo = 'images/custom_logo/pandora_logo_head_3.png';
    $custom_logo_collapsed = 'images/custom_logo/pandora_logo_green_collapsed.png';
} else {
    if (file_exists(ENTERPRISE_DIR.'/'.$custom_logo)) {
        $custom_logo = ENTERPRISE_DIR.'/'.$custom_logo;
    }

    $logo_title = get_product_name().' Enterprise';
}

echo '<div class="logo_green"><a href="index.php?sec=main">';
if (isset($config['custom_logo'])) {
    if ($config['menu_type'] == 'classic') {
        echo html_print_image($custom_logo, true, ['border' => '0', 'width' => '215', 'alt' => $logo_title, 'class' => 'logo_full', 'style' => 'display:block']);
    } else {
        echo html_print_image($custom_logo, true, ['border' => '0', 'width' => '215', 'alt' => $logo_title, 'class' => 'logo_full', 'style' => 'display:none']);
    }
}

if (isset($config['custom_logo_collapsed'])) {
    if ($config['menu_type'] == 'classic') {
        echo html_print_image($custom_logo_collapsed, true, ['border' => '0', 'width' => '60', 'alt' => $logo_title, 'class' => 'logo_icon', 'style' => 'display:none']);
    } else {
        echo html_print_image($custom_logo_collapsed, true, ['border' => '0', 'width' => '60', 'alt' => $logo_title, 'class' => 'logo_icon', 'style' => 'display:block']);
    }
}

echo '</a></div>';

// echo '<div class="tit bg titop">:: '.__('Operation').' ::</div>';
require 'operation/menu.php';

// Check all enterprise ACL used in godmenu items to print menu headers
if (check_acl($config['id_user'], 0, 'AW')
    || check_acl($config['id_user'], 0, 'PM')
    || check_acl($config['id_user'], 0, 'LM')
    || check_acl($config['id_user'], 0, 'UM')
    || check_acl($config['id_user'], 0, 'LW')
    || check_acl($config['id_user'], 0, 'IW')
    || check_acl($config['id_user'], 0, 'EW')
    || check_acl($config['id_user'], 0, 'DW')
) {
    // echo '<div class="tit bg3">:: '.__('Administration').' ::</div>';
}

require 'godmode/menu.php';

if ($config['menu_type'] == 'classic') {
    echo '<div id="button_collapse" class="button_classic button_collapse"></div>';
} else {
    echo '<div id="button_collapse" class="button_collapsed button_collapse"></div>';
}

// require ("links_menu.php");
echo '</div>';
// menu_container
ui_require_jquery_file('cookie');

$config_fixed_header = false;
if (isset($config['fixed_header'])) {
    $config_fixed_header = $config['fixed_header'];
}
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$('#button_collapse').on('click', function() { 

    if($('#menu_full').hasClass('menu_full_classic')){
        localStorage.setItem("menuType", "collapsed");
        $('ul.submenu').css('left', '59px');
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
    }
    else if($('#menu_full').hasClass('menu_full_collapsed')){
        localStorage.setItem("menuType", "classic");
        $('ul.submenu').css('left', '214px');
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
    }

    $('.logo_full').toggle();
    $('.logo_icon').toggle();
    $('#menu_full').toggleClass('menu_full_classic menu_full_collapsed');    
    $('#button_collapse').toggleClass('button_classic button_collapsed');
    $('div#title_menu').toggleClass('title_menu_classic title_menu_collapsed');
    $('div#page').toggleClass('page_classic page_collapsed');
    $('#header_table').toggleClass('header_table_classic header_table_collapsed');
    $('li.menu_icon').toggleClass("no_hidden_menu menu_icon_collapsed");
});


var autohidden_menu = <?php echo $autohidden_menu; ?>;
var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;
var id_user = "<?php echo $config['id_user']; ?>";
var cookie_name = id_user + '-pandora_menu_state';
var cookie_name_encoded = btoa(cookie_name);
var click_display = "<?php echo $config['click_display']; ?>";


var menuState = $.cookie(cookie_name_encoded);
if (!menuState) {
    menuState = {};
}
else {
    menuState = JSON.parse(menuState);
    open_submenus();
}

function open_submenus () {
    $.each(menuState, function (index, value) {
        if (value)
            $('div.menu>ul>li#' + index + '>ul').show();
    });
    $('div.menu>ul>li.selected>ul').removeClass('invisible');
}

function close_submenus () {
    $.each(menuState, function (index, value) {
        if (value)
            $('div.menu>ul>li#' + index + '>ul').hide();
    });
    $('div.menu>ul>li.selected>ul').addClass('invisible');
}


/* ]]> */
</script>

<script type="text/javascript">
    openTime = 0;
    openTime2 = 0;
    handsIn = 0;
    handsIn2 = 0;


/**
 * Positionate the submenu elements. Add a negative top.
 *
 * @param int index It is the position of li.menu_icon in the ul.
 * @param string id_submenu It is the id of first level submenu.
 * @param string id_submenu2 It is the id of second level submenu.
 * @param int item_height It is the height of a menu item (28 o 35).
 *
 * @return (int) The position (in px).
 */
function menu_calculate_top(index, id_submenu, id_submenu2, item_height){

    var level1 = index;
    var level2 = $('#'+id_submenu+' ul.submenu > li').length;
    var level3 = $('#'+id_submenu2+' > li.sub_subMenu').length;
    var item_height = item_height;

    level2--;
    if (id_submenu2 !== false) {
        // If level3 is set, the position is calculated like box is in the center.
        // wiouth considering level2 box can be moved.
        level3--;
        total = (level1 + level3);
        comp = level3;
    } else {
        total = (level1 + level2);
        comp = level2;
    }

    // Positionate in the middle
    if (total > 12 && ((total < 18) || ((level1 - comp) <= 4))) {
        return - ( Math.floor(comp / 2) * item_height);
    }

    // Positionate in the bottom
    if (total >= 18) {
        return (- comp * item_height);
    }

    // return 0 by default
    return 0;
}


/**
 * Get the menu items to be positioned.
 *
 * @param string item It is the selector of the current element.
 *
 * @return Add the top position in a inline style.
 */
function get_menu_items(item){
    var item_height = parseInt(item.css('min-height'));
    var id_submenu = item.attr('id');
    var id_submenu2 = false;
    var index = item.index();

    if(item.parent().hasClass('godmode')){
        index = index+6; // This is because the menu has divided in two parts.
    }
    var top_submenu = menu_calculate_top(index, id_submenu, id_submenu2, item_height);
    top_submenu = top_submenu+'px';
    $('#'+id_submenu+' ul.submenu').css('top', top_submenu);

    $('.has_submenu').mouseenter(function() {
        id_submenu2 = item.attr('id');
        id_submenu2 = $('#'+id_submenu2+' ul.submenu2').attr('id');
        var top_submenu2 = menu_calculate_top(index, id_submenu, id_submenu2, item_height);
        top_submenu2 = top_submenu2+'px';
        $('#'+id_submenu2).css('top', top_submenu2);
    });
}

/*
 * Show and hide submenus
 */
if(!click_display){
    $('.menu_icon').mouseenter(function() {
        table_hover = $(this);
        handsIn = 1;
        openTime = new Date().getTime();
        $("ul#sub"+table_hover[0].id).show();
        get_menu_items(table_hover);
        if( typeof(table_noHover) != 'undefined')
            if ( "ul#sub"+table_hover[0].id != "ul#sub"+table_noHover[0].id )
                $("ul#sub"+table_noHover[0].id).hide();
    }).mouseleave(function() {
        table_noHover = $(this);
        handsIn = 0;
        setTimeout(function() {
            opened = new Date().getTime() - openTime;
            if(opened > 3000 && handsIn == 0) {
                openTime = 4000;
                $("ul#sub"+table_noHover[0].id).hide();
            }
        }, 2500);
    });
}else{
    $(document).ready(function() {
        if (autohidden_menu) {
            $('.menu_icon').on("click", function() {
                if( typeof(table_hover) != 'undefined'){
                    $("ul#sub"+table_hover[0].id).hide();
                }
                table_hover = $(this);
                handsIn = 1;
                openTime = new Date().getTime();
                $("ul#sub"+table_hover[0].id).show();
                get_menu_items(table_hover);
            }).mouseleave(function() {
                table_noHover = $(this);
                handsIn = 0;
                setTimeout(function() {
                    opened = new Date().getTime() - openTime;
                    if(opened > 5000 && handsIn == 0) {
                        openTime = 6000;
                        $("ul#sub"+table_noHover[0].id).hide();
                    }
                }, 5500);
            });
        } else {
            $('.menu_icon').on("click", function() {
                if( typeof(table_hover) != 'undefined'){
                    $("ul#sub"+table_hover[0].id).hide();
                }
                table_hover = $(this);
                handsIn = 1;
                openTime = new Date().getTime();
                $("ul#sub"+table_hover[0].id).show();
                get_menu_items(table_hover);
            });
        }
    });
}

$('.has_submenu').mouseenter(function() {
    table_hover2 = $(this);
    handsIn2 = 1;
    openTime2 = new Date().getTime();
    $("#sub"+table_hover2[0].id).show();
    if( typeof(table_noHover2) != 'undefined')
        if ( "ul#sub"+table_hover2[0].id != "ul#sub"+table_noHover2[0].id )
            $("ul#sub"+table_noHover2[0].id).hide();
}).mouseleave(function() {
    table_noHover2 = table_hover2;
    handsIn2 = 0;
    setTimeout(function() {
    opened = new Date().getTime() - openTime2;
        if(opened >= 3000 && handsIn2 == 0) {
            openTime2 = 4000;
            $("ul#sub"+table_hover2[0].id).hide();
        }
    }, 3500);
});

$(document).ready(function() {

    if(!click_display){
        $('#container').click(function() {
            openTime = 4000;
            if( typeof(table_hover) != 'undefined')
                $("ul#sub"+table_hover[0].id).hide();
            if( typeof(table_hover2) != 'undefined')
                $("ul#sub"+table_hover2[0].id).hide();
        });
    }else{
        $('#main').click(function() {
            openTime = 4000;
            if( typeof(table_hover) != 'undefined')
                $("ul#sub"+table_hover[0].id).hide();
            if( typeof(table_hover2) != 'undefined')
                $("ul#sub"+table_hover2[0].id).hide();
        });
    }


    $('div.menu>ul>li>ul>li>a').click(function() {
        openTime = 4000;
        if( typeof(table_hover) != 'undefined')
            $("ul#sub"+table_hover[0].id).hide();
        if( typeof(table_hover2) != 'undefined')
            $("ul#sub"+table_hover2[0].id).hide();               
    });    
        
    $('div.menu>ul>li>ul>li>ul>li>a').click(function() {
        openTime = 4000;
        if( typeof(table_hover) != 'undefined')
            $("ul#sub"+table_hover[0].id).hide();
        if( typeof(table_hover2) != 'undefined')
            $("ul#sub"+table_hover2[0].id).hide();
    });
    
});
    
</script>
