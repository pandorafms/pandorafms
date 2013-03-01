<?php
/**
 * @package Include/help/en 
 */
?> 

<h1> Creating a new service</h1>

The service represents an association of agent modules and their value is calculated in real time. Because of that first of all you need to have all your devices that make a service monitored and with their module's values normalized to three status: Normal, Warning and Critical. You can learn more about them in their wiki sections: Monitoring with Pandora FMS and Monitoring with policies.
<br><br>
When you have all the devices monitored you can make group of them with the service. Inside each service you can add all modules you need to monitor the service. For example if you want to monitor the online shop service you need a module that monitors the content, another which monitors the comunication status and so on.

<br><br>

To create a new service just click on botton Create. 
<br><br>

<?php html_print_image ("images/help/service1.png", false, array('width' => '550px')); ?>
<br><br>
At this moment we have a service created without items, so we have to add items to the service. To add a new item click on the oragne tool an the right top of Service Management tab and after in the botton Create. Then the form below will appear. In this form you must select a module of an agent to add. Also you must fill the fields related to the weight of this module inside the service for Normal, Warning and Critical status. The heavier a module the more important is within the service. 

