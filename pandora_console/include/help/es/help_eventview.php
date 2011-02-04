<?php
/**
 * @package Include/help/en
 */
?>
<h1>Vista de Eventos</h1>

<br>
<br>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Validar</h3>
	<?php print_image("images/tick.png", false, array("title" => "Validated event", "alt" => "Validated event", "width" => '10', "height" => '10')); ?> - Validar evento<br>
	<?php print_image("images/cross.png", false, array("title" => "Event not validated", "alt" => "Event not validated", "width" => '10', "height" => '10')); ?> - Evento no validado
</div>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Severidad</h3>
	<?php print_image("images/status_sets/default/severity_maintenance.png", false, array("title" => "Maintenance event", "alt" => "Maintenance event")); ?> - Evento de mantenimiento<br>
	<?php print_image("images/status_sets/default/severity_informational.png", false, array("title" => "Informational event", "alt" => "Informational event")); ?> - Evento informativo<br>
	<?php print_image("images/status_sets/default/severity_normal.png", false, array("title" => "Normal event", "alt" => "Normal event")); ?> - Evento normal<br>
	<?php print_image("images/status_sets/default/severity_warning.png", false, array("title" => "Warning event", "alt" => "Warning event")); ?> - Evento de alerta<br>
	<?php print_image("images/status_sets/default/severity_critical.png", false, array("title" => "Critical event", "alt" => "Critical event")); ?> - Evento crítico<br>
</div>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Acciones</h3>
	<?php print_image("images/ok.png", false, array("title" => "Validate event", "alt" => "Validate event")); ?> - Validar evento<br>
	<?php print_image("images/cross.png", false, array("title" => "Delete event", "alt" => "Delete event")); ?> - Borrar evento<br>
	<?php print_image("images/page_lightning.png", false, array("title" => "Create incident from event", "alt" => "Create incident from event")); ?> - Crear incidente del evento
</div>

<div style="clear: both;">&nbsp;</div>
