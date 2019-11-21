<html>
<head>
    
<style>

#alert_messages_na{
    -moz-border-bottom-right-radius: 5px;
    -webkit-border-bottom-left-radius: 5px;
    border-bottom-right-radius: 5px;
    border-bottom-left-radius: 5px;
    z-index:2;
    position:fixed;
    width:700px;
    background:white;
    left:50%;
    top:20%;
    margin-left:-350px;

}

.modalheade{
    text-align:center;
    width:100%;
    height:37px;
    left:0px;
    background-color:#82b92e;
}
.modalheadertex{
    color:white;
    position:relative;
    font-family:Nunito;
    font-size:13pt;
    top:8px;
}

.modalconten{
    color:black;
    background:white;
}
.modalcontentim{
    float:left;
    margin-left:30px;
    margin-top:30px;
    margin-bottom:30px;
}
.modalcontenttex{
    float:left;
    text-align:justify;
    color:black;
    font-size: 9.5pt;
    line-height:13pt;
    margin-top:40px;
    width:430px;
    margin-left:30px;
}
.modalwikibutto{
    cursor:pointer;
    text-align:center;
    margin-right:45px;
    float:right;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    margin-bottom:30px;
    border-radius: 3px;
    width:170px;
    height:30px;
    border: 1px solid #82b92e;
    margin-top:8%;
    background-color:#82b92e;
}
.modalwikibuttontex{
    color:#ffffff;
    font-family:Nunito;
    font-size:10pt;
    position:relative;
    top:6px;
}

#opacity{
background:black;opacity:0.1;left:0px;top:0px;width:100%;height:100%;
}

</style>
</head>
<body>
<div id="alert_messages_na">
    
    <div class='modalheade'>
        <span class='modalheadertex'>
            <?php echo __('Database error'); ?>
        </span>
    </div>

    <div class='modalconten'>
        <img class='modalcontentim' src='<?php echo $config['homeurl']; ?>/images/mysqlerr.png'>
        <div class='modalcontenttex'>
            <?php
            echo __('Failure to connect to Database server, please check the configuration file config.php or contact system administrator if you need assistance.');
            ?>
        </div>
    </div>
    <?php
    $custom_conf_enabled = false;
    foreach ($config as $key => $value) {
        if (preg_match('/._alt/i', $key)) {
            $custom_conf_enabled = true;
            break;
        }
    }

    if (!$custom_conf_enabled || isset($config['custom_docs_url_alt'])) {
        if (isset($config['custom_docs_url_alt'])) {
            $docs_url = $config['custom_docs_url_alt'];
        } else {
            $docs_url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Configuration';
        }

        echo '
                <a href="'.ui_get_full_external_url($docs_url).'" target="_blank">
            <div class="modalwikibutto cerrar">
                <span class="modalwikibuttontex">'.__('Documentation').'
            </span>
            </div>
            </a>
            ';
    }

    ?>
      
    
    </a>
</div>
    
<div id="opacity"></div>
    
</body>
</html>