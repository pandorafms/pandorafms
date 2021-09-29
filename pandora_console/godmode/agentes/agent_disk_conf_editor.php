<?php
if (enterprise_include('godmode/agentes/agent_disk_conf_editor.php') === ENTERPRISE_NOT_HOOK) {
    $message = [
        'title'    => 'Bavirion',
        'message'  => "<h3>This feature is not included on the Open Source version. Please visit our website to learn more about the advanced features of <a href='http://barivion.com'>Pandora FMS Enterprise edition</a></h3>",
        'no_close' => 1,
    ];

    ui_print_info_message($message);
}
