<?php

$tipo = $_GET['message'];

echo "
<div class='modalheader'>
<span class='modalheadertext'>
Community version</span>
<img class='modalclosex cerrar' src='".$config['homeurl']."images/icono_cerrar.png'>
</div>

<div class='modalcontent'>
<img class='modalcontentimg' src='".$config['homeurl']."images/icono_popup.png'>
<div class='modalcontenttext'>";

switch ($tipo) {
    case "module":
      echo __("The community version have not the ability to define your own library of local modules, or distribute it to remote agents. You need to do that changes individually on each agent, but it's possible by using external tools and space time and effort. Nor can distribute local plugins, and nor does it have access to the library of plugins enterprise to monitor applications such as Informix, Oracle, DB2, SQL Server, Exchange, WebSphere, Oracle Exadata, F5, JBoss, HyperV, VMWare, RHEV, to name a few. With the Enterprise version will have all this, and the ability to distribute and manage their own local modules to your systems, individually or through policies.
      <br><br><img style='width:105px' src='".$config['homeurl']."images/logo_oracle.png'><img style='width:105px' src='".$config['homeurl']."images/logo_citrix.png'><img style='width:105px' src='".$config['homeurl']."images/logo_sap.png'><img style='width:105px' src='".$config['homeurl']."images/logo_exchange.png'>");
      break;
    case "massive":
      echo __("You want to manage your monitoring homogeneously? Do you have many systems and is difficult to manage in a comprehensive manner? Would you like to deploy monitoring, alerts and even local plugins with a single click? Pandora FMS Enterprise Policies are exactly what you need, you'll save time, effort and dislikes. More information (link to pandorafms.com)");
      break;
    case "events":
      echo __("Pandora FMS Enterprise has event correlation. Through correlation you can generate alerts and / or new events based on logical rules on your realtime events. This allows you to automate the troubleshooting. If you know the value of working with events, the correlation will take you to a new level.");
      break;
    case "reporting":
      echo __("The reports of the Enterprise version are more powerful: it has wizards, you can schedule sending via email in PDF, and it has a template system to create reports quickly for each of your customers. It will even allow your customers generate their own reports from templates created by you. If reports are key to your business, Pandora FMS Enterprise version can be very useful for you.");
      break;
    default:
    break;
}

echo "
</div>
<div class='modalokbutton cerrar'>
<span class='modalokbuttontext'>OK</span>
</div>

<div class='modalgobutton gopandora'>
<span class='modalokbuttontext'>Go to Enterprise version</span>
</div>
";

?>

<script>

$(".cerrar").click(function(){
  $("#alert_messages").hide();
  $( "#opacidad" ).remove();
});

$(".gopandora").click(function(){
  window.open('https://pandorafms.com/es/software-de-monitorizacion-pandorafms/','_blank');
});

</script>
