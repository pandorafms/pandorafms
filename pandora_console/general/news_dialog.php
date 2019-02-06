<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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
echo '<div id="news_dialog" title="" style="display: none;">';

    echo '<div style="position:absolute; top:30px; left: 10px; text-align: left; right:0%; height:70px; min-width:560px; width: 95%; margin: 0 auto; border: 1px solid #FFF; line-height: 19px;">';
        echo '<span style="display: block; height: 260px; overflow: auto; text-align: justify; padding: 5px 15px 4px 10px; background: #ECECEC; border-radius: 4px;" id="new_text"></span>';
        echo '<span style="font-size: 12px; display: block; margin-top: 20px;" id="new_author"></span>';
        echo '<span style="font-size: 12px; display: block; font-style: italic;" id="new_timestamp"></span>';
    echo '</div>';

    echo '<div style="position:absolute; margin: 0 auto; top: 340px; right: 10px; width: 570px">';
        echo '<div style="float: right; width: 20%;">';
        html_print_submit_button('Ok', 'hide-news-help', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');
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
        
        $("#submit-hide-news-help").click (function () {
            $("#news_dialog" ).dialog('close');
            inew++;
            show_new ();
        });
        
        show_new ();
    }
});

/* ]]> */
</script>
