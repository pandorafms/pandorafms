<?php
/*
 * @package Include/en
 */
?>

<h1>Networkmap console</h1>

<p>With <?php echo get_product_name(); ?> Enterprise we have the possibility of create editable network maps that are more interactive comparing with the Open version that is currently on the "See agents" submenu.</p>

<p>On the contrary to the Open version, the Networkmap Enterprise provide us with more features, such as: </p>


<li>Networkmaps much bigger, of more than 1000 agents to monitor.</li>
<li>Monitoring in teal time of all the network topology of their systems. </li>
<li>Different views of its network topology, defined in a manual way or generated automatically with agent groups. </li>
<li>ETo link differebt views of its topology through fictitious points. </li>
<li>To manipulate the topology represented in the view: </li>
<li>Adding new nodes, one by one of in a massive way. </li>
<li>Editing the nodes features. </li>
<li>Organizing them inside the view: <br>
            - The nodes position. <br>
            - The relationships between the nodes. </li>

<p>The network maps could contain:</p>

    <p><b>Real nodes,</b> which represent in an unique way the agents added in the map there. These nodes have an icon that represent the agent operative system and a circle (with circular shape by default, but it's also possible to select between other different shapes) of the agent status that could be: </p>
        <li>Green, it's right status.</li>
        <li>Red,some module it's on critical status. </li>
        <li>Yellow, some module is on warning status. </li>
        <li>Orange,some of the alarms has been fired in the agent.</li>
        <li>Grey, the agent is on unknown status. </li>
    <p><b>Fictitious nodes,</b>, which represent the link to other network map or simply one point simply for personal use in the map, could have any shape of the availables ones (circle, rhombus, square), any size and the text, of course. If it's a link to another map the color follows the following rules, it the color can't be customized. </p>
        <li>Green, if all the nodes of the linked map are right. </li>
        <li>Red,if any of the nodes of the linked map is on critical status. </li>
        <li>Yellow, if any of the nodes of the map is on warning status and there is any one on critical status. </li>
        <li>Orange, grey following the same rule that the other colors.  </li>

<h2>Minimap</h2>

<p>This minimap gives us a global view that shows all the map extension, but in a smaller view, and besides, in contrast with the map view, all the nodes are shown, but without status and without relationships. Except the <?php echo get_product_name(); ?> fictitious point, that is shown in green. And a red box is also shown of the part of the map that is being shown. </p>

<p>It's on the upper left corner, and could be hidden pressing on the arrow icon. 
</p>


<h2>Control Panel </h2>

<p>From the control panel you can do tasks more complex on the network map. </p>

<p>t's hidden on the right upper corner. Same as with the minimap, it could be shown pressing on the arrow.  </p>
<?php html_print_image('images/help/netmap1.png', false, ['width' => '550px']); ?>

<p>And the available options are:</p>

    <li>To change the refresh frequency of the nodes status. </li>
    <li>To force the refresh.</li>
    <li>To add the agent, through the intelligent control that allows to search the agent in a quick way and to add it. The new node is shown in the point (0,0) of the map that is on the left upper side of the map. </li>
    <li>To add several agents, filtering them by group, which will show the agents of this group that are not yet in the map in a list of multiple selection </li>
    <li>To do a screenshot of the visible part of the map.</li>
    <li>To add a fictitious point, where you can select the text as name of this point, the size defined by the range, the point shape, color by default, and, if you want that the fictitious point would be a link to a map. </li>
    <li>To search agent, also through an intelligent control, once selected, the map will go automatically to the point where the agent node is. </li>
    <li>Zoom, change the network map zoom level </li>

<h2>Detail View Window </h2>

<p>The detail view window is a visual view of one agent. It is refreshed at the same velocity that the map that has opened, and the windows are completely independents, so you can have several windows opened.</p>


<?php html_print_image('images/help/netmap2.png', false, ['width' => '550px']); ?><br><br>



    <p>It shows a box which rim will be of the same color that the agent status. <br>
    The agent name is a link to the <?php echo get_product_name(); ?> agent page. <br>
    Inside the box are all the modules that are not in unknown status, which, depending if the module status is green or red. <br>
        It's possible to click on these modules and they shown a tooltip with the module main data.  <br>
    In the box rim are the modules kind SNMP Proc,that use to be for network interfaces when an agent related with network systems is monitored.  <br></p>

<h2>Palette of fictitious point
</h2>
<p>If you select see details on a fictitious point, this will show you a pop up window with a palette of options to modify the fictitious point </p>

<?php html_print_image('images/help/netmap3.png', false, ['width' => '550px']); ?><br><br>


<p>We have a form with these options: </p>

    <li>Name of the fictitious point. </li>
    <li>Radio of the fictitious point.</li>
    <li>Shape of the fictitious point. </li>
    <li>Color of the fictitious point. </li>
    <li>Map that links the fictitious point.  </li>

<h2>Creating a Network map</h2>

<p>If you want to create a network map, you can do it as: </p>

    <li>Show of all the agents contained in one group. </li>
    <li>Creation of an empty network map </li>


<br><br>We are going to summarize the fields that the creation form has availables: <br><br>

    <li><b>Name:</b> Name of the network map </li>
    <li><b>Group:</b> The group for the ACL, and also the group which map we want to generate with the agents that are contained in this group. </li>
    <li><b>Creating the network map from:</b> option only available in the creation. It's the way to create the network map if we do it from the agents that are in the previously selected group, or on the contrary we want an empty network map. </li>
    <li><b>Size of the network map:</b> where it's possible to define the size of the network map, by default it's of 3000 width and 3000 high. </li>
    <li><b>Method for creating of the network map:</b> the method of distribution of the nodes that will make up the network map, by default it's radial, but there are the following ones: </li>
        <p>- <i>Radial:</i> In which all the nodes will be placed around the fictitious node that the <?php echo get_product_name(); ?> represents. <br>
        - <i>Circular:</i> In which the nodes will be placed in concentric circles en el cual se dispondrá los nodos en círculos concentricos. <br>
        - <i>Flat:</i> In which the nodes with tree shape will be placed. <br>
        - <i>spring1, spring2:</i> are variations of the Flat.  <br>
    <li><b>Networkmap Refresh:</b> the refresh velocity of the status of the nodes contained in the networkmap, by default is any 5 minutes.  </p>

The rest of the fields disabled, as for example "resizing map" is because there are only actives in the edition of one map already created.
