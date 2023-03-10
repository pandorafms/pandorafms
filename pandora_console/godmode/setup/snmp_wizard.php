<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Link Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once 'include/functions_snmp.php';

// Header
ui_print_page_header(__('SNMP Wizard'), '', false, '', true);

$table = new stdClass();
$table->id = 'snmp';
$table->width = '100%';
$table->class = 'databox data';
$table->cellpadding = 0;
$table->cellspacing = 0;

$table->head = [];
$table->head['description'] = __('Description');
$table->head['oid'] = __('OID');
$table->head['post_process'] = __('Post process');
$table->head['op'] = __('OP');

$table->headstyle = [];
$table->headstyle['description'] = 'text-align: left;';
$table->headstyle['oid'] = 'text-align: left;';
$table->headstyle['post_process'] = 'text-align: right;';
$table->headstyle['op'] = 'text-align: center;';

$table->align = [];
$table->align['description'] = 'left';
$table->align['oid'] = 'left';
$table->align['post_process'] = 'right';
$table->align['op'] = 'center';


$table->size = [];
$table->size['description'] = '40%';
$table->size['oid'] = '25%';
$table->size['post_process'] = '25%';
$table->size['op'] = '10%';

$oid_translations = snmp_get_translation_wizard();

$table->data = [];

foreach ($oid_translations as $oid => $data) {
    $row = [];

    $row['oid'] = $oid;
    $row['description'] = $data['description'];
    $row['post_process'] = $data['post_process'];

    if ($data['readonly']) {
        $row['op'] = '';
    } else {
        $row['op'] = cell_op($oid);
    }


    $table->data[$oid] = $row;
}


$table->data['template'] = [
    'oid'          => '',
    'description'  => '',
    'post_process' => '',
    'op'           => cell_op(),
];
$table->rowstyle['template'] = 'display: none;';

// Form editor
$table->data['editor'] = [
    'oid'          => html_print_input_text('oid_editor', '', '', 40, 255, true),
    'description'  => html_print_input_text('description_editor', '', '', 40, 255, true),
    'post_process' => html_print_input_text('post_process_editor', '', '', 20, 255, true),
    'op'           => '<img class="loading invisible" src="'.'images/spinner.gif'.'" />'.'<a class="button_save_snmp" href="javascript: save_translation();">'.html_print_image('images/save_mc.png', true, ['title' => __('Save'), 'class' => 'invert_filter']).'</a>'.'<a class="button_update_snmp invisible" href="javascript: update_snmp();">'.html_print_image('images/update.png', true, ['title' => __('Update'), 'class' => 'invert_filter']).'</a>'.'<a class="cancel_button_snmp invisible" href="javascript: cancel_snmp();">'.html_print_image('images/cancel.png', true, ['title' => __('Cancel')]).'</a>',
];



html_print_table($table);


function cell_op($oid='')
{
    return '<img class="loading invisible" src="'.'images/spinner.gif'.'" />'.'<a class="button_edit_snmp" href="javascript: edit_snmp(\''.$oid.'\');">'.html_print_image('images/cog.png', true, ['class' => 'invert_filter', 'title' => __('Edit')]).'</a>'.'<a class="delete_button_snmp" href="javascript: delete_snmp(\''.$oid.'\');">'.html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'invert_filter']).'</a>';
}


?>

<script type="text/javascript">
    function remove_snmp_editor(oid) {
        $("#snmp-" + oid + "-editor").remove();
    }
    
    function cancel_snmp(oid) {
        remove_snmp_editor(oid);
        $("#snmp-" + oid).show();
    }
    
    function update_snmp_row(oid, new_oid, description, post_process) {
        add_snmp_row("#snmp-" + oid, new_oid, description, post_process);
        remove_snmp_editor(oid);
        $("#snmp-" + oid).remove();
    }
    
    function update_snmp(oid) {
        var new_oid = $("#snmp-" + oid + "-editor input[name='oid_editor']").val();
        var description = $("#snmp-" + oid + "-editor input[name='description_editor']").val();
        var post_process = $("#snmp-" + oid + "-editor input[name='post_process_editor']").val();
        
        params = {};
        params['page'] = "include/ajax/snmp.ajax";
        params['update_snmp_translation'] = 1;
        params['oid'] = oid;
        params['new_oid'] = new_oid;
        params['description'] = description;
        params['post_process'] = post_process;
        
        $("#snmp-" + oid + " .loading").show();
        $("#snmp-" + oid + " .button_update_snmp").hide();
        
        jQuery.ajax ({
            data: params,
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            success: function (data) {
                if (!data['correct']) {
                    alert("<?php echo __('Unsucessful update the snmp translation'); ?>");
                }
                else {
                    update_snmp_row(oid, new_oid, description, post_process);
                }
            },
            error: function() {
                alert("<?php echo __('Unsucessful update the snmp translation.'); ?>");
                
                $("#snmp-" + oid + " .loading").hide();
                $("#snmp-" + oid + " .button_update_snmp").show();
            },
        });
    }
    
    function edit_snmp(oid) {
        var description = $("#snmp-" + oid + "-description").html();
        var post_process = $("#snmp-" + oid + "-post_process").html();
        
        var copy_editor = $("#snmp-editor").clone();
        
        $(copy_editor).attr('id', 'snmp-' + oid + "-editor");
        $(".button_save_snmp", copy_editor).hide();
        $(".cancel_button_snmp", copy_editor).show();
        $(".cancel_button_snmp", copy_editor).attr("href", "javascript: cancel_snmp('" + oid + "');");
        $(".button_update_snmp", copy_editor).show();
        $(".button_update_snmp", copy_editor).attr("href", "javascript: update_snmp('" + oid + "');");
        $("#snmp-editor-oid input", copy_editor).val(oid);
        $("#snmp-editor-description input", copy_editor).val(description);
        $("#snmp-editor-post_process input", copy_editor).val(post_process);
        $("#snmp-" + oid).hide();
        $("#snmp-" + oid).after(copy_editor);
    }
    
    function delete_snmp_row(oid) {
        $("#snmp-" + oid).remove();
    }
    
    function delete_snmp(oid) {
        params = {};
        params['page'] = "include/ajax/snmp.ajax";
        params['delete_snmp_translation'] = 1;
        params['oid'] = oid;
        
        $("#snmp-" + oid + " .loading").show();
        $("#snmp-" + oid + " .button_edit_snmp").hide();
        $("#snmp-" + oid + " .delete_button_snmp").hide();
        
        jQuery.ajax ({
            data: params,
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            success: function (data) {
                if (!data['correct']) {
                    alert("<?php echo __('Unsucessful delete the snmp translation'); ?>");
                }
                else {
                    delete_snmp_row(oid);
                }
            },
            error: function() {
                alert("<?php echo __('Unsucessful delete the snmp translation.'); ?>");
                
                $("#snmp-" + oid + " .loading").hide();
                $("#snmp-" + oid + " .button_edit_snmp").show();
                $("#snmp-" + oid + " .delete_button_snmp").show();
            },
        });
    }
    
    function add_snmp_row(position, oid, description, post_process) {
        var copy_template = $("#snmp-template").clone();
        
        $(copy_template).attr('id', 'snmp-' + oid);
        
        $("#snmp-template-oid", copy_template).html(oid);
        $("#snmp-template-oid", copy_template)
            .attr('id', $(copy_template).attr('id') + "-oid");
        
        $("#snmp-template-description", copy_template).html(description);
        $("#snmp-template-description", copy_template)
            .attr('id', $(copy_template).attr('id') + "-description");
        
        $("#snmp-template-post_process", copy_template).html(post_process);
        $("#snmp-template-post_process", copy_template)
            .attr('id', $(copy_template).attr('id') + "-post_process");
        
        
        $(".button_edit_snmp", copy_template)
            .attr("href", "javascript: edit_snmp('" + oid + "');");
        $(".delete_button_snmp", copy_template)
            .attr("href", "javascript: delete_snmp('" + oid + "');");
        $("#snmp-template-op", copy_template)
            .attr('id', $(copy_template).attr('id') + "-op");
        
        $(copy_template).show();
        
        $(position).before(copy_template);
    }
    
    function save_translation() {
        var oid = $("input[name='oid_editor']").val();
        var description = $("input[name='description_editor']").val();
        var post_process = $("input[name='post_process_editor']").val();
        
        $(".button_save_snmp").hide();
        $(".loading").show();
        
        params = {};
        params['page'] = "include/ajax/snmp.ajax";
        params['save_snmp_translation'] = 1;
        params['oid'] = oid;
        params['description'] = description;
        params['post_process'] = post_process;
        
        jQuery.ajax ({
            data: params,
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            success: function (data) {
                if (!data['correct']) {
                    alert("<?php echo __('Unsucessful save the snmp translation'); ?>");
                }
                else {
                    add_snmp_row("#snmp-editor", oid, description, post_process);
                }
                $(".button_save_snmp").show();
                $(".loading").hide();
            },
            error: function() {
                alert("<?php echo __('Unsucessful save the snmp translation.'); ?>");
                
                $(".button_save_snmp").show();
                $(".loading").hide();
            },
        });
    }
</script>