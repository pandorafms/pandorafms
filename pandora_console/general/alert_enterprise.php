<?php

if(!enterprise_installed()){
	$open=true; 
}

$tipo = $_GET['message'];

echo "
<div class='modalheader'>
<span class='modalheadertext'>
Community version</span>
<img class='modalclosex cerrar' src='".$config['homeurl']."images/icono_cerrar.png'>
</div>

<div class='modalcontent'>
<img class='modalcontentimg' src='".$config['homeurl']."images/";

switch ($tipo) {
    case "infomodal":
      echo "icono_info.png";
      break;
    case "modulemodal":
      echo "icono_popup.png";
        break;
    case "massivemodal":
      echo "icono_popup.png";
      break;
    case "eventsmodal":
      echo "icono_popup.png";  
      break;
    case "reportingmodal":
      echo "icono_popup.png";  
      break;
    case "visualmodal":  
      echo "icono_popup.png";
      break;
    case "updatemodal":  
      echo "icono_info.png";
        break;
    default:
    break;
}


echo "'>
<div class='modalcontenttext'>";

switch ($tipo) {
    case "infomodal":
    
    if($open){

  echo 
    '<p>' .
    	__('The Update Manager client is included on Pandora FMS. It helps system administrators update their Pandora FMS automatically, since the Update Manager retrieves new modules, new plugins and new features (even full migrations tools for future versions) automatically.') .
    '</p>' .
    '<p>' .
    	__('<b>OpenSource version updates are automated packages generated each week. This updates comes WITHOUT ANY warranty or support. If your system goes corrupt or a feature stop working properly, you will need to recover a backup by yourself.</b>') .
    '</p>' .
    '<p>' .
    	__('Enterprise version comes with a different update system, with fully tested, proffessional supported packages, and our support team will be helping you in case of any problem happen. The Update Manager is another feature present in Enterprise version and missing in the OpenSource version. There are lots of advanced features ready for the enterprise on the Pandora FMS Enterprise Edition. For more information visit <a href="http://pandorafms.com">pandorafms.com</a>') .
    '</p>'
    ;
    	
    }else{
    	
    	echo 
      '<p>' .
    		__('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with Pandora FMS It helps system administrators to update their Pandora FMS automatically, since the Update Manager does the task of getting new modules, new plugins and new features (even full migrations tools for future versions) automatically.') .
    	'</p>' .
    	'<p>' .
    		__('Update Manager is one of the most advanced features of Pandora FMS Enterprise version, for more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>.') .
    	'</p>' .
    	'<p>' .
    		__('Update Manager sends anonymous information about Pandora FMS usage (number of agents and modules running). To disable it, remove remote server address from Update Manager plugin setup.') .
    	'</p>'
    ;
    }
    
      break;
    case "modulemodal":
      echo __("La versión de la comunidad no permite definir su propia librería de módulos ni distribuirla a agentes remotos, solo le permite hacerlo de manera individual en cada agente usando herramientas externas. Tampoco puede distribuir plugins locales ni acceder a la librería de plugins Enterprise para monitorizar aplicaciones como VMWare, RHEV o Informix entre otras. Cambie a la versión Enterprise para administrar sus propios módulos de forma individual o mediante políticas.
      <br><br><img style='width:105px' src='".$config['homeurl']."images/logo_oracle.png'><img style='width:105px' src='".$config['homeurl']."images/logo_citrix.png'><img style='width:105px' src='".$config['homeurl']."images/logo_sap.png'><img style='width:105px' src='".$config['homeurl']."images/logo_exchange.png'><br><br><span style='font-style:italic;'>* Todos los logotipos pertenecen a marcas registradas</span>");
      break;
    case "massivemodal":
      echo __("You want to manage your monitoring homogeneously? Do you have many systems and is difficult to manage in a comprehensive manner? Would you like to deploy monitoring, alerts and even local plugins with a single click? Pandora FMS Enterprise Policies are exactly what you need, you'll save time, effort and dislikes. More information (link to pandorafms.com)");
      break;
    case "eventsmodal":
      echo __("Pandora FMS Enterprise has event correlation. Through correlation you can generate alerts and / or new events based on logical rules on your realtime events. This allows you to automate the troubleshooting. If you know the value of working with events, the correlation will take you to a new level.");
      break;
    case "reportingmodal":
      echo __("The reports of the Enterprise version are more powerful: it has wizards, you can schedule sending via email in PDF, and it has a template system to create reports quickly for each of your customers. It will even allow your customers generate their own reports from templates created by you. If reports are key to your business, Pandora FMS Enterprise version can be very useful for you.");
      break;
    case "visualmodal":  
      echo __("These options are only effective on the Enterprise version.");
      break;
    case "updatemodal":  
        echo __("WARNING: You are just one click of an automated update. This may result on a damaged system, including loss of data and operation. Check you have a recent backup. OpenSource updates are automated created packages, and there is no WARRANTY or SUPPORT. If you need professional support and warranty, please upgrade to Enterprise Version.");
      break;
    default:
    break;
}

echo "

</div>
<div style='float:right;width:100%;height:30px;'>
</div>
<div class='modalokbutton cerrar'>
<span class='modalokbuttontext'>OK</span>
</div>

<div class='modalgobutton gopandora'>
<span class='modalokbuttontext'>About Enterprise</span>
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
