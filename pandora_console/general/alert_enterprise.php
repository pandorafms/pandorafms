<?php

/*
    Hello there! :)

    We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(

    You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.

*/

if (check_login()) {
    if (!enterprise_installed()) {
        $open = true;
    }

    $tipo = $_POST['message'];

    echo "
<div class='modalheader'>
<span class='modalheadertext'>";

    if ($tipo == 'noaccess') {
        echo "You don't have access to this page";
    } else if (!enterprise_installed()) {
        echo 'Community version';
    } else {
        echo 'Enterprise version';
    }

    echo "</span>
<img class='modalclosex cerrar' src='".$config['homeurl'].'images/icono_cerrar.png'."'>
</div>

<div class='modalcontent'>
<img class='modalcontentimg' src='".$config['homeurl'].'images/';

    switch ($tipo) {
        case 'infomodal':
            echo 'icono_info.png';
        break;

        case 'helpmodal':
            echo 'icono_info.png';
        break;

        case 'modulemodal':
            echo 'icono_popup.png';
        break;

        case 'massivemodal':
            echo 'icono_popup.png';
        break;

        case 'eventsmodal':
            echo 'icono_popup.png';
        break;

        case 'reportingmodal':
            echo 'icono_popup.png';
        break;

        case 'visualmodal':
            echo 'icono_popup.png';
        break;

        case 'updatemodal':
            echo 'icono_info.png';
        break;

        case 'agentsmodal':
            echo 'icono_info.png';
        break;

        case 'monitorcheckmodal':
            echo 'icono_info.png';
        break;

        case 'remotemodulesmodal':
            echo 'icono_info.png';
        break;

        case 'monitoreventsmodal':
            echo 'icono_info.png';
        break;

        case 'alertagentmodal':
            echo 'icono_info.png';
        break;

        case 'noaccess':
            echo 'access_denied.png';
        break;

        default:
        break;
    }


    echo "'>
<div class='modalcontenttext'>";

    switch ($tipo) {
        case 'helpmodal':

            echo __(
                "This is the online help for %s console. This help is -in best cases- just a brief contextual help, not intented to teach you how to use %s. Official documentation of %s is about 900 pages, and you probably don't need to read it entirely, but sure, you should download it and take a look.<br><br>
  <a href='%s' target='_blanck' class='pandora_green_text font_10 underline'>Download the official documentation</a>",
                get_product_name(),
                get_product_name(),
                get_product_name(),
                $config['custom_docs_url']
            );

        break;

        case 'noaccess':

            echo __(
                'Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br> <br>
    Please know that all attempts to access this page are recorded in security logs of %s System Database.',
                get_product_name()
            );

        break;

        case 'infomodal':

            if ($open) {
                echo '<p>'.__('The Update Manager client is included on %s. It helps system administrators update their %s automatically, since the Update Manager retrieves new modules, new plugins and new features (even full migrations tools for future versions) automatically.', get_product_name(), get_product_name()).'</p>'.'<p>'.__('<b>OpenSource version updates are automated packages generated each week. These updates come WITHOUT ANY warranty or support. If your system is corrupted or a feature stops working properly, you will need to recover a backup by yourself.</b>').'</p>'.'<p>'.__('The Enterprise version comes with a different update system, with fully tested, professionally-supported packages, and our support team is there to help you in case of problems or queries. Update Manager is another feature present in the Enterprise version and not included in the OpenSource version. There are lots of advanced business-oriented features contained in %s Enterprise Edition. For more information visit <a href="http://pandorafms.com">pandorafms.com</a>', get_product_name()).'</p>';
            } else {
                echo '<p>'.__('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is included on %s. It helps system administrators update their %s automatically, since the Update Manager retrieves new modules, new plugins and new features (even full migrations tools for future versions) automatically.', get_product_name(), get_product_name()).'</p>'.'<p>'.__('The Update Manager is one of the most advanced features on the %s Enterprise Edition. For more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>.', get_product_name()).'</p>'.'<p>'.__('Update Manager sends anonymous information about %s usage (number of agents and modules running). To disable it, please remove the remote server address from the Update Manager plugin setup.', get_product_name()).'</p>';
            }
        break;

        case 'modulemodal':
            echo __(
                "The community version doesn't have the ability to define your own library of local modules, or distribute it to remote agents. You need to make those changes individually on each agent which is possible by using external tools and time and effort. Nor can it distribute local plugins, or have access to the library of enterprise plugins to monitor applications such as VMWare, RHEV or Informix between others. The Enterprise version will have all this, plus the ability to distribute and manage your own local modules on your systems, individually or through policies.
      <br><br><img class='w105px' src='".$config['homeurl'].'images/logo_oracle.png'."'><img class='w105px' src='".$config['homeurl'].'images/logo_citrix.png'."'><img class='w105px' src='".$config['homeurl'].'images/logo_sap.png'."'><img class='w105px' src='".$config['homeurl'].'images/logo_exchange.png'."'><br><br><span class='italic'>* Todos los logotipos pertenecen a marcas registradas</span>"
            );
        break;

        case 'massivemodal':
            echo __("Do you want to consolidate all your system monitoring? Do you have many systems, making it difficult to manage them in a comprehensive manner? Would you like to deploy monitoring, alerts and even local plugins with a single click? %s Enterprise Policies are exactly what you need; you'll save time, effort and annoyances. More information <a href='pandorafms.com'>pandorafms.com</a>", get_product_name());
        break;

        case 'eventsmodal':
            echo __('%s Enterprise also features event correlation. Through correlation you can generate realtime alerts and / or new events based on logical rules. This allows you to automate troubleshooting. If you know the value of working with events, event correlation will take you to a new level.', get_product_name());
        break;

        case 'reportingmodal':
            echo __('Report generating on the Enterprise version is also more powerful: it has wizards, you can schedule emails in PDF to be sent according to the schedule you decide, and it has a template system to create personalized reports quickly for each of your customers. It will even allow your customers to generate their own reports from templates created by you. If reports are key to your business, %s Enterprise version is for you.', get_product_name());
        break;

        case 'visualmodal':
            echo __('These options are only effective on the Enterprise version.');
        break;

        case 'updatemodal':
            echo __('WARNING: You are just one click away from an automated update. This may result in a damaged system, including loss of data and operativity. Check you have a recent backup. OpenSource updates are automatically created packages, and there is no WARRANTY or SUPPORT. If you need professional support and warranty, please upgrade to Enterprise Version.');
        break;

        case 'agentsmodal':
            echo __('This system is heavily loaded. OpenSource version could get a lot more agents but fine tuning requires knowledge and time. Checkout the Enterprise Version for a professional supported system.');
        break;

        case 'monitorcheckmodal':
                // Get agent/module average.
                $agentCount = db_get_value_sql('SELECT count(*) FROM tagente');
                $modulesCount = db_get_value_sql('SELECT count(*) FROM tagente_modulo');
                $average = ($modulesCount / $agentCount);

                echo __('This system has too many modules per agent. OpenSource version could manage thousands of modules, but is not recommended to have more than 100 modules per agent. This configuration has %d modules per agent. Checkout the Enterprise Version for a professional supported system.', $average);
        break;

        case 'remotemodulesmodal':
                echo __('Too much remote modules has been detected on this system. OpenSource version could manage thousands of modules, but performance is limited on high amount of SNMP or ICMP request. Checkout the Enterprise Version for a professional supported system with improved capacity on network monitoring, including distributed servers.');
        break;

        case 'monitoreventsmodal':
                echo __('This system has too much events in the database. Checkout database purge options. Checkout the Enterprise Version for a professional supported system.');
        break;

        case 'alertagentmodal':
                echo __('You have defined a high number of alerts, this may cause you performance problems in the future. In the Enterprise version, you can use event correlation alerts to simplify the alerting system and have easier administration and increased performance.');
        break;

        default:
        break;
    }

    echo "

</div>
<div class='btn_update_online_open height_30px'>

<div class='modalokbutton cerrar'>
<span class='modalokbuttontext'>OK</span>
</div>";
    if ($open) {
        echo "<div class='modalgobutton gopandora'>
<span class='modalgobuttontext'>About Enterprise</span>
</div></div>";
    }
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

$(".gopandora").click(function(){
  window.open('https://pandorafms.com/es/software-de-monitorizacion-pandorafms/','_blank');
});

</script>
