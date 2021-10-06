<?php
/**
 * Tags.
 *
 * @category   Tags
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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Check login and ACLs.
check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Tag Management');
    include 'general/noaccess.php';
    return;
}

// Include functions code.
require_once $config['homedir'].'/include/functions_tags.php';

// Get parameters.
$delete = (int) get_parameter('delete_tag', 0);
$tag_name = (string) get_parameter('tag_name', '');
$tab = (string) get_parameter('tab', 'list');

if ($delete !== 0 && is_metaconsole() === true) {
    open_meta_frame();
}

if ($tag_name != '' && is_metaconsole() === true) {
    open_meta_frame();
}

// Metaconsole nodes.
$servers = false;
if (is_metaconsole() === true) {
    enterprise_include_once('include/functions_metaconsole.php');
    $servers = metaconsole_get_servers();
}

// Ajax tooltip to deploy module's count info of a tag.
if (is_ajax() === true) {
    ob_clean();

    $get_tag_tooltip = (bool) get_parameter('get_tag_tooltip', 0);

    if ($get_tag_tooltip === true) {
        $id_tag = (int) get_parameter('id_tag');
        $tag = tags_search_tag_id($id_tag);
        if ($tag === false) {
            return;
        }

        $local_modules_count = 0;
        if (is_metaconsole() === true && empty($servers) === false) {
            $local_modules_count = array_reduce(
                $servers,
                function ($counter, $server) use ($id_tag) {
                    if (metaconsole_connect($server) === NOERR) {
                        $counter += tags_get_local_modules_count($id_tag);
                        metaconsole_restore_db();
                    }

                    return $counter;
                },
                0
            );
        } else {
            $local_modules_count = tags_get_local_modules_count($id_tag);
        }

        $policy_modules_count = 0;
        if (is_metaconsole() === true && empty($servers) === false) {
            $policy_modules_count = array_reduce(
                $servers,
                function ($counter, $server) use ($id_tag) {
                    if (metaconsole_connect($server) === NOERR) {
                        $counter += tags_get_policy_modules_count($id_tag);
                        metaconsole_restore_db();
                    }

                    return $counter;
                },
                0
            );
        } else {
            $policy_modules_count = tags_get_policy_modules_count($id_tag);
        }

        echo '<h3>'.$tag['name'].'</h3>';
        echo '<strong>'.__('Number of modules').': </strong> '.$local_modules_count;
        echo '<br>';
        echo '<strong>'.__('Number of policy modules').': </strong>'.$policy_modules_count;

        return;
    }

    return;
}

if (is_metaconsole() === true) {
    $sec = 'advanced';
} else {
    $sec = 'gmodules';
}

$buttons = [
    'list' => [
        'active' => false,
        'text'   => '<a href="index.php?sec='.$sec.'&sec2=godmode/tag/tag&tab=list">'.html_print_image(
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

if (is_metaconsole() === false) {
    // Header.
    ui_print_page_header(
        __('Tags configuration'),
        'images/tag.png',
        false,
        '',
        true,
        $buttons
    );
}

// Two actions can performed in this page: search and delete tags
// Delete action: This will delete a tag.
if ($delete !== 0) {
    $return_delete = tags_delete_tag($delete);

    if ($return_delete === false) {
        db_pandora_audit('Tag management', 'Fail try to delete tag #'.$delete);
        ui_print_error_message(__('Error deleting tag'));
    } else {
        db_pandora_audit('Tag management', 'Delete tag #'.$delete);
        ui_print_success_message(__('Successfully deleted tag'));
    }
}

$is_management_allowed = is_management_allowed();
if ($is_management_allowed === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/component_management'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All tags information is read only. Go to %s to manage it.',
            $url
        )
    );
}

// Search action: This will filter the display tag view.
$filter = [];
// Filtered view?
if (empty($tag_name) === false) {
    $filter['name'] = $tag_name;
}

// If the user has filtered the view.
$filter_performed = !empty($filter);

$filter['offset'] = (int) get_parameter('offset');
$filter['limit'] = (int) $config['block_size'];

// Statements for pagination.
$url = ui_get_url_refresh();
$total_tags = tags_get_tag_count($filter);

$result = tags_search_tag(false, $filter);

// Filter form.
$table = new StdClass();
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];

$row = [];

$name_input = __('Name').' / '.__('Description');
$name_input .= '&nbsp;&nbsp;';
$name_input .= html_print_input_text('tag_name', $tag_name, '', 30, 255, true);
$row[] = $name_input;

$filter_button = html_print_submit_button(__('Filter'), 'filter_button', false, 'class="sub search"', true);
$row[] = $filter_button;

$table->data[] = $row;

$filter_form = '<form method="POST" action="index.php?sec='.$sec.'&sec2=godmode/tag/tag&tag_name="'.$tag_name.'>';
$filter_form .= html_print_table($table, true);
$filter_form .= '</form>';
// End of filter form.
if (empty($result) === false) {
    if (is_metaconsole() === false) {
        echo $filter_form;
    } else {
        ui_toggle($filter_form, __('Show Options'));
    }

    // Prepare pagination.
    ui_pagination($total_tags, $url);

    // Display tags previously filtered or not.
    $rowPair = true;
    $iterator = 0;

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';

    $table->data = [];
    $table->head = [];
    $table->align = [];
    $table->style = [];
    $table->size = [
        '15%',
        '30%',
        '15%',
        '15%',
        '',
        '',
        '8%',
    ];

    $table->style[0] = 'font-weight: bold;';
    $table->style[3] = 'text-align:left';
    $table->style[6] = 'text-align:left';
    $table->head[0] = __('Tag name');
    $table->head[1] = __('Description');
    $table->head[2] = __('Detail information');
    $table->head[3] = __('Number of modules affected');
    $table->head[4] = __('Email');
    $table->head[5] = __('Phone');
    if ($is_management_allowed === true) {
        $table->head[6] = __('Actions');
    }

    foreach ($result as $tag) {
        if ($rowPair) {
            $table->rowclass[$iterator] = 'rowPair';
        } else {
            $table->rowclass[$iterator] = 'rowOdd';
        }

        $rowPair = !$rowPair;
        $iterator++;

        $data = [];

        if ($is_management_allowed === true) {
            $data[0] = "<a href='index.php?sec=".$sec.'&sec2=godmode/tag/edit_tag&action=update&id_tag='.$tag['id_tag']."'>";
            $data[0] .= $tag['name'];
            $data[0] .= '</a>';
        } else {
            $data[0] = $tag['name'];
        }

        $data[1] = ui_print_truncate_text($tag['description'], 'description', false);
        $data[2] = '<a href="'.$tag['url'].'">'.$tag['url'].'</a>';

        // The tooltip needs a title on the item, don't delete the title.
        $data[3] = '<a class="tag_details img_help" title="'.__('Tag details').'"
			href="'.ui_get_full_url(false, false, false, false).'/ajax.php?page=godmode/tag/tag&get_tag_tooltip=1&id_tag='.$tag['id_tag'].'">'.html_print_image(
            'images/zoom.png',
            true,
            ['class' => 'invert_filter']
        ).'</a> ';

        $modules_count = 0;
        if (is_metaconsole() === true && empty($servers) === false) {
            $tag_id = $tag['id_tag'];
            $modules_count = array_reduce(
                $servers,
                function ($counter, $server) use ($tag_id) {
                    if (metaconsole_connect($server) === NOERR) {
                        $counter += tags_get_modules_count($tag_id);
                        metaconsole_restore_db();
                    }

                    return $counter;
                },
                0
            );
        } else {
            $modules_count = tags_get_modules_count($tag['id_tag']);
        }

        $data[3] .= $modules_count;

        $email_large = io_safe_output($tag['email']);
        $email_small = substr($email_large, 0, 24);
        if ($email_large == $email_small) {
            $output = $email_large;
        } else {
            $title_mail = sprintf(__('Emails for the tag: %s'), $tag['name']);
            $output = "<div title='".$title_mail."' class='email_large invisible' id='email_large_".$tag['id_tag']."'>";
            $output .= $email_large;
            $output .= '</div>';
            $output .= '<span id="value_'.$tag['id_tag'].'">';
            $output .= $email_small;
            $output .= '</span> ';
            $output .= "<a href='javascript: show_dialog(".$tag['id_tag'].")'>";
            $output .= html_print_image(
                'images/rosette.png',
                true,
                ['class' => 'invert_filter']
            );
            $output .= '</a></span>';
        }

        $data[4] = $output;

        $phone_large = io_safe_output($tag['phone']);
        $phone_small = substr($phone_large, 0, 24);
        if ($phone_large == $phone_small) {
            $output = $phone_large;
        } else {
            $t_phone = sprintf(__('Phones for the tag: %s'), $tag['name']);
            $output = "<div title='".$t_phone."' class='phone_large invisible' id='phone_large_".$tag['id_tag']."'>";
            $output .= $phone_large;
            $output .= '</div>';
            $output .= '<span id="value_'.$tag['id_tag'].'">'.$phone_small.'</span> ';
            $output .= "<a href='javascript: show_phone_dialog(".$tag['id_tag'].")'>";
            $output .= html_print_image(
                'images/rosette.png',
                true,
                ['class' => 'invert_filter']
            );
            $output .= '</a></span>';
        }

        $data[5] = $output;

        if ($is_management_allowed === true) {
            $table->cellclass[][6] = 'action_buttons';
            $data[6] = "<a href='index.php?sec=".$sec.'&sec2=godmode/tag/edit_tag&action=update&id_tag='.$tag['id_tag']."'>";
            $data[6] .= html_print_image(
                'images/config.png',
                true,
                [
                    'title' => 'Edit',
                    'class' => 'invert_filter',
                ]
            );
            $data[6] .= '</a>';
            $data[6] .= '<a  href="index.php?sec='.$sec.'&sec2=godmode/tag/tag&delete_tag='.$tag['id_tag'].'"onclick="if (! confirm (\''.__('Are you sure?').'\')) return false">'.html_print_image(
                'images/cross.png',
                true,
                [
                    'title' => 'Delete',
                    'class' => 'invert_filter',
                ]
            ).'</a>';
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    ui_pagination($total_tags, $url, 0, 0, false, 'offset', true, 'pagination-bottom');
} else {
    if (is_metaconsole() === true) {
        ui_toggle($filter_form, __('Show Options'));
        ui_print_info_message(['no_close' => true, 'message' => __('No tags defined')]);
    } else if ($filter_performed) {
        echo $filter_form;
    } else {
        include $config['homedir'].'/general/first_task/tags.php';
        return;
    }
}

if ($is_management_allowed === true) {
    echo '<table border=0 cellpadding=0 cellspacing=0 width=100%>';
    echo '<tr>';
    echo '<td align=right>';
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/tag/edit_tag&action=new">';
    html_print_input_hidden('create_tag', '1', true);
    html_print_submit_button(__('Create tag'), 'create_button', false, 'class="sub next"');
    echo '</form>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

if ($delete != 0 && is_metaconsole() === true) {
    close_meta_frame();
}

?>

<script type="text/javascript">
    $("a.tag_details")
        .tooltip({
            track: true,
            content: '<?php html_print_image('images/spinner.gif'); ?>',
            open: function (evt, ui) {
                var elem = $(this);
                var uri = elem.prop('href');
                if (typeof uri !== 'undefined' && uri.length > 0) {
                    var jqXHR = $.ajax(uri).done(function(data) {
                        elem.tooltip('option', 'content', data);
                    });
                    // Store the connection handler
                    elem.data('jqXHR', jqXHR);
                }
                $(".ui-tooltip>.ui-tooltip-content:not(.cluetip-default)")
                    .addClass("cluetip-default");
            },
            close: function (evt, ui) {
                var elem = $(this);
                var jqXHR = elem.data('jqXHR');
                // Close the connection handler
                if (typeof jqXHR !== 'undefined')
                    jqXHR.abort();
            }
        })
        .click (function (event) {
            event.preventDefault();
        })
        .css('cursor', 'help');
    $(".email_large, .phone_large").dialog({
        autoOpen: false,
        resizable: true,
        width: 400,
        height: 200
    });
    function show_dialog(id) {
        $("#email_large_" + id).dialog("open");
    }
    function show_phone_dialog(id) {
        $("#phone_large_" + id).dialog("open");
    }
</script>
