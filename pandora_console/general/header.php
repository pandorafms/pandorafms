<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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


if ($config['menu_type'] == 'classic') {
    echo '<div id="header_table" class="header_table_classic">';
} else {
    echo '<div id="header_table" class="header_table_collapsed">';
}
?>
    <div id="header_table_inner">        
        <?php
        // ======= Notifications Discovery ===============================================
        $notifications_numbers = notifications_get_counters();
        $header_discovery = '<div id="header_discovery">'.notifications_print_ball(
            $notifications_numbers['notifications'],
            $notifications_numbers['last_id']
        ).'</div>';

        // ======= Servers List ===============================================
        $servers_list = '<div id="servers_list">';
        $servers = [];
        $servers['all'] = (int) db_get_value('COUNT(id_server)', 'tserver');
        if ($servers['all'] != 0) {
            $servers['up'] = (int) servers_check_status();
            $servers['down'] = ($servers['all'] - $servers['up']);
            if ($servers['up'] == 0) {
                // All Servers down or no servers at all.
                $servers_check_img = html_print_image('images/header_down_gray.png', true, ['alt' => 'cross', 'class' => 'bot', 'title' => __('All systems').': '.__('Down')]);
            } else if ($servers['down'] != 0) {
                // Some servers down.
                $servers_check_img = html_print_image('images/header_warning_gray.png', true, ['alt' => 'error', 'class' => 'bot', 'title' => $servers['down'].' '.__('servers down')]);
            } else {
                // All servers up.
                $servers_check_img = html_print_image('images/header_ready_gray.png', true, ['alt' => 'ok', 'class' => 'bot', 'title' => __('All systems').': '.__('Ready')]);
            }

            unset($servers);
            // Since this is the header, we don't like to trickle down variables.
            $servers_check_img_link = '<a class="white" href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60">';
             $servers_check_img_link .= $servers_check_img;
             $servers_check_img_link .= '</a>';
        };
        $servers_list .= $servers_check_img_link.'</div>';



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


        // Chat messages.
        $header_chat = "<div id='header_chat'><span id='icon_new_messages_chat' style='display: none;'>";
        $header_chat .= "<a href='index.php?sec=workspace&sec2=operation/users/webchat'>";
        $header_chat .= html_print_image('images/header_chat_gray.png', true, ['title' => __('New chat message')]);
        $header_chat .= '</a></span></div>';


        // Search.
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
            // Search bar.
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
                    onkeyup="javascript: fieldKeyWordEmpty = false;" class="search_input" />';

            // $search_bar .= 'onClick="javascript: document.quicksearch.submit()"';
            $search_bar .= "<input type='hidden' name='head_search_keywords' value='abc' />";
            $search_bar .= '</form>';

            $header_searchbar = '<div id="header_searchbar">'.$search_bar.'</div>';
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

        if ($_GET['sec'] == 'main' || !isset($_GET['sec'])) {
            // home screen chosen by the user
            $home_page = '';
            if (isset($config['id_user'])) {
                $user_info = users_get_user_by_id($config['id_user']);
                $home_page = io_safe_output($user_info['section']);
                $home_url = $user_info['data_section'];
            }

            if ($home_page != '') {
                switch ($home_page) {
                    case 'Event list':
                        $_GET['sec2'] = 'operation/events/events';
                    break;

                    case 'Group view':
                        $_GET['sec2'] = 'operation/agentes/group_view';
                    break;

                    case 'Alert detail':
                        $_GET['sec2'] = 'operation/agentes/alerts_status';
                    break;

                    case 'Tactical view':
                        $_GET['sec2'] = 'operation/agentes/tactical';
                    break;

                    case 'Default':
                        $_GET['sec2'] = 'general/logon_ok';
                    break;

                    case 'Dashboard':
                        $_GET['sec2'] = 'enterprise/dashboard/main_dashboard';
                    break;

                    case 'Visual console':
                        $_GET['sec2'] = 'operation/visual_console/render_view';
                    break;

                    case 'Other':
                        $home_url = io_safe_output($home_url);
                        $url_array = parse_url($home_url);
                        parse_str($url_array['query'], $res);
                        foreach ($res as $key => $param) {
                            $_GET[$key] = $param;
                        }
                    break;
                }
            }
        }

        if (!isset($_GET['refr'])) {
            $_GET['refr'] = null;
        }

        $select = db_process_sql(
            "SELECT autorefresh_white_list,time_autorefresh 
            FROM tusuario 
            WHERE id_user = '".$config['id_user']."'"
        );

        $autorefresh_list = json_decode(
            $select[0]['autorefresh_white_list']
        );

        if ($autorefresh_list !== null
            && array_search($_GET['sec2'], $autorefresh_list) !== false
        ) {
            $do_refresh = true;
            if ($_GET['sec2'] == 'operation/agentes/pandora_networkmap') {
                if ((!isset($_GET['tab'])) || ($_GET['tab'] != 'view')) {
                    $do_refresh = false;
                }
            }

            if ($do_refresh) {
                $autorefresh_img = html_print_image(
                    'images/header_refresh_gray.png',
                    true,
                    [
                        'class' => 'bot',
                        'alt'   => 'lightning',
                        'title' => __('Configure autorefresh'),
                    ]
                );

                if ((isset($select[0]['time_autorefresh']) === true)
                    && $select[0]['time_autorefresh'] !== 0
                    && $config['refr'] === null
                ) {
                    $config['refr'] = $select[0]['time_autorefresh'];
                    $autorefresh_txt .= ' (<span id="refrcounter">';
                    $autorefresh_txt .= date(
                        'i:s',
                        $config['refr']
                    );
                    $autorefresh_txt .= '</span>)';
                } else if ($_GET['refr']) {
                    $autorefresh_txt .= ' (<span id="refrcounter">';
                    $autorefresh_txt .= date('i:s', $config['refr']);
                    $autorefresh_txt .= '</span>)';
                }

                $ignored_params['refr'] = '';
                $values = get_refresh_time_array();

                $autorefresh_additional = '<span id="combo_refr" style="display: none;">';
                $autorefresh_additional .= html_print_select(
                    $values,
                    'ref',
                    '',
                    '',
                    __('Select'),
                    '0',
                    true,
                    false,
                    false
                );
                $autorefresh_additional .= '</span>';
                unset($values);
                if ($home_page != '') {
                    $autorefresh_link_open_img = '<a class="white autorefresh" href="index.php?refr=">';
                } else {
                    $autorefresh_link_open_img = '<a class="white autorefresh" href="'.ui_get_url_refresh($ignored_params).'">';
                }

                if ($_GET['refr']
                    || ((isset($select[0]['time_autorefresh']) === true)
                    && $select[0]['time_autorefresh'] !== 0)
                ) {
                    if ($home_page != '') {
                        $autorefresh_link_open_txt = '<a class="autorefresh autorefresh_txt" href="index.php?refr=">';
                    } else {
                        $autorefresh_link_open_txt = '<a class="autorefresh autorefresh_txt" href="'.ui_get_url_refresh($ignored_params).'">';
                    }
                } else {
                    $autorefresh_link_open_txt = '<a>';
                }

                $autorefresh_link_close = '</a>';
                $display_counter = 'display:block';
            } else {
                $autorefresh_img = html_print_image('images/header_refresh_disabled_gray.png', true, ['class' => 'bot autorefresh_disabled', 'alt' => 'lightning', 'title' => __('Disabled autorefresh')]);

                $ignored_params['refr'] = false;

                $autorefresh_link_open_img = '';
                $autorefresh_link_open_txt = '';
                $autorefresh_link_close = '';

                $display_counter = 'display:none';
            }
        } else {
            $autorefresh_img = html_print_image(
                'images/header_refresh_disabled_gray.png',
                true,
                [
                    'class' => 'bot autorefresh_disabled',
                    'alt'   => 'lightning',
                    'title' => __('Disabled autorefresh'),
                ]
            );

            $ignored_params['refr'] = false;

            $autorefresh_link_open_img = '';
            $autorefresh_link_open_txt = '';
            $autorefresh_link_close = '';

            $display_counter = 'display:none';
        }

        $header_autorefresh = '<div id="header_autorefresh">';
        $header_autorefresh .= $autorefresh_link_open_img;
        $header_autorefresh .= $autorefresh_img;
        $header_autorefresh .= $autorefresh_link_close;
        $header_autorefresh .= '</div>';

        $header_autorefresh_counter = '<div id="header_autorefresh_counter" style="'.$display_counter.'">';
        $header_autorefresh_counter .= $autorefresh_link_open_txt;
        $header_autorefresh_counter .= $autorefresh_txt;
        $header_autorefresh_counter .= $autorefresh_link_close;
        $header_autorefresh_counter .= $autorefresh_additional;
        $header_autorefresh_counter .= '</div>';

        // Button for feedback pandora.
        if (enterprise_installed()) {
            $header_feedback = '<div id="feedback-icon-header">';
            $header_feedback .= '<div id="modal-feedback-form" style="display:none;"></div>';
            $header_feedback .= '<div id="msg-header" style="display: none"></div>';
            $header_feedback .= html_print_image(
                '/images/feedback-header.png',
                true,
                [
                    'title' => __('Feedback'),
                    'id'    => 'feedback-header',
                    'alt'   => __('Feedback'),
                    'style' => 'cursor: pointer;',
                ]
            );
            $header_feedback .= '</div>';
        }


        // Support.
        if (enterprise_installed()) {
            $header_support_link = $config['custom_support_url'];
        } else {
            $header_support_link = 'https://pandorafms.com/forums/';
        }

        $header_support = '<div id="header_support">';
        $header_support .= '<a href="'.ui_get_full_external_url($header_support_link).'" target="_blank">';
        $header_support .= html_print_image('/images/header_support.png', true, ['title' => __('Go to support'), 'class' => 'bot', 'alt' => 'user']);
        $header_support .= '</a></div>';

        // Documentation.
        $header_docu = '<div id="header_docu">';
        $header_docu .= '<a href="'.ui_get_full_external_url($config['custom_docs_url']).'" target="_blank">';
        $header_docu .= html_print_image('/images/header_docu.png', true, ['title' => __('Go to documentation'), 'class' => 'bot', 'alt' => 'user']);
        $header_docu .= '</a></div>';


        // User.
        if (is_user_admin($config['id_user']) == 1) {
            $header_user = html_print_image(
                'images/header_user_admin_green.png',
                true,
                [
                    'title' => __('Edit my user'),
                    'class' => 'bot',
                    'alt'   => 'user',
                ]
            );
        } else {
            $header_user = html_print_image(
                'images/header_user_green.png',
                true,
                [
                    'title' => __('Edit my user'),
                    'class' => 'bot',
                    'alt'   => 'user',
                ]
            );
        }

        $header_user = '<div id="header_user"><a href="index.php?sec=workspace&sec2=operation/users/user_edit">'.$header_user.'<span> ('.$config['id_user'].')</span></a></div>';

        // Logout.
        $header_logout = '<div id="header_logout"><a class="white" href="'.ui_get_full_url('index.php?bye=bye').'">';
        $header_logout .= html_print_image(
            'images/header_logout_gray.png',
            true,
            [
                'alt'   => __('Logout'),
                'class' => 'bot',
                'title' => __('Logout'),
            ]
        );
        $header_logout .= '</a></div>';

        echo '<div class="header_left"><span class="header_title">'.$config['custom_title_header'].'</span><span class="header_subtitle">'.$config['custom_subtitle_header'].'</span></div>
            <div class="header_center">'.$header_searchbar.'</div>
            <div class="header_right">'.$header_chat, $header_autorefresh, $header_autorefresh_counter, $header_discovery, $servers_list, $header_feedback, $header_support, $header_docu, $header_user, $header_logout.'</div>';
        ?>
    </div>    <!-- Closes #table_header_inner -->
</div>    <!-- Closes #table_header -->


<!-- Notifications content wrapper-->
<div id='notification-content' style='display:none;' /></div>

<!-- Old style div wrapper -->
<div id="alert_messages" style="display: none"></div>

<script type="text/javascript">
    /* <![CDATA[ */
    
    <?php
    $config_fixed_header = false;
    if (isset($config['fixed_header'])) {
        $config_fixed_header = $config['fixed_header'];
    }

    ?>

    function addNotifications(event) {
        var element = document.getElementById("notification-content");
        if (!element) {
            console.error('Cannot locate the notification content element.');
            return;
        }
        // If notification-content is empty, retrieve the notifications.
        if (!element.firstChild) {
            jQuery.post ("ajax.php",
                {
                    "page" : "godmode/setup/setup_notifications",
                    "get_notifications_dropdown" : 1,
                },
                function (data, status) {
                    // Apppend data
                    element.innerHTML = data;
                    // Show the content
                    element.style.display = "block";
                    attatch_to_image();
                },
                "html"
            );
        } else {
            // If there is some notifications retrieved, only show it.
            element.style.display = "block";
            attatch_to_image();
        }
    }

    function attatch_to_image() {
        var notification_elem = document.getElementById("notification-wrapper");
        if (!notification_elem) return;
        var image_attached =
            document.getElementById("notification-ball-header")
                .getBoundingClientRect()
                .left
        ;
        notification_elem.style.left = image_attached - 300 + "px";
    }

    function notifications_clean_ui(action, self_id) {
        switch(action) {
            case 'item':
                // Recalculate the notification ball.
                check_new_notifications();
                break;
            case 'toast':
                // Only remove the toast element.
                document.getElementById(self_id).remove();
                break;
        }
    }

    function notifications_hide() {
        var element = document.getElementById("notification-content");
        element.style.display = "none"
    }

    function click_on_notification_toast(event) {
        var match = /notification-(.*)-id-([0-9]+)/.exec(event.target.id);
        if (!match) {
            console.error(
                "Cannot handle toast click event. Id not valid: ",
                event.target.id
            );
            return;
        }
        jQuery.post ("ajax.php",
            {
                "page" : "godmode/setup/setup_notifications",
                "mark_notification_as_read" : 1,
                "message": match[2]
            },
            function (data, status) {
                if (!data.result) {
                    console.error("Cannot redirect to URL.");
                    return;
                }
                notifications_clean_ui(match[1], event.target.id);
            },
            "json"
        )
        .fail(function(xhr, textStatus, errorThrown){
            console.error(
                "Failed onclik event on toast. Error: ",
                xhr.responseText
            );
        });
    }

    function print_toast(title, subtitle, severity, url, id, onclick) {
        // TODO severity.
        severity = '';

        // Start the toast.
        var toast = document.createElement('a');
        toast.setAttribute('onclick', onclick);
        toast.setAttribute('href', url);
        toast.setAttribute('target', '_blank');

        // Fill toast.
        var toast_div = document.createElement('div');
        toast_div.className = 'snackbar ' + severity;
        toast_div.id = id;
        var toast_title = document.createElement('h3');
        var toast_text = document.createElement('p');
        toast_title.innerHTML = title;
        toast_text.innerHTML = subtitle;
        toast_div.appendChild(toast_title);
        toast_div.appendChild(toast_text);
        toast.appendChild(toast_div);

        // Show and program the hide event.
        toast_div.className = toast_div.className + ' show';
        setTimeout(function(){
            toast_div.className = toast_div.className.replace("show", "");
        }, 8000);

        return toast;
    }

    function check_new_notifications() {
        var last_id = document.getElementById('notification-ball-header')
            .getAttribute('last_id');
        if (last_id === null) {
            console.error('Cannot retrieve notifications ball last_id.');
            return;
        }

        jQuery.post ("ajax.php",
            {
                "page" : "godmode/setup/setup_notifications",
                "check_new_notifications" : 1,
                "last_id": last_id
            },
            function (data, status) {
                // Clean the toasts wrapper at first.
                var toast_wrapper = document.getElementById(
                    'notifications-toasts-wrapper'
                );
                if (toast_wrapper === null) {
                    console.error('Cannot place toast notifications.');
                    return;
                }
                while (toast_wrapper.firstChild) {
                    toast_wrapper.removeChild(toast_wrapper.firstChild);
                }

                // Return if no new notification.
                if(!data.has_new_notifications) return;

                // Substitute the ball
                var new_ball = atob(data.new_ball);
                var ball_wrapper = document
                    .getElementById('notification-ball-header')
                    .parentElement;
                if (ball_wrapper === null) {
                    console.error('Cannot update notification ball');
                    return;
                }
                // Print the new ball and clean old notifications
                ball_wrapper.innerHTML = new_ball;
                var not_drop = document.getElementById('notification-content');
                while (not_drop.firstChild && not_drop) {
                    not_drop.removeChild(not_drop.firstChild);
                }

                // Add the new toasts.
                if (Array.isArray(data.new_notifications)) {
                    data.new_notifications.forEach(function(ele) {
                        toast_wrapper.appendChild(
                            print_toast(
                                ele.subject,
                                ele.mensaje,
                                ele.criticity,
                                ele.full_url,
                                'notification-toast-id-' + ele.id_mensaje,
                                'click_on_notification_toast(event)'
                            )
                        );
                    });
                }
            },
            "json"
        )
        .fail(function(xhr, textStatus, errorThrown){
            console.error(
                "Cannot get new notifications. Error: ",
                xhr.responseText
            );
        });
    }

    // Resize event
    window.addEventListener("resize", function() {
        attatch_to_image();
    });

    var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;

    var new_chat = <?php echo (int) $_SESSION['new_chat']; ?>;

    /**
    * Loads modal from AJAX to add feedback.
    */
    function show_feedback() {
        var btn_ok_text = '<?php echo __('Send'); ?>';
        var btn_cancel_text = '<?php echo __('Cancel'); ?>';
        var title = '<?php echo __('Report an issue'); ?>';
        var url = '<?php echo 'tools/diagnostics'; ?>';

        load_modal({
            target: $('#modal-feedback-form'),
            form: 'modal_form_feedback',
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            modal: {
                title: title,
                ok: btn_ok_text,
                cancel: btn_cancel_text,
            },
            onshow: {
                page: url,
                method: 'formFeedback',
            },
            onsubmit: {
                page: url,
                method: 'createdScheduleFeedbackTask',
                dataType: 'json',
            },
            ajax_callback: generalShowMsg,
            idMsgCallback: 'msg-header',
        });
    }

    $(document).ready (function () {

        // Check new notifications on a periodic way
        setInterval(check_new_notifications, 10000);

        // Print the wrapper for notifications
        var notifications_toasts_wrapper = document.createElement('div');
        notifications_toasts_wrapper.id = 'notifications-toasts-wrapper';
        document.body.insertBefore(
            notifications_toasts_wrapper,
            document.body.firstChild
        );

        <?php
        if (($autorefresh_list !== null)
            && (array_search(
                $_GET['sec2'],
                $autorefresh_list
            ) !== false) && (!isset($_GET['refr']))
        ) {
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
        }
        ?>

        if (fixed_header) {
            $('div#head').addClass('fixed_header');
            $('div#main').css('padding-top', $('div#head').innerHeight() + 'px');
        }
        
        check_new_chats_icon('icon_new_messages_chat');
        
        /* Temporal fix to hide graphics when ui_dialog are displayed */
        $("#yougotalert").click(function () { 
            $("#agent_access").css("display", "none");
        });
        $("#ui_close_dialog_titlebar").click(function () {
            $("#agent_access").css("display","");
        });

        <?php if (enterprise_installed()) { ?>
            // Feedback.
            $("#feedback-header").click(function () {
                // Clean DOM.
                $("#feedback-header").empty();
                // Function charge Modal.
                show_feedback();
            });
        <?php } ?>

        function blinkpubli(){
            $(".publienterprise").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkpubli);
        }

        blinkpubli();

        <?php
        if ($_GET['refr'] || $do_refresh === true) {
            if ($_GET['sec2'] == 'operation/events/events') {
                $autorefresh_draw = true;
            }
            ?>

            var autorefresh_draw = '<?php echo $autorefresh_draw; ?>';
            $("#header_autorefresh").css('padding-right', '5px');
            if(autorefresh_draw == true) { 
                var refresh_interval = parseInt('<?php echo ($config['refr'] * 1000); ?>');
                var until_time='';

                function events_refresh() {
                    until_time = new Date();
                    until_time.setTime (until_time.getTime () + parseInt(<?php echo ($config['refr'] * 1000); ?>));

                    $("#refrcounter").countdown ({
                        until: until_time, 
                        layout: '%M%nn%M:%S%nn%S',
                        labels: ['', '', '', '', '', '', ''],
                        onExpiry: function () {
                            dt_events.draw(false);
                        }
                    });
                }
                // Start the countdown when page is loaded (first time).
                events_refresh();
                // Repeat countdown according to refresh_interval.
                setInterval(events_refresh, refresh_interval);
            } else {
                var refr_time = <?php echo (int) get_parameter('refr', $config['refr']); ?>;
                var t = new Date();
                t.setTime (t.getTime () + parseInt(<?php echo ($config['refr'] * 1000); ?>)); 
                $("#refrcounter").countdown ({
                    until: t, 
                    layout: '%M%nn%M:%S%nn%S',
                    labels: ['', '', '', '', '', '', ''],
                    onExpiry: function () {
                        href = $("a.autorefresh").attr ("href");
                        href = href + refr_time;
                        $(document).attr ("location", href);
                    }
                });
            }
            <?php
        }
        ?>
        
        $("a.autorefresh").click (function () {
            $("a.autorefresh_txt").toggle ();
            $("#combo_refr").toggle ();
            $("select#ref").change (function () {
                href = $("a.autorefresh").attr ("href");
            
                if(autorefresh_draw == true){
                    inputs = $("#events_form :input");
                    values = {};
                    inputs.each(function() {
                        values[this.name] = $(this).val();
                    })

                    var newValue = btoa(JSON.stringify(values));           
                    <?php
                    // Check if the url has the parameter fb64.
                    if ($_GET['fb64']) {
                        $fb64 = $_GET['fb64'];
                        ?>
                            var fb64 = '<?php echo $fb64; ?>';  
                            // Check if the filters have changed.
                            if(fb64 !== newValue){
                                href = href.replace(fb64, newValue);
                            } 

                            $(document).attr("location", href+ '&refr=' + this.value);
                        <?php
                    } else {
                        ?>
                            $(document).attr("location", href+'&fb64=' + newValue + '&refr=' + this.value);
                        <?php
                    }
                    ?>
                } else {
                    $(document).attr ("location", href + this.value);
                }
        });
            
            return false;
        });
    });
/* ]]> */
</script>
