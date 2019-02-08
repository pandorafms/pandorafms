<?php
/**
 * @package Include/help/en
 */
?>
<h1>GIS Connection Configuration</h1>

<p>
This page is the place where the admin can configure <strong>a connection to a GIS Map Servers</strong>.
</p>

<h2>Connection types</h2>
<p>
Currently <?php echo get_product_name(); ?> support 3 differet kinds of connections: OpenStreetMap, Google Maps and Static Image.
</p>
<h3>Open Street Maps</h3>
<p>
To use the Open Street maps connection you can setup your own server (see <a href="http://wiki.openstreetmap.org/wiki/Main_Page">http://wiki.openstreetmap.org/wiki/Main_Page</a> to start and <a href="http://wiki.openstreetmap.org/wiki/Mapnik">http://wiki.openstreetmap.org/wiki/Mapnik</a> as an example of how to render your own tiles.) also you can access the open street map tile server:<br />
</p>
<pre>
http://tile.openstreetmap.org/${z}/${x}/${y}.png
</pre>
<p>
Using their <a href="http://wiki.openstreetmap.org/wiki/Licence">Licence</a>
</p>
<h3>Google MAPS</h3>
<p>
First, you need to register and get a free API KEY. Read about this at:<br/>
<a href="http://code.google.com/intl/en/apis/maps/signup.html">http://code.google.com/intl/en/apis/maps/signup.html</a></p>
<p>A Google API Key is something like:</p>
<pre>
ABQIAAAAZuJY-VSG4gOH73b6mcUw1hTfSvFQRXGUGjHx8f036YCF-UKjgxT9lUhqOJx7KDHSnFnt46qnj89SOQ
</pre>
<h3>Static Image</h3>
<p>
It's also possible to use a static image (a PNG for example) as the only source of the map. To use it, the <strong>url</strong>, the <strong>positional information</strong> of the image and the <strong>height</strong> and <strong>width</strong> must be filled.
</p>
