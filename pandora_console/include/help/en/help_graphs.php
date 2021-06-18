<?php
/*
 * @package Include/help/es/
 */
?>


<style type="text/css">

* {
    font-size: 1em;
}

img.hlp_graphs {
    width: 80%;
    max-width: 800px;
    min-width: 400px;
    margin: 15px auto;
    display: block;
}

ul.clean {
    list-style-type: none;
}

b {
    font-size: 0.90em!important;
}
dl dt {
    margin-top: 1em;
    font-weight: bold;
}
dl {
    margin-bottom: 2em;
}

div.img_title {
    text-align: center;
    font-size: 0.8em;
    font-style: italic;
    width: 100%;
    margin-top: 4em;
}
</style>

<body class="hlp_graphs">
<h1>INTERPRETING GRAPHS IN <?php echo get_product_name(); ?></h1>


<p>In <?php echo get_product_name(); ?>, graphs represent the values a module has had during a given period.</p>
<p>Due to the large amount of data that <?php echo get_product_name(); ?> stores, two different types of functionality are offered</p>


<h2>NORMAL GRAPHS</h2>

<img class="hlp_graphs" src="<?php echo $config['homeurl']; ?>images/help/chart_normal_sample.png" alt="regular chart sample" />

<h4>General characteristics</h4>
<p>These are graphs that represent the information stored by the module at a basic level.</p>
<p>They allow us to see an approximation of the values in which our module oscillates.</p>
<p>The module data are divided into <i>boxes</i> in such a way that a sample of the module values is represented, <b>not all values are shown</b>. This is complemented by dividing the view into three graphs, <b>Max</b> (maximum values), <b>min</b> (minimum values) and <b>avg</b> (average values)</p>

<ul class="clean">
<li><b>Advantages</b>: They are generated very quickly without consuming hardly any resources.</li>
<li><b>Disadvantages</b>: The information provided is approximate. The status of the monitors they represent are calculated on an event-driven basis.</li>



<h4>Display options</h4>

<dl>
<dt>Refresh time</dt>
<dd>Time the graph will take to be created again.</dd>

<dt>Avg. Only</dt>
<dd>Only the averages graph will be created.</dd>

<dt>Starting date</dt>
<dd>Date until which the graphic will be created.</dd>

<dt>Startup time</dt>
<dd>Hour minutes and seconds until the graphic is created.</dd>

<dt>Zoom factor</dt>
<dd>Graph viewfinder size, multiplicative.</dd>

<dt>Time Range</dt>
<dd>Sets the time period from which data will be collected.</dd>

<dt>Show events</dt>
<dd>Displays indicator points with event information at the top.</dd>

<dt>Show alerts</dt>
<dd>Shows indicator points with triggered alert information at the top.</dd>

<dt>Show percentile</dt>
<dd>Adds a graph that indicates the percentile line (configurable in general visual options of <?php echo get_product_name(); ?>).</dd>

<dt>Time comparison (superimposed)</dt>
<dd>Displays the same graphic overlay, but in the period before the selected one. For example, if we request a period of one week and activate this option, the week before the chosen one will also be shown superimposed.</dd>

<dt>Time comparison (independent)</dt>
<dd>Displays the same graph, but in the period before the selected one, in a separate area. For example, if we request a period of one week and activate this option, the week before the chosen one will also be shown.</dd>

<dt>Display unknown graphic</dt>
<dd>It shows boxes in grey shading covering the periods in which <?php echo get_product_name(); ?> cannot guarantee the module's status, either due to data loss, disconnection of a software agent, etc.</dd>

<dt>Show Full Scale Graph (TIP)</dt>
<dd>Switches the creation mode from "normal" to "TIP". In this mode, the graphs will show real data rather than approximations, so the time it will take to generate them will be longer. More detailed information on this type of graphs can be found in the following section.</dd>

</dl>




<br />
<br />


<h2>TIP GRAPS</h2>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_sample.png" alt="TIP chart sample" />

<h4>General characteristics</h4>
<p>These are graphs that represent <b>real data</b>.</p>
<p>They show us a true representation of the data reported by our module.</p>
<p>As these are real data, it will not be necessary to supplement the information with extra graphs (avg, min, max).</p>
<p>The calculation of periods in unknown state is supported by events, such as normal graphs, but is complemented by extra detection if there is any.</p>
<p>Examples of resolution offered by normal and TIP methods:</p>

<div class="img_title">Example of normal graph in unknown interval</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_normal_detail.png" alt="TIP chart detail" />

<div class="img_title">Example of TIP graph in unknown interval</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_detail.png" alt="TIP chart detail" />

<br />

<ul class="clean">
<li><b>Advantages</b>: The data represented are real data. This is the most realistic way to review module data.</li>
<li><b>Disadvantages</b>: Processing is slower than in normal graphs. Depending on the time range and the volume of data to be displayed, your display may be less fluid.</li>
</ul>

</body>

