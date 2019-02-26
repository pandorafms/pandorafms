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
require_once 'include/functions_messages.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_notifications.php';

// Check permissions
// Global errors/warnings checking.
config_check();

?>

<div id="header_table"> 
    <div id="header_table_inner">           
        <?php
        // ======= Alerts ===============================================
        $check_minor_release_available = false;
        $pandora_management = check_acl($config['id_user'], 0, 'PM');

        $check_minor_release_available = db_check_minor_relase_available();

        if ($check_minor_release_available) {
            if (users_is_admin($config['id_user'])) {
                if ($config['language'] == 'es') {
                    set_pandora_error_for_header('Hay una o mas revisiones menores en espera para ser actualizadas. <a style="font-size:8pt;font-style:italic;" target="blank" href="http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Actualizacion#Versi.C3.B3n_7.0NG_.28_Rolling_Release_.29">'.__('Sobre actualización de revisión menor').'</a>', 'Revisión/es menor/es disponible/s');
                } else {
                    set_pandora_error_for_header('There are one or more minor releases waiting for update. <a style="font-size:8pt;font-style:italic;" target="blank" href="http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Anexo_Upgrade#Version_7.0NG_.28_Rolling_Release_.29">'.__('About minor release update').'</a>', 'minor release/s available');
                }
            }
        }

        echo '<div id="alert_messages" style="display: none"></div>';

        if ($config['alert_cnt'] > 0) {
            $maintenance_link = 'javascript:';
            $maintenance_title = __('System alerts detected - Please fix as soon as possible');
            $maintenance_class = $maintenance_id = 'show_systemalert_dialog white';

            $maintenance_link_open_txt = '<a href="'.$maintenance_link.'" title="'.$maintenance_title.'" class="'.$maintenance_class.'" id="show_systemalert_dialog">';
            $maintenance_link_open_img = '<a href="'.$maintenance_link.'" title="'.$maintenance_title.'" class="'.$maintenance_class.'">';
            $maintenance_link_close = '</a>';
            if (!$pandora_management) {
                $maintenance_img = '';
            } else {
                $maintenance_img = $maintenance_link_open_img.html_print_image(
                    'images/header_alert_gray.png',
                    true,
                    [
                        'title' => __(
                            'You have %d warning(s)',
                            $config['alert_cnt']
                        ),
                       // 'id'    => 'yougotalert',
                        'class' => 'bot',
                    ]
                ).'<p><span>'.$config['alert_cnt'].'</span></p>'.$maintenance_link_close;
            }
        } else {
            if (!$pandora_management) {
                $maintenance_img = '';
            } else {
                $maintenance_img = html_print_image('images/header_ready_gray.png', true, ['title' => __('There are not warnings'), 'id' => 'yougotalert', 'class' => 'bot']);
            }
        }

        $header_alert = '<div id="header_alert">'.$maintenance_img.'</div>';


        // Messages
        $msg_cnt = messages_get_count($config['id_user']);
        if ($msg_cnt > 0) {
            echo '<div id="dialog_messages" style="display: none"></div>';

            $header_message = '<div id="header_message"><a href="ajax.php?page=operation/messages/message_list" title="'.__('Message overview').'" id="show_messages_dialog">';
            $header_message .= html_print_image('images/header_email.png', true, ['title' => __('You have %d unread message(s)', $msg_cnt), 'id' => 'yougotmail', 'class' => 'bot', 'style' => 'width:24px;']);
            $header_message .= '<p><span>'.$msg_cnt.'</span></p></a></div>';
        }


        // Chat messages
        $header_chat = "<div id='header_chat'><span id='icon_new_messages_chat' style='display: none;'>";
        $header_chat .= "<a href='index.php?sec=workspace&sec2=operation/users/webchat'>";
        $header_chat .= html_print_image('images/header_chat_gray.png', true, ['title' => __('New chat message')]);
        $header_chat .= '</a></span></div>';


        // Search
        $acl_head_search = true;
        if ($config['acl_enterprise'] == 1 && !users_is_admin()) {
            $acl_head_search = db_get_sql(
                "SELECT sec FROM tusuario 
                    INNER JOIN tusuario_perfil ON tusuario.id_user = tusuario_perfil.id_usuario 
                    INNER JOIN tprofile_view ON tprofile_view.id_profile = tusuario_perfil.id_perfil 
                    WHERE tusuario.id_user = '".$config['id_user']."' AND (sec = '*' OR sec = 'head_search')"
            );
        }

        if ($acl_head_search) {
            // Search bar
            $search_bar = '<form method="get" style="display: inline;" name="quicksearch" action="">';
            if (!isset($config['search_keywords'])) {
                $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
            } else {
                if (strlen($config['search_keywords']) == 0) {
                    $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
                } else {
                    $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = false; </script>';
                }
            }

            $search_bar .= '<input type="text" id="keywords" name="keywords"';
            if (!isset($config['search_keywords'])) {
                $search_bar .= "value='".__('Enter keywords to search')."'";
            } else if (strlen($config['search_keywords']) == 0) {
                $search_bar .= "value='".__('Enter keywords to search')."'";
            } else {
                $search_bar .= "value='".$config['search_keywords']."'";
            }

            $search_bar .= 'onfocus="javascript: if (fieldKeyWordEmpty) $(\'#keywords\').val(\'\');"
                    onkeyup="javascript: fieldKeyWordEmpty = false;"
                    style="margin-top:5px;" class="search_input" />';

            // $search_bar .= 'onClick="javascript: document.quicksearch.submit()"';
            $search_bar .= "<input type='hidden' name='head_search_keywords' value='abc' />";
            $search_bar .= '</form>';

            $header_searchbar  = '<div id="header_searchbar">'.ui_print_help_tip(__('Blank characters are used as AND conditions'), true);
            $header_searchbar .= $search_bar.'</div>';
        }


            // clippy
        if ($config['tutorial_mode'] !== 'expert' && !$config['disable_help']) {
            $header_clippy = '<div id="header_clippy"><a href="javascript: show_clippy();">'.html_print_image(
                'images/clippy_icon_gray.png',
                true,
                [
                    'id'    => 'clippy',
                    'class' => 'clippy',
                    'alt'   => __('%s assistant', get_product_name()),
                    'title' => __(
                        '%s assistant',
                        get_product_name()
                    ),
                ]
            ).'</a></div>';
        }


        // Servers check
        $servers = [];
        $servers['all'] = (int) db_get_value('COUNT(id_server)', 'tserver');
        $servers['up'] = (int) servers_check_status();
        $servers['down'] = ($servers['all'] - $servers['up']);
        if ($servers['up'] == 0) {
            // All Servers down or no servers at all
            $servers_check_img = html_print_image('images/header_down_gray.png', true, ['alt' => 'cross', 'class' => 'bot', 'title' => __('All systems').': '.__('Down')]);
        } else if ($servers['down'] != 0) {
            // Some servers down
            $servers_check_img = html_print_image('images/header_warning_gray.png', true, ['alt' => 'error', 'class' => 'bot', 'title' => $servers['down'].' '.__('servers down')]);
        } else {
            // All servers up
            $servers_check_img = html_print_image('images/header_ready_gray.png', true, ['alt' => 'ok', 'class' => 'bot', 'title' => __('All systems').': '.__('Ready')]);
        }

        unset($servers);
        // Since this is the header, we don't like to trickle down variables.
        $servers_link_open = '<a class="white" href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server&amp;refr=60">';
        $servers_link_close = '</a>';

        $header_server = '<div id="header_server">'.$servers_link_open.$servers_check_img.$servers_link_close.'</div>';


        // Main help icon
        if (!$config['disable_help']) {
            $header_help = '<div id="header_help"><a href="#" class="modalpopup" id="helpmodal">'.html_print_image(
                'images/header_help_gray.png',
                true,
                [
                    'title' => __('Main help'),
                    'id'    => 'helpmodal',
                    'class' => 'modalpopup',
                ]
            ).'</a></div>';
        }


        // ======= Autorefresh code =============================
        $autorefresh_txt = '';
        $autorefresh_additional = '';

        $ignored_params = [
            'agent_config' => false,
            'code'         => false,
        ];

        if (!isset($_GET['sec2'])) {
            $_GET['sec2'] = '';
        }

        if (!isset($_GET['refr'])) {
            $_GET['refr'] = null;
        }

        $select = db_process_sql("SELECT autorefresh_white_list,time_autorefresh FROM tusuario WHERE id_user = '".$config['id_user']."'");
        $autorefresh_list = json_decode($select[0]['autorefresh_white_list']);

        if ($autorefresh_list !== null && array_search($_GET['sec2'], $autorefresh_list) !== false) {
            $do_refresh = true;
            if ($_GET['sec2'] == 'operation/agentes/pandora_networkmap') {
                if ((!isset($_GET['tab'])) || ($_GET['tab'] != 'view')) {
                    $do_refresh = false;
                }
            }

            if ($do_refresh) {
                $autorefresh_img = html_print_image('images/header_refresh_gray.png', true, ['class' => 'bot', 'alt' => 'lightning', 'title' => __('Configure autorefresh')]);

                if ($_GET['refr']) {
                    $autorefresh_txt .= ' (<span id="refrcounter">'.date('i:s', $config['refr']).'</span>)';
                }

                $ignored_params['refr'] = '';
                $values = get_refresh_time_array();
                $autorefresh_additional = '<span id="combo_refr" style="display: none;">';
                $autorefresh_additional .= html_print_select($values, 'ref', '', '', __('Select'), '0', true, false, false);
                $autorefresh_additional .= '</span>';
                unset($values);

                $autorefresh_link_open_img = '<a class="white autorefresh" href="'.ui_get_url_refresh($ignored_params).'">';

                if ($_GET['refr']) {
                    $autorefresh_link_open_txt = '<a class="autorefresh autorefresh_txt" href="'.ui_get_url_refresh($ignored_params).'">';
                } else {
                    $autorefresh_link_open_txt = '<a>';
                }

                $autorefresh_link_close = '</a>';
            } else {
                $autorefresh_img = html_print_image('images/header_refresh_disabled_gray.png', true, ['class' => 'bot autorefresh_disabled', 'alt' => 'lightning', 'title' => __('Disabled autorefresh')]);

                $ignored_params['refr'] = false;

                $autorefresh_link_open_img = '';
                $autorefresh_link_open_txt = '';
                $autorefresh_link_close = '';
            }
        } else {
            $autorefresh_img = html_print_image('images/header_refresh_disabled_gray.png', true, ['class' => 'bot autorefresh_disabled', 'alt' => 'lightning', 'title' => __('Disabled autorefresh')]);

            $ignored_params['refr'] = false;

            $autorefresh_link_open_img = '';
            $autorefresh_link_open_txt = '';
            $autorefresh_link_close = '';
        }

        $header_autorefresh = '<div id="header_autorefresh">'.$autorefresh_link_open_img.$autorefresh_img.$autorefresh_link_close.'</div>';
        $header_autorefresh_counter = '<div id="header_autorefresh_counter">'.$autorefresh_link_open_txt.$autorefresh_txt.$autorefresh_link_close.$autorefresh_additional.'</div>';


        // qr
        if ($config['show_qr_code_header'] == 0) {
            $show_qr_code_header = 'display: none;';
        } else {
            $show_qr_code_header = 'display: inline;';
        }

        $header_qr = '<div id="header_qr"><div style="'.$show_qr_code_header.'" id="qr_code_container"><a href="javascript: show_dialog_qrcode();">'.html_print_image(
            'images/qrcode_icon_gray.png',
            true,
            [
                'alt'   => __('QR Code of the page'),
                'title' => __('QR Code of the page'),
            ]
        ).'</a></div></div>';

        echo "<div style='display: none;' id='qrcode_container' title='".__('QR code of the page')."'>";
        echo "<div id='qrcode_container_image'></div>";
        echo '</div>';
        ?>
        <script type='text/javascript'>
            $(document).ready(function() {
                $( "#qrcode_container" ).dialog({
                    autoOpen: false,
                    modal: true
                });
            });
        </script>
        <?php
        // User
        if (is_user_admin($config['id_user']) == 1) {
            $header_user = html_print_image('images/header_user_admin_green.png', true, ['title' => __('Edit my user'), 'class' => 'bot', 'alt' => 'user']);
        } else {
            $header_user = html_print_image('images/header_user_green.png', true, ['title' => __('Edit my user'), 'class' => 'bot', 'alt' => 'user']);
        }

        $header_user = '<div id="header_user"><a href="index.php?sec=workspace&sec2=operation/users/user_edit">'.$header_user.'<span> ('.$config['id_user'].')</span></a></div>';

        // Logout
        $header_logout = '<div id="header_logout"><a class="white" href="'.ui_get_full_url('index.php?bye=bye').'">';
        $header_logout .= html_print_image('images/header_logout_gray.png', true, ['alt' => __('Logout'), 'class' => 'bot', 'title' => __('Logout')]);
        $header_logout .= '</a></div>';


        echo '<div class="header_left">'.$header_alert, $header_message, $header_chat.'</div><div class="header_center">'.$header_searchbar, $header_clippy, $header_help, $header_server, $header_autorefresh, $header_autorefresh_counter, $header_qr.'</div><div class="header_right">'.$header_user, $header_logout.'</div>';
        ?>
    </div>    <!--div que cierra #table_header_inner -->        
</div>    <!--div que cierra #table_header -->


<script type="text/javascript">
    /* <![CDATA[ */
    
    <?php
    $config_fixed_header = false;
    if (isset($config['fixed_header'])) {
        $config_fixed_header = $config['fixed_header'];
    }
    ?>
    
    var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;
    
    var new_chat = <?php echo (int) $_SESSION['new_chat']; ?>;
    $(document).ready (function () {
        <?php
        if (($autorefresh_list !== null) && (array_search($_GET['sec2'], $autorefresh_list) !== false) && (!isset($_GET['refr']))) {
            $do_refresh = true;
            if ($_GET['sec2'] == 'operation/agentes/pandora_networkmap') {
                if ((!isset($_GET['tab'])) || ($_GET['tab'] != 'view')) {
                    $do_refresh = false;
                }
            }

            $new_dashboard = get_parameter('new_dashboard', 0);

            if ($_GET['sec2'] == 'enterprise/dashboard/main_dashboard' && $new_dashboard) {
                $do_refresh = false;
            }

            if ($do_refresh) {
                ?>
                $("a.autorefresh_txt").toggle ();
                $("#combo_refr").toggle ();
                $("#combo_refr").css('padding-right', '9px');
                href = $("a.autorefresh").attr ("href");
                <?php
                if ($select[0]['time_autorefresh']) {
                    ?>
                    var refresh = '<?php echo $select[0]['time_autorefresh']; ?>';
                    $(document).attr ("location", href + refresh);
                    <?php
                }
                ?>
                
                <?php
            }
        }
        ?>

        if (fixed_header) {
            $('div#head').addClass('fixed_header');
            $('div#page')
                .css('padding-top', $('div#head').innerHeight() + 'px')
                .css('position', 'relative');
        }
        
        check_new_chats_icon('icon_new_messages_chat');
        
        /* Temporal fix to hide graphics when ui_dialog are displayed */
        $("#yougotalert").click(function () { 
            $("#agent_access").css("display", "none");
        });
        $("#ui_close_dialog_titlebar").click(function () {
            $("#agent_access").css("display","");
        });
        
        function blinkmail(){
            //$("#yougotmail").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkmail);
        }
        function blinkalert(){
            $("#yougotalert").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkalert);
        }
        function blinkpubli(){
            $(".publienterprise").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkpubli);
        }
        <?php
        if ($msg_cnt > 0) {
            ?>
            blinkmail();
            <?php
        }
        ?>
        
        
        <?php
        if ($config['alert_cnt'] > 0) {
            ?>
            blinkalert();
            <?php
        }
        ?>
            blinkpubli();

        <?php
        if ($_GET['refr']) {
            ?>
            var refr_time = <?php echo (int) get_parameter('refr', 0); ?>;
            var t = new Date();
            t.setTime (t.getTime () +
                parseInt(<?php echo ($config['refr'] * 1000); ?>));
            $("#refrcounter").countdown ({until: t, 
                layout: '%M%nn%M:%S%nn%S',
                labels: ['', '', '', '', '', '', ''],
                onExpiry: function () {
                        href = $("a.autorefresh").attr ("href");
                        href = href + refr_time;
                        $(document).attr ("location", href);
                    }
                });
            <?php
        }
        ?>
        
        $("a.autorefresh").click (function () {
            $("a.autorefresh_txt").toggle ();
            $("#combo_refr").toggle ();
            $("#combo_refr").css('padding-right', '9px');
            $("select#ref").change (function () {
                href = $("a.autorefresh").attr ("href");
                $(document).attr ("location", href + this.value);
            });
            
            return false;
        });
    });
/* ]]> */
</script>
