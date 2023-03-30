<?php
/**
 * @package Include/help/en
 */
?>
<h1>Event viewer</h1>

<br>
<br>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Validate</h3>
    <?php html_print_image('images/tick.png', false, ['class' => 'invert_filter', 'title' => 'Validated event', 'alt' => 'Validated event', 'width' => '10', 'height' => '10']); ?> - Validated event<br>
        <div class="w10px height_10px inline"></div> - Event not validated
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Severity</h3>
    <?php html_print_image('images/status_sets/default/severity_maintenance.png', false, ['title' => 'Maintenance event', 'alt' => 'Maintenance event']); ?> - Maintenance event<br>
    <?php html_print_image('images/status_sets/default/severity_informational.png', false, ['title' => 'Informational event', 'alt' => 'Informational event']); ?> - Informational event<br>
    <?php html_print_image('images/status_sets/default/severity_normal.png', false, ['title' => 'Normal event', 'alt' => 'Normal event']); ?> - Normal event<br>
    <?php html_print_image('images/status_sets/default/severity_warning.png', false, ['title' => 'Warning event', 'alt' => 'Warning event']); ?> - Warning event<br>
    <?php html_print_image('images/status_sets/default/severity_critical.png', false, ['title' => 'Critical event', 'alt' => 'Critical event']); ?> - Critical event<br>
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>Actions</h3>
    <?php html_print_image('images/ok.png', false, ['title' => 'Validate event', 'alt' => 'Validate event']); ?> - Validate event<br>
    <?php html_print_image('images/delete.svg', false, ['title' => 'Delete event', 'alt' => 'Delete event']); ?> - Delete event<br>
    <?php html_print_image('images/eye.png', false, ['title' => 'Show more', 'alt' => 'Show more']); ?> - Show more<br>
    <?php html_print_image('images/hourglass.png', false, ['title' => 'In progress', 'alt' => 'In progress']); ?> - In progress    
</div>

<div class="both">&nbsp;</div>

