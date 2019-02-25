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

/*
    Cambiar $config['classic_menu']
    ¿¿¿donde??? ¿donde la repeticion?
 */

var type_menu = "<?php echo $config['classic_menu']; ?>";

if(type_menu){
    console.log('es clasico, mantenlo');
}
else{
    console.log('es colapsado, mantenlo');
}

//Asignar por defecto colapsado !!! IMPORTANTE!!!!!!!!!!!!!!!!!!!!!!!!!

$(document).ready(function(){    
    var variable_boton = localStorage.getItem("variable");    
    document.getElementById("menu_type").innerHTML = variable_boton;

    if ($('#menu_type').text() == 'colapsado' || $('#menu_type').text() == '') {
        if($('#menu_type').text() == ''){
            localStorage.setItem("variable", "colapsado");
            document.getElementById("menu_type").innerHTML = localStorage.variable;
        }
        
        $('#menu_full').removeClass('menu_full_classic').addClass('menu_full_collapsed');  
      /*  $('.logo_full').removeClass("logo_show").addClass("logo_hide");
        $('.logo_icon').removeClass('logo_hide').addClass('logo_show'); */
        $('.logo_full').css('display','none');
        $('.logo_icon').css('display','block');
        $('div#title_menu').removeClass('title_menu_classic').addClass('title_menu_collapsed');
        $('div#page').removeClass('page_classic').addClass('page_collapsed');
        $('#header_table').removeClass('header_table_classic').addClass('header_table_collapsed');
        $('#button_collapse').removeClass('button_classic').addClass('button_collapsed');      
        $('ul.submenu').css('left', '59px');
        $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');//PROBLEMA Y TB SBMENU NO HIDDEN
        $('#top_btn').css('left', '0px');
    }
    else if ($('#menu_type').text() == 'clasico') {
        $('#menu_full').removeClass('menu_full_collapsed').addClass('menu_full_classic'); 
      /*  $('.logo_icon').removeClass('logo_show').addClass('logo_hide'); 
        $('.logo_full').removeClass("logo_hide").addClass("logo_show");*/
        $('.logo_icon').css('display','none');
        $('.logo_full').css('display','block');
        $('div#title_menu').removeClass('title_menu_collapsed').addClass('title_menu_classic');
        $('div#page').removeClass('page_collapsed').addClass('page_classic');
        $('#header_table').removeClass('header_table_collapsed').addClass('header_table_classic');
        $('#button_collapse').removeClass('button_collapsed').addClass('button_classic');
        $('ul.submenu').css('left', '214px');    
        $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");
        $('#top_btn').css('left', '77.5px');
    }
    else{
        console.log('else no ha elegido aun, default-else');
        localStorage.setItem("variable", "colapsado");
        document.getElementById("menu_type").innerHTML = localStorage.variable;
    }
    
});







    // Set the height of the menu.
    $(window).on('load', function (){   
        $("#menu_full").height($("#container").height());
    });

    // When the user scrolls down 400px from the top of the document, show the button.
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        if (document.body.scrollTop > 400 || document.documentElement.scrollTop > 400) {
            document.getElementById("top_btn").style.display = "block";
        } else {
            document.getElementById("top_btn").style.display = "none";
        }
    }

    // When the user clicks on the button, scroll to the top of the document.
    function topFunction() {
        document.body.scrollTop = 0; // For Safari.
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera.
    }


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
    echo html_print_image('images/custom_logo/'.$config['custom_logo'], true, ['border' => '0', 'width' => '215', 'alt' => 'Logo', 'class' => 'logo_full']);
}

if (isset($config['custom_logo_collapsed'])) {
    echo html_print_image('images/custom_logo/'.$config['custom_logo_collapsed'], true, ['border' => '0', 'width' => '60', 'alt' => 'Logo', 'class' => 'logo_icon']);
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
echo '<div id="menu_type" class="invisible"></div>';
echo '<button onclick="topFunction()" id="top_btn" title="Go to top"></button>';
/*
    echo '<form method="post"><button type="button" id="button_collapse" class="button_collapse">BOTON</button>';
    html_print_input_hidden('button_collapse', 1);
    echo '</form>';
*/

// require ("links_menu.php");
echo '</div>';
// menu_container
ui_require_jquery_file('cookie');

$config_fixed_menu = false;
if (isset($config['fixed_menu'])) {
    $config_fixed_menu = $config['fixed_menu'];
}

$config_fixed_header = false;
if (isset($config['fixed_header'])) {
    $config_fixed_header = $config['fixed_header'];
}
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */



//var classic_menu;
//$(document).ready(function(){   
$('#button_collapse').on('click', function() {   

/*
    var elem = document.getElementById('button_collapse');
elem.className = elem.className.replace('button_collapse', 'cambiar');
*/
    if($('#menu_full').hasClass('menu_full_classic')){
        localStorage.setItem("variable", "colapsado");
        //$('#button_collapse').css('color','pink');
        document.getElementById("menu_type").innerHTML = localStorage.variable;
        $('ul.submenu').css('left', '59px');//hacer que esto se haga aqui
        $('#top_btn').css('left', '0px');
    }
    else if($('#menu_full').hasClass('menu_full_collapsed')){
        localStorage.setItem("variable", "clasico");
        //$('#button_collapse').css('color','blue');
        document.getElementById("menu_type").innerHTML = localStorage.variable;
        $('ul.submenu').css('left', '214px');//hacer que esto se haga aqui
        $('#top_btn').css('left', '77.5px');
    }
    else{
       console.log('else');
    }

  /*  $('.logo_full').toggleClass("logo_show logo_hide");
    $('.logo_icon').toggleClass('logo_hide logo_show');*/
    $('.logo_full').toggle();
    $('.logo_icon').toggle();
    $('#menu_full').toggleClass('menu_full_classic menu_full_collapsed');    
    $('#button_collapse').toggleClass('button_classic button_collapsed');
    $('div#title_menu').toggleClass('title_menu_classic title_menu_collapsed');
    $('div#page').toggleClass('page_classic page_collapsed');
    $('#header_table').toggleClass('header_table_classic header_table_collapsed');
    $('li.menu_icon').toggleClass("no_hidden_menu menu_icon_collapsed");



    console.log('entra click');
    console.log(localStorage.variable);
/*
if ($('#button_collapse').text() == 'clasico') {  
    classic_menu = true;
}
else {
    classic_menu = false;
}
console.log('aqui comprueba click, y si el menu no ha cambiado, no deberia recargar todo');
console.log(classic_menu);
*/


});

//});


var autohidden_menu = <?php echo $autohidden_menu; ?>;
var fixed_menu = <?php echo json_encode((bool) $config_fixed_menu); ?>;
var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;
var id_user = "<?php echo $config['id_user']; ?>";
var cookie_name = id_user + '-pandora_menu_state';
var cookie_name_encoded = btoa(cookie_name);
var click_display = "<?php echo $config['click_display']; ?>";
//var classic_menu = parseInt("<?php echo $config['classic_menu']; ?>");


//if ((isNaN(classic_menu)) || (classic_menu == 0)) {
//f(localStorage.variable == 'clasico'){  
/*
if ($('#button_collapse').text() == 'clasico') {  
    classic_menu = true;
}
else {
    classic_menu = false;
}
console.log('aqui comprueba si es classic_menu');
console.log(classic_menu);
*/
/*
if (classic_menu) {
    autohidden_menu = 1;
}
*/

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

$(document).ready( function() {

// repeticion de if

var classic_menu;
if ($('#menu_type').text() == 'clasico') {  
    classic_menu = true;
    <?php $config['classic_menu'] = true; ?>
}
else {
    classic_menu = false;
    <?php $config['classic_menu'] = false; ?>
}



console.log('aqui comprueba repeticion ');
console.log(classic_menu);
// fin repeticion de if


    //Daniel maya 02/06/2016 Fixed menu position--INI
   /* if (fixed_menu) {
        $('div#menu')
            .css('position', 'fixed')
            .css('z-index', '9000')
            .css('top','80px')
    }else{
        $('div#menu')
            .css('z-index', '9000')
    }
    if (fixed_header) {
        $('div#menu')
            .css('position', 'fixed')
            .css('z-index', '9000')
            .css('top','80px')
        $('#menu_tab_frame_view').css('margin-top','20px')
    }*/

   //console.log(click_display);

   // if (autohidden_menu) {
        handsInMenu = 0;
        openTimeMenu = 0;
        if (classic_menu) {
            //$('div#title_menu').show();
            handsInMenu = 1;
            openTimeMenu = new Date().getTime();
            /*$('#menu').css('width', '145px');
            $('#menu').css('position', 'block');
            $('div#menu').css('top', '80px');
            $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");*/
            /*$('ul.submenu').css('left', '214px');*/
           /* $('#menu_full').css('width','215px');
            $('.button_collapse').css('width','215px');
            $('div#page').addClass('page_classic');
            $('#header_table').addClass('header_table_classic'); */
            console.log('a (es clasico)');
            /*$('#menu').mouseleave(function() {
                handsInMenu = 0;
                setTimeout(function() {
                    openedMenu = new Date().getTime() - openTimeMenu;
                    if(openedMenu > 1000 && handsInMenu == 0) {
                       /* $('#menu').css('width', '145px');
                        $('#menu').css('position', 'block');*/
             /*           console.log('a');
                        console.log(classic_menu);
                      /*  $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");*/
              /*          $('ul.submenu').css('left', '214px');
                       /* $('#menu_full').css('width','215px');
                        $('.button_collapse').css('width','215px');
                        $('div#page').addClass('page_classic');
                        $('#header_table').addClass('header_table_classic');*/         
              /*      }
                }, 2500);
            });*/
        }
        else {
            //NO ES CLASSIC MENU
            if(!click_display){
                $('#menu').mouseenter(function() {
                   // $('div#title_menu').show();
                    handsInMenu = 1;
                    openTimeMenu = new Date().getTime();
                    /*$('#menu').css('width', '145px');*/
                    console.log('b (collapsed)');
                   /* $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");*/
                    $('li.menu_icon').find('li').addClass("no_hidden_menu");
                    /*$('ul.submenu').css('left', '214px');*/
                    /*$('#menu_full').css('width','215px');
                    $('.button_collapse').css('width','215px');
                    $('div#page').addClass('page_classic');
                    $('#header_table').addClass('header_table_classic'); */
                }).mouseleave(function() {
                    handsInMenu = 0;
                    setTimeout(function() {
                        openedMenu = new Date().getTime() - openTimeMenu;
                        if(openedMenu > 1000 && handsInMenu == 0) {
                            console.log('c (collapsed)');
                           /* $('#menu').css('width', '45px');
                            $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');*/
                            $('li.menu_icon').find('li').removeClass( " no_hidden_menu" );
                          /*  $('ul.submenu').css('left', '44px');*/
                            //$('div#title_menu').hide();
                        }
                    }, 2500);
                });
            }else{
                $(document).ready(function() {
                    $('#menu').on("click", function() {
                        //$('div#title_menu').show();
                        handsInMenu = 1;
                        openTimeMenu = new Date().getTime();
                       /* $('#menu').css('width', '145px');*/
                       console.log('d (collapsed)');
                       /* $('li.menu_icon').removeClass('menu_icon_collapsed').addClass("no_hidden_menu");*/
                        $('li.menu_icon').find('li').addClass("no_hidden_menu");
                       /* $('ul.submenu').css('left', '44px');*/
                       /* $('#menu_full').css('width','215px');
                        $('.button_collapse').css('width','215px');
                        $('div#page').addClass('page_classic');
                        $('#header_table').addClass('header_table_classic'); */
                    })
                    .mouseleave(function() {
                        handsInMenu = 0;
                        setTimeout(function() {
                            openedMenu = new Date().getTime() - openTimeMenu;
                            if(openedMenu > 1000 && handsInMenu == 0) {
                                console.log('e (collapsed)');
                              /*  $('#menu').css('width', '45px');
                                $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');*/
                                $('li.menu_icon').find('li').removeClass( " no_hidden_menu" );
                               /* $('ul.submenu').css('left', '44px');*/
                                //$('div#title_menu').hide();
                            }
                        }, 5500);
                    });
                });
            }
        }
   /* }
    else {
        $('div#title_menu').hide();
        if(!click_display){
            $('#menu').mouseenter(function() {
                handsInMenu = 1;
                openTimeMenu = new Date().getTime();
                $('ul.submenu').css('left', '44px');
            }).mouseleave(function() {
                handsInMenu = 0;
                setTimeout(function() {
                    openedMenu = new Date().getTime() - openTimeMenu;
                    if(openedMenu > 1000 && handsInMenu == 0) {
                        console.log('f');
                        $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
                        $('li.menu_icon').find('li').removeClass( " no_hidden_menu" );
                        $('ul.submenu').css('left', '44px');
                    }
                }, 2500);
            });
        }        
    }
        }        
    }
        }        
    }
        }        
    }*/
});
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


       // if (!classic_menu) {
            //Daniel maya 02/06/2016 Display menu with click --INI
            if(!click_display){
                $('#container').click(function() {
                    openTime = 4000;
                    if( typeof(table_hover) != 'undefined')
                        $("ul#sub"+table_hover[0].id).hide();
                    if( typeof(table_hover2) != 'undefined')
                        $("ul#sub"+table_hover2[0].id).hide();
                   /* $('#menu').css('width', '45px');
                    $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
                    $('ul.submenu').css('left', '44px');*/
                    //$('div#title_menu').hide();
                    console.log('m (collapsed)');
                });
            }else{
                $('#main').click(function() {
                    openTime = 4000;
                    if( typeof(table_hover) != 'undefined')
                        $("ul#sub"+table_hover[0].id).hide();
                    if( typeof(table_hover2) != 'undefined')
                        $("ul#sub"+table_hover2[0].id).hide();
                    /*$('#menu').css('width', '45px');
                    $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
                    $('ul.submenu').css('left', '44px');*/
                   // $('div#title_menu').hide();
                    console.log('n (collapsed)');
                });
            }
            //Daniel maya 02/06/2016 Display menu with click --END
      /*  }
        else {
            if(!click_display){
                $('#container').click(function() {
                    openTime = 4000;
                    if( typeof(table_hover) != 'undefined')
                        $("ul#sub"+table_hover[0].id).hide();
                    if( typeof(table_hover2) != 'undefined')
                        $("ul#sub"+table_hover2[0].id).hide();
                    /*$('#menu').css('width', '145px');*/
                   /* $('ul.submenu').css('left', '214px');
                    $('#menu_full').css('width','215px');
                    $('.button_collapse').css('width','215px');
                    $('div#page').addClass('page_classic');
                    $('#header_table').addClass('header_table_classic'); */
        /*            console.log('o (es clasico)');
                });
            }else{
                $('#main').click(function() {
                    openTime = 4000;
                    if( typeof(table_hover) != 'undefined')
                        $("ul#sub"+table_hover[0].id).hide();
                    if( typeof(table_hover2) != 'undefined')
                        $("ul#sub"+table_hover2[0].id).hide();
                    /*$('#menu').css('width', '145px');*/
                   /* $('ul.submenu').css('left', '214px');
                    $('#menu_full').css('width','215px');
                    $('.button_collapse').css('width','215px');
                    $('div#page').addClass('page_classic');
                    $('#header_table').addClass('header_table_classic'); */
          /*          console.log('p (es clasico)');
                });
            }
        }*/
        
       // if (classic_menu) {
            $('div.menu>ul>li>ul>li>a').click(function() {
                openTime = 4000;
                if( typeof(table_hover) != 'undefined')
                    $("ul#sub"+table_hover[0].id).hide();
                if( typeof(table_hover2) != 'undefined')
                    $("ul#sub"+table_hover2[0].id).hide();
               /* $('ul.submenu').css('left', '214px');*/
                console.log('q (es clasico)');
            });    
                
            $('div.menu>ul>li>ul>li>ul>li>a').click(function() {
                openTime = 4000;
                if( typeof(table_hover) != 'undefined')
                    $("ul#sub"+table_hover[0].id).hide();
                if( typeof(table_hover2) != 'undefined')
                    $("ul#sub"+table_hover2[0].id).hide();
                /*$('ul.submenu').css('left', '214px');*/
                console.log('r (es clasico)');
            });
       /* }
        else {
            $('div.menu>ul>li>ul>li>a').click(function() {
                openTime = 4000;
                if( typeof(table_hover) != 'undefined')
                    $("ul#sub"+table_hover[0].id).hide();
                if( typeof(table_hover2) != 'undefined')
                    $("ul#sub"+table_hover2[0].id).hide();
                /*$('#menu').css('width', '45px');
                $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
                $('ul.submenu').css('left', '44px');*/
                //$('div#title_menu').hide();
        /*        console.log('s (no es clasico)');
            });    

            $('div.menu>ul>li>ul>li>ul>li>a').click(function() {
                openTime = 4000;
                if( typeof(table_hover) != 'undefined')
                    $("ul#sub"+table_hover[0].id).hide();
                if( typeof(table_hover2) != 'undefined')
                    $("ul#sub"+table_hover2[0].id).hide();
               /* $('#menu').css('width', '45px');
                $('li.menu_icon').removeClass("no_hidden_menu").addClass('menu_icon_collapsed');
                $('ul.submenu').css('left', '44px');*/
               // $('div#title_menu').hide();
        /*        console.log('t (no es clasico)');
            });
        }*/
    });
    
    

</script>
