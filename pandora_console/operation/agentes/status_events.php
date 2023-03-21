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
check_login();

if (!isset($id_agente)) {
    include 'general/noaccess.php';
    exit;
}

require_once 'include/functions_events.php';
ui_require_css_file('events');

html_print_div(
    [
        'class'   => 'agent_details_line',
        'content' => ui_toggle(
            '<div class=\'w100p\' id=\'event_list\'>'.html_print_image('images/spinner.gif', true).'</div>',
            '<span class="subsection_header_title">'.__('Latest events for this agent').'</span>',
            __('Latest events for this agent'),
            'latest_events_agent',
            false,
            true,
            '',
            'box-flat white-box-content no_border',
            'box-flat white_table_graph w100p'
        ),
    ],
);

?>
<script type="text/javascript">
    $(document).ready(function() {
        events_table(0);
    });

    function events_table(all_events_24h){
        var parameters = {};
        parameters["table_events"] = 1;
        parameters["id_agente"] = <?php echo $id_agente; ?>;
        parameters["page"] = "include/ajax/events";
        parameters["all_events_24h"] = all_events_24h;
        
        jQuery.ajax ({
            data: parameters,
            type: 'POST',
            url: "ajax.php",
            dataType: 'html',
            success: function (data) {
                $("#event_list").empty();
                $("#event_list").html(data);
                $('#checkbox-all_events_24h').on('change',function(){
                    if( $('#checkbox-all_events_24h').is(":checked") ){
                        $('#checkbox-all_events_24h').val(1);
                    }
                    else{
                        $('#checkbox-all_events_24h').val(0);
                    }
                    all_events_24h = $('#checkbox-all_events_24h').val();
                    events_table(all_events_24h);
                });
            }
        });
    }
</script>
