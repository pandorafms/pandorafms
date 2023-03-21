<?php
/**
 * @package Include/help/en
 */
?>
<h1>Vista de Eventos</h1>

<br>
<br>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Validar</h3>
    <?php html_print_image('images/tick.png', false, ['title' => 'Validated event', 'alt' => 'Validated event', 'width' => '10', 'height' => '10']); ?> - Validar evento<br>
        <div class="w10px height_10px inline"></div> - Evento no validado
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Severidad</h3>
    <?php html_print_image('images/status_sets/default/severity_maintenance.png', false, ['title' => 'Maintenance event', 'alt' => 'Maintenance event']); ?> - Evento de mantenimiento<br>
    <?php html_print_image('images/status_sets/default/severity_informational.png', false, ['title' => 'Informational event', 'alt' => 'Informational event']); ?> - Evento informativo<br>
    <?php html_print_image('images/status_sets/default/severity_normal.png', false, ['title' => 'Normal event', 'alt' => 'Normal event']); ?> - Evento normal<br>
    <?php html_print_image('images/status_sets/default/severity_warning.png', false, ['title' => 'Warning event', 'alt' => 'Warning event']); ?> - Evento de alerta<br>
    <?php html_print_image('images/status_sets/default/severity_critical.png', false, ['title' => 'Critical event', 'alt' => 'Critical event']); ?> - Evento crítico<br>
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Acciones</h3>
    <?php html_print_image('images/ok.png', false, ['title' => 'Validate event', 'alt' => 'Validate event']); ?> - Validar evento<br>
    <?php html_print_image('images/delete.svg', false, ['title' => 'Delete event', 'alt' => 'Delete event']); ?> - Borrar evento<br>
    <?php html_print_image('images/eye.png', false, ['title' => 'Mostrar más', 'alt' => 'Mostrar más']); ?> - Mostrar más<br>
    <?php html_print_image('images/hourglass.png', false, ['title' => 'En progreso', 'alt' => 'En progreso']); ?> - En progreso
</div>

<div class="both">&nbsp;</div>
