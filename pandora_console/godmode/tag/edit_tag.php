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
check_login();

// Include functions code
require_once $config['homedir'].'/include/functions_tags.php';

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Edit Tag'
    );
    include 'general/noaccess.php';

    return;
}

// Get parameters
$action = (string) get_parameter('action', '');
$id_tag = (int) get_parameter('id_tag', 0);
$update_tag = (int) get_parameter('update_tag', 0);
$create_tag = (int) get_parameter('create_tag', 0);
$name_tag = (string) get_parameter('name_tag', '');
$description_tag = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description_tag'))));
$url_tag = (string) get_parameter('url_tag', '');
$email_tag = io_safe_input(strip_tags(io_safe_output(((string) get_parameter('email_tag')))));
$phone_tag = io_safe_input(strip_tags(io_safe_output(((string) get_parameter('phone_tag')))));
$tab = (string) get_parameter('tab', 'list');

if (is_metaconsole() === true) {
    $sec = 'advanced';
    $url = 'index.php?sec='.$sec.'&sec2=advanced/component_management&tab=tags';
} else {
    $sec = 'gmodules';
    $url = 'index.php?sec='.$sec.'&sec2=godmode/tag/tag&tab=list';
}

$buttons = [
    'list' => [
        'active' => false,
        'text'   => '<a href="'.$url.'">'.html_print_image(
            'images/list.png',
            true,
            [
                'title' => __('List tags'),
                'class' => 'invert_filter',
            ]
        ).'</a>',
    ],
];

$buttons[$tab]['active'] = true;

// Header.
ui_print_standard_header(
    __('Tags configuration'),
    'images/tag.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Profile'),
        ],
        [
            'link'  => '',
            'label' => __('Manage tags'),
        ],
    ]
);

// Two actions can performed in this page: update and create tags
// Update tag: update an existing tag
if ($update_tag && $id_tag != 0) {
    // Erase comma characters on tag name
    $name_tag = str_replace(',', '', $name_tag);

    $values = [];
    $values['name'] = $name_tag;
    $values['description'] = $description_tag;
    $values['url'] = $url_tag;
    $values['email'] = $email_tag;
    $values['phone'] = $phone_tag;
    // Only for Metaconsole. Save the previous name for synchronizing.
    if (is_metaconsole()) {
        $values['previous_name'] = db_get_value('name', 'ttag', 'id_tag', $id_tag);
    }

    $result = false;
    if ($values['name'] != '') {
        $result = tags_update_tag($values, 'id_tag = '.$id_tag);
    }

    $auditMessage = ($result === false) ? 'Fail try to update tag' : 'Update tag';
    db_pandora_audit(
        AUDIT_LOG_TAG_MANAGEMENT,
        sprintf(
            '%s #%s',
            $auditMessage,
            $id_tag
        )
    );

    ui_print_result_message(
        (bool) $result,
        __('Successfully updated tag'),
        __('Error updating tag')
    );
}

// Create tag: creates a new tag.
if ($create_tag) {
    $return_create = true;

    // Erase comma characters and spaces on tag name.
    $name_tag = str_replace(',', '', $name_tag);
    $name_tag = str_replace('&#x20;', '', $name_tag);

    $data = [];
    $data['name'] = $name_tag;
    $data['description'] = $description_tag;
    $data['url'] = $url_tag;
    $data['email'] = $email_tag;
    $data['phone'] = $phone_tag;

    // DB insert
    $return_create = false;
    if ($data['name'] != '') {
        $return_create = tags_create_tag($data);
    }

    if ($return_create === false) {
        $auditMessage = 'Fail try to create tag';
        $action = 'new';
        // If create action ends successfully then current action is update.
    } else {
        $auditMessage = sprintf('Create tag #%s', $return_create);
        $id_tag = $return_create;
        $action = 'update';
    }

    db_pandora_audit(
        AUDIT_LOG_TAG_MANAGEMENT,
        $auditMessage
    );

    if ($name_tag !== '') {
        ui_print_result_message(
            $action === 'update',
            __('Successfully created tag'),
            __('Error creating tag')
        );
    }
}

// Form fields are filled here
// Get results when update action is performed
if ($action == 'update' && $id_tag != 0) {
    $result_tag = tags_search_tag_id($id_tag);
    $name_tag = $result_tag['name'];
    $description_tag = $result_tag['description'];
    $url_tag = $result_tag['url'];
    $email_tag = $result_tag['email'];
    $phone_tag = $result_tag['phone'];
} //end if
else {
    $name_tag = '';
    $description_tag = '';
    $url_tag = '';
    $email_tag = '';
    $phone_tag = '';
}


// Create/Update tag form.
echo '<form method="post" class="max_floating_element_size" action="index.php?sec='.$sec.'&sec2=godmode/tag/edit_tag&action='.$action.'&id_tag='.$id_tag.'" enctype="multipart/form-data">';
echo "<table border=0 cellpadding=4 cellspacing=4 class='databox filter-table-adv' width=100%>";
    echo '<tr>';
    echo '<td>';
    echo html_print_label_input_block(
        __('Name'),
        html_print_input_text('name_tag', $name_tag, '', 50, 255, true)
    );
    echo '</td>';
    echo '<td>';
    echo html_print_label_input_block(
        __('Description'),
        html_print_input_text(
            'description_tag',
            $description_tag,
            '',
            50,
            255,
            true
        )
    );
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td colspan="2">';
    echo html_print_label_input_block(
        __('Url').ui_print_help_tip(
            __('Hyperlink to help information that has to exist previously.'),
            true
        ),
        html_print_input_text('url_tag', $url_tag, '', 50, 255, true)
    );
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo html_print_label_input_block(
        __('Email').ui_print_help_tip(
            __('Associated Email direction to use later in alerts associated to Tags.'),
            true
        ),
        html_print_textarea('email_tag', 5, 20, $email_tag, '', true)
    );
    echo '</td>';
    echo '<td>';
    echo html_print_label_input_block(
        __('Phone').ui_print_help_tip(
            __('Associated phone number to use later in alerts associated to Tags.'),
            true
        ),
        html_print_textarea('phone_tag', 5, 20, $phone_tag, '', true)
    );
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    $buttons = '';
    if ($action == 'update') {
        $buttons .= html_print_input_hidden('update_tag', 1, true);
        $buttons .= html_print_submit_button(
            __('Update'),
            'update_button',
            false,
            ['icon' => 'next'],
            true
        );
    }

    if ($action == 'new') {
        $buttons .= html_print_input_hidden('create_tag', 1, true);
        $buttons .= html_print_submit_button(
            __('Create'),
            'create_button',
            false,
            ['icon' => 'next'],
            true
        );
    }

    html_print_action_buttons(
        $buttons
    );

    echo '</form>';
