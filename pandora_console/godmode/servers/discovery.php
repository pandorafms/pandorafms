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
            if ($classpath !== '/var/www/html/pandora_console/godmode/wizards/DiscoveryTaskList.class.php') {
                continue;
            }
        }

        $classname = basename($classpath, '.class.php');
        $obj = new $classname();

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
