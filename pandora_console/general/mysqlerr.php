<?php
echo '<script src="'.ui_get_full_url('include/javascript/jquery.current.js', false, false, false).'" type="text/javascript"></script>';

$message = '';

if ($config['history_db_connection'] === false) {
    $message = __('Failure to connect to historical database, please check the configuration or contact system administrator if you need assistance.');
} else {
    $message = __('Failure to connect to Database server, please check the configuration file config.php or contact system administrator if you need assistance.');
}

$custom_conf_enabled = false;
foreach ($config as $key => $value) {
    if (preg_match('/._alt/i', $key)) {
        $custom_conf_enabled = true;
        break;
    }
}

if (empty($custom_conf_enabled) === true || isset($config['custom_docs_url_alt']) === true) {
    if (isset($config['custom_docs_url_alt']) === true) {
        $docs_url = $config['custom_docs_url_alt'];
    } else {
        $docs_url = 'https://pandorafms.com/manual/en/documentation/02_installation/04_configuration';
    }
}

echo '<div id="mysqlerr" title="'.__('Error').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/mysqlerr.png', true, ['alt' => __('Mysql error'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('Database error').'</h1>';
                    echo '<p>'.$message.'</p>';
                    echo '<br>';
                echo '</div>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button(
                        __('Documentation'),
                        'mysqlerr_button',
                        false,
                        ['class' => 'mini float-right']
                    );
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    ?>


<script>
$(function() {
    $("#mysqlerr").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        width: 700,
        clickOutside: true,
        overlay: {
            opacity: 0.5,
            background: "black"
        }
    });
});

$("#mysqlerr").hide();

$("#button-mysqlerr_button").click (function () {
    window.open('<?php echo ui_get_full_external_url($docs_url); ?>', '_blank');
});

$(document).ready(function () {
    $("#mysqlerr").show();
});
</script>
