<?php
/**
 * View for edit users in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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
check_login();

global $config;

if (check_acl($config['id_user'], 0, 'UM') !== 1) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive edit users'
    );
    include 'general/noaccess.php';
    return;
}

if (is_management_allowed() === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/users_setup&tab=profile&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All profiles user information is read only. Go to %s to manage it.',
            $url
        )
    );

    return;
}

if (is_metaconsole() === true) {
    include_once $config['homedir'].'/include/functions_visual_map.php';
}

$edit_users = (int) get_parameter('edit_users');
if ($edit_users === 1) {
    $users = get_parameter('id_users', false, false);
    if ($users !== false) {
        $update = [];

        $language = (string) get_parameter('language', '-1');
        if ($language !== '-1') {
            $update['language'] = $language;
        }

        $block_size_change = (int) get_parameter('block_size_change');
        if ($block_size_change === 0) {
            $block_size = (int) get_parameter('block_size', -1);
            if ($block_size !== -1) {
                $update['block_size'] = $block_size;
            }
        }

        $section = get_parameter('section', '-1');
        if ($section !== '-1') {
            $update['section'] = $section;
        }

        $data_section = get_parameter('data_section', '');
        $dashboard = get_parameter('dashboard', '');
        $visual_console = get_parameter('visual_console', '');
        $section = io_safe_output($section);

        if (($section === 'Event list') || ($section === 'Group view')
            || ($section === 'Alert detail') || ($section === 'Tactical view')
            || ($section === 'Default')
        ) {
            $update['data_section'] = '';
        } else if ($section === 'Dashboard') {
            $update['data_section'] = $dashboard;
        } else if ($section === 'Visual console') {
            $update['data_section'] = $visual_console;
        }

        $event = (int) get_parameter('event_filter', -1);
        if ($event !== -1) {
            $update['default_event_filter'] = $event;
        }

        $autorefresh_list = get_parameter_post('autorefresh_list', [0 => '-1']);
        if ($autorefresh_list[0] !== '-1') {
            if (($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')) {
                $update['autorefresh_white_list'] = '';
            } else {
                $update['autorefresh_white_list'] = json_encode($autorefresh_list);
            }
        }

        $time_autorefresh = (int) get_parameter('time_autorefresh', -1);
        if ($time_autorefresh !== -1) {
            $update['time_autorefresh'] = $time_autorefresh;
        }

        $timezone = (string) get_parameter('timezone', '-1');
        if ($timezone !== '-1') {
            $update['timezone'] = $timezone;
        }

        $disabled = (int) get_parameter('disabled', -1);
        if ($disabled !== -1) {
            $update['disabled'] = $disabled;
        }

        $error = [];
        $success = [];
        foreach ($users as $key => $user) {
            if (empty($update) === false) {
                $result = update_user($user, $update);
                if ($result === false) {
                    $error[] = $user;
                } else {
                    $success[] = $user;
                }
            } else {
                $error[] = $user;
            }
        }

        if (empty($success) === false) {
            ui_print_success_message(
                __(
                    'Users updated successfully (%s)',
                    implode(
                        ',',
                        $success
                    )
                )
            );
        }

        if (empty($error) === false) {
            ui_print_error_message(
                __(
                    'Users cannot be updated (%s)',
                    implode(',', $error)
                )
            );
        }
    }
}

if (is_metaconsole() === false) {
    include 'include/javascript/timezonepicker/includes/parser.inc';

    // Read in options for map builder.
    $bases = [
        'gray'           => 'Gray',
        'blue-marble'    => 'Blue marble',
        'night-electric' => 'Night Electric',
        'living'         => 'Living Earth',
    ];

    $local_file = 'include/javascript/timezonepicker/images/gray-400.png';

    // Dimensions must always be exact since the imagemap does not scale.
    $array_size = getimagesize($local_file);

    $map_width = $array_size[0];
    $map_height = $array_size[1];

    $timezones = timezone_picker_parse_files(
        $map_width,
        $map_height,
        'include/javascript/timezonepicker/tz_world.txt',
        'include/javascript/timezonepicker/tz_islands.txt'
    );
}


$get_users = get_users();
$users = [];
if (empty($get_users) === false) {
    foreach ($get_users as $key => $value) {
        $users[$key] = $key;
    }
}

$users_div = '<div class="label_select"><p class="edit_user_labels">'.__('Users').'</p>';
$users_div .= html_print_select(
    $users,
    'id_users[]',
    0,
    false,
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    '',
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    false,
    true,
    true,
    true
).'</div>';
echo '<form method="post" id="form_profiles" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users&option=edit_users">';
echo '<div class="user_edit_second_row white_box">';
echo '<div class="" style="width:65%">'.$users_div.'</div>';
echo '</div>';

// Language.
$language_db = db_get_all_rows_sql('SELECT id_language, name FROM tlanguage');
array_unshift($language_db, ['id_language' => 'default', 'name' => __('Default')]);
$language_list = [];
foreach ($language_db as $key => $value) {
    $language_list[$value['id_language']] = $value['name'];
}

$language = '<div class="label_select"><p class="edit_user_labels">'.__('Language').'</p>';
$language .= html_print_select(
    $language_list,
    'language',
    '',
    '',
    __('No change'),
    -1,
    true,
    false,
    false
).'</div>';

// Pagination.
$block_size = $config['global_block_size'];
$size_pagination = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Block size for pagination').'</p>';
$size_pagination .= html_print_input_text('block_size', $block_size, '', 5, 5, true);
$size_pagination .= html_print_checkbox_switch('block_size_change', 1, 1, true);
$size_pagination .= '<span>'.__('No change').'</span>';
$size_pagination .= '</div>';

// Home screen.
$home_screen = '<div class="label_select"><p class="edit_user_labels">'.__('Home screen').ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1 to show agent detail view'), true).'</p>';
$values = [
    '-1'             => __('No change'),
    'Default'        => __('Default'),
    'Visual console' => __('Visual console'),
    'Event list'     => __('Event list'),
    'Group view'     => __('Group view'),
    'Tactical view'  => __('Tactical view'),
    'Alert detail'   => __('Alert detail'),
    'Other'          => __('Other'),
    'Dashboard'      => __('Dashboard'),
];

$home_screen .= html_print_select(
    $values,
    'section',
    '',
    'show_data_section();',
    '',
    -1,
    true,
    false,
    false,
    '',
    false,
    '',
    '',
    10
).'</div>';

$dashboards = get_user_dashboards($config['id_user']);

$dashboards_aux = [];
if ($dashboards === false) {
    $dashboards = ['None' => 'None'];
} else {
    foreach ($dashboards as $key => $dashboard) {
        $dashboards_aux[$dashboard['id']] = $dashboard['name'];
    }
}

$home_screen .= '<div id="show_db" style="display: none; width: 100%;">';
$home_screen .= html_print_select($dashboards_aux, 'dashboard', '', '', '', '', true, false, false, '');
$home_screen .= '</div>';

$layouts = visual_map_get_user_layouts($config['id_user'], true);
$layouts_aux = [];
if (empty($layouts) === true) {
    $layouts_aux = ['None' => 'None'];
} else {
    foreach ($layouts as $layout) {
        $layouts_aux[$layout] = $layout;
    }
}

$home_screen .= '<div id="show_vc" style="display: none; width: 100%;">';
$home_screen .= html_print_select($layouts_aux, 'visual_console', '', '', '', '', true);
$home_screen .= '</div>';
$home_screen .= html_print_input_text('data_section', '', '', 60, 255, true, false);


// Event filter.
$user_groups = implode(',', array_keys((users_get_groups($config['id_user'], 'AR', true))));
$event_list = db_get_all_rows_sql('SELECT id_filter, id_name AS name FROM tevent_filter WHERE id_group_filter IN ('.$user_groups.')');
if (empty($event_list) === true) {
    $event_list = [];
}

array_unshift($event_list, ['id_filter' => 'none', 'name' => __('None')]);

$event_filter = '<div class="label_select"><p class="edit_user_labels">'.__('Event filter').'</p>';
$event_filter .= html_print_select(
    $event_list,
    'event_filter',
    '',
    '',
    __('No change'),
    -1,
    true,
    false,
    false
).'</div>';

// Autorefresh.
$autorefresh_list_out = [];
if (is_metaconsole() === false || is_centralized() === true) {
    $autorefresh_list_out['operation/agentes/estado_agente'] = 'Agent detail';
    $autorefresh_list_out['operation/agentes/alerts_status'] = 'Alert detail';
    $autorefresh_list_out['enterprise/operation/cluster/cluster'] = 'Cluster view';
    $autorefresh_list_out['operation/gis_maps/render_view'] = 'Gis Map';
    $autorefresh_list_out['operation/reporting/graph_viewer'] = 'Graph Viewer';
    $autorefresh_list_out['operation/snmpconsole/snmp_view'] = 'SNMP console';

    if (enterprise_installed()) {
        $autorefresh_list_out['general/sap_view'] = 'SAP view';
    }
}

$autorefresh_list_out['operation/agentes/tactical'] = 'Tactical view';
$autorefresh_list_out['operation/agentes/group_view'] = 'Group view';
$autorefresh_list_out['operation/agentes/status_monitor'] = 'Monitor detail';
$autorefresh_list_out['enterprise/operation/services/services'] = 'Services';
$autorefresh_list_out['operation/dashboard/dashboard'] = 'Dashboard';

$autorefresh_list_out['operation/agentes/pandora_networkmap'] = 'Network map';
$autorefresh_list_out['operation/visual_console/render_view'] = 'Visual console';
$autorefresh_list_out['operation/events/events'] = 'Events';

$autorefresh_show = '<p class="edit_user_labels">'._('Autorefresh').ui_print_help_tip(
    __('This will activate autorefresh in selected pages'),
    true
).'</p>';
$select_out = html_print_select(
    $autorefresh_list_out,
    'autorefresh_list_out[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:100%;min-height: 150px;'
);
$arrows = ' ';
$autorefresh_list = [];
$autorefresh_list['-1'] = __('No change');
$autorefresh_list[] = __('None');

$select_in = html_print_select(
    $autorefresh_list,
    'autorefresh_list[]',
    '-1',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:100%;min-height: 150px;'
);

$table_ichanges = '<div class="autorefresh_select">
    <div class="autorefresh_select_list_out">
        <p class="autorefresh_select_text">'.__('Full list of pages').': </p>
        <div>'.$select_out.'</div>
    </div>
    <div class="autorefresh_select_arrows" style="display:grid">
        <a href="javascript:">'.html_print_image(
            'images/darrowright_green.png',
            true,
            [
                'id'    => 'right_autorefreshlist',
                'alt'   => __('Push selected pages into autorefresh list'),
                'title' => __('Push selected pages into autorefresh list'),
            ]
        ).'</a>

        <a href="javascript:">'.html_print_image(
            'images/darrowleft_green.png',
            true,
            [
                'id'    => 'left_autorefreshlist',
                'alt'   => __('Pop selected pages out of autorefresh list'),
                'title' => __('Pop selected pages out of autorefresh list'),
            ]
        ).'</a>
    </div>
    <div class="autorefresh_select_list">
        <p class="autorefresh_select_text">'.__('List of pages with autorefresh').': </p>
        <div>'.$select_in.'</div>
    </div>
</div>';

$autorefresh_show .= $table_ichanges;

// Time autorefresh.
$times = get_refresh_time_array();
$time_autorefresh = '<div class="label_select"><p class="edit_user_labels">'.__('Time autorefresh');
$time_autorefresh .= ui_print_help_tip(
    __('Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first'),
    true
).'</p>';
$time_autorefresh .= html_print_select(
    $times,
    'time_autorefresh',
    '',
    '',
    __('No change'),
    '-1',
    true,
    false,
    false
).'</div>';

$timezone = '<div class="label_select"><p class="edit_user_labels">'.__('Timezone').ui_print_help_tip(__('The timezone must be that of the associated server.'), true).'</p>';
$timezone .= html_print_timezone_select('timezone', '-1', __('No change'), '-1').'</div>';
$timezone_map = '';

if (is_metaconsole() === false) {
    foreach ($timezones as $timezone_name => $tz) {
        if ($timezone_name == 'America/Montreal') {
            $timezone_name = 'America/Toronto';
        } else if ($timezone_name == 'Asia/Chongqing') {
            $timezone_name = 'Asia/Shanghai';
        }

        $area_data_timezone_polys .= '';
        foreach ($tz['polys'] as $coords) {
            $area_data_timezone_polys .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="poly" coords="'.implode(',', $coords).'" />';
        }

        $area_data_timezone_rects .= '';
        foreach ($tz['rects'] as $coords) {
            $area_data_timezone_rects .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="rect" coords="'.implode(',', $coords).'" />';
        }
    }

    $timezone_map = '<div id="timezone-picker" style="width:0px">
            <img id="timezone-image" src="'.$local_file.'" width="'.$map_width.'" height="'.$map_height.'" usemap="#timezone-map" />
            <img class="timezone-pin pdd_t_4px" src="include/javascript/timezonepicker/images/pin.png" />
            <map name="timezone-map" id="timezone-map">'.$area_data_timezone_polys.$area_data_timezone_rects.'</map>
        </div>';
}

// Status (Disable / Enable).
$status = '</br>';
$status .= '<div class="label_select"><p class="edit_user_labels">'.__('Status').'</p>';

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->data[0][0] = __('No change');
$table->data[0][0] .= html_print_radio_button_extended(
    'disabled',
    -1,
    '',
    -1,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[0][0] .= __('Disable');
$table->data[0][0] .= html_print_radio_button_extended(
    'disabled',
    1,
    '',
    '',
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[0][0] .= __('Enable');
$table->data[0][0] .= html_print_radio_button_extended(
    'disabled',
    0,
    '',
    '',
    false,
    '',
    'class="mrgn_right_40px"',
    true
);

$status .= html_print_table($table, true);

echo '<div id="users_options" class="user_edit_second_row white_box" style="display: none">';
echo sprintf(
    '<div class="edit_user_options">%s %s %s %s %s %s %s %s %s %s</div>',
    $language,
    $size_pagination,
    $skin,
    $home_screen,
    $event_filter,
    $autorefresh_show,
    $time_autorefresh,
    $timezone,
    $timezone_map,
    $status
);
echo '</div>';
echo '</div>';

attachActionButton('edit_users', 'update', '100%', false, $SelectAction);

echo '</form>';

if (is_metaconsole() === false) {
    // Include OpenLayers and timezone user map library.
    echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/timezonepicker/lib/jquery.timezone-picker.min.js').'"></script>'."\n\t";
    echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/timezonepicker/lib/jquery.maphilight.min.js').'"></script>'."\n\t";
    // Closes no meta condition.
    ?>

    <style>
        /* Styles for timezone map */
        #timezone-picker div.timezone-picker {
            margin: 0 auto;
        }
    </style>

    <script language="javascript" type="text/javascript">
        $(document).ready (function () {
            // Set up the picker to update target timezone and country select lists.
            $('#timezone-image').timezonePicker({
                target: '#timezone',
            });

            // Optionally an auto-detect button to trigger JavaScript geolocation.
            $('#timezone-detect').click(function() {
                $('#timezone-image').timezonePicker('detectLocation');
            });
        });
    </script>
    <?php
}

?>
<script type="text/javascript">
    $(document).ready(function() {
        show_data_section();
        $('#id_users').change(function() {
            const users = $("#id_users option:selected").length;
            if (users === 0) {
                $('#users_options').hide();
            } else {
                $('#users_options').show();
            }
        });

        $("#right_autorefreshlist").click (function () {
            jQuery.each($("select[name='autorefresh_list_out[]'] option:selected"), function (key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?> || imodule_name != <?php echo "'".__('No change')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("select[name='autorefresh_list[]']").append($("<option></option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                    // $("select[name='autorefresh_list[]']").val(id_imodule).prop("selected", "selected");
                    $("#autorefresh_list_out").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list").find("option[value='-1']").remove();
                    $("#autorefresh_list").find("option[value='0']").remove();
                    if($("#autorefresh_list_out option").length == 0) {
                        $("select[name='autorefresh_list_out[]']").append($("<option></option>").val('0').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
            });

            $('#autorefresh_list option').prop('selected', true);
        });

        $("#left_autorefreshlist").click (function () {
            jQuery.each($("select[name='autorefresh_list[]'] option:selected"), function (key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?> || imodule_name != <?php echo "'".__('No change')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("#autorefresh_list").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list_out").find("option[value='0']").remove();
                    $("select[name='autorefresh_list_out[]']").append($("<option><option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                    $("#autorefresh_list_out option").last().remove();
                    if($("#autorefresh_list option").length == 0) {
                        $("select[name='autorefresh_list[]']").append($("<option></option>").val('-1').html('<i><?php echo __('No change'); ?></i>'));
                        $("select[name='autorefresh_list[]']").append($("<option></option>").val('0').html('<i><?php echo __('None'); ?></i>'));
                        $('#autorefresh_list').val('-1').prop('selected', true);
                    }
                }
            });
        });
    });

    function show_data_section () {
        section = $("#section").val();

        switch (section) {
            case <?php echo "'".'Dashboard'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "inline-grid");

                break;
            case <?php echo "'".'Visual console'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "");
                $("#show_vc").css("display", "inline-grid");
                $("#show_db").css("display", "none");

                break;
            case <?php echo "'".'Event list'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
            case <?php echo "'".'Group view'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
            case <?php echo "'".'Tactical view'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
            case <?php echo "'".'Alert detail'."'"; ?>:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
            case <?php echo "'".'Other'."'"; ?>:
                $("#text-data_section").css("display", "");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
            default:
                $("#text-data_section").css("display", "none");
                $("#dashboard").css("display", "none");
                $("#visual_console").css("display", "none");
                $("#show_vc").css("display", "none");
                $("#show_db").css("display", "none");

                break;
        }
    }
</script>