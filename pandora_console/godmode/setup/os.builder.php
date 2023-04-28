<?php
/**
 * OS Builder
 *
 * @category   Os
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

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

$icons = get_list_os_icons_dir();

$iconData = [];
$iconData[] = html_print_select(
    $icons,
    'icon',
    $icon,
    'show_icon_OS();',
    __('None'),
    0,
    true
);
$iconData[] = html_print_div(
    [
        'id'      => 'icon_image',
        'class'   => 'invert_filter main_menu_icon',
        'style'   => 'margin-left: 10px',
        'content' => ui_print_os_icon($idOS, false, true),
    ],
    true
);

echo '<form id="form_setup" method="post">';
$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filter-table-adv';

// $table->style[0] = 'width: 15%';
$table->data[0][] = html_print_label_input_block(
    __('Name'),
    html_print_input_text('name', $name, __('Name'), 20, 30, true, false, false, '', 'w250px')
);

$table->data[0][] = html_print_label_input_block(
    __('Icon'),
    html_print_div(
        [
            'class'   => 'flex-row-center',
            'content' => implode('', $iconData),
        ],
        true
    )
);

$table->data[1][] = html_print_label_input_block(
    __('Description'),
    html_print_textarea('description', 5, 20, $description, '', true, 'w250px')
);

html_print_table($table);

html_print_input_hidden('id_os', $idOS);
html_print_input_hidden('action', $actionHidden);

html_print_action_buttons(
    html_print_submit_button($textButton, 'update_button', false, $classButton, true),
    ['type' => 'form_action']
);

echo '</form>';


function get_list_os_icons_dir()
{
    global $config;

    $return = [];

    $items = scandir($config['homedir'].'/images/');

    foreach ($items as $item) {
        if (strstr($item, '@os.svg')) {
            $return[$item] = $item;
        }
    }

    $items2 = scandir($config['homedir'].'/images/os_icons');

    foreach ($items2 as $item2) {
        if (strstr($item2, '_small.png') || strstr($item2, '_small.gif')
            || strstr($item2, '_small.jpg')
        ) {
            continue;
        }

        if (strstr($item2, '.png') || strstr($item2, '.gif')
            || strstr($item2, '.jpg')
        ) {
            $return[$item2] = $item2;
        }
    }

    return $return;
}


?>
<script type="text/javascript">

function show_icon_OS() {
    var extension = $("#icon").val().split('.').pop();

    var params = [];
    params.push("get_image_path=1");
    if (extension !== 'svg') {
        params.push('img_src=images/os_icons/' + $("#icon").val());
    } else {
        params.push('img_src=images/' + $("#icon").val());
    }
    params.push("page=include/ajax/skins.ajax");
    jQuery.ajax ({
        data: params.join ("&"),
        type: 'POST',
        url: action="ajax.php",
        async: false,
        timeout: 10000,
        success: function (data) {
            $("#icon_image").html(data);
        }
    });
}
</script>