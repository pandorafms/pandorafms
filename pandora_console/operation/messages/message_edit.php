<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

global $config;

require_once 'include/functions_users.php';
require_once 'include/functions_groups.php';
require_once 'include/functions_io.php';

// Parse parameters.
$new_msg = get_parameter('new_msg', 0);
$dst_user = get_parameter('dst_user');
$dst_group = get_parameter('dst_group');
$subject = get_parameter('subject', '');
$message = get_parameter('message');
$read_message = get_parameter('read_message', 0);
$reply = get_parameter('reply', 0);
$show_sent = get_parameter('show_sent', 0);

$buttons['message_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list">'.html_print_image(
        'images/email_inbox.png',
        true,
        ['title' => __('Received messages')]
    ).'</a>',
];

$buttons['sent_messages'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list&amp;show_sent=1">'.html_print_image(
        'images/email_outbox.png',
        true,
        ['title' => __('Sent messages')]
    ).'</a>',
];

$buttons['create_message'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_edit">'.html_print_image(
        'images/new_message.png',
        true,
        ['title' => __('Create message')]
    ).'</a>',
];

// Header.
ui_print_page_header(
    __('Messages'),
    'images/email_mc.png',
    false,
    '',
    false,
    $buttons
);

// Read a message.
if ($read_message) {
    $message_id = (int) get_parameter('id_message');
    if ($show_sent) {
        $message = messages_get_message_sent($message_id);
    } else {
        $message = messages_get_message($message_id);
        messages_process_read($message_id);
    }

    if ($message === false) {
        echo '<div>'.__('This message does not exist in the system').'</div>';
        return;
        // Move out of this page and go processing other pages.
    }

    $user_name = get_user_fullname($message['id_usuario_origen']);
    if (!$user_name) {
        $user_name = $message['id_usuario_origen'];
    }

    $dst_name = get_user_fullname($message['id_usuario_destino']);
    if (!$dst_name) {
        $dst_name = $message['id_usuario_destino'];
    }

    if (isset($user_name) !== true || empty($user_name) === true) {
        echo '<h1>Notification</h1>';
    } else {
        echo '<h1>Conversation with '.$user_name.'</h1>';
    }

    echo '<h2>Subject: '.$message['subject'].'</h2>';

    $conversation = messages_get_conversation($message);

    ui_require_css_file('message_edit');
    foreach ($conversation as $row) {
        $date = $row['date'];

        if ($date === null) {
            $date = date(
                $config['date_format'],
                $message['timestamp']
            ).' '.$user_name;
        }

        $order = [
            "\r\n",
            "\n",
            "\r",
        ];
        $replace = '<br />';
        $parsed_message = str_replace(
            $order,
            $replace,
            trim(io_safe_output($row['message']))
        );

        echo '<div class="container">';
        echo '  <p>'.$parsed_message.'</p>';
        echo '<span class="time-left">'.$date.'</span>';
        echo '</div>';
    }


    $order = [
        "\r\n",
        "\n",
        "\r",
    ];
    $replace = '<br />';
    $parsed_message = str_replace($order, $replace, $message['mensaje']);

    // Prevent RE: RE: RE:.
    if (strstr($message['subject'], 'RE:')) {
        $new_subj = $message['subject'];
    } else {
        $new_subj = 'RE: '.$message['subject'];
    }

    // Start the message much like an e-mail reply.
    $new_msg = "\n\n\nOn ".date(
        $config['date_format'],
        $message['timestamp']
    ).' '.$user_name.' '.__('wrote').":\n\n".$message['mensaje'];


    echo '<form id="delete_message" method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_list&show_sent=1&amp;delete_message=1&amp;id='.$message_id.'">';
    echo '</form>';

    echo '<form id="reply_message" method="post" action="index.php?sec=message_list&sec2=operation/messages/message_edit&amp;new_msg=1&amp;reply=1">';
        html_print_input_hidden('dst_user', $message['id_usuario_origen']);
        html_print_input_hidden('subject', $new_subj);
        html_print_input_hidden('message', $new_msg);
        html_print_input_hidden('orig_user', $message['id_usuario_destino']);
    echo '</form>';

    echo "<div class= 'action-buttons' style=' width:".$table->width."'>";
    html_print_submit_button(
        __('Delete conversation'),
        'delete_btn',
        false,
        'form="delete_message" class="sub delete"'
    );
    echo '&nbsp';
    html_print_submit_button(
        __('Reply'),
        'reply',
        false,
        'form="reply_message" class="sub next"'
    );
    echo '</div>';

    return;
}

// Create message (destination user).
if (($new_msg) && (!empty($dst_user)) && (!$reply)) {
    $return = messages_create_message(
        $config['id_user'],
        [$dst_user],
        [],
        $subject,
        $message
    );

    $user_name = get_user_fullname($dst_user);
    if (!$user_name) {
        $user_name = $dst_user;
    }

    ui_print_result_message(
        $return,
        __('Message successfully sent to user %s', $user_name),
        __('Error sending message to user %s', $user_name)
    );
}

// Create message (destination group).
if (($new_msg) && ($dst_group != '') && (!$reply)) {
    $return = messages_create_message(
        $config['id_user'],
        [],
        [$dst_group],
        $subject,
        $message
    );

    ui_print_result_message(
        $return,
        __('Message successfully sent'),
        __('Error sending message to group %s', groups_get_name($dst_group))
    );
}

// Message creation form.
// User info.
$own_info = get_user_info($config['id_user']);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->data = [];

$table->data[0][0] = __('Sender');

if (!empty($own_info['fullname'])) {
    $table->data[0][1] = $own_info['fullname'];
} else {
    $table->data[0][1] = $config['id_user'];
}

$table->data[1][0] = __('Destination');

$is_admin = (bool) db_get_value(
    'is_admin',
    'tusuario',
    'id_user',
    $config['id_user']
);

if ($is_admin) {
    $users_full = db_get_all_rows_filter(
        'tusuario',
        [],
        [
            'id_user',
            'fullname',
        ]
    );
} else {
    $users_full = groups_get_users(
        array_keys(users_get_groups()),
        false,
        false
    );
}

$users = [];
foreach ($users_full as $user_id => $user_info) {
    $users[$user_info['id_user']] = $user_info['fullname'];
}

// Check if the user to reply is in the list, if not add reply user.
if ($reply) {
    if (!array_key_exists($dst_user, $users)) {
        // Add the user to reply.
        $user_reply = db_get_row('tusuario', 'id_user', $dst_user);
        $users[$user_reply['id_user']] = $user_reply['fullname'];
    }
}


if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $return_all_groups = true;
} else {
    $return_all_groups = false;
}

$groups = users_get_groups($config['id_user'], 'AR');
// Get a list of all groups.
$table->data[1][1] = html_print_select(
    $users,
    'dst_user',
    $dst_user,
    '',
    __('Select user'),
    false,
    true,
    false,
    '',
    false
);
$table->data[1][1] .= '&nbsp;&nbsp;'.__('OR').'&nbsp;&nbsp;';
$table->data[1][1] .= html_print_select_groups(
    $config['id_user'],
    'AR',
    $return_all_groups,
    'dst_group',
    $dst_group,
    '',
    __('Select group'),
    '',
    true
);

$table->data[2][0] = __('Subject');
$table->data[2][1] = html_print_input_text(
    'subject',
    $subject,
    '',
    50,
    70,
    true
);

$table->data[3][0] = __('Message');
$table->data[3][1] = html_print_textarea(
    'message',
    15,
    255,
    $message,
    '',
    true
);

echo '<form method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;new_msg=1">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
    html_print_submit_button(
        __('Send message'),
        'send_mes',
        false,
        'class="sub wand"'
    );
    echo '</form>';
    echo '</div>';
