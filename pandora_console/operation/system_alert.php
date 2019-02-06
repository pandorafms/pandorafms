<?php

// TODO: Poner esto resizable y dragable: http://jqueryui.com/demos/dialog
if (check_login()) {
    echo "
<div class='modalheader'>
<span class='modalheadertext'>";

    if (!enterprise_installed()) {
        echo 'Community version';
    } else {
        echo 'Enterprise version';
    }

    echo "</span>
<img class='modalclosex cerrar' src='".$config['homeurl']."/images/icono_cerrar.png'>
</div>";

    echo "<div style='overflow-y:auto;height:400px;min-height:400px;max-height:400px;padding:20px;'>";
    echo get_pandora_error_for_header();
    echo '</div>';

    echo "<div class='modalokbutton cerrar' style='margin-top: 10px'>
<span class='modalokbuttontext close'>OK</span>
</div>";
}
?>

<script>

$(".cerrar").click(function(){
  $("#alert_messages")
      .css('opacity', 0)
      .hide();
  $( "#opacidad" )
      .css('opacity', 0)
      .remove();
});

</script>
