<?php

global $config;
ui_print_page_header(__('Discover'), 'wizards/hostDevices.png', false, '', true);


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

        default:
            // Ignore.
        return null;
    }
}


// Dynamic class loader.
$classes = glob($config['homedir'].'/godmode/wizards/*.class.php');
foreach ($classes as $classpath) {
    include_once $classpath;
}


$wiz_in_use = get_parameter('wiz', null);
$page = get_parameter('page', 0);

$classname_selected = get_wiz_class($wiz_in_use);

// Else: class not found pseudo exception.
if ($classname_selected !== null) {
    $wiz = new $classname_selected($page);
    $wiz->run();
    // TODO: Here we'll controlle if return is a valid recon task id.
    exit();
}

if ($classname_selected === null) {
    // Load classes and print selector.
    echo '<ul>';
    foreach ($classes as $classpath) {
        $classname = basename($classpath, '.class.php');
        $obj = new $classname();
        $wiz_data = $obj->load();

        hd($wiz_data);
        ?>
    <li>
        <a href="<?php echo $wiz_data['url']; ?>">
            <img src="<?php echo 'wizards/'.$wiz_data['icon']; ?>" alt="<?php echo $classname; ?>">
            <br><label><?php echo $wiz_data['label']; ?></label>
        </a>
    </li>

        <?php
    }

    echo '</ul>';
}
