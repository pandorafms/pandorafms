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
$table_aux->id = 'table-password-policy';
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
                '',
                '',
                true,
                false,
                true,
                'w80px'
            ).'&nbsp&nbsp<span class="italic_a">'.__('(%d demo agents currently in the system)').'</span>',
        ],
        true
    )
);

if ($mode === 'advanced') {
    $table_aux->data['row2'][] = html_print_label_input_block(
        __('Generate historical data for all agents (15 days by default)'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row3'][] = html_print_label_input_block(
        __('Create services, visual console, dashboard, reports, clusters and network maps'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row4'][] = html_print_label_input_block(
        __('Generate custom/combined graphs'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row5'][] = html_print_label_input_block(
        __('Generate netflow demo data'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row6'][] = html_print_label_input_block(
        __('Generate logs for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row7'][] = html_print_label_input_block(
        __('Generate inventory data for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row8'][] = html_print_label_input_block(
        __('Generate SNMP traps for each agent'),
        html_print_checkbox_switch(
            'enable_pass_policy_admin',
            1,
            $config['enable_pass_policy_admin'],
            true
        )
    );

    $table_aux->data['row9'][] = html_print_label_input_block(
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
    [ 'icon' => 'update' ],
    true
);

$actionButtons[] = html_print_button(
    __('Delete demo data'),
    'delete_session_users',
    false,
    '',
    [
        'icon' => 'delete',
        'mode' => 'secondary',
    ],
    true
);

html_print_action_buttons(
    implode('', $actionButtons)
);

echo '</form>';

?>

<script type="text/javascript">
    $('#form_setup').on('submit', function(e) {
        e.preventDefault();
console.log("SBM");
        var params = {};
        params["action"] = "create_demo_data";
        params["page"] = "include/ajax/demo_data.ajax";
        params["agents_num"] = $('#agents_num').val();

        // Browse!
        jQuery.ajax({
            data: params,
            type: "POST",
            url: "ajax.php",
            success: function(data) {
                console.log("SUCCESS", data);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log("ERROR");
            }
        });
    });
</script>