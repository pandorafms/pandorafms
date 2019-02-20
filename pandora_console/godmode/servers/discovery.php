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
 * Undocumented function
 *
 * @param  [type] $str
 * @return void
 */
function get_wiz_class($str)
{
    switch ($str) {
        case 'hd':
        return 'HostDevices';

        case 'tasklist':
        return 'DiscoveryTaskList';

        case 'app':
        return 'Applications';

        default:
            // Ignore.
        return null;
    }
}


/*
 * CLASS LOADER.
 */

// Dynamic class loader.
$classes = glob($config['homedir'].'/godmode/wizards/*.class.php');
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

$classes = array_merge($classes, $enterprise_classes);

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

        $classname_selected = null;
    }
}

if ($classname_selected === null) {
    // Load classes and print selector.
    $wiz_data = [];
    foreach ($classes as $classpath) {
        $classname = basename($classpath, '.class.php');
        $obj = new $classname();
        $wiz_data[] = $obj->load();
    }

    Wizard::printBigButtonsList($wiz_data);
}
