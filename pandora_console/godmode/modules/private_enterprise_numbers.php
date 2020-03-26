<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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
        'Trying to access Wizard Setup'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/class/ConfigPEN.class.php';

try {
    $configPEN = new ConfigPEN();
    // Run the page.
    $configPEN->run();
} catch (Exception $ex) {
    ui_print_error_message(__('Something went wrong. Please, take a look in the Pandora FMS log'));
    echo '[PEN Configuration]'.$ex->getMessage();
}
?>
<script>

    function SendPENsAjax(action, id, number, manufacturer, description){
        $.ajax({
            async: true,
            type: "POST",
            url: $("#hidden-ajax_file").val(),
            data: {
                page: "include/ajax/wizardSetup.ajax",
                action: action,
                pen_id: id,
                pen_number: number,
                pen_manufacturer: manufacturer,
                pen_description: description,
            },
            success: function(d) {
                $('#main_table_area').html(d);
            },
            error: function(d) {
                alert('Failed trying update database -> '+String(data)); // True, must change the message
            }
        });

    }

    function addNewPEN(){
        var action = 'add';
        var pen_id = '2'; // Temporary solution
        var success = true;
        var pen_number = $('#text-pen_number');
        var pen_number_val = pen_number.val();
        var pen_manufacturer = $('#text-pen_manufacturer');
        var pen_manufacturer_val = pen_manufacturer.val();
        var pen_description = $('#text-pen_description');
        var pen_description_val = pen_description.val();
        // Test if the data is correct.
        if (pen_number_val == '' || isNaN(pen_number_val)) {
            success = false;
            pen_number.css('border','1px solid red');
        } 
        if (pen_manufacturer_val == '') {
            success = false;
            pen_manufacturer.css('border','1px solid red');
        } 
        if (pen_description_val == '') {
            pen_description.css('border','1px solid red');
        } 
        // Number and Manuacturer are enought for save data.
        if (success) {
            SendPENsAjax(action, pen_id, pen_number_val, pen_manufacturer_val, pen_description_val);
        }
    }

    function modifyPENLine(e){
        var action = 'update';
        var pen_id = e.target.value;
        var changed = false;
        // Enable the label for be editable.
        $("span[id$='_"+pen_id+"']").each(function(){
            let thisElement = $(this);
            if (thisElement.attr('contenteditable') === 'false') {
                thisElement.attr('contenteditable', 'true');
                thisElement.css('border', '1px solid #e2e2e2').css('background-color','ghostwhite');
                $('#'+e.target.id).attr('src','images/file.png');
            } else {
                thisElement.attr('contenteditable', 'false');
                thisElement.css('border', '0').css('background-color','transparent');
                changed = true;
                $('#'+e.target.id).attr('src','images/edit.png')
            }
        });
        // If select the red pill.
        if (changed === true) {
            let pen_number = $('#pen_number_'+pen_id).html();
            let pen_manufacturer = $('#pen_manufacturer_'+pen_id).html();
            let pen_description = $('#pen_description_'+pen_id).html();
            // Modify the data. // Temporary solution
            SendPENsAjax(action, '2', pen_number, pen_manufacturer, pen_description);
        }

    }

    function deletePEN(e){
        var action = 'delete';
        var pen_id = e.target.value;
        //Is not necessary pass more data.
        SendPENsAjax(action, '2', pen_id, '', '');
    }
</script>
