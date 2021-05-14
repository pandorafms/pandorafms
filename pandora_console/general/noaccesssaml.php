<html>
<head>
    
<style>

#alert_messages_na{
    z-index:2;
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    -webkit-transform: translate(-50%, -50%);   
    width:650px;
    height: 400px;
    background:white;
    background-image:url('images/imagen-no-acceso.jpg');
    background-repeat:no-repeat;
    justify-content: center;
    display: flex;
    flex-direction: column;
    box-shadow:4px 5px 10px 3px rgba(0, 0, 0, 0.4);
}

.modalheade{
    text-align:center;
    width:100%;
    position:absolute;
    top:0;
}
.modalheadertex{
    color:#000;
    line-height: 40px;
    font-size: 23pt;
    margin-bottom:30px;
}
.modalclose{
    cursor:pointer;
    display:inline;
    float:right;
    margin-right:10px;
    margin-top:10px;
}
.modalconten{
    color:black;
    width:300px;
    margin-left: 30px;
}
.modalcontenttex{
    text-align:left;
    color:black;
    font-size: 11pt;
    line-height:13pt;
    margin-bottom:30px;
}
.modalokbutto{
    cursor:pointer;
    text-align:center;
    display: inline-block;
    padding: 6px 45px;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    background-color:white;
    border: 1px solid #82b92e;
}
.modalokbuttontex{
    color:#82b92e;
    font-size:13pt;
}
.modalgobutto{
    cursor:pointer;
    text-align:center;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    background-color:white;
    border: 1px solid #82b92e;
}
.modalgobuttontex{
color:#82b92e;
font-size:10pt;
}


#opacidad{
    position:fixed;
    background:black;
    opacity:0.6;
    z-index:-1;
    left:0px;
    top:0px;
    width:100%;
    height:100%;
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
    
    <div id="alert_messages_na">
        
        <div class='modalheade'>
            <img class='modalclose cerrar' src='<?php echo $config['homeurl']; ?>images/input_cross.png'>  
        </div>

        <div class='modalconten'>
            <div class='modalheadertex'>
                <?php echo __("You don't have access to this page"); ?>
            </div>

            <div class='modalcontenttex'>
                <?php
                echo __('Access to this page is restricted to authorized users SAML only, please contact system administrator if you need assistance.');
                    echo '<br/> <br/>';
                    echo __('Please make sure you have SAML authentication properly configured. For more information the error to access this page are recorded in security logs of %s System Database', get_product_name());
                ?>
                      
            </div>

            <div class='modalokbutto cerrar'>
                <span class='modalokbuttontex'>OK</span>
            </div>
        </div>
    </div>
        
    <div id="opacidad"></div>
    
</body>
</html>

<script>

    $(".cerrar").click(function(){
    window.location=".";
    });

    $('div#page').css('background-color','#d3d3d3');

</script>
