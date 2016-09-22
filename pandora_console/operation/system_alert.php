<?php

// TODO: Poner esto resizable y dragable: http://jqueryui.com/demos/dialog

echo "
<div class='modalheader'>
<span class='modalheadertext'>Community version</span>
<img class='modalclosex cerrar' src='".$config['homeurl']."/images/icono_cerrar.png'>
</div>".get_pandora_error_for_header()."

<div class='modalokbutton cerrar'>
<span class='modalokbuttontext close'>OK</span>
</div>";

?>

<script>

$(".cerrar").click(function(){
  $("#alert_messages").hide();
  $( "#opacidad" ).remove();
});

</script>
