<?php
/**
 * Special days.
 *
 * @category   Alerts
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

// Extras required.
ui_require_css_file('wizard');

// Header.
\ui_print_page_header(
    // Title.
    __('Alerts').' &raquo; '.__('Configure special day'),
    // Icon.
    'images/gm_alerts.png',
    // Return.
    false,
    // Help.
    'alert_special_days',
    // Godmode.
    true,
    // Options.
    $tabs
);

if (empty($message) === false) {
    echo $message;
}

$inputs = [];

// Date.
$inputs[] = [
    'label'     => __('Date'),
    'arguments' => [
        'type'     => 'text',
        'name'     => 'date',
        'required' => true,
        'value'    => $specialDay->date(),
    ],
];

// Date img.
$inputs[] = [
    'arguments' => [
        'type'    => 'image',
        'src'     => 'images/calendar_view_day.png',
        'value'   => $specialDay->date(),
        'options' => [
            'alt'     => 'calendar',
            'onclick' => "scwShow(scwID('text-date'),this);",
            'class'   => 'invert_filter',
        ],

    ],
];

if (users_can_manage_group_all('LM') === true) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

// Group.
$inputs[] = [
    'label'     => __('Group'),
    'arguments' => [
        'type'           => 'select_groups',
        'returnAllGroup' => $display_all_group,
        'name'           => 'id_group',
        'selected'       => $specialDay->id_group(),
    ],
];

$days = [];
$days[1] = __('Monday');
$days[2] = __('Tuesday');
$days[3] = __('Wednesday');
$days[4] = __('Thursday');
$days[5] = __('Friday');
$days[6] = __('Saturday');
$days[7] = __('Sunday');
$days[8] = __('Holidays');

// Same day of the week.
$inputs[] = [
    'label'     => __('Same day of the week'),
    'arguments' => [
        'name'     => 'day_code',
        'type'     => 'select',
        'fields'   => $days,
        'selected' => $specialDay->day_code(),
    ],
];

// Description.
$inputs[] = [
    'label'     => __('Description'),
    'arguments' => [
        'type'     => 'textarea',
        'name'     => 'description',
        'required' => false,
        'value'    => $specialDay->description(),
        'rows'     => 50,
        'columns'  => 30,
    ],
];

// Submit.
$inputs[] = [
    'arguments' => [
        'name'       => 'button',
        'label'      => (($create === true) ? __('Create') : __('Update')),
        'type'       => 'submit',
        'attributes' => 'class="sub next"',
    ],
];

// Print form.
HTML::printForm(
    [
        'form'   => [
            'id'     => 'form-special-days',
            'action' => $url.'&tab=special_days&op=edit&action=save&id='.$specialDay->id(),
            'method' => 'POST',
        ],
        'inputs' => $inputs,
    ],
    false,
    true
);

echo '<div id="modal-alert-templates" class="invisible"></div>';

ui_require_javascript_file('calendar');
ui_require_javascript_file('pandora_alerts');

hd($ajax_url);
?>
<script type="text/javascript">
$(document).ready (function () {
    $("#submit-button").click (function (e) {
        e.preventDefault();
        var date = new Date($("#text-date").val());
        var dateformat = date.toLocaleString(
            'default',
            {day: 'numeric', month: 'short',  year: 'numeric'}
        );

        load_templates_alerts_special_days({
            date: $("#text-date").val(),
            id_group: $("#id_group").val(),
            day_code: $("#day_code").val(),
            btn_ok_text: '<?php echo __('Create'); ?>',
            btn_cancel_text: '<?php echo __('Cancel'); ?>',
            title: dateformat,
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            page: '<?php echo $ajax_url; ?>',
            loading: '<?php echo __('Loading, this operation might take several minutes...'); ?>',
            name_form: 'form-special-days'
        });
    });
});
</script>