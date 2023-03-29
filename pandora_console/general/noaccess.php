<html>
<head>
    <link rel="stylesheet" href="<?php echo $config['homeurl']; ?>include/styles/pandora_minimal.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $config['homeurl']; ?>include/styles/js/jquery-ui.min.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $config['homeurl']; ?>include/styles/js/jquery-ui_custom.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $config['homeurl']; ?>include/styles/select2.min.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $config['homeurl']; ?>include/styles/pandora.css" type="text/css" />
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/jquery.current.js'></script>
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/jquery.pandora.js'></script>
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/jquery-ui.min.js'></script>
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/select2.min.js'></script>
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/pandora.js'></script>
    <script type='text/javascript' src='<?php echo $config['homeurl']; ?>include/javascript/pandora_ui.js'></script>

    <style>
    #alert_messages_na {
        z-index: 2;
        position: fixed;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        -webkit-transform: translate(-50%, -50%);
        width: 650px;
        height: 400px;
        background: white;
        background-repeat: no-repeat;
        justify-content: center;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 5px 10px 3px rgba(0, 0, 0, 0.4);
    }

    .modalheade {
        text-align: center;
        width: 100%;
        position: absolute;
        top: 0;
    }

    .modalheadertex {
        color: #000;
        line-height: 40px;
        font-size: 23pt;
        margin-bottom: 30px;
    }

    .modalclose {
        cursor: pointer;
        display: inline;
        float: right;
        margin-right: 10px;
        margin-top: 10px;
    }

    .modalconten {
        color: black;
        width: 300px;
        margin-left: 30px;
    }

    .modalcontenttex {
        text-align: left;
        color: black;
        font-size: 11pt;
        line-height: 13pt;
        margin-bottom: 30px;
    }

    .modalokbutto {
        cursor: pointer;
        text-align: center;
        display: inline-block;
        padding: 6px 45px;
        -moz-border-radius: 3px;
        -webkit-border-radius: 3px;
        border-radius: 3px;
        background-color: white;
        border: 1px solid #82b92e;
    }

    .modalokbuttontex {
        color: #82b92e;
        font-size: 13pt;
    }

    .modalgobutto {
        cursor: pointer;
        text-align: center;
        -moz-border-radius: 3px;
        -webkit-border-radius: 3px;
        border-radius: 3px;
        background-color: white;
        border: 1px solid #82b92e;
    }

    .modalgobuttontex {
        color: #82b92e;
        font-size: 10pt;
    }


    #opacidad {
        position: fixed;
        background: black;
        opacity: 0.6;
        z-index: -1;
        left: 0px;
        top: 0px;
        width: 100%;
        height: 100%;
    }
    /*
.textodialog{
    margin-left: 0px;
    color:#333;
    padding:20px;
    font-size:9pt;
}

.cargatextodialog{
    max-width:58.5%;
    width:58.5%;
    min-width:58.5%;
    float:left;
    margin-left: 0px;
    font-size:18pt;
    padding:20px;
    text-align:center;
}

.cargatextodialog p, .cargatextodialog b, .cargatextodialog a{
    font-size:18pt; 
}
*/
</style>
</head>
<body>

    <div id="alert_messages_na" style='background-image: url(<?php echo ui_get_full_url('images/imagen-no-acceso.jpg', false, false, false); ?>)'>

        <div class='modalheade'>
            <img class='modalclose cerrar' src='<?php echo $config['homeurl']; ?>images/input_cross.png'>
        </div>

        <div class='modalconten'>
            <div class='modalheadertex' style='font-size: 23pt'>
                <?php echo __('You do not have access to this page'); ?>
            </div>

            <div class='modalcontenttex'>
                <?php
                echo __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance.');
                echo '<br/> <br/>';
                echo __('Please know that all attempts to access this page are recorded in security logs of %s System Database', get_product_name());
                if ($config['logged'] == false) {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                }
                ?>
            </div>

            <button type="submit" class="cerrar submitButton" name="" id="" value="OK">
                <span id="" style="" class="font_11">OK</span>
            </button>
        </div>
    </div>
    <div id="opacidad"></div>
</body>
</html>

<script>
    $(".cerrar").click(function() {
        window.location = "<?php echo $config['homeurl']; ?>";
    });
</script>
