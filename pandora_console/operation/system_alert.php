<?php

// TODO: Poner esto resizable y dragable: http://jqueryui.com/demos/dialog
if(check_login()){

echo "
<div class='modalheader'>
<span class='modalheadertext'>";

if (!enterprise_installed()) {
	echo "Community version";
}
else {
	echo "Enterprise version";
}

echo "</span>
<img class='modalclosex cerrar' src='".$config['homeurl']."/images/icono_cerrar.png'>
</div>";

echo "<div style='overflow-y:scroll;height:550px;'>";
echo get_pandora_error_for_header();
echo "</div>";

echo "<div class='modalokbutton cerrar'>
<span class='modalokbuttontext close'>OK</span>
</div>";

}
?>

<script>

$(".cerrar").click(function(){
  $("#alert_messages").dialog('close');
});

</script>
