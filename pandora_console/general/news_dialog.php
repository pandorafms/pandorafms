<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package General
 */

global $config;

$options = [];
$options['id_user'] = $config['id_user'];
$options['modal'] = true;
$news = get_news($options);

// Clean subject entities
foreach ($news as $k => $v) {
    $news[$k]['text'] = io_safe_output($v['text']);
    $news[$k]['subject'] = io_safe_output($v['subject']);
}

if (!empty($news)) {
    $options = [];
    $options['id'] = 'news_json';
    $options['hidden'] = 1;
    $options['content'] = base64_encode(json_encode($news));
    html_print_div($options);
}

// Prints news dialog template
echo '<div id="news_dialog" class="invisible">';
    echo '<div class="parent_new_dialog_tmplt">';
        echo '<span id="new_text"></span>';
        echo '<span id="new_author"></span>';
        echo '<span id="new_timestamp"></span>';
    echo '</div>';

    echo '<div id="div_btn_new_dialog">';
        echo '<div class="float-right w20p">';
        html_print_submit_button(
            'Ok',
            'hide-news-help',
            false,
            [
                'mode' => 'ui-widget ok mini',
                'icon' => 'wand',
            ]
        );
        echo '</div>';
        echo '</div>';

        echo '</div>';

        ui_require_javascript_file('encode_decode_base64');
        ?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {
    if (typeof($('#news_json').html()) != "undefined") {
        
        var news_raw = Base64.decode($('#news_json').html());
        var news = JSON.parse(news_raw);
        var inew = 0;
        
        function show_new () {
            if (news[inew] != undefined) {
                $('#new_text').html(news[inew].text);
                $('#new_timestamp').html(news[inew].timestamp);
                $('#new_author').html(news[inew].author);
                
                $("#news_dialog").dialog({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    closeOnEscape: false,
                    height: 450,
                    width: 630,
                    title: news[inew].subject,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    }
                });
                    
                $('.ui-dialog-titlebar-close').hide();
            }
        }
        
        $("#button-hide-news-help").click (function () {
            $("#news_dialog" ).dialog('close');
            inew++;
            show_new();
        });
        
        show_new();
    }
});

/* ]]> */
</script>
