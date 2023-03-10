<?php
/**
 * Compose message view
 *
 * @category   Workspace
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_io.php';

// Parse parameters.
$send_mes     = (bool) get_parameter('send_mes', false);
$new_msg      = (string) get_parameter('new_msg');
$dst_user     = get_parameter('dst_user');
$dst_group    = get_parameter('dst_group');
$subject      = io_safe_html_tags(get_parameter('subject'));
$message      = (string) get_parameter('message');
$read_message = (bool) get_parameter('read_message', false);
$reply        = (bool) get_parameter('reply', false);
$replied      = (bool) get_parameter('replied', false);
$show_sent    = get_parameter('show_sent', 0);

$buttons['message_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list">'.html_print_image(
        'images/email_inbox.png',
        true,
        [
            'title' => __('Received messages'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons['sent_messages'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list&amp;show_sent=1">'.html_print_image(
        'images/email_outbox.png',
        true,
        [
            'title' => __('Sent messages'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons['create_message'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_edit">'.html_print_image(
        'images/new_message.png',
        true,
        [
            'title' => __('Create message'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

ui_print_standard_header(
    __('Compose message'),
    'images/email_mc.png',
    false,
    '',
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Workspace'),
        ],
        [
            'link'  => '',
            'label' => __('Messages'),
        ],
    ]
);

// Read a message.
if ($read_message) {
    $message_id = (int) get_parameter('id_message');
    if ((bool) $show_sent === true) {
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

    if (empty($message['id_usuario_origen']) === true) {
        echo '<h1>Notification</h1>';
    } else {
        echo '<h1>Conversation with '.$user_name.'</h1>';
    }

    echo '<h2>Subject: '.$message['subject'].'</h2>';

    $conversation = messages_get_conversation($message);

    ui_require_css_file('message_edit');

    if (empty($message['id_usuario_origen']) !== true) {
        foreach ($conversation as $row) {
            $date = $row['date'];

            if ($date === null) {
                $date = date(
                    $config['date_format'],
                    $message['timestamp']
                ).' '.$user_name;
            }

            $parsed_message = nl2br(htmlspecialchars(trim(io_safe_output($row['message']))));

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
    } else {
        // Direct message from System.
        echo io_safe_output($message['mensaje']);
    }

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


    echo '<form id="delete_message" method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_list&show_sent='.$show_sent.'&amp;delete_message=1&amp;id='.$message_id.'">';
    echo '</form>';

    echo '<form id="reply_message" method="post" action="index.php?sec=message_list&sec2=operation/messages/message_edit&amp;new_msg=1&amp;reply=1">';
        html_print_input_hidden('dst_user', $message['id_usuario_origen']);
        html_print_input_hidden('subject', $new_subj);
        html_print_input_hidden('message', $new_msg);
        html_print_input_hidden('orig_user', $message['id_usuario_destino']);
    echo '</form>';

    if (empty($message['id_usuario_origen']) !== true) {
        $outputButtons .= html_print_submit_button(
            __('Reply'),
            'reply',
            false,
            [
                'icon' => 'next',
                'form' => 'reply_message',
            ],
            true
        );
    }

    $outputButtons .= html_print_submit_button(
        __('Delete conversation'),
        'delete_btn',
        false,
        [
            'icon' => 'delete',
            'mode' => 'secondary',
            'form' => 'delete_message',
        ],
        true
    );

    html_print_action_buttons(
        $outputButtons
    );

    return;
}

if ($send_mes === true) {
    if (empty($dst_user) === true && empty($dst_group) === true) {
        // The user or group must be selected for send the message.
        ui_print_error_message(__('User or group must be selected.'));
    } else {
        // Create message (destination user).
        $return = messages_create_message(
            $config['id_user'],
            [$dst_user],
            [],
            $subject,
            $message
        );

        $user_name = get_user_fullname($dst_user);
        if (empty($user_name) === true) {
            $user_name = $dst_user;
        }

        ui_print_result_message(
            $return,
            __('Message successfully sent to user %s', $user_name),
            __('Error sending message to user %s', $user_name)
        );

        // If is a reply, is not necessary do more.
        if ($replied === true) {
            return;
        }
    }
}

// Message creation form.
// User info.
$own_info = get_user_info($config['id_user']);

$is_admin = (bool) db_get_value(
    'is_admin',
    'tusuario',
    'id_user',
    $config['id_user']
);

if ($is_admin === true) {
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
    $users[$user_info['id_user']] = (empty($user_info['fullname']) === true) ? $user_info['id_user'] : $user_info['fullname'];
}

$table = new stdClass();
$table->id = 'send_message_table';
$table->width = '100%';
$table->class = 'databox max_floating_element_size filter-table-adv';
$table->style = [];
$table->style[0] = 'width: 30%';
$table->style[1] = 'width: 70%';
$table->data = [];

$table->data[0][] = html_print_label_input_block(
    __('Sender'),
    '<span class="result_info_text">'.((empty($own_info['fullname']) === false) ? $own_info['fullname'] : $config['id_user']).'</span>'
);

// Check if the user to reply is in the list, if not add reply user.
if ($reply === true) {
    $destinationInputs = (array_key_exists($dst_user, $users) === true) ? $users[$dst_user] : $dst_user;
    $destinationInputs .= html_print_input_hidden(
        'dst_user',
        $dst_user,
        true
    );
    $destinationInputs .= html_print_input_hidden(
        'replied',
        '1',
        true
    );
} else {
    $return_all_groups = ((bool) $own_info['is_admin'] === true
    || check_acl($config['id_user'], 0, 'PM') === true);

    $groups = users_get_groups($config['id_user'], 'AR');
    // Get a list of all groups.
    $destinationInputs = html_print_div(
        [
            'class'   => 'select_users mrgn_right_5px',
            'content' => html_print_select(
                $users,
                'dst_user',
                $dst_user,
                'changeStatusOtherSelect(\'dst_user\', \'dst_group\')',
                __('Select user'),
                false,
                true,
                false,
                ''
            ),
        ],
        true
    );
    $destinationInputs .= __('OR');
    $destinationInputs .= html_print_div(
        [
            'class'   => 'mrgn_lft_5px',
            'content' => html_print_select_groups(
                $config['id_user'],
                'AR',
                $return_all_groups,
                'dst_group',
                $dst_group,
                'changeStatusOtherSelect(\'dst_group\', \'dst_user\')',
                __('Select group'),
                '',
                true
            ),
        ],
        true
    );
}

$table->data[0][] = html_print_label_input_block(
    __('Destination'),
    html_print_div(
        [
            'class'   => 'flex-content-left',
            'content' => $destinationInputs,
        ],
        true
    )
);

$table->colspan[1][] = 2;
$table->data[1][] = html_print_label_input_block(
    __('Subject'),
    html_print_input_text(
        'subject',
        $subject,
        '',
        50,
        70,
        true
    )
);

$table->colspan[2][] = 2;
$table->data[2][] = html_print_label_input_block(
    __('Message'),
    html_print_textarea(
        'message',
        15,
        50,
        $message,
        '',
        true
    )
);

$jsOutput = '';
ob_start();
?>
<script type="text/javascript">
    function changeStatusOtherSelect(myId, otherId) {
        if (document.getElementById(myId).value !== "") {
            if (otherId === "dst_group") {
                $('#'+otherId).select2('val', '0');
            } else {
                document.getElementById(otherId).value = "";
            }
        }
    }
</script>
<?php
$jsOutput = ob_get_clean();

echo '<form method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;new_msg=1">';
// Print the main table.
html_print_table($table);
// Print the action buttons section.
html_print_action_buttons(
    html_print_submit_button(
        __('Send message'),
        'send_mes',
        false,
        [ 'icon' => 'wand' ],
        true
    )
);

echo '</form>';
echo $jsOutput;
