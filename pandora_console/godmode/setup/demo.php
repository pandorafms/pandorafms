<?php
/**
 * Enterprise Main Setup.
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Pandora FMS, http://www.pandorafms.com
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;
global $table;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Visual Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

config_update_value('demo_data_load_progress', 0);

html_print_input_hidden('demo_items_count', 0);

$submit_value = (string) get_parameter('update_button', '');
$demo_items_count = db_get_value('count(*)', 'tdemo_data');
$demo_agents_count = db_get_value('count(*)', 'tdemo_data', 'table_name', 'tagente');

// Basic/Advanced mode.
$mode = (string) get_parameter('mode', 'basic');

$buttons = [];

// Draws header.
$buttons['basic'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/demo&amp;mode=basic').'">'.html_print_image(
        'images/setup.png',
        true,
        [
            'title' => __('General'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['advanced'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/demo&amp;mode=advanced').'">'.html_print_image(
        'images/key.png',
        true,
        [
            'title' => __('Advanced'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

// Header.
ui_print_standard_header(
    __('Demo data'),
    'images/custom_field.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Setup'),
        ],
        [
            'link'  => '',
            'label' => __('Demo data'),
        ],
    ]
);

$table_aux = new stdClass();
$table_aux->id = 'table-demo';
$table_aux->class = 'filter-table-adv';
$table_aux->width = '100%';
$table_aux->data = [];
$table_aux->size = [];
$table_aux->size[0] = '50%';
$table_aux->size[1] = '50%';

if ($mode === 'advanced') {
    $arraySelectIcon = [
        10   => '10',
        25   => '25',
        30   => '30',
        50   => '50',
        500  => '500',
        1000 => '1000',
        2000 => '2000',
    ];
} else {
    $arraySelectIcon = [
        10 => '10',
        30 => '30',
        50 => '50',
    ];
}

$agent_num = (int) get_parameter('agents_num');

$otherData = [];
$table_aux->data['row1'][] = html_print_label_input_block(
    __('Agents'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_select(
                $arraySelectIcon,
                'agents_num',
                $config['gis_default_icon'],
                '',
                '30',
                30,
                true,
                false,
                true,
                'w80px'
            ).'&nbsp&nbsp<span id="agent-count-span" class="italic_a">'.__('(%d demo agents currently in the system)', $demo_agents_count).'</span>',
        ],
        true
    )
);

$table_aux->data['row2'][] = progress_bar(
    0,
    100,
    20,
    '',
    0,
    false,
    ((int) 0 !== -1) ? false : '#f3b200',
    [
        'class' => 'progress_bar',
        'id'    => 'progress_bar',
    ]
).html_print_input_hidden('js_timer_'.$operation['id'], 0, true);

if ($mode === 'advanced') {
    $table_aux->data['row3'][] = html_print_label_input_block(
        __('Generate historical data for all agents (15 days by default)'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row4'][] = html_print_label_input_block(
        __('Create services, visual console, dashboard, reports, clusters and network maps'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row5'][] = html_print_label_input_block(
        __('Generate custom/combined graphs'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row6'][] = html_print_label_input_block(
        __('Generate netflow demo data'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row7'][] = html_print_label_input_block(
        __('Generate logs for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row8'][] = html_print_label_input_block(
        __('Generate inventory data for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row9'][] = html_print_label_input_block(
        __('Generate SNMP traps for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row10'][] = html_print_label_input_block(
        __('Days of historical data to insert in the agent data'),
        html_print_input_text(
            'days_hist_data',
            15,
            '',
            10,
            20,
            true,
            false,
            false,
            '',
            'w80px'
        )
    );
    ?>
    <script type="text/javascript">
                    confirmDialog({
                        title: "<?php echo __('Warning'); ?>",
                        message: "<?php echo __('Advanced editor is intended for advanced users.'); ?>",
                        hideCancelButton: true,
                        onAccept: function() {
                            $('#user_profile_form').submit();
                        }
                    });
    </script>
    <?php
}


echo '<form class="max_floating_element_size" id="form_setup" method="post">';
echo '<fieldset>';
echo '<legend>'.__('Configure demo data').'</legend>';
html_print_input_hidden('update_config', 1);
html_print_table($table_aux);
echo '</fieldset>';

$actionButtons = [];

$actionButtons[] = html_print_submit_button(
    __('Create demo data'),
    'update_button',
    false,
    [
        'icon'     => 'update',
        'fixed_id' => 'btn-create-demo-data',
    ],
    true
);

$actionButtons[] = html_print_submit_button(
    __('Delete demo data'),
    'update_button',
    false,
    [
        'icon'     => 'delete',
        'mode'     => 'secondary',
        'fixed_id' => 'btn-delete-demo-data',
    ],
    true
);

html_print_action_buttons(
    implode('', $actionButtons)
);

echo '</form>';
?>

<script type="text/javascript">
    $('#btn-delete-demo-data').hide();

    $(document).ready (function () {
        var demo_items_count = <?php echo $demo_items_count; ?>;
        var demo_agents_count = <?php echo $demo_agents_count; ?>;
        var agent_count_span_str = '<?php echo __('demo agents currently in the system'); ?>';
        var agents_str = '<?php echo __('agents'); ?>';
        var delete_demo_data_str = '<?php echo __('Delete demo data'); ?>';
        

        if (demo_agents_count > 0) {
            $('#span-btn-delete-demo-data').text(delete_demo_data_str+' ('+demo_agents_count+')');
            $('#btn-delete-demo-data').show();
        }

        $("#table-demo-row2").hide();

        var submit_value = '<?php echo $submit_value; ?>';

        if (submit_value == 'Create&#x20;demo&#x20;data') {
            $("#table-demo-row2").show();

            init_progress_bar('create');

            var params = {};
            params["action"] = "create_demo_data";
            params["page"] = "include/ajax/demo_data.ajax";
            params["agents_num"] = $('#agents_num').val();

            jQuery.ajax({
                data: params,
                type: "POST",
                url: "ajax.php",
                dataType: 'json',
                success: function(data) {
                    if (data.agents_count > 0) {
                        $('#span-btn-delete-demo-data').text(delete_demo_data_str+' ('+data.agents_count+')');
                        $('#agent-count-span').text('('+(data.agents_count ?? 0)+' '+agent_count_span_str+')');
                        $('#btn-delete-demo-data').show();
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log("ERROR");
                }
            });
        }

        if (submit_value == 'Delete&#x20;demo&#x20;data') {
            $("#table-demo-row2").show();
            init_progress_bar('cleanup');

            var params = {};
            params["action"] = "cleanup_demo_data";
            params["page"] = "include/ajax/demo_data.ajax";

            jQuery.ajax({
                data: params,
                type: "POST",
                url: "ajax.php",
                success: function(data) {
                    $('#span-btn-delete-demo-data').text(delete_demo_data_str);
                    $('#agent-count-span').text('('+(data.agents_count ?? 0)+' '+agent_count_span_str+')');
                    $('#btn-delete-demo-data').hide();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log("ERROR");
                }
            });
        }
    });

    function demo_load_progress(id_queue, operation) {
        if (id_queue == null)
            return;

        var src_code = $('#' + id_queue).attr("src");
        /* Check stop begin */
        var progress_src = null;
        var elements_src = src_code.split("&");

        $.each(elements_src, function (key, value) {
            /* Get progress of element */
            if (value.indexOf("progress=") != -1) {
                var tokens_src = value.split("=");
                progress_src = tokens_src[1];
            }
        });

        /* STOP timer condition (progress >= 100) */
        if (progress_src >= 100) {
            clearInterval($("#hidden-js_timer_" + id_queue).val());
            return;
        }

        var params = {};
        params["action"] = "get_progress_bar";
        params["operation"] = operation;
        if (operation == 'cleanup') {
            var demo_items_count = '<?php echo $demo_items_count; ?>';
            params["demo_items_to_cleanup"] = demo_items_count;
        }
        params["page"] = "include/ajax/demo_data.ajax";
        params["id_queue"] = id_queue;

        jQuery.ajax({
            data: params,
            type: "POST",
            url: "ajax.php",
            success: function(data) {
                progress_tag_pos = src_code.indexOf("progress=");
                rest_pos = src_code.indexOf("&", progress_tag_pos);

                pre_src = src_code.substr(0,progress_tag_pos);
                post_src = src_code.substr(rest_pos);

                /* Create new src code for progress bar */
                new_src_code = pre_src + "progress=" + data + post_src;

                if (data != '')
                    $('#' + id_queue).attr("src", new_src_code);
            }
        });

    }

    function init_progress_bar(operation) {
        /* Get progress bar */
        var elements = $(".progress_bar");
        $.each(elements, function (key, progress_bar) {
            var elements_bar = $(progress_bar).attr("src").split("&");
            var current_progress = null;
            $.each(elements_bar, function (key, value) {
                /* Get progress */
                if (value.indexOf("progress=") != -1) {
                    var tokens = value.split("=");
                    current_progress = tokens[1];
                }
            });

            /* Get Queue id */
            var id_bar = $(progress_bar).attr("id");
            clearInterval($("#hidden-js_timer_" + id_bar).val());

            /* Only autorefresh incomplete bars */
            if (current_progress < 100) {
                /* 1 seconds between ajax request */
                var id_interval = setInterval("demo_load_progress('"+ id_bar +"','"+operation+"')", (1 * 1000));
                /* This will keep timer info */
                $("#hidden-js_timer_" + id_bar).val(id_interval);
            }
        });
    }


</script>