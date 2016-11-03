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
	width:650px;
	background:white;
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
.modalclose{
	cursor:pointer;
	display:inline;
	float:right;
	margin-right:10px;
	margin-top:10px;
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
	margin-top:30px;
	width:430px;
	margin-left:30px;
}
.modalokbutto{
	cursor:pointer;
	text-align:center;
	margin-right:45px;
	float:right;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	margin-bottom:30px;
	border-radius: 3px;width:90px;height:30px;background-color:white;border: 1px solid #82b92e;
}
.modalokbuttontex{
	color:#82b92e;font-family:Nunito;font-size:10pt;position:relative;top:6px;
}
.modalgobutto{
	cursor:pointer;text-align:center;margin-right:15px;margin-bottom:30px;float:right;-moz-border-radius: 3px;
	-webkit-border-radius: 3px;border-radius: 3px;width:240px;height:30px;background-color:white;border: 1px solid #82b92e;
}
.modalgobuttontex{
color:#82b92e;font-family:Nunito;font-size:10pt;position:relative;top:6px;	
}


#opacida{
position:absolute;background:black;opacity:0.6;z-index:-1;left:0px;top:0px;width:100%;height:100%;
}

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
</style>
</head>
<body>
	
<div id="alert_messages_na">
	
	<div class='modalheade'>
	<span class='modalheadertex'>
	You don't have access to this page
	</span>
	<img class='modalclose cerrar' src='<?php echo $config['homeurl'];?>images/icono_cerrar.png'>
	
	</div>

	<div class='modalconten'>
	<img class='modalcontentim' src='<?php echo $config['homeurl'];?>images/access_denied.png'>
	<div class='modalcontenttex'>
		Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br/> <br/>
		Please know that all attempts to access this page are recorded in security logs of Pandora System Database	
	
	</div>


</div>

<div class='modalokbutto cerrar'>
<span class='modalokbuttontex'>OK</span>
</div>

</div>
	
	<div id="opacida" style="position:fixed;background:black;opacity:0.6;z-index:-1;left:0px;top:0px;width:100%;height:100%;"></div>
	
</body>
</html>

<script>

$(".cerrar").click(function(){
  window.location=".";
});

</script>