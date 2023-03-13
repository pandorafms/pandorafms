<?php
/**
 * @package Include/help/en
 */
?>
<h1>GIS Map builder</h1>

<p>
This page shows a list of the defined maps, and let you edit, delete or view any of them. Also from this page is where the <strong>default Map</strong> of <?php echo get_product_name(); ?> is set.
</p>
To create a map a connection to a map server is needed, the connections are created by the Adminstrator in the <strong>Setup</strong> menu.
<p>
</p>
<p>
Options:
</p>
<div>
<dl>
<dt>Map name</dt>
<dd>Click on the <strong>Map Name</strong> corresponding to the map you want edit the map </dd>
<dt><?php html_print_image('images/eye.png', false, ['alt' => 'View']); ?> View</dt>
<dd>Click on the view icon to <strong>view</strong> the map.</dd>
<dt>Default radio button</dt>
<dd>Click on the <strong>radio button</strong> corresponding to the map you want to make the default to set the <strong>default map</strong> </dd>
<dt><?php html_print_image('images/delete.svg', false, ['alt' => 'Delete']); ?> Delete</dt>
<dd>Click on the delete icon to <strong>delete</strong> the map.</dd>
<dt>Create Button</dt>
<dd>Click on Create button to <strong>create</strong> a new map.</dd>
</dl>
</div>
