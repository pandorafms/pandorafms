<?php

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Server Management'
    );
    include 'general/noaccess.php';
    exit;
}

ui_require_css_file('discovery');

ui_print_page_header(__('Discover'), '', false, '', true);


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

        default:
            // Ignore.
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
        $config['homedir'].'/enterprise/godmode/wizards/*.class.php'
    );
    if ($ent_classes === false) {
        $ent_classes = [];
    }

    $classes = array_merge($classes, $ent_classes);
}

foreach ($classes as $classpath) {
    include_once $classpath;
}

// Load enterprise wizards.
if (enterprise_installed() === true) {
    $enterprise_classes = glob(
        $config['homedir'].'/'.ENTERPRISE_DIR.'/wizards/*.class.php'
    );
    foreach ($enterprise_classes as $classpath) {
        $r = enterprise_include_once(
            'wizards/'.basename($classpath)
        );
    }
}

// Combine class paths.
$classes = array_merge($classes, $enterprise_classes);

// Sort output.
uasort($classes, 'cl_load_cmp');

// Check user action.
$wiz_in_use = get_parameter('wiz', null);
$page = get_parameter('page', 0);

$classname_selected = get_wiz_class($wiz_in_use);

// Else: class not found pseudo exception.
if ($classname_selected !== null) {
    $wiz = new $classname_selected($page);
    $result = $wiz->run();
    if (is_array($result) === true) {
        if ($result['result'] === 0) {
            // Success.
            ui_print_success_message($result['msg']);
            // TODO: Show task progress before redirect to main discovery menu.
        } else {
            // Failed.
            ui_print_error_message($result['msg']);
        }

        // Redirect to Tasklist.
        $classname_selected = 'DiscoveryTaskList';
        $wiz = new $classname_selected($page);
        $result = $wiz->run();
    }
}

if ($classname_selected === null) {
    // Load classes and print selector.
    $wiz_data = [];
    foreach ($classes as $classpath) {
        $classname = basename($classpath, '.class.php');
        $obj = new $classname();

        // DiscoveryTaskList must be first button.
        if ($classname == 'DiscoveryTaskList') {
            array_unshift($wiz_data, $obj->load());
        } else {
            $wiz_data[] = $obj->load();
        }
    }

    Wizard::printBigButtonsList($wiz_data);
}
