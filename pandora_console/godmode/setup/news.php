<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Link Management'
    );
    include 'general/noaccess.php';
    exit;
}

// Header
ui_print_page_header(__('Site news management'), '', false, '', true);



if (isset($_POST['create'])) {
    // If create
    $subject = get_parameter('subject');
    $text = get_parameter('text');
    $timestamp = db_get_value('NOW()', 'tconfig_os', 'id_os', 1);
    $id_group = get_parameter('id_group');
    $modal = get_parameter('modal');
    $expire = get_parameter('expire');
    $expire_date = get_parameter('expire_date');
    $expire_time = get_parameter('expire_time');
    // Change the user's timezone to the system's timezone
    $expire_timestamp = (strtotime("$expire_date $expire_time") - get_fixed_offset());
    $expire_timestamp = date('Y-m-d H:i:s', $expire_timestamp);

    $values = [
        'subject'          => $subject,
        'text'             => $text,
        'author'           => $config['id_user'],
        'timestamp'        => $timestamp,
        'id_group'         => $id_group,
        'modal'            => $modal,
        'expire'           => $expire,
        'expire_timestamp' => $expire_timestamp,
    ];

    $id_link = db_process_sql_insert('tnews', $values);

    ui_print_result_message(
        $id_link,
        __('Successfully created'),
        __('Could not be created')
    );
}

if (isset($_POST['update'])) {
    // if update
    $id_news = (int) get_parameter('id_news', 0);
    $subject = get_parameter('subject');
    $text = get_parameter('text');
    $id_group = get_parameter('id_group');
    $modal = get_parameter('modal');
    $expire = get_parameter('expire');
    $expire_date = get_parameter('expire_date');
    $expire_time = get_parameter('expire_time');
    // Change the user's timezone to the system's timezone
    $expire_timestamp = (strtotime("$expire_date $expire_time") - get_fixed_offset());
    $expire_timestamp = date('Y-m-d H:i:s', $expire_timestamp);

    // NOW() column exists in any table and always displays the current date and time, so let's get the value from a row in a table which can't be deleted.
    // This way we prevent getting no value for this variable
    $timestamp = db_get_value('NOW()', 'tconfig_os', 'id_os', 1);

    $values = [
        'subject'          => $subject,
        'text'             => $text,
        'timestamp'        => $timestamp,
        'id_group'         => $id_group,
        'modal'            => $modal,
        'expire'           => $expire,
        'expire_timestamp' => $expire_timestamp,
    ];

    $result = db_process_sql_update('tnews', $values, ['id_news' => $id_news]);

    ui_print_result_message(
        $result,
        __('Successfully updated'),
        __('Not updated. Error updating data')
    );
}

if (isset($_GET['borrar'])) {
    // if delete
    $id_news = (int) get_parameter('borrar', 0);

    $result = db_process_sql_delete('tnews', ['id_news' => $id_news]);

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

// Main form view for Links edit
if ((isset($_GET['form_add'])) || (isset($_GET['form_edit']))) {
    if (isset($_GET['form_edit'])) {
        $creation_mode = 0;
        $id_news = (int) get_parameter('id_news', 0);

        $result = db_get_row('tnews', 'id_news', $id_news);

        if ($result !== false) {
            $subject = $result['subject'];
            $text = $result['text'];
            $author = $result['author'];
            $timestamp = $result['timestamp'];
            $id_group = $result['id_group'];
            $modal = $result['modal'];
            $expire = $result['expire'];

            if ($expire) {
                $expire_timestamp = $result['expire_timestamp'];
                $expire_utimestamp = time_w_fixed_tz($expire_timestamp);
            } else {
                $expire_utimestamp = (get_system_time() + SECONDS_1WEEK);
            }

            $expire_date = date('Y/m/d', $expire_utimestamp);
            $expire_time = date('H:i:s', $expire_utimestamp);
        } else {
            ui_print_error_message(__('Name error'));
        }
    } else {
        // form_add
        $creation_mode = 1;
        $text = '';
        $subject = '';
        $author = $config['id_user'];
        $id_group = 0;
        $modal = 0;
        $expire = 0;
        $expire_date = date('Y/m/d', (get_system_time() + SECONDS_1WEEK));
        $expire_time = date('H:i:s', get_system_time());
    }

    // Create news
    $table = new stdClass();
    $table->width = '100%';
    $table->id = 'news';
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->class = 'databox filters';
    $table->head = [];
    $table->data = [];
    $table->style[0] = 'font-weight: bold;';
    $table->style[1] = 'font-weight: bold;';
    $table->style[2] = 'font-weight: bold;';
    $table->style[3] = 'font-weight: bold;';
    $table->style[4] = 'font-weight: bold;';

    $data = [];
    $data[0] = __('Subject').'<br>';
    $data[0] .= '<input type="text" name="subject" size="35" value="'.$subject.'">';

    $data[1] = __('Group').'<br>';
    $data[1] .= html_print_select_groups($config['id_user'], 'ER', users_can_manage_group_all(), 'id_group', $id_group, '', '', 0, true, false, false, '');

    $data[2] = __('Modal screen').'<br>';
    $data[2] .= html_print_checkbox_extended('modal', 1, $modal, false, '', 'style="margin-top: 5px;margin-bottom: 7px;"', true);

    $data[3] = __('Expire').'<br>';
    $data[3] .= html_print_checkbox_extended('expire', 1, $expire, false, '', 'style="margin-top: 5px;margin-bottom: 7px;"', true);

    $data[4] = __('Expiration').'<br>';
    $data[4] .= html_print_input_text('expire_date', $expire_date, '', 12, 10, true).' ';
    $data[4] .= html_print_input_text('expire_time', $expire_time, '', 10, 7, true).' ';

    $table->rowclass[] = '';
    $table->data[] = $data;

    $data = [];
    $data[0] = __('Text').'<br>';
    $data[0] .= html_print_textarea('text', 25, 15, $text, '', true);
    $table->rowclass[] = '';
    $table->colspan[1][0] = 5;

    $table->data[] = $data;

    echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/news">';
    if ($creation_mode == 1) {
        echo "<input type='hidden' name='create' value='1'>";
    } else {
        echo "<input type='hidden' name='update' value='1'>";
    }

    echo "<input type='hidden' name='id_news' value='";
    if (isset($id_news)) {
        echo $id_news;
    }

    echo "'>";

    html_print_table($table);

    echo "<table width='".$table->width."'>";
    echo "<tr><td align='right'>";
    if (isset($_GET['form_add'])) {
        echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
    } else {
        echo "<input name='crtbutton' type='submit' class='sub upd' value='".__('Update')."'>";
    }

    echo '</form></td></tr></table>';
} else {
    $rows = db_get_all_rows_in_table('tnews', 'timestamp');
    if ($rows === false) {
        $rows = [];
        ui_print_info_message(['no_close' => true, 'message' => __('There are no defined news') ]);
    } else {
        // Main list view for Links editor
        echo "<table cellpadding='0' cellspacing='0' class='info_table' width=100%>";
        echo '<thead><tr>';
        echo '<th>'.__('Subject').'</th>';
        echo '<th>'.__('Type').'</th>';
        echo '<th>'.__('Author').'</th>';
        echo '<th>'.__('Timestamp').'</th>';
        echo '<th>'.__('Expiration').'</th>';
        echo '<th>'.__('Delete').'</th>';
        echo '</tr></thead>';


        $color = 1;
        foreach ($rows as $row) {
            if ($color == 1) {
                $tdcolor = 'datos';
                $color = 0;
            } else {
                $tdcolor = 'datos2';
                $color = 1;
            }

            echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/news&form_edit=1&id_news=".$row['id_news']."'>".$row['subject'].'</a></b></td>';

            if ($row['modal']) {
                echo "<td class='$tdcolor'>".__('Modal').'</b></td>';
            } else {
                echo "<td class='$tdcolor'>".__('Board').'</b></td>';
            }

            echo "<td class='$tdcolor'>".$row['author'].'</b></td>';
            $utimestamp = time_w_fixed_tz($row['timestamp']);
            echo "<td class='$tdcolor'>".date($config['date_format'], $utimestamp).'</b></td>';
            if ($row['expire']) {
                $expire_utimestamp = time_w_fixed_tz($row['expire_timestamp']);
                $expire_in_secs = ($expire_utimestamp - $utimestamp);

                if ($expire_in_secs <= 0) {
                    echo "<td class='$tdcolor'>".__('Expired').'</b></td>';
                } else {
                    $expire_in = human_time_description_raw($expire_in_secs, false, 'large');
                    echo "<td class='$tdcolor'>".$expire_in.'</b></td>';
                }
            } else {
                echo "<td class='$tdcolor'>".__('No').'</b></td>';
            }

            echo '<td class="'.$tdcolor.' action_buttons"><a href="index.php?sec=gsetup&sec2=godmode/setup/news&id_news='.$row['id_news'].'&borrar='.$row['id_news'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a></td></tr>';
        }

        echo '</table>';
    }

    echo "<table width='100%'>";
    echo "<tr><td align='right'>";
    echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/news&form_add=1'>";
    echo "<input type='submit' class='sub next' name='form_add' value='".__('Add')."'>";
    echo '</form></table>';
}

/*
 * We must add javascript here. Otherwise, the date picker won't
 * work if the date is not correct because php is returning.
 */
ui_include_time_picker();

ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

// Include tiny for wysiwyg editor
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');

?>
<script language="javascript" type="text/javascript">

    $(document).ready (function () {
        $("#text-expire_time").timepicker({
                showSecond: true,
                timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
                timeOnlyTitle: '<?php echo __('Choose time'); ?>',
                timeText: '<?php echo __('Time'); ?>',
                hourText: '<?php echo __('Hour'); ?>',
                minuteText: '<?php echo __('Minute'); ?>',
                secondText: '<?php echo __('Second'); ?>',
                currentText: '<?php echo __('Now'); ?>',
                closeText: '<?php echo __('Close'); ?>'});
        
        $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
        
        $("#text-expire_date").datepicker({
            dateFormat: "<?php echo DATE_FORMAT_JS; ?>",
            changeMonth: true,
            changeYear: true,
            showAnim: "slideDown"});
            
            
        tinyMCE.init({
            mode : "exact",
            elements: "textarea_text",
            theme : "advanced",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_buttons1 : "bold,italic, |, image, link, |, cut, copy, paste, |, undo, redo, |, forecolor, |, fontsizeselect, |, justifyleft, justifycenter, justifyright, | ,code",
            theme_advanced_buttons2 : "",
            theme_advanced_buttons3 : "",
            convert_urls : false,
            theme_advanced_statusbar_location : "none"
        });
        
        $("#checkbox-expire").click(function() {
            check_expire();
        });
        
    });
            
    check_expire();

    function check_expire() {
        if ($("#checkbox-expire").is(":checked")) {
            $('#news-0-4').css('visibility', '');
        }
        else {
            $('#news-0-4').css('visibility', 'hidden');
        }
    }

</script>
