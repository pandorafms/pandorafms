<?php

enterprise_include_once('include/functions_license.php');

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AR')
    && ! check_acl($config['id_user'], 0, 'AW')
    && ! check_acl($config['id_user'], 0, 'AM')
    && ! check_acl($config['id_user'], 0, 'RR')
    && ! check_acl($config['id_user'], 0, 'RW')
    && ! check_acl($config['id_user'], 0, 'RM')
    && ! check_acl($config['id_user'], 0, 'PM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Server Management'
    );
    include 'general/noaccess.php';
    exit;
}

ui_require_css_file('discovery');

ui_require_javascript_file('pandora_alerts');
ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/');
ui_require_css_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('pandora_fullcalendar');


/**
 * Mask class names.
 *
 * @param string $str Wiz parameter.
 *
 * @return string Classname.
 */
function get_wiz_class($str)
{
    switch ($str) {
        case 'hd':
        return 'HostDevices';

        case 'cloud':
        return 'Cloud';

        case 'tasklist':
        return 'DiscoveryTaskList';

        case 'app':
        return 'Applications';

        case 'ctask':
        return 'ConsoleTasks';

        case 'deploymentCenter':
        return 'DeploymentCenter';

        case 'magextensions':
        return 'ManageExtensions';

        case 'custom':
        return 'Custom';

        default:
            // Main, show header.
            ui_print_standard_header(
                __('Discovery'),
                '',
                false,
                '',
                true,
                [],
                [
                    [
                        'link'  => '',
                        'label' => __('Discovery'),
                    ],
                ]
            );
        return null;
    }
}


/**
 * Aux. function to compare classpath names.
 *
 * @param string $a Classpath A.
 * @param string $b Classpath B.
 *
 * @return string Matching one.
 */
function cl_load_cmp($a, $b)
{
    $str_a = basename($a, '.class.php');
    $str_b = basename($b, '.class.php');
    if ($str_a == $str_b) {
        return 0;
    }

    if ($str_a < $str_b) {
        return -1;
    }

    return 1;

}


/*
 * CLASS LOADER.
 */

// Dynamic class loader.
$classes = glob($config['homedir'].'/godmode/wizards/*.class.php');
if (enterprise_installed()) {
    $ent_classes = glob(
        $config['homedir'].'/'.ENTERPRISE_DIR.'/godmode/wizards/*.class.php'
    );
    if ($ent_classes === false) {
        $ent_classes = [];
    }

    $classes = array_merge($classes, $ent_classes);
}

foreach ($classes as $classpath) {
    include_once $classpath;
}

// Sort output.
uasort($classes, 'cl_load_cmp');

// Check user action.
$wiz_in_use = get_parameter('wiz', null);
$page = get_parameter('page', 0);

$classname_selected = get_wiz_class($wiz_in_use);

// Else: class not found pseudo exception.
if ($classname_selected !== null) {
    $wiz = new $classname_selected((int) $page);

    // AJAX controller.
    if (is_ajax()) {
        $method = get_parameter('method');

        if (method_exists($wiz, $method) === true) {
            $wiz->{$method}();
        } else {
            $wiz->error('Method not found. ['.$method.']');
        }

        // Stop any execution.
        exit;
    } else {
        $result = $wiz->run();
        if (is_array($result) === true) {
            // Redirect control and messages to DiscoveryTasklist.
            $classname_selected = 'DiscoveryTaskList';
            $wiz = new $classname_selected($page);
            $result = $wiz->run($result['msg'], $result['result']);
        }
    }
}

if ($classname_selected === null) {
    // Load classes and print selector.
    $wiz_data = [];
    foreach ($classes as $classpath) {
        if (is_reporting_console_node() === true) {
            if ($classpath !== $config['homedir'].'/godmode/wizards/DiscoveryTaskList.class.php') {
                continue;
            }
        }

        $classname = basename($classpath, '.class.php');
        $obj = new $classname();

        if (method_exists($obj, 'isEmpty') === true) {
            if ($obj->isEmpty() === true) {
                continue;
            }
        }

        $button = $obj->load();

        if ($button === false) {
            // No acess, skip.
            continue;
        }

        // DiscoveryTaskList must be first button.
        if ($classname == 'DiscoveryTaskList') {
            array_unshift($wiz_data, $button);
        } else {
            $wiz_data[] = $button;
        }
    }

    // Show hints if there is no task.
    if (get_parameter('discovery_hint', 0)) {
        ui_require_css_file('discovery-hint');
        ui_print_info_message(__('You must create a task first'));
    }

    Wizard::printBigButtonsList($wiz_data);
}

$is_management_allowed = is_management_allowed();
$task_id = get_parameter('task', '');
if ($task_id !== '') {
    $task = db_get_row_filter(
        'tuser_task_scheduled',
        ['id' => $task_id]
    );
    $args = unserialize($task['args']);
    $event_calendar = io_safe_output($args['weekly_schedule']);
} else {
    $event_calendar = '{"monday":[{"start":"00:00:00","end":"00:00:00"}],"tuesday":[{"start":"00:00:00","end":"00:00:00"}],"wednesday":[{"start":"00:00:00","end":"00:00:00"}],"thursday":[{"start":"00:00:00","end":"00:00:00"}],"friday":[{"start":"00:00:00","end":"00:00:00"}],"saturday":[{"start":"00:00:00","end":"00:00:00"}],"sunday":[{"start":"00:00:00","end":"00:00:00"}]}';
}
?>
<script type="text/javascript">
    $(document).ready (function () {
        $("#table-new-job-3").hide();
        var edit = '<?php echo $task_id; ?>';
        if (edit != '') {
            exec_calendar();
        }

        $("#scheduled").change(exec_calendar);

        function exec_calendar() {
            if ($("#scheduled").val() == "weekly") {
                var is_management_allowed = parseInt('<?php echo (int) $is_management_allowed; ?>');
                var eventsBBDD = '<?php echo $event_calendar; ?>';
                var events = loadEventBBDD(eventsBBDD);
                var calendarEl = document.getElementById('calendar_map');

                var options = {
                    contentHeight: "auto",
                    headerToolbar: {
                        left: "",
                        center: "",
                        right: is_management_allowed === 0 ? '' : "timeGridWeek,dayGridWeek"
                    },
                    buttonText: {
                        dayGridWeek: '<?php echo __('Simple'); ?>',
                        timeGridWeek: '<?php echo __('Detailed'); ?>'
                    },
                    dayHeaderFormat: { weekday: "short" },
                    initialView: "dayGridWeek",
                    navLinks: false,
                    selectable: true,
                    selectMirror: true,
                    slotDuration: "01:00:00",
                    slotLabelInterval: "02:00:00",
                    snapDuration: "01:00:00",
                    slotMinTime: "00:00:00",
                    slotMaxTime: "24:00:00",
                    scrollTime: "01:00:00",
                    locale: "en-GB",
                    firstDay: 1,
                    eventTimeFormat: {
                        hour: "numeric",
                        minute: "2-digit",
                        hour12: false
                    },
                    eventColor: "#82b92e",
                    editable: is_management_allowed === 0 ? false : true,
                    dayMaxEvents: 3,
                    dayPopoverFormat: { weekday: "long" },
                    defaultAllDay: false,
                    displayEventTime: true,
                    displayEventEnd: true,
                    selectOverlap: false,
                    eventOverlap: false,
                    allDaySlot: true,
                    droppable: false,
                    select: is_management_allowed === 0 ? false : select_alert_template,
                    selectAllow: is_management_allowed === 0 ? false : selectAllow_alert_template,
                    eventAllow: is_management_allowed === 0 ? false : eventAllow_alert_template,
                    eventDrop: is_management_allowed === 0 ? false : eventDrop_alert_template,
                    eventDragStop: is_management_allowed === 0 ? false : eventDragStop_alert_template,
                    eventResize: is_management_allowed === 0 ? false : eventResize_alert_template,
                    eventMouseEnter: is_management_allowed === 0 ? false : eventMouseEnter_alert_template,
                    eventMouseLeave: is_management_allowed === 0 ? false : eventMouseLeave_alert_template,
                    eventClick: is_management_allowed === 0 ? false : eventClick_alert_template,
                };

                var settings = {
                    timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
                    timeOnlyTitle: '<?php echo __('Choose time'); ?>',
                    timeText: '<?php echo __('Time'); ?>',
                    hourText: '<?php echo __('Hour'); ?>',
                    minuteText: '<?php echo __('Minute'); ?>',
                    secondText: '<?php echo __('Second'); ?>',
                    currentText: '<?php echo __('Now'); ?>',
                    closeText: '<?php echo __('Close'); ?>',
                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                    removeText:  '<?php echo __('Remove'); ?>',
                    userLanguage: '<?php echo get_user_language(); ?>',
                    loadingText: '<?php echo __('Loading, this operation might take several minutes...'); ?>',
                    tooltipText: '<?php echo __('Drag out to remove'); ?>',
                    alert: '<?php echo __('Alert'); ?>'
                }

                var calendar = fullCalendarPandora(calendarEl, options, settings, events);
                calendar.render();

                $("#table-new-job-3").show();
                $('.fc-event-title').hide();
                $(".fc-button-active" ).trigger( "click" );
            } else {
                $("#calendar_map").html();
                $("#table-new-job-3").hide();
            }
        }
    });
</script>