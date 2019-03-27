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
        // Redirect control and messages to DiscoveryTasklist.
        $classname_selected = 'DiscoveryTaskList';
        $wiz = new $classname_selected($page);
        $result = $wiz->run($result['msg'], $result['result']);
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
