<?php
/**
 * Pandora FMS - https://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

require_once 'include/functions_messages.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_notifications.php';
require_once 'include/ajax/order_interpreter.php';
ui_require_css_file('order_interpreter');

// Check permissions
// Global errors/warnings checking.
config_check();

echo sprintf('<div id="header_table" class="header_table_%s">', $menuTypeClass);

?>
    <div id="header_table_inner">
        <?php
        // ======= Notifications Discovery ===============================================
        $notifications_numbers = notifications_get_counters();
        $header_discovery = '<div id="header_discovery">'.notifications_print_ball(
            $notifications_numbers['notifications'],
            $notifications_numbers['last_id']
        ).'</div>';
        $header_welcome = '';
        if (check_acl($config['id_user'], 0, 'AW')) {
            $header_welcome .= '<div id="welcome-icon-header">';
            $header_welcome .= html_print_image(
                'images/wizard@svg.svg',
                true,
                [
                    'class' => 'main_menu_icon invert_filter',
                    'title' => __('Welcome dialog'),
                    'id'    => 'Welcome-dialog',
                    'alt'   => __('Welcome dialog'),
                    'style' => 'cursor: pointer;',
                ]
            );
            $header_welcome .= '</div>';
        }

        // ======= Servers List ===============================================
        if ((bool) check_acl($config['id_user'], 0, 'AW') !== false) {
            $servers = [];
            $servers_info = servers_get_info();

            $servers['all'] = 0;

            if ($servers_info !== null && $servers_info !== false) {
                $servers['all'] = (int) count($servers_info);
            }

            if ($servers['all'] != 0) {
                $servers['up'] = (int) servers_check_status();
                $servers['down'] = ($servers['all'] - $servers['up']);
                if ($servers['up'] == 0) {
                    // All Servers down or no servers at all.
                    $servers_check_img = html_print_image('images/system_error@header.svg', true, ['alt' => 'cross', 'class' => 'main_menu_icon bot', 'title' => __('All systems').': '.__('Down')]);
                } else if ($servers['down'] != 0) {
                    // Some servers down.
                    $servers_check_img = html_print_image('images/system_warning@header.svg', true, ['alt' => 'error', 'class' => 'main_menu_icon bot', 'title' => $servers['down'].' '.__('servers down')]);
                } else {
                    // All servers up.
                    $servers_check_img = html_print_image('images/system_ok@header.svg', true, ['alt' => 'ok', 'class' => 'main_menu_icon bot', 'title' => __('All systems').': '.__('Ready')]);
                }

                unset($servers);
                // Since this is the header, we don't like to trickle down variables.
                $servers_check_img_link = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60',
                        'content' => $servers_check_img,
                    ],
                    true
                );
            };

            $servers_list = html_print_div(
                [
                    'id'      => 'servers_list',
                    'content' => $servers_check_img_link,
                ],
                true
            );
        }



        // ======= Alerts ===============================================
        $check_minor_release_available = false;
        $pandora_management = check_acl($config['id_user'], 0, 'PM');

        $check_minor_release_available = db_check_minor_relase_available();

        if ($check_minor_release_available === true) {
            if (users_is_admin($config['id_user'])) {
                if ($config['language'] === 'es') {
                    set_pandora_error_for_header('Hay una o mas revisiones menores en espera para ser actualizadas. <a id="aviable_updates" target="blank" href="https://pandorafms.com/manual/es/documentation/pandorafms/installation/02_anexo_upgrade#version_70ng_rolling_release">'.__('Sobre actualización de revisión menor').'</a>', 'Revisión/es menor/es disponible/s');
                } else {
                    set_pandora_error_for_header('There are one or more minor releases waiting for update. <a id="aviable_updates" target="blank" href="https://pandorafms.com/manual/en/documentation/pandorafms/installation/02_anexo_upgrade#version_70ng_rolling_release">'.__('About minor release update').'</a>', 'minor release/s available');
                }
            }
        }


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
            $search_bar = '<form autocomplete="off" method="get" class="display_in" name="quicksearch" action="">';
            '<input autocomplete="false" name="hidden" type="text" class="invisible">';
            if (!isset($config['search_keywords'])) {
                $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
            } else {
                if (strlen($config['search_keywords']) == 0) {
                    $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = true; </script>';
                } else {
                    $search_bar .= '<script type="text/javascript"> var fieldKeyWordEmpty = false; </script>';
                }
            }

            $search_bar .= '<div id="result_order" class="result_order"></div>';
            $search_bar .= '<input id="keywords" name="keywords"';
            if (!isset($config['search_keywords'])) {
                $search_bar .= "value='".__('Enter keywords to search')."'";
            } else if (strlen($config['search_keywords']) == 0) {
                $search_bar .= "value='".__('Enter keywords to search')."'";
            } else {
                $search_bar .= "value='".$config['search_keywords']."'";
            }

            $search_bar .= 'type="search" onfocus="javascript: if (fieldKeyWordEmpty) $(\'#keywords\').val(\'\');" onkeyup="showinterpreter()" class="search_input"/>';

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

        if (isset($_GET['sec']) === false) {
            $_GET['sec'] = '';
        }

        if (!isset($_GET['sec2'])) {
            $_GET['sec2'] = '';
        }

        if ($_GET['sec'] == 'main' || !isset($_GET['sec'])) {
            // Home screen chosen by the user.
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
                    default:
                        $_GET['sec2'] = 'general/logon_ok';
                    break;

                    case 'Dashboard':
                        $_GET['sec2'] = 'operation/dashboard/dashboard';
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
            (empty($select[0]['autorefresh_white_list']) === false)
            ? $select[0]['autorefresh_white_list']
            : ''
        );

        $header_autorefresh = '';
        $header_autorefresh_counter = '';
        $header_setup = '';

        if (($_GET['sec2'] !== 'operation/visual_console/render_view')) {
            if ($autorefresh_list !== null
                && array_search($_GET['sec2'], $autorefresh_list) !== false
            ) {
                $do_refresh = true;

                // Exception for network maps.
                if ($_GET['sec2'] === 'operation/agentes/pandora_networkmap') {
                    $do_refresh = false;
                }

                if ($do_refresh) {
                    $autorefresh_img = html_print_image(
                        'images/auto_refresh@header.svg',
                        true,
                        [
                            'class' => 'main_menu_icon bot',
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

                    $autorefresh_additional = '<span id="combo_refr" class="invisible_events">';
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
                    $autorefresh_img = html_print_image(
                        'images/auto_refresh@header.svg',
                        true,
                        [
                            'class' => 'main_menu_icon bot autorefresh_disabled invert_filter',
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
            } else {
                $autorefresh_img = html_print_image(
                    'images/auto_refresh@header.svg',
                    true,
                    [
                        'class' => 'main_menu_icon bot autorefresh_disabled invert_filter',
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

            if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
                $header_setup .= '<div id="header_logout"><a class="white" href="'.ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general').'">';
                $header_setup .= html_print_image(
                    'images/configuration@svg.svg',
                    true,
                    [
                        'alt'   => __('Setup'),
                        'class' => 'bot invert_filter main_menu_icon',
                        'title' => __('Setup'),
                    ]
                );
                $header_setup .= '</a></div>';
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
        }

        $modal_box = '<div id="modal_help" class="invisible">
        <div id="modal-feedback-form" class="invisible"></div>
        <div id="msg-header" class="invisible"></div>
            <a href="https://pandorafms.com/manual" target="_blank">'.__('Pandora documentation').'</a>';
        if (enterprise_installed() === true) {
            $modal_box .= '<a href="https://support.pandorafms.com/" target="_blank">'.__('Enterprise support ').'</a>';
            $modal_box .= '<a href="#" id="feedback-header">'.__('Give us feedback').'</a>';
        } else {
            $modal_box .= '<a href="https://pandorafms.com/community/forums/" target="_blank">'.__('Community Support').'</a>';
        }

        $modal_box .= '<hr class="separator" />';
        $modal_box .= '<a href="https://github.com/pandorafms/pandorafms/issues" target="_blank">'.__('Open an issue in Github').'</a>';
        $modal_box .= '<a href="https://discord.com/invite/xVt2ruSxmr" target="_blank">'.__('Join discord community').'</a>';
        $modal_box .= '</div>';

        if ($config['activate_feedback'] === '1') {
            $modal_help = html_print_div(
                [
                    'id'      => 'modal-help-content',
                    'content' => html_print_image(
                        'images/help@header.svg',
                        true,
                        [
                            'title' => __('Help'),
                            'class' => 'main_menu_icon bot invert_filter',
                            'alt'   => 'user',
                        ]
                    ).$modal_box,
                ],
                true,
            );
        }


        // User.
        $headerUser = [];
        $headerUser[] = html_print_image(
            'images/edit_user@header.svg',
            true,
            [
                'title' => __('Edit my user'),
                'class' => 'main_menu_icon bot invert_filter',
                'alt'   => 'user',
            ]
        );

        $headerUser[] = sprintf('<span id="user_name_header">[ %s ]</span>', $config['id_user']);

        $header_user = html_print_div(
            [
                'id'      => 'header_user',
                'content' => html_print_anchor(
                    [
                        'href'    => sprintf('index.php?sec=gusuarios&sec2=godmode/users/configure_user&edit_user=1&pure=0&id_user=%s', $config['id_user']),
                        'content' => implode('', $headerUser),
                    ],
                    true
                ),
            ],
            true
        );

        // Logout.
        $header_logout = '<div id="header_logout"><a onClick=\'if (!confirm("'.__('Are you sure?').'")) return false;\' class="white" href="'.ui_get_full_url('index.php?bye=bye').'">';
        $header_logout .= html_print_image(
            'images/sign_out@header.svg',
            true,
            [
                'alt'   => __('Logout'),
                'class' => 'bot invert_filter',
                'title' => __('Logout'),
            ]
        );
        $header_logout .= '</a></div>';

        if (enterprise_installed()) {
            $subtitle_header = $config['custom_subtitle_header'];
            $class_header = '';
        } else {
            $subtitle_header = __('the Flexible Monitoring System (OpenSource version)');
            echo '<div id="dialog_why_enterprise" class="invisible"></div>';
            $class_header = 'underline-hover modal_module_list';
        }

        if (is_reporting_console_node() === true) {
            echo '<div class="header_left '.$class_header.'">';
                echo '<span class="header_title">';
                echo $config['custom_title_header'];
                echo '</span>';
                echo '<span class="header_subtitle">';
                echo $subtitle_header;
                echo '</span>';
            echo '</div>';
            echo '<div class="header_center"></div>';
            echo '<div class="header_right">'.$modal_help, $header_user, $header_logout.'</div>';
        } else {
            echo '<div class="header_left '.$class_header.'"><span class="header_title">'.$config['custom_title_header'].'</span><span class="header_subtitle">'.$subtitle_header.'</span></div>
            <div class="header_center">'.$header_searchbar.'</div>
            <div class="header_right">'.$header_autorefresh, $header_autorefresh_counter, $header_discovery, $header_welcome, $servers_list, $modal_help, $header_setup, $header_user, $header_logout.'</div>';
        }
        ?>
    </div>    <!-- Closes #table_header_inner -->
</div>    <!-- Closes #table_header -->

<!-- Old style div wrapper -->
<div id="alert_messages" class="invisible"></div>

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

    function notifications_clean_all() {
        let wrapper_inner = document.getElementById('notification-wrapper-inner');
        while (wrapper_inner.firstChild) {
            wrapper_inner.removeChild(wrapper_inner.firstChild);
        }
    }

    function mark_all_notification_as_read() {
        jQuery.post ("ajax.php",
            {
                "page" : "godmode/setup/setup_notifications",
                "mark_all_notification_as_read" : 1
            },
            function (data, status) {
                notifications_clean_all();
                location.reload();
            },
            "json"
        )
        .fail(function(xhr, textStatus, errorThrown){
            console.error(
                "Failed to mark al notification as read. Error: ",
                xhr.responseText
            );
        });
    }

    function filter_notification() {
        let notification_type = '';
        $('.notification-item').hide();
        $(".checkbox_filter_notifications:checkbox:checked").each(function() {
            notification_type = $(this).val();
            console.log(notification_type);
            $('.notification-item[value='+notification_type+']').show();
            if (notification_type == 'All'){
                $('.notification-item').show();
            }
        });

        if (notification_type == 'All'){
            $('.notification-item').show();
        }

        if (notification_type == ''){
            $('.notification-item').hide();
        }
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

    function closeToast(event) {
        var match = /notification-(.*)-id-([0-9]+)/.exec(event.target.id);
        var div_id = document.getElementById(match.input);
        $(div_id).attr("hidden",true);
    }

    function print_toast(title, subtitle, severity, url, id, onclick, closeToast) {
        // TODO severity.
        severity = '';
        // Start the toast.

        var parent_div = document.createElement('div');

        // Print close image
        var img = document.createElement('img');
        img.setAttribute('id', id);
        img.setAttribute("src", './images/close_button_dialog.png');
        img.setAttribute('onclick', closeToast);
        img.setAttribute('style', 'margin-left: 95%;');
        parent_div.appendChild(img);

        // Print a element
        var toast = document.createElement('a');
        toast.setAttribute('target', '_blank');
        toast.setAttribute('href', url);
        toast.setAttribute('onclick', onclick);

        var link_div = document.createElement('div');

        // Fill toast.
        var toast_div = document.createElement('div');
        toast_div.className = 'snackbar ' + severity;
        toast_div.id = id;
        var toast_title = document.createElement('h3');
        var toast_text = document.createElement('p');
        toast_title.innerHTML = title;
        toast_text.innerHTML = subtitle;

        // Append Elements
        toast_div.appendChild(img);
        link_div.appendChild(toast_title);
        toast.appendChild(link_div);
        toast_div.appendChild(toast);
        toast_div.appendChild(toast_text);

        // Show and program the hide event.
        toast_div.className = toast_div.className + ' show';
        setTimeout(function(){
            toast_div.className = toast_div.className.replace("show", "");
        }, 8000);

        toast_div.appendChild(parent_div);

        return toast_div;
    }
  
    function check_new_notifications() {
        var last_id = document.getElementById('notification-ball-header')
            .getAttribute('last_id');
        if (last_id === null) {
            console.error('Cannot retrieve notifications ball last_id.');
            return;
        }

        // Get notifications buffer in local storage.
        var user_notifications = localStorage.getItem('user_notifications');

        if (user_notifications !== null && user_notifications.length) {
            var user_notifications_parsed = JSON.parse(user_notifications);
            var current_timestamp = Math.floor(Date.now() / 1000);

            // Remove old notifications from local storage.
            user_notifications_parsed_updated = user_notifications_parsed.filter(function(notification) {
                return (notification.item_datetime > current_timestamp - 90);
            });

            if (user_notifications_parsed_updated.length !== user_notifications_parsed.length) {
                localStorage.setItem('user_notifications', JSON.stringify(user_notifications_parsed_updated));
                user_notifications_parsed = user_notifications_parsed_updated;
            }
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

                // Prevent to print toasts if tab is not active.
                if (document.hidden === false) {
                    var localStorageItemsArray = [];

                    // Add the new toasts.
                    if (Array.isArray(data.new_notifications)) {
                        data.new_notifications.forEach(function(ele) {
                            // Keep track of notifications in browser local storage to avoid displaying toasts more than once across different tabs for a specific user.
                            if (typeof user_notifications_parsed !== "undefined") {
                                localStorageItemsArray = user_notifications_parsed;

                                // Check if toast has already been fired and therefore it should be skipped.
                                if (localStorageItemsArray.some(function(item) {
                                    return item.message_id == ele.id_mensaje && ele.id_usuario_origen == ele.id_usuario_origen
                                })) {
                                    return;
                                }

                            }

                            localStorageItemsArray.push({message_id: ele.id_mensaje, source_user_id: ele.id_usuario_origen, item_datetime: Math.floor(Date.now() / 1000)});

                            localStorage.setItem('user_notifications', JSON.stringify(localStorageItemsArray));
                            
                            toast_wrapper.appendChild(
                                print_toast(
                                    ele.subject,
                                    ele.mensaje,
                                    ele.criticity,
                                    ele.full_url,
                                    'notification-toast-id-' + ele.id_mensaje,
                                    'click_on_notification_toast(event)',
                                    'closeToast(event)'
                                )
                            );
                        });
                    }
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

    // Resize event.
    window.addEventListener("resize", function() {
        attatch_to_image();
    });

    var fixed_header = <?php echo json_encode((bool) $config_fixed_header); ?>;

    function showinterpreter(){

        document.onclick = function(e) {
            $('#result_order').hide();
            $('#keywords').addClass('search_input');
            $('#keywords').removeClass('results-found');
            $('#keywords').value = '';
            $('#keywords').attr('placeholder','Enter keywords to search');
        }

        if(event.keyCode == 13 && $("#result_items li.active").length != 0 )
        {
            window.location = $('#result_items').find("li.active a").attr('href');
        }
        var code = event.key;
        switch (code){
            case 'ArrowDown':
                if($("#result_items li.active").length!=0)
                {
                    var storeTarget = $('#result_items').find("li.active").next();
                    $("#result_items li.active").removeClass("active");
                    storeTarget.focus().addClass("active");
                   
                }
                else
                {
                    $('#result_items').find("li:first").focus().addClass("active");
                }
           return;

           case 'ArrowUp':
                if($("#result_items li.active"))
                {
                    var storeTarget = $('#result_items').find("li.active").prev();
                    $("#result_items li.active").removeClass("active");
                    storeTarget.focus().addClass("active");
                }
                else
                {
                    $('#result_items').find("li:first").focus().addClass("active");
                }
           return;
                
           case 'ArrowRight':
           return;
           case 'ArrowLeft':
           return;

        }
        
        if( $('#keywords').val() === ''){
            $('#keywords').addClass('search_input');
            $('#keywords').removeClass('results-found');
            $('#result_order').hide();
            $('#keywords').attr('placeholder','Enter keywords to search');
        }else {
            $.ajax({
                type: "POST",
                url: "ajax.php",
                dataType: "html",
                data: {
                    page: 'include/ajax/order_interpreter',
                    method: 'getResult',
                    text: $('#keywords').val(),
                    enterprise: <?php echo (int) enterprise_installed(); ?>,
                },
                success: function (data) {
                   $('#result_order').html(data);
                },
                error: function (data) {
                    console.error("Fatal error in AJAX call to interpreter order", data)
                }
            });
            $('#keywords').removeClass('search_input');
            $('#keywords').addClass('results-found');
            $('#result_order').show();

        }
    }
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

        <?php if (enterprise_installed() === false) { ?>
            $('.header_left').on('click', function(){
                jQuery.post(
                    "ajax.php",
                    {
                        page: "include/functions_menu",
                        'why_enterprise': "true"
                    },
                    function(data) {
                        if (data) {
                            $("#dialog_why_enterprise").html(data);
                            // Open dialog
                            $("#dialog_why_enterprise").dialog({
                                resizable: false,
                                draggable: false,
                                modal: true,
                                show: {
                                    effect: "fade",
                                    duration: 200
                                },
                                hide: {
                                    effect: "fade",
                                    duration: 200
                                },
                                closeOnEscape: true,
                                width: 700,
                                height: 450,
                                close: function(){
                                    $('#dialog_why_enterprise').html('');
                                }
                            });
                        }
                    },
                    "html"
                );
            });
        <?php } ?>

        // Check new notifications on a periodic way
        setInterval(check_new_notifications, 60000);

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

            if ($_GET['sec2'] == 'operation/dashboard/dashboard' && $new_dashboard) {
                $do_refresh = false;
            }
        }
        ?>

        if (fixed_header) {
            $('div#head').addClass('fixed_header');
            $('div#main').css('padding-top', $('div#head').innerHeight() + 'px');
        }

        /* Temporal fix to hide graphics when ui_dialog are displayed */
        $("#yougotalert").click(function () {
            $("#agent_access").css("display", "none");
        });
        $("#ui_close_dialog_titlebar").click(function () {
            $("#agent_access").css("display","");
        });

        $("#welcome-icon-header").click(function () {
            if (!$('#welcome_modal_window').length){
                $(document.body).append('<div id="welcome_modal_window"></div>');
                $(document.body).append( $('<link rel="stylesheet" type="text/css" />').attr('href', 'include/styles/new_installation_welcome_window.css') );
            }
            // Clean DOM.
            load_modal({
                target: $('#welcome_modal_window'),
                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                modal: {
                    title: "<?php echo __('Welcome to').' '.io_safe_output(get_product_name()); ?>",
                    cancel: '<?php echo __('Do not show anymore'); ?>',
                    ok: '<?php echo __('Close'); ?>',
                    overlay: true,
                    overlayExtraClass: 'welcome-overlay',
                },
                onshow: {
                    page: 'include/ajax/welcome_window',
                    method: 'loadWelcomeWindow',
                    width: 1000,
                },
                oncancel: {
                    page: 'include/ajax/welcome_window',
                    title: "<?php echo __('Cancel Configuration Window'); ?>",
                    method: 'cancelWelcome',
                    confirm: function (fn) {
                        confirmDialog({
                            title: '<?php echo __('Are you sure?'); ?>',
                            message: '<?php echo __('Are you sure you want to cancel this tutorial?'); ?>',
                            ok: '<?php echo __('OK'); ?>',
                            cancel: '<?php echo __('Cancel'); ?>',
                            onAccept: function() {
                                // Continue execution.
                                fn();
                            }
                        })
                    }
                },
                onload: () => {
                    $(document).ready(function () {
                        var buttonpane = $("div[aria-describedby='welcome_modal_window'] .ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix");
                        $(buttonpane).append(`
                        <div class="welcome-wizard-buttons">
                            <label>
                                <input type="checkbox" class="welcome-wizard-do-not-show" value="1" />
                                <?php echo __('Do not show anymore'); ?>
                            </label>
                            <button class="close-wizard-button"><?php echo __('Close wizard'); ?></button>
                        </div>
                        `);

                        var closeWizard = $("button.close-wizard-button");

                        $(closeWizard).click(function (e) {
                            var close = $("div[aria-describedby='welcome_modal_window'] button.sub.ok.submit-next.ui-button");
                            var cancel = $("div[aria-describedby='welcome_modal_window'] button.sub.upd.submit-cancel.ui-button");
                            var checkbox = $("div[aria-describedby='welcome_modal_window'] .welcome-wizard-do-not-show:checked").length;

                            if (checkbox === 1) {
                                $(cancel).click();
                            } else {
                                $(close).click()
                            }
                        });
                    });
                }
            });
        });

        <?php if (enterprise_installed()) { ?>
            // Feedback.
            $("#feedback-header").click(function () {
                // Function charge Modal.
                show_feedback();
            });
        <?php } ?>

        function blinkpubli(){
            $(".publienterprise").delay(100).fadeTo(300,0.2).delay(100).fadeTo(300,1, blinkpubli);
        }

        blinkpubli();

        <?php
        if ($_GET['refr']
            || (isset($do_refresh) === true && $do_refresh === true)
        ) {
            $autorefresh_draw = false;
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
                            $("#table_events")
                                .DataTable()
                                .draw(false);
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
            $("#combo_refr").toggle();
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
                    if (isset($_GET['fb64']) === true) {
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


        $(document).click(function(event) {
            if (!$(event.target).closest('#modal-help-content').length &&
                $('#modal_help').hasClass('invisible') === false) {
                $('#modal_help').addClass('invisible');
            }
        });

        $('#modal-help-content').on('click', (e) => {
            if($(e.target).prop('tagName') === 'A') {
                $('#modal_help').addClass('invisible');
            } else {
                $('#modal_help').removeClass('invisible');
            }
        });
    });
/* ]]> */
</script>
