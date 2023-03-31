<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';

require_once $config['homedir'].'/vendor/autoload.php';

use PandoraFMS\User;

// Set root on homedir, as defined in setup.
chdir($config['homedir']);

ob_start();
// Enterprise support
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

if (file_exists(ENTERPRISE_DIR.'/include/functions_login.php')) {
    include_once ENTERPRISE_DIR.'/include/functions_login.php';
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start('ui_process_page_head');
// Enterprise main
enterprise_include('index.php');

$url_css = ui_get_full_url('include/styles/visual_maps.css', false, false, false);
echo '<link rel="stylesheet" href="'.$url_css.'?v='.$config['current_package'].'" type="text/css" />';

html_print_input_hidden('homeurl', $config['homeurl']);

$url_css_modal = ui_get_full_url('include/styles/register.css', false, false, false);
echo '<link rel="stylesheet" href="'.$url_css_modal.'?v='.$config['current_package'].'" type="text/css" />';
// Connection lost alert.
ui_require_javascript_file('connection_check', 'include/javascript/', true);
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
$conn_title = __('Connection with server has been lost');
$conn_text = __('Connection to the server has been lost. Please check your internet connection or contact with administrator.');
ui_print_message_dialog($conn_title, $conn_text, 'connection', '/images/fail@svg.svg');

require_once 'include/functions_visual_map.php';

$hash = get_parameter('hash');
$id_layout = (int) get_parameter('id_layout');
$graph_javascript = (bool) get_parameter('graph_javascript');
$config['id_user'] = get_parameter('id_user');

// Check input hash.
if (User::validatePublicHash($hash) !== true) {
    db_pandora_audit(
        AUDIT_LOG_HACK_ATTEMPT,
        'Trying to access public visual console'
    );
    include 'general/noaccess.php';
    exit;
}

$refr = (int) get_parameter('refr', $config['refr']);
$layout = db_get_row('tlayout', 'id', $id_layout);

if (! $layout) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access visual console without id layout'
    );
    include $config['homedir'].'/general/noaccess.php';
    exit;
}

if (!isset($config['pure'])) {
    $config['pure'] = 0;
}

// ~ $xhr = (bool) get_parameter('xhr');
if ($layout) {
    $id_group = $layout['id_group'];
    $layout_name = $layout['name'];
    $background = $layout['background'];
    $bwidth = $layout['width'];
    $bheight = $layout['height'];
    // ~ $width = (int) get_parameter('width');
    // ~ if ($width <= 0) $width = null;
    // ~ $height = (int) get_parameter('height');
    // ~ if ($height <= 0) $height = null;
    // ~ ob_start();
    // ~ // Render map
    visual_map_print_visual_map(
        $id_layout,
        true,
        true,
        $bwidth,
        $bheight,
        '../../',
        true,
        true,
        true
    );
    // ~ return;
} else {
    echo '<div id="vc-container"></div>';
}

// Floating menu - Start.
echo '<div id="vc-controls" class="zindex300">';

echo '<div id="menu_tab">';
echo '<ul class="mn white-box-content box-shadow flex-row">';

// QR code.
echo '<li class="nomn">';
echo '<a href="javascript: show_dialog_qrcode();">';
echo '<img class="vc-qr" src="../../images/qrcode_icon_2.jpg"/>';
echo '</a>';
echo '</li>';

// Countdown.
echo '<li class="nomn">';
echo '<div class="vc-refr">';
echo '<div class="vc-countdown display_in"></div>';
echo '<div id="vc-refr-form">';
echo __('Refresh').':';
echo html_print_select(
    get_refresh_time_array(),
    'vc-refr',
    $refr,
    '',
    '',
    0,
    true,
    false,
    false
);
echo '</div>';
echo '</div>';
echo '</li>';

// Console name.
echo '<li class="nomn">';
echo '<div class="vc-title">'.$layout_name.'</div>';
echo '</li>';

echo '</ul>';
echo '</div>';

echo '</div>';

// QR code dialog.
echo '<div class="invisible" id="qrcode_container" title="'.__('QR code of the page').'">';
echo '<div id="qrcode_container_image"></div>';
echo '</div>';


ui_require_jquery_file('countdown', 'include/javascript/', true);
ui_require_javascript_file('wz_jsgraphics', 'include/javascript/', true);
ui_require_javascript_file('pandora_visual_console', 'include/javascript/', true);
$ignored_params['refr'] = '';
?>

<style type="text/css">
    svg {
        stroke: none;
    }
</style>

<script language="javascript" type="text/javascript">
    $(document).ready(function () {
        var refr = <?php echo (int) $refr; ?>;
        var href = "<?php echo ui_get_url_refresh($ignored_params); ?>";
        
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
                    //~ cb();
                    url = js_html_entity_decode( href ) + duration;
                    $(document).attr ("location", url);
                }
            });
        }
        startCountDown(refr, false);
        // Auto hide controls
        var controls = document.getElementById('vc-controls');
        autoHideElement(controls, 1000);
        
        $('#vc-controls').change(function (event) {
            refr = Number.parseInt(event.target.value, 10);
            startCountDown(refr, false);
        });
        
        
        $('body').css('background-color','<?php echo $layout['background_color']; ?>');
        $('body').css('margin','0');
        $(".module_graph .menu_graph").css('display','none');
        
        $(".parent_graph").each(function(){
            
        if($(this).css('background-color') != 'rgb(255, 255, 255)'){
                $(this).css('color', '#999');                
                }
        });            

        $(".overlay").removeClass("overlay").addClass("overlaydisabled");
        
        // Start the map fetch
        //~ fetchMap();
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
        
        // Start the map fetch
        //~ fetchMap();
    });
</script>
