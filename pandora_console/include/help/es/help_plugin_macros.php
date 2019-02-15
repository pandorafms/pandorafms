<?php
/**
 * @package Include/help/en
 */
?>
<h1>Macros de plug-in</h1>

<p>
Las siguientes macros están disponibles:
</p>
<ul>
<li>_agentdescription_ : Descripción del agente.</li>
<li>_agentgroup_ : Nombre del grupo del agente.</li>
<li>_agentstatus_ : Estado actual del agente.</li>
<li>_address_: Dirección del agente.</li>
<li>_module_: Nombre del módulo</li>
<li>_modulegroup_ : Nombre del grupo del módulo.</li>
<li>_moduledescription_: Descripcion del modulo.</li>
<li>_modulestatus_ : Estado del módulo.</li>
<li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de <?php echo get_product_name(); ?>.</li>
<li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
<li>_interval_ : Intervalo de la ejecución del módulo. </li>

<!--
Hidden this macros because they cannot edit in the module form
-->
<!--
<li>_target_ip_ : Dirección IP del objetivo del módulo.</li>
<li>_target_port_ : Puerto del objetivo del módulo.</li>
-->

<li>_plugin_parameters_ : Parámetros del Plug-in del módulo.</li>
<li>_name_tag_ : Nombre de los tags asociados al módulo.</li>
<li>_email_tag_ : Emails asociados a los tags de módulos.</li>
<li>_phone_tag_ : Teléfonos asociados a los tags de módulos.</li>
<li>_moduletags_ : URLs asociadas a los tags de módulos.</li>
<li>_agentcustomfield_<i>n</i>_: Campo personalizado número <i>n</i> del agente (eg. _agentcustomfield_9_). </li>
</ul>
