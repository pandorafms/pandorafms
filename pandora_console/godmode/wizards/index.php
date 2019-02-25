<?php
global $config;
require $config['homedir'].'/godmode/wizards/Wizard.interface.php';

$obj = new HostDevices();

$obj->load();
