<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

echo '<form id="form_setup" method="post">';
$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style[0] = 'font-weight: bolder;';

$table->data[0][0] = __('Name:');
$table->data[0][1] = html_print_input_text('name', $name, __('Name'), 20, 30, true);
$table->data[1][0] = __('Description');
$table->data[1][1] = html_print_textarea('description', 5, 10, $description, '', true);
$icons = get_list_os_icons_dir();
$table->data[2][0] = __('Icon');
$table->data[2][1] = html_print_select($icons, 'icon', $icon, 'show_icon_OS();', __('None'), 0, true);
$table->data[2][1] .= ' <span id="icon_image">'.ui_print_os_icon($idOS, false, true).'</span>';


html_print_table($table);

html_print_input_hidden('id_os', $idOS);
html_print_input_hidden('action', $actionHidden);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button($textButton, 'update_button', false, $classButton);
echo '</div>';
echo '</form>';


function get_list_os_icons_dir()
{
    global $config;

    $return = [];

    $items = scandir($config['homedir'].'/images/os_icons');

    foreach ($items as $item) {
        if (strstr($item, '_small.png') || strstr($item, '_small.gif')
            || strstr($item, '_small.jpg')
        ) {
            continue;
        }

        if (strstr($item, '.png') || strstr($item, '.gif')
            || strstr($item, '.jpg')
        ) {
            $return[$item] = $item;
        }
    }

    return $return;
}


?>
<script type="text/javascript">

function show_icon_OS() {

    var params = [];
    params.push("get_image_path=1");
    params.push('img_src=images/os_icons/' + $("#icon").val());
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