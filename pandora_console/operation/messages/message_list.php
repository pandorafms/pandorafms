<?php
/**
 * Sent / Received messages view.
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

require_once $config['homedir'].'/include/functions_messages.php';

$delete_msg = get_parameter('delete_message', 0);
$multiple_delete = get_parameter('multiple_delete', 0);
$show_sent = (bool) get_parameter('show_sent', false);
$mark_unread = get_parameter('mark_unread', 0);

$active_list = true;
$active_sent = false;
if ($show_sent === true) {
    $active_list = false;
    $active_sent = true;
}

$tabSelectedMessage = ($show_sent === true) ? __('Sent messages') : __('Received messages');

$buttons['message_list'] = [
    'active' => $active_list,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list">'.html_print_image('images/email_inbox.png', true, ['title' => __('Received messages'), 'class' => 'invert_filter']).'</a>',
];

$buttons['sent_messages'] = [
    'active' => $active_sent,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_list&amp;show_sent=1">'.html_print_image('images/email_outbox.png', true, ['title' => __('Sent messages'), 'class' => 'invert_filter']).'</a>',
];

$buttons['create_message'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=message_list&sec2=operation/messages/message_edit">'.html_print_image(
        'images/new_message.png',
        true,
        [
            'title' => __('Create message'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

if (is_ajax() === false) {
    // Header.
    ui_print_standard_header(
        $tabSelectedMessage,
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
}

if ($mark_unread) {
    $message_id = get_parameter('id_message');
    messages_process_read($message_id, false);
}

if ($delete_msg) {
    $id = (int) get_parameter('id');

    if ($show_sent === true) {
        $result = messages_delete_message_sent($id);
    } else {
        $result = messages_delete_message($id);
    }

    // Delete message function will actually check the credentials.
    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

if ($multiple_delete) {
    $ids = (array) get_parameter('delete_multiple_messages', []);

    foreach ($ids as $id) {
        if ($show_sent === true) {
            $result = messages_delete_message_sent($id);
        } else {
            $result = messages_delete_message($id);
        }

        if ($result === false) {
            break;
        }
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Not deleted. Error deleting messages')
    );
}

if ($show_sent === true) {
    // Sent view.
    $num_messages = messages_get_count_sent($config['id_user']);
    if ($num_messages > 0 && !is_ajax()) {
        echo '<p>'.__('You have').' <b>'.$num_messages.'</b> '.__('sent message(s)').'.</p>';
    }

    $messages = messages_get_overview_sent('', 'DESC');
} else {
    // Messages received.
    $num_messages = messages_get_count($config['id_user'], true, true);
    if ($num_messages > 0 && !is_ajax()) {
        $unread_messages = messages_get_count($config['id_user'], false, true);
        echo '<p>'.__('You have').' <b>'.$unread_messages.'</b> '.__('unread message(s)').'.</p>';
        $messages = messages_get_overview();
    } else {
        $messages = messages_get_overview('status', 'ASC');
    }
}

if (empty($messages) === true) {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no messages.'),
        ]
    );
} else {
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->cellpadding = 0;
    $table->cellspacing = 0;
    $table->head = [];
    $table->data = [];
    $table->align = [];
    $table->size = [];

    $table->align[5] = 'left';
    $table->align[0] = 'left';
    $table->align[1] = 'left';
    $table->align[2] = 'left';
    $table->align[3] = 'left';
    $table->align[4] = 'right';

    $table->size[5] = '20px';
    $table->size[0] = '20px';
    $table->size[1] = '100px';
    $table->size[3] = '80px';
    $table->size[4] = '60px';

    $table->head[5] = html_print_checkbox('all_delete_messages', 0, false, true, false, 'check_all_checkboxes()');
    $table->head[0] = __('Status');
    if ($show_sent === true) {
        $table->head[1] = __('Destination');
    } else {
        $table->head[1] = __('Sender');
    }

    $table->head[2] = __('Subject');
    $table->head[3] = __('Timestamp');
    $table->head[4] = __('Delete');


    foreach ($messages as $message) {
        $message_id = $message['id_mensaje'];
        $data = [];

        $data[5] = html_print_checkbox_extended('delete_multiple_messages[]', $message_id, false, false, '', 'class="check_delete_messages"', true);

        $data[0] = '';
        if ($message['read'] == 1) {
            if ($show_sent === true) {
                $pathRead = 'index.php?sec=message_list&amp;sec2=operation/messages/message_edit&read_message=1&amp;show_sent=1&amp;id_message='.$message_id;
                $titleRead = __('Click to read');
            } else {
                $pathRead = 'index.php?sec=message_list&amp;sec2=operation/messages/message_list&amp;mark_unread=1&amp;id_message='.$message_id;
                $titleRead = __('Mark as unread');
            }
        } else {
            if ($show_sent === true) {
                $pathRead = 'index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;show_sent=1&amp;id_message='.$message_id;
                $titleRead = __('Message unread - click to read');
            } else {
                $pathRead = 'index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;id_message='.$message_id;
                $titleRead = __('Message unread - click to read');
            }
        }

        $data[0] = html_print_anchor(
            [
                'href'    => $pathRead,
                'content' => html_print_image(
                    'images/email_inbox.png',
                    true,
                    [
                        'title' => $titleRead,
                        'class' => 'main_menu_icon invert_filter',
                    ],
                ),
            ],
            true
        );

        if ($show_sent === true) {
            $dest_user = get_user_fullname($message['dest']);
            if (!$dest_user) {
                $dest_user = $message['dest'];
            }

            $data[1] = $dest_user;
        } else {
            $orig_user = get_user_fullname($message['sender']);
            if (!$orig_user) {
                $orig_user = $message['sender'];
            }

            $data[1] = $orig_user;
        }

        if ($show_sent === true) {
            $pathSubject = 'index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;read_message=1&show_sent=1&amp;id_message='.$message_id;
        } else {
            $pathSubject = 'index.php?sec=message_list&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;id_message='.$message_id;
        }

        $contentSubject = (empty($message['subject']) === true) ? __('No Subject') : $message['subject'];

        if ((int) $message['read'] !== 1) {
            $contentSubject = '<strong>'.$contentSubject.'</strong>';
        }

        $data[2] .= html_print_anchor(
            [
                'href'    => $pathSubject,
                'content' => $contentSubject,
            ],
            true
        );

        $data[3] = ui_print_timestamp(
            $message['timestamp'],
            true,
            ['prominent' => 'timestamp']
        );

        $table->cellclass[][4] = 'table_action_buttons';
        if ($show_sent === true) {
            $pathDelete = 'index.php?sec=message_list&amp;sec2=operation/messages/message_list&show_sent=1&delete_message=1&id='.$message_id;
        } else {
            $pathDelete = 'index.php?sec=message_list&amp;sec2=operation/messages/message_list&delete_message=1&id='.$message_id;
        }

        $data[4] = html_print_anchor(
            [
                'href'    => $pathDelete,
                'content' => html_print_image(
                    'images/delete.svg',
                    true,
                    [
                        'title' => __('Delete'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ),
                'onClick' => 'javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;',
            ],
            true
        );

        array_push($table->data, $data);
    }
}

$outputButton = html_print_submit_button(
    __('Create message'),
    'create',
    false,
    [
        'icon' => 'next',
        'form' => 'create_message_form',
    ],
    true
);

if (empty($messages) === false) {
    if ($show_sent === true) {
        echo '<form id="message_form" method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_list&show_sent=1">';
    } else {
        echo '<form id="message_form" method="post" action="index.php?sec=message_list&amp;sec2=operation/messages/message_list">';
    }

    html_print_input_hidden('multiple_delete', 1);
    html_print_table($table);
    echo '</form>';

    $outputButton .= html_print_submit_button(
        __('Delete'),
        'delete_btn',
        false,
        [
            'icon' => 'delete',
            'mode' => 'secondary',
            'form' => 'message_form',
        ],
        true
    );
}

    echo '<form id="create_message_form" method="post" class="float-right" action="index.php?sec=message_list&sec2=operation/messages/message_edit"></form>';

    html_print_action_buttons(
        $outputButton
    );

    ?>

<script type="text/javascript">

function check_all_checkboxes() {
    if ($("input[name=all_delete_messages]").prop("checked")) {
        $("[name^='delete_multiple']").prop("checked", true);
    }
    else {
        $("[name^='delete_multiple']").prop("checked", false);
    }
}


</script>
