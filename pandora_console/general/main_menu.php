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
    var menuType_value = localStorage.getItem("menuType");    
    if (menuType_value == 'collapsed' || menuType_value == ''  || menuType_value == null  || menuType_value == undefined) {
        if(menuType_value == '' || menuType_value == null  || menuType_value == undefined){    
            localStorage.setItem("menuType", "collapsed");
        }

        $('#menu_full').removeClass('menu_full_classic').addClass('menu_full_collapsed'); 
        $('.logo_full').css('display','none');
        $('.logo_icon').css('display','block');
        $('div#title_menu').removeClass('title_menu_classic').addClass('title_menu_collapsed');
        $('div#page').removeClass('page_classic').addClass('page_collapsed');
        $('#header_table').removeClass('header_table_classic').addClass('header_table_collapsed');
        $('#button_collapse').removeClass('button_classic').addClass('button_collapsed');      
        $('ul.submenu').css('left', '59px');
        $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
    }
    else if (menuType_value == 'classic') {
        $('#menu_full').removeClass('menu_full_collapsed').addClass('menu_full_classic'); 
        $('.logo_icon').css('display','none');
        $('.logo_full').css('display','block');
        $('div#title_menu').removeClass('title_menu_collapsed').addClass('title_menu_classic');
        $('div#page').removeClass('page_collapsed').addClass('page_classic');
        $('#header_table').removeClass('header_table_collapsed').addClass('header_table_classic');
        $('#button_collapse').removeClass('button_collapsed').addClass('button_classic');
        $('ul.submenu').css('left', '214px');    
        $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");
    }
    else{
        console.log('else no ha elegido aun, default-else');
        localStorage.setItem("menuType", "collapsed");
    }
    
});


    // Set the height of the menu.
    $(window).on('load', function (){   
        $("#menu_full").height($("#container").height());
    });


</script>
<?php
$autohidden_menu = 0;

if (isset($config['autohidden_menu']) && $config['autohidden_menu']) {
    $autohidden_menu = 1;
}

// Menu container prepared to autohide menu
echo '<div id="menu_full">';

echo '<div class="logo_green"><a href="index.php?sec=main">';
if (isset($config['custom_logo'])) {
    echo html_print_image('images/custom_logo/'.$config['custom_logo'], true, ['border' => '0', 'width' => '215', 'alt' => 'Logo', 'class' => 'logo_full', 'style' => 'display:none']);
}

if (isset($config['custom_logo_collapsed'])) {
    echo html_print_image('images/custom_logo/'.$config['custom_logo_collapsed'], true, ['border' => '0', 'width' => '60', 'alt' => 'Logo', 'class' => 'logo_icon', 'style' => 'display:block']);
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

echo '<div id="button_collapse" class="button_collapse"></div>';


// require ("links_menu.php");
echo '</div>';
// menu_container
ui_require_jquery_file('cookie');
/*
    $config_fixed_menu = false;
    if (isset($config['fixed_menu'])) {
    $config_fixed_menu = $config['fixed_menu'];
}*/

$config_fixed_header = false;
if (isset($config['fixed_header'])) {
    $config_fixed_header = $config['fixed_header'];
}
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */



//var classic_menu;  
$('#button_collapse').on('click', function() {   
    if($('#menu_full').hasClass('menu_full_classic')){
        localStorage.setItem("menuType", "collapsed");
        $('ul.submenu').css('left', '59px');
    }
    else if($('#menu_full').hasClass('menu_full_collapsed')){
        localStorage.setItem("menuType", "classic");
        $('ul.submenu').css('left', '214px');
    }
    else{
       console.log('else');
    }

    $('.logo_full').toggle();
    $('.logo_icon').toggle();
    $('#menu_full').toggleClass('menu_full_classic menu_full_collapsed');    
    $('#button_collapse').toggleClass('button_classic button_collapsed');
    $('div#title_menu').toggleClass('title_menu_classic title_menu_collapsed');
    $('div#page').toggleClass('page_classic page_collapsed');
    $('#header_table').toggleClass('header_table_classic header_table_collapsed');
    $('li.menu_icon').toggleClass("no_hidden_menu menu_icon_collapsed");


    console.log('entra click: '+localStorage.menuType);

});



var autohidden_menu = <?php echo $autohidden_menu; ?>;
//var fixed_menu = 
<?php
// echo json_encode((bool) $config_fixed_menu);
?>

var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;
var id_user = "<?php echo $config['id_user']; ?>";
var cookie_name = id_user + '-pandora_menu_state';
var cookie_name_encoded = btoa(cookie_name);
var click_display = "<?php echo $config['click_display']; ?>";
//var classic_menu = parseInt("
<?php
// echo $config['classic_menu'];
?>





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

    if(!click_display){
            $('.menu_icon').mouseenter(function() {
                table_hover = $(this);
                handsIn = 1;
                openTime = new Date().getTime();
                $("ul#sub"+table_hover[0].id).show();
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
                        $("ul#sub"+table_noHover[0].id).hide(); //table_hover
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
                }).mouseleave(function() {
                    table_noHover = $(this);
                    handsIn = 0;
                    setTimeout(function() {
                        opened = new Date().getTime() - openTime;
                        if(opened > 5000 && handsIn == 0) {
                            openTime = 6000;
                            $("ul#sub"+table_noHover[0].id).hide(); //table_hover
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
                    
                console.log('m'); // cuando no es de click, pero pinchas (dentro o fuera) (¿sirve de algo?)
            });
        }else{
            $('#main').click(function() {
                openTime = 4000;
                if( typeof(table_hover) != 'undefined')
                    $("ul#sub"+table_hover[0].id).hide();
                if( typeof(table_hover2) != 'undefined')
                    $("ul#sub"+table_hover2[0].id).hide();
                    
                console.log('n'); //al pinchar fuera (es de click)
            });
        }

    
        $('div.menu>ul>li>ul>li>a').click(function() {
            openTime = 4000;
            if( typeof(table_hover) != 'undefined')
                $("ul#sub"+table_hover[0].id).hide();
            if( typeof(table_hover2) != 'undefined')
                $("ul#sub"+table_hover2[0].id).hide();
                
            console.log('q'); //al pinchar en un enlace de un submenu
        });    
            
        $('div.menu>ul>li>ul>li>ul>li>a').click(function() {
            openTime = 4000;
            if( typeof(table_hover) != 'undefined')
                $("ul#sub"+table_hover[0].id).hide();
            if( typeof(table_hover2) != 'undefined')
                $("ul#sub"+table_hover2[0].id).hide();
                
            console.log('r'); //al pinchar en un enlace de un sub-submenu
        });
      
    });
    
</script>
