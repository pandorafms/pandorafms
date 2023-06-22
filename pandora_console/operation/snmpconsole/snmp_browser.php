<?php
/**
 * SMNP Browser view
 *
 * @category   Monitoring
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
require_once $config['homedir'].'/include/functions_snmp_browser.php';
ui_require_javascript_file('pandora_snmp_browser');
ui_require_jquery_file('pandora.controls');

// Check login and ACLs.
check_login();
if (!check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access SNMP Console'
    );
    include 'general/noaccess.php';
    exit();
}



$url = 'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_browser&pure='.$config['pure'];
if ($config['pure']) {
    // Windowed.
    $link['text'] = '<a target="_top" href="'.$url.'&pure=0&refr=30">';
    $link['text'] .= html_print_image(
        'images/exit_fullscreen@svg.svg',
        true,
        [
            'title' => __('Normal screen'),
            'class' => 'main_menu_icon invert_filter',
        ]
    );
    $link['text'] .= '</a>';
} else {
    // Fullscreen.
    $link['text'] = '<a target="_top" href="'.$url.'&pure=1&refr=0">';
    $link['text'] .= html_print_image(
        'images/fullscreen@svg.svg',
        true,
        [
            'title' => __('Full screen'),
            'class' => 'main_menu_icon invert_filter',
        ]
    );
    $link['text'] .= '</a>';
}

// Control from managent polices.
$type = get_parameter('type', false);
$page = get_parameter('page', false);
if (empty($page) && $type !== 'networkserver') {
    // Header.
    ui_print_standard_header(
        __('SNMP Browser'),
        'images/op_snmp.png',
        false,
        'snmp_browser_view',
        false,
        [$link],
        [
            [
                'link'  => '',
                'label' => __('Monitoring'),
            ],
            [
                'link'  => '',
                'label' => __('SNMP'),
            ],
        ]
    );

    // SNMP tree container.
    if (!isset($_GET['tab'])) {
        snmp_browser_print_container(false, '100%', '60%', '', true, true);
    }
}

// Div for modal.
echo '<div id="modal" style="display:none"></div>';
// Div for loading modal.
echo '<div id="loading_modal" style="display:none"></div>';


?>

<script type="text/javascript">

// Show button for add module to agent on oid detail.
$(document).on('DOMSubtreeModified', "#snmp_create_module", function() {
    $('#button-create_module_agent_single').show();

});

$(document).on("click", 'div#snmp_create_buttons > button', function (event) {
        var source_button = this.name;
        var target = "";

        switch (source_button) {
            case  "create_modules_agent":
                target = "agent";
            break;

            case "create_modules_policy":
                target = "policy";
            break;

            case "create_modules_network_component":
                target = "network_component";
            break;

            default:
                target = "network_component";
            break;
    }

    if (target === "network_component") {
        waiting_modal();
        snmp_browser_create_modules("network_component", false);
    } else {
        snmp_browser_show_add_module_massive(target);
    }
});
        
/**
 * 
 * Show select to add massive snmp modules.
 * 
 * @param {string} target The target where module will be created (agent/policy).
 */
function snmp_browser_show_add_module_massive(module_target = 'agent') {
    

    var module_target = module_target;

    var title = '<?php echo __('Add modules'); ?>';
    var snmp_extradata = snmp_browser_create_modules(module_target);
    snmp_extradata.push({name:"target_port", value:$("#target_port").val()});
    // Load dinamically modal form.
    load_modal({
        form: 'snmp_browser_add_module_form',
        extradata: [
            {
                name: 'module_target',
                value: module_target,
            },
        ],
        ajax_callback: snmp_show_result_message,
        cleanup:  cleanupDOM,
        onsubmitClose: true,
        form: 'create_module_massive',
        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
        modal: {
            title: title,
            cancel: '<?php echo __('Cancel'); ?>',
            ok: '<?php echo __('Add modules'); ?>',
        },
        onload: function (data) {
            // Add policy button handler.
            if (module_target == 'policy') {
                $('#button-snmp_browser_create_policy').click(function () {
                    snmp_browser_show_add_policy();
                });
            }
        },
        onshow: {
            extradata: snmp_extradata,
            page: 'include/ajax/snmp_browser.ajax',
            method: 'snmp_browser_print_create_module_massive',
        },
        onsubmit:   {
            preaction: modal_preaction,
            page: 'include/ajax/snmp_browser.ajax',
            method: 'snmp_browser_create_modules',
        },
    });
}

/**
 * Select all items in select box.
 *
 * @return void
 */
function modal_preaction() {

    // Select all in select box.
    $('#id_item2>option').prop('selected', true);

    // Load adding modules modal.
    waiting_modal();

}

function waiting_modal(stop = false) {


    var waiting_modal = 
    '<div id="loading_in_progress_dialog" class="center">' +
    '<?php echo __('Adding modules in progress'); ?> <br /><br />' +
    '<?php echo html_print_image('images/spinner.gif', true); ?> </div>';

    $('#loading_modal').html(waiting_modal);

    $('#loading_modal')
    .dialog({
        title: '<?php echo __('Action in progress'); ?>',
        resizable: true,
        draggable: true,
        modal: true,
        close: false,
        height: 100,
        width: 200,
        overlay: {
            opacity: 0.5,
            background: "black"
        }
    })
    .show();

    if(stop) {
        $('#loading_modal').dialog("close");
    }
}


/**
 * Show error/success message.
 * 
 * @param mixed data   Fail modules.
 */
function snmp_show_result_message(data) {

    // Stop waiting modal.
    waiting_modal(stop);

    var dato = JSON.parse(data);
    if (dato.length !== 0) {
    $("#error_text").text("");

    if (dato[0] === -1) {
        $("#dialog_no_agents_selected").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            height: 300,
            width: 500,
            close: function(e, ui) {
            $("button[name=create_modules_network_component]").removeClass("sub spinn");
            $("button[name=create_modules_network_component]").addClass("sub add");
            },
            overlay: {
            opacity: 0.5,
            background: "black"
            }
        });
    } else {
        $("#error_text").text("");

        dato.forEach(function (valor, indice, array) {
            $("#error_text").append("<br/>" + valor);
        });
        $("#dialog_error").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            height: 300,
            width: 500,
            overlay: {
            opacity: 0.5,
            background: "black"
            }
        });
    }

    } else {
    $("#dialog_success").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        height: 250,
        width: 500,
        overlay: {
        opacity: 0.5,
        background: "black"
        }
    });
    }
}
/**
 * When invoking modals from JS, some DOM id could be repeated.
 * This method cleans DOM to avoid duplicated IDs.
*/
function cleanupDOM() {
    $("#modal").empty();
}

/**
 * Controls internal add module massive dialog behaviour.
 *
 * @param string $target
 * @return void
 */
function add_module_massive_controller(target = 'agent')
{  
    // Load items from groups.
    filter_by_group($("#group").val(), '', target);

    // Change group listener.
    $("select[name='group']").change(function(){
        filter_by_group($(this).val(), '', target);
    });

    // Item filter search listener
    $("#text-filter").keyup (function () {
        
        $('#loading_filter').show();    
        refresh_item($(this).val(), items_out_keys, items_out, $("#id_item"));
    });

    // Select all Items.
    $("#image-select_all_available").click(function (event) {
        event.preventDefault();
        $('#id_item>option').prop('selected', true);
    })

    $("#image-select_all_apply").click(function (event) {
        event.preventDefault();
        $('#id_item2>option').prop('selected', true);
    });

    $("#checkbox-select_all_right").change(function () {
        if ($("#checkbox-select_all_right").prop('checked') == true) {
            $('#id_item2 option').map(function() {
                $(this).prop('selected', true);
            });
        } else {
            $('#id_item2 option').map(function() {
                $(this).prop('selected', false);
            });
        }

        return false;
    });

    $("#id_item").click(function(e) {
        if ($("#checkbox-select_all_left").prop('checked') == true) {
            $("#checkbox-select_all_left").prop('checked', false);
        }
    });

    $("#id_item2").click(function(e) {
        if ($("#checkbox-select_all_right").prop('checked') == true) {
            $("#checkbox-select_all_right").prop('checked', false);
        }
    });

    $(".p-switch").css('display', 'table');

    // Select items.
    $("#right").click (function () {
        jQuery.each($("select[name='id_item[]'] option:selected"), function (key, value) {
            
            item_name = $(value).html();
            if (item_name != <?php echo "'".__('None')."'"; ?>){
                id_item = $(value).attr('value');
                
                //Remove the none value
                $("#id_item2").find("option[value='']").remove();
                
                $("select[name='id_item2[]']").append($("<option>").val(id_item).html('<i>' + item_name + '</i>'));
                $("#id_item").find("option[value='" + id_item + "']").remove();
            }
        });
    });

    $("#left").click(function() {
        jQuery.each($("select[name='id_item2[]'] option:selected"), function (key, value) {
            item_name = $(value).html();
            if (item_name != <?php echo "'".__('None')."'"; ?>){
                id_item = $(value).attr('value');
                $("select[name='id_item[]']").append($("<option>").val(id_item).html('<i>' + item_name + '</i>'));
                $("#id_item2").find("option[value='" + id_item + "']").remove();
            }
            
            //If empty the selectbox
            if ($("#id_item2 option").length == 0) {
                $("select[name='id_item2[]']")
                    .append($("<option>").val("")
                    .html("<?php echo __('None'); ?>"));
            }
        });
    });
}

/**
    * Get agents by group dinamically..
    * 
    * @param {number} id_group
    * @param {string} id_select
    * @param {string} module_target
    */
function filter_by_group(id_group, id_select, module_target) {
    $('#loading_group').show();
    

    var params = {};
    
    $('#id_item' + id_select).empty ();
    search = $("#text-filter" + id_select).val();

    params["id_group"] = id_group;
    params["search"]= search;

    switch (module_target) {
        case 'agent':
            page = "godmode/groups/group_list";
            method = 'get_group_agents';
            break;
        case 'policy':
            page = "enterprise/include/ajax/policy.ajax";
            method = 'get_policies_by_group';
            break;

        default:
            page = '';
            method = '';
            break;
    }

    params['page'] = page;
    if(method != '') {
        params[method] = 1;
    }
    
    jQuery.ajax ({
        data: params,
        type: "POST",
        url: "ajax.php",
        dataType: "json",
        success: function (data, status) {
            
            var group_items = new Array();
            var group_items_keys = new Array();

            jQuery.each (data, function (id, value) {
                
                group_items.push(value);
                group_items_keys.push(id);
            });
            
            if(id_select == '') {
                items_out_keys = group_items_keys; 
                items_out = group_items; 
            }
            else {
                items_in_keys = group_items_keys; 
                items_in = group_agents; 
            }
            
            refresh_item($("#text-filter"+id_select).attr('value'), items_out_keys, items_out, $("#id_item"+id_select));        
        },
    });
}


function refresh_item(start_search, keys, values, select) {
    var n = 0;
    var i = 0;
    select.empty();
    
    $('#id_item2 option').each(function(){
        
        var out_agent = $(this).val();
        
        if (out_agent) {
            
            keys.forEach(function(it) {
                
                var it_data = it;

                if (it_data == out_agent) {
                    
                    var index = keys.indexOf(it);
                    
                    // Remove from array!
                    values.splice(index, 1);
                    keys.splice(index, 1);
                }
                
            });
            
        }
        
    });
    
    values.forEach(function(item) {
        var re = new RegExp(start_search,"gi");
        match = item.match(re);
        
        if (match != null) {
            select.append ($("<option></option>").attr("value", keys[n]).html(values[n]));
            i++;
        }
        n++;
    });
    if (i == 0) {
        $(select).empty ();
        $(select).append ($("<option></option>").attr ("value", 0).html (' <?php echo __('None'); ?> '));
    }
    
    $('.loading_div').hide();
}

function snmp_browser_show_add_policy() {
    
    var title = '<?php __('Create new policy'); ?>'; 

    load_modal({
        target: $('#policy_modal'),
        form: 'snmp_browser_add_policy_form',
        ajax_callback: snmp_browser_add_policy_callback,
        idMsgCallback: 'snmp_result_msg',
        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
        modal: {
            title: title,
            cancel: '<?php echo __('Cancel'); ?>',
            ok: '<?php echo __('Create policy'); ?>',
        },
        onshow: {
            page: 'include/ajax/snmp_browser.ajax',
            method: 'snmp_browser_print_create_policy',
            width: 550,

        },
        onsubmit: {
            page: 'include/ajax/snmp_browser.ajax',
            method: 'snmp_browser_create_policy',
        },
        onload: function () {
            $('#id_group').pandoraSelectGroupIcon();
        }
    });
}

function snmp_browser_add_policy_callback(data, idMsg) {

    data = JSON.parse(data);

    //Show message
    var title = data.title[data.error];
    var text = data.text[data.error];
    var failed = !data.error;

    $("#" + idMsg).empty();
    $("#" + idMsg).html(text);
    $("#" + idMsg).dialog({
    width: 450,
    position: {
      my: "center",
      at: "center",
      of: window,
      collision: "fit"
    },
    title: title,
    buttons: [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
        text: "OK",
        click: function(e) {
          if (!failed) {
            filter_by_group($("#group").val(), '', 'policy');
          }
          
            $(this).dialog("close");
            $('#policy_modal').dialog("close");
        }
      }
    ]
  });
}

/**
 * Shows dialog to select agent to add module.
 *
 */
function show_add_module() {

    var id_agent = 0;
    var id_module = 0;
    var id_agent_module = $("#id_agent_module").val();

    if (id_agent_module) {
        // Get SNMP target.
        confirmDialog({
            title: '<?php echo __('Are you sure?'); ?>',
            message: '<?php echo __('Are you sure you want add module?'); ?> ',
            ok: '<?php echo __('OK'); ?>',
            cancel: '<?php echo __('Cancel'); ?>',
            onAccept: function() {

                // Get SNMP target.
                var snmp_target = {
                    target_ip : $('#text-target_ip').val(),
                    community : $('#text-community').val(),
                    snmp_version : $('#snmp_browser_version').val(),
                    snmp3_auth_user : $('#text-snmp3_browser_auth_user').val(),
                    snmp3_security_level : $('#snmp3_browser_security_level').val(),
                    snmp3_auth_method : $('#snmp3_browser_auth_method').val(),
                    snmp3_auth_pass : $('#password-snmp3_browser_auth_pass').val(),
                    snmp3_privacy_method : $('#snmp3_browser_privacy_method').val(),
                    snmp3_privacy_pass : $('#password-snmp3_browser_privacy_pass').val(),
                    tcp_port : $('#target_port').val(),
                };

                // Append values to form.
                var input = "";

                $.each( snmp_target, function( key, val ) {
                    input = $("<input>")
                    .attr("type", "hidden")
                    .attr("name", key).val(val);

                    $("#snmp_create_module").append(input);
                });

                //Submit form to agent module url.
                $("#snmp_create_module").attr(
                    "action",
                    "index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente="
                    +id_agent_module+
                    "&tab=module&edit_module=1"
                );

                $('#snmp_create_module').submit();

            },
            onDeny: function () {
                $("#dialog_create_module").dialog("close");
                return false;
            }
        });

    } else {

        $("#dialog_create_module").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            width: '300',
            height:'auto',
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            buttons:
            [
                {
                    class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-cancel",
                    text: '<?php echo __('Cancel'); ?>',
                    click: function() {
                        $(this).dialog("close");
                    }
                },
                
                {
                    class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                    text: '<?php echo __('Create module'); ?>',
                    click: function(e) {
                        
                                confirmDialog({
                                    title: '<?php echo __('Are you sure?'); ?>',
                                    message: '<?php echo __('Are you sure you want add module?'); ?> ',
                                    ok: '<?php echo __('OK'); ?>',
                                    cancel: '<?php echo __('Cancel'); ?>',
                                    onAccept: function() {
                                        
                                        // Get id agent and add it to form.
                                        id_agent = $('#hidden-id_agent').val();
                                        
                                        // Get SNMP target.
                                        var snmp_target = {

                                            target_ip : $('#text-target_ip').val(),
                                            community : $('#text-community').val(),
                                            snmp_version : $('#snmp_browser_version').val(),
                                            snmp3_auth_user : $('#text-snmp3_browser_auth_user').val(),
                                            snmp3_security_level : $('#snmp3_browser_security_level').val(),
                                            snmp3_auth_method : $('#snmp3_browser_auth_method').val(),
                                            snmp3_auth_pass : $('#password-snmp3_browser_auth_pass').val(),
                                            snmp3_privacy_method : $('#snmp3_browser_privacy_method').val(),
                                            snmp3_privacy_pass : $('#password-snmp3_browser_privacy_pass').val(),
                                            tcp_port : $('#target_port').val(),
                                        };

                                        // Append values to form.
                                        var input = "";

                                        $.each( snmp_target, function( key, val ) {
                                            input = $("<input>")
                                            .attr("type", "hidden")
                                            .attr("name", key).val(val);
                                            
                                            $("#snmp_create_module").append(input);
                                        });

                                        //Submit form to agent module url.
                                        $("#snmp_create_module").attr(
                                            "action",
                                            "index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente="
                                            +id_agent+
                                            "&tab=module&edit_module=1"
                                        );

                                        $('#snmp_create_module').submit();
                                    },
                                    onDeny: function () {
                                        $("#dialog_create_module").dialog("close");
                                        return false;
                                    }
                                });
                        }
                }
            ],
        });
    }
}

function use_oid() {
    $("#text-snmp_oid").val($("#hidden-snmp_oid").val());

    $("#snmp_data").empty();

    $("#snmp_data").css("display", "none");
    $(".forced_title_layer").css("display", "none");

    $("#snmp_browser_container").dialog("close");
}

</script>
