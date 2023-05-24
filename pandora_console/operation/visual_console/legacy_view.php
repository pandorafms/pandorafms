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
global $config;

use PandoraFMS\User;

// Login check
require_once $config['homedir'].'/include/functions_visual_map.php';
ui_require_css_file('visual_maps');

check_login();

$id_layout = (int) get_parameter('id');

if ($id_layout) {
    $default_action = 'edit';
} else {
    $default_action = 'new';
}

if (!defined('METACONSOLE')) {
    $action = get_parameterBetweenListValues(
        'action',
        [
            'new',
            'save',
            'edit',
            'update',
            'delete',
        ],
        $default_action
    );
} else {
    $action = get_parameterBetweenListValues(
        'action2',
        [
            'new',
            'save',
            'edit',
            'update',
            'delete',
        ],
        $default_action
    );
}

$refr = (int) get_parameter('refr', $config['vc_refr']);
$graph_javascript = (bool) get_parameter('graph_javascript', true);
$vc_refr = false;

if (isset($config['vc_refr']) && $config['vc_refr'] != 0) {
    $view_refresh = $config['vc_refr'];
} else {
    $view_refresh = '300';
}

// Get input parameter for layout id
if (! $id_layout) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$layout = db_get_row('tlayout', 'id', $id_layout);

if (! $layout) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$id_group = $layout['id_group'];
$layout_name = $layout['name'];
$background = $layout['background'];
$bwidth = $layout['width'];
$bheight = $layout['height'];

$pure_url = '&pure='.$config['pure'];

// ACL
$vconsole_read = check_acl_restricted_all($config['id_user'], $id_group, 'VR');
$vconsole_write = check_acl_restricted_all($config['id_user'], $id_group, 'VW');
$vconsole_manage = check_acl_restricted_all($config['id_user'], $id_group, 'VM');

if (! $vconsole_read && !$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access visual console without group access'
    );
    include 'general/noaccess.php';
    exit;
}

// Render map
$options = [];

$options['consoles_list']['text'] = '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder&refr='.$refr.'">'.html_print_image(
    'images/visual_console.png',
    true,
    ['title' => __('Visual consoles list')]
).'</a>';

if ($vconsole_write || $vconsole_manage) {
    $url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';

    // Hash for auto-auth in public link.
    $hash = User::generatePublicHash();


    $options['public_link']['text'] = '<a href="'.ui_get_full_url(
        'operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config['id_user']
    ).'" target="_blank">'.html_print_image(
        'images/camera_mc.png',
        true,
        [
            'title' => __('Show link to public Visual Console'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $options['public_link']['active'] = false;

    $options['data']['text'] = '<a href="'.$url_base.$action.'&tab=data&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        [
            'title' => __('Main data'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $options['list_elements']['text'] = '<a href="'.$url_base.$action.'&tab=list_elements&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/list.png',
        true,
        [
            'title' => __('List elements'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    if (enterprise_installed()) {
        $options['wizard_services']['text'] = '<a href="'.$url_base.$action.'&tab=wizard_services&id_visual_console='.$id_layout.'">'.html_print_image(
            'images/wand_services.png',
            true,
            [
                'title' => __('Services wizard'),
                'class' => 'invert_filter',
            ]
        ).'</a>';
    }

    $options['wizard']['text'] = '<a href="'.$url_base.$action.'&tab=wizard&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/wand.png',
        true,
        [
            'title' => __('Wizard'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $options['editor']['text'] = '<a href="'.$url_base.$action.'&tab=editor&id_visual_console='.$id_layout.'">'.html_print_image(
        'images/builder@svg.svg',
        true,
        [
            'title' => __('Builder'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$view_refresh.'">'.html_print_image(
    'images/eye.png',
    true,
    [
        'title' => __('View'),
        'class' => 'invert_filter',
    ]
).'</a>';
$options['view']['active'] = true;

if (!$config['pure']) {
    $options['pure']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$refr.'&pure=1">'.html_print_image(
        'images/full_screen.png',
        true,
        [
            'title' => __('Full screen mode'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    ui_print_standard_header(
        $layout_name,
        'images/visual_console.png',
        false,
        'visual_console_view',
        false,
        $options,
        [
            [
                'link'  => '',
                'label' => __('Topology maps'),
            ],
            [
                'link'  => '',
                'label' => __('Visual console'),
            ],
        ]
    );
}

if (!is_metaconsole()) {
    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 0);
} else {
    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 1);
}

if ($config['pure']) {
    // Container of the visual map (ajax loaded).
    echo '<div id="vc-container">'.visual_map_print_visual_map(
        $id_layout,
        true,
        true,
        null,
        null,
        '',
        false,
        true
    ).'</div>';

    // Floating menu - Start.
    echo '<div id="vc-controls" class="zindex999"">';

    echo '<div id="menu_tab">';
    echo '<ul class="mn">';

    // Quit fullscreen.
    echo '<li class="nomn">';
    if (!is_metaconsole()) {
        echo '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$id_layout.'&refr='.$refr.'">';
    } else {
        echo '<a href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id='.$id_layout.'&refr='.$refr.'">';
    }

    echo html_print_image('images/normal_screen.png', true, ['title' => __('Back to normal mode'), 'class' => 'invert_filter']);
    echo '</a>';
    echo '</li>';

    // Countdown.
    echo '<li class="nomn">';
    if (is_metaconsole()) {
        echo '<div class="vc-refr-meta">';
    } else {
        echo '<div class="vc-refr">';
    }

    echo '<div class="vc-countdown"></div>';
    echo '<div id="vc-refr-form">';
    echo __('Refresh').':';
    echo html_print_select(get_refresh_time_array(), 'refr', $refr, '', '', 0, true, false, false);
    echo '</div>';
    echo '</div>';
    echo '</li>';

    // Console name.
    echo '<li class="nomn">';
    if (is_metaconsole()) {
        echo '<div class="vc-title-meta">'.$layout_name.'</div>';
    } else {
        echo '<div class="vc-title">'.$layout_name.'</div>';
    }

    echo '</li>';

    echo '</ul>';
    echo '</div>';

    echo '</div>';
    // Floating menu - End.
    ui_require_jquery_file('countdown');

    ?>
    <style type="text/css">
        /* Avoid the main_pure container 1000px height */
        body.pure {
            min-height: 100px;
            margin: 0px;
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
} else {
    visual_map_print_visual_map($id_layout, true, true, null, null, '', false, true, true);
}

ui_require_javascript_file('wz_jsgraphics');
ui_require_javascript_file('pandora_visual_console');
$ignored_params['refr'] = '';
?>

<style type="text/css">
    svg {
        stroke: none;
    }
</style>

<script language="javascript" type="text/javascript">
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
