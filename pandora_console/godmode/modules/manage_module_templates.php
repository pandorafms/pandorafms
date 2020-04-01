<?php
/**
 * Private Enterprise Number managemtn.
 *
 * @category   PEN management.
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */
// Begin.
global $config;

require_once $config['homedir'].'/include/class/ModuleTemplates.class.php';

// This page.
$ajaxPage = 'godmode/modules/manage_module_templates';

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $obj = new ModuleTemplates($ajaxPage);
} catch (Exception $e) {
    if (is_ajax()) {
        echo json_encode(['error' => '[ModuleTemplates]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[ModuleTemplates]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if (is_ajax()) {
    $method = get_parameter('method');

    if (method_exists($obj, $method) === true) {
        $obj->{$method}();
    } else {
        $obj->error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $obj->run();
}

/*
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
    // TESTING
    /*
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL); *
    // END
    global $config;

    /* hd($_POST);
    hd($_REQUEST); *
    check_login();

    if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Network Profile Management'
    );
    include 'general/noaccess.php';
    return;
    }

    require_once $config['homedir'].'/include/class/ModuleTemplates.class.php';
    $ajaxPage = ENTERPRISE_DIR.'/godmode/agentes/ModuleTemplates';
    // Control call flow.
    try {
    // User access and validation is being processed on class constructor.
    $moduleTemplates = new ModuleTemplates('');
    // Run.
    $moduleTemplates->run();
    // Get the id_np.
    $id_np = $moduleTemplates->getIdNp();
    $moduleTemplates->processData();
    if ($id_np === -1) {
        // List all Module Blocks.
        $moduleTemplates->moduleBlockList();
    } else {
        // Create new o update Template.
        $moduleTemplates->moduleTemplateForm();
    }
    } catch (Exception $e) {
    echo '[ModuleTemplates]'.$e->getMessage();
    // Stop this execution, but continue 'globally'.
    return;
    }

    ?>
    <script>

    function switchBlockControl(e){
        var switchId = e.target.id.split('_');
        var blockNumber = switchId[2];
        var switchNumber = switchId[3];
        var totalCount = 0;
        var markedCount = 0;

        $('[id*=checkbox-module_check_'+blockNumber+']').each(function(){
            if ($(this).prop('checked')) {
                markedCount++;
            }
            totalCount++;
        });

        if (totalCount == markedCount) {
            $('#checkbox-block_id_'+blockNumber).prop('checked', true);
            $('#checkbox-block_id_'+blockNumber).parent().removeClass('alpha50');
        } else if (markedCount == 0) {
            $('#checkbox-block_id_'+blockNumber).prop('checked', false);
            $('#checkbox-block_id_'+blockNumber).parent().removeClass('alpha50');
        } else {
            $('#checkbox-block_id_'+blockNumber).prop('checked', true);
            $('#checkbox-block_id_'+blockNumber).parent().addClass('alpha50');
        }
    }

    $(document).ready (function () {
        var listValidPens = $('#hidden-valid-pen').val();
        listValidPens = listValidPens.split(',');
        //Adding tagEditor for PEN management.
        $('#text-pen').tagEditor({
            beforeTagSave: function(field, editor, tags, tag, val) {
                if (listValidPens.indexOf(val) == -1) {
                    return false;
                }
            }
        });
        //Values for add.
        $('#add-modules-components').change(function(){
            var valores = ($('#add-modules-components').val()).join(',');
            $('#hidden-add-modules-components-values').val(valores);
        });

    });
</script>
