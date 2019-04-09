<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

// Login check.
check_login();

require_once $config['homedir'].'/vendor/autoload.php';
require_once $config['homedir'].'/include/functions_visual_map.php';

$id_layout = (int) get_parameter(!is_metaconsole() ? 'id' : 'id_visualmap');

// Get input parameter for layout id.
if (!$id_layout) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$layout = db_get_row('tlayout', 'id', $id_layout);

if (!$layout) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$id_group = $layout['id_group'];
$layout_name = $layout['name'];

// ACL.
$vconsole_read = check_acl($config['id_user'], $id_group, 'VR');
$vconsole_write = check_acl($config['id_user'], $id_group, 'VW');
$vconsole_manage = check_acl($config['id_user'], $id_group, 'VM');

if (!$vconsole_read && !$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without group access'
    );
    include 'general/noaccess.php';
    exit;
}

$refr = (int) get_parameter('refr', $config['vc_refr']);
$graph_javascript = (bool) get_parameter('graph_javascript', true);
$vc_refr = false;

if (isset($config['vc_refr']) && $config['vc_refr'] != 0) {
    $view_refresh = $config['vc_refr'];
} else {
    $view_refresh = '300';
}

// Render map.
$options = [];

$options['consoles_list']['text'] = '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder&refr='.$refr.'">'.html_print_image(
    'images/visual_console.png',
    true,
    ['title' => __('Visual consoles list')]
).'</a>';

if ($vconsole_write || $vconsole_manage) {
    $url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';

    $hash = md5($config['dbpass'].$id_layout.$config['id_user']);

    $options['public_link']['text'] = '<a href="'.ui_get_full_url(
        'operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config['id_user']
    ).'" target="_blank">'.html_print_image(
        'images/camera_mc.png',
        true,
        ['title' => __('Show link to public Visual Console')]
    ).'</a>';
    $options['public_link']['active'] = false;

    $options['data']['text'] = '<a href="'.$url_base.$action.'&tab=data&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        ['title' => __('Main data')]
    ).'</a>';
    $options['list_elements']['text'] = '<a href="'.$url_base.$action.'&tab=list_elements&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/list.png',
        true,
        ['title' => __('List elements')]
    ).'</a>';

    if (enterprise_installed()) {
        $options['wizard_services']['text'] = '<a href="'.$url_base.$action.'&tab=wizard_services&id_visual_console='.$id_layout.'">'.html_print_image(
            'images/wand_services.png',
            true,
            ['title' => __('Services wizard')]
        ).'</a>';
    }

    $options['wizard']['text'] = '<a href="'.$url_base.$action.'&tab=wizard&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/wand.png',
        true,
        ['title' => __('Wizard')]
    ).'</a>';
    $options['editor']['text'] = '<a href="'.$url_base.$action.'&tab=editor&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/builder.png',
        true,
        ['title' => __('Builder')]
    ).'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$view_refresh.'">'.html_print_image('images/operation.png', true, ['title' => __('View')]).'</a>';
$options['view']['active'] = true;
if (!is_metaconsole()) {
    if (!$config['pure']) {
        $options['pure']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$refr.'&pure=1">'.html_print_image('images/full_screen.png', true, ['title' => __('Full screen mode')]).'</a>';
        ui_print_page_header($layout_name, 'images/visual_console.png', false, '', false, $options);
    }

    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 0);
} else {
    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 1);
}

use Models\VisualConsole\Container as VisualConsole;

$visualConsole = VisualConsole::fromArray($layout);
$visualConsoleItems = VisualConsole::getItemsFromDB($id_layout);

// TODO: Extract to a function.
$vcClientPath = 'include/visual-console-client';
$dir = $config['homedir'].'/'.$vcClientPath;
if (is_dir($dir)) {
    $dh = opendir($dir);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            preg_match('/.*.js$/', $file, $match, PREG_OFFSET_CAPTURE);
            if (empty($match) === false) {
                $url = ui_get_full_url(false, false, false, false).$vcClientPath.'/'.$match[0][0];
                echo '<script type="text/javascript" src="'.$url.'"></script>';
                continue;
            }

            preg_match('/.*.css$/', $file, $match, PREG_OFFSET_CAPTURE);
            if (empty($match) === false) {
                $url = ui_get_full_url(false, false, false, false).$vcClientPath.'/'.$match[0][0];
                echo '<link rel="stylesheet" type="text/css" href="'.$url.'" />';
            }
        }

        closedir($dh);
    }
}

echo '<div id="visual-console-container" style="margin:0px auto;position:relative;"></div>';

if ($config['pure']) {
    // Floating menu - Start
    echo '<div id="vc-controls" style="z-index: 999">';

    echo '<div id="menu_tab">';
    echo '<ul class="mn">';

    // Quit fullscreen
    echo '<li class="nomn">';
    echo '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$refr.'">';
    echo html_print_image('images/normal_screen.png', true, ['title' => __('Back to normal mode')]);
    echo '</a>';
    echo '</li>';

    // Countdown
    echo '<li class="nomn">';
    echo '<div class="vc-refr">';
    echo '<div class="vc-countdown"></div>';
    echo '<div id="vc-refr-form">';
    echo __('Refresh').':';
    echo html_print_select(get_refresh_time_array(), 'refr', $refr, '', '', 0, true, false, false);
    echo '</div>';
    echo '</div>';
    echo '</li>';

    // Console name
    echo '<li class="nomn">';
    echo '<div class="vc-title">'.$layout_name.'</div>';
    echo '</li>';

    echo '</ul>';
    echo '</div>';

    echo '</div>';
    // Floating menu - End
    ui_require_jquery_file('countdown');

    ?>
    <style type="text/css">
        /* Avoid the main_pure container 1000px height */
        body.pure {
            min-height: 100px;
            margin: 0px;
            overflow: hidden;
            height: 100%;
            <?php
            echo 'background-color: '.$layout['background_color'].';';
            ?>
        }
        div#main_pure {
            height: 100%;
            margin: 0px;
            <?php
            echo 'background-color: '.$layout['background_color'].';';
            ?>
        }
    </style>
    <?php
}

ui_require_javascript_file('wz_jsgraphics');
ui_require_javascript_file('pandora_visual_console');
$ignored_params['refr'] = '';
?>

<script type="text/javascript">
    var container = document.getElementById("visual-console-container");
    var props = <?php echo (string) $visualConsole; ?>;
    var items = <?php echo '['.implode($visualConsoleItems, ',').']'; ?>;

    if (container != null) {
        try {
            var visualConsole = new VisualConsole(container, props, items);
            console.log(visualConsole);
        } catch (error) {
            console.log("ERROR", error.message);
        }
    }

    $(document).ready (function () {
        var refr = <?php echo (int) $refr; ?>;
        var pure = <?php echo (int) $config['pure']; ?>;
        var href = "<?php echo ui_get_url_refresh($ignored_params); ?>";
        if (pure) {
            var startCountDown = function (duration, cb) {
                $('div.vc-countdown').countdown('destroy');
                if (!duration) return;
                var t = new Date();
                t.setTime(t.getTime() + duration * 1000);
                $('div.vc-countdown').countdown({
                    until: t,
                    format: 'MS',
                    layout: '(%M%nn%M:%S%nn%S <?php echo __('Until refresh'); ?>) ',
                    alwaysExpire: true,
                    onExpiry: function () {
                        $('div.vc-countdown').countdown('destroy');
                        //cb();
                        url = js_html_entity_decode( href ) + duration;
                        $(document).attr ("location", url);
                        /*$.post(window.location.href.replace("refr=300","refr="+new_count), function(respuestaSolicitud){
                            $('#background_<?php echo $id_layout; ?>').html(respuestaSolicitud);
                        });
                        */
                        $("#main_pure").css('background-color','<?php echo $layout['background_color']; ?>');
                        
                        }
                });
            }
            
            startCountDown(refr, false);
            
            var controls = document.getElementById('vc-controls');
            autoHideElement(controls, 1000);
            
            $('select#refr').change(function (event) {
                refr = Number.parseInt(event.target.value, 10);
                new_count = event.target.value;
                startCountDown(refr, false);
            });
        }
        else {
            $('#refr').change(function () {
                $('#hidden-vc_refr').val($('#refr option:selected').val());
            });
        }
        
        $(".module_graph .menu_graph").css('display','none');
        
        $(".parent_graph").each( function() {
            if ($(this).css('background-color') != 'rgb(255, 255, 255)')
                $(this).css('color', '#999');
        });
        
        $(".overlay").removeClass("overlay").addClass("overlaydisabled");
    
    });

    $(window).on('load', function () {
        $('.item:not(.icon) img:not(.b64img)').each( function() {
            if ($(this).css('float')=='left' || $(this).css('float')=='right') {
                if(    $(this).parent()[0].tagName == 'DIV'){
                    $(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
                }
                else if (    $(this).parent()[0].tagName == 'A') {
                    $(this).css('margin-top',(parseInt($(this).parent().parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
                }
                $(this).css('margin-left','');
            }
            else {
                if(parseInt($(this).parent().parent().css('width'))/2-parseInt($(this).css('width'))/2 < 0){
                    $(this).css('margin-left','');
                    $(this).css('margin-top','');
                } else {
                    if(    $(this).parent()[0].tagName == 'DIV'){
                        $(this).css('margin-left',(parseInt($(this).parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                    }
                    else if (    $(this).parent()[0].tagName == 'A') {
                        $(this).css('margin-left',(parseInt($(this).parent().parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                    }
                    $(this).css('margin-top','');
                }
            }
        });
        
        $('.item > div').each( function() {
            if ($(this).css('float')=='left' || $(this).css('float')=='right') {
                if($(this).attr('id').indexOf('clock') || $(this).attr('id').indexOf('overlay')){
                    $(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
                }
                else{
                    $(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2-15)+'px');
                }
                $(this).css('margin-left','');
            }
            else {
                $(this).css('margin-left',(parseInt($(this).parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                $(this).css('margin-top','');
            }
        });
        
        $('.item > a > div').each( function() {
            if ($(this).css('float')=='left' || $(this).css('float')=='right') {
                $(this).css('margin-top',(parseInt($(this).parent().parent().css('height'))/2-parseInt($(this).css('height'))/2-5)+'px');
                $(this).css('margin-left','');
            }
            else {
                $(this).css('margin-left',(parseInt($(this).parent().parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
                $(this).css('margin-top','');
            }
        });
        
        $(".graph:not([class~='noresizevc'])").each(function(){
            height = parseInt($(this).css("height")) - 30;
            $(this).css('height', height);
        });
        
    });
</script>
