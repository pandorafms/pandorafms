<?php
/**
 * @package Include/help/en
 */
?> 

<h1> Creating a new service</h1>

The service represents an association of agent modules and their value is calculated in real time. Because of that first of all you need to have all your devices that make a service monitored and with their module's values normalized to three status: Normal, Warning and Critical. 
<br><br>
When you have all the devices monitored you can make group of them with the service. Inside each service you can add all modules you need to monitor the service. For example if you want to monitor the online shop service you need a module that monitors the content, another which monitors the comunication status and so on.

<br><br>

To create a new service just click on botton Create. 
<br><br>

<?php html_print_image('images/help/service2.png', false, ['width' => '550px']); ?>
<br><br>
At this moment we have a service created without items, so we have to add items to the service. To add a new item click on the oragne tool an the right top of Service Management tab and after in the botton Create. Then the form below will appear. In this form you must select a module of an agent to add. Also you must fill the fields related to the weight of this module inside the service for Normal, Warning and Critical status. The heavier a module the more important is within the service. 
<br><br>

<?php html_print_image('images/help/service1.png', false, ['width' => '550px']); ?>
<br><br>
When all fields are filled click on button create and the next picture will appear with the succesful message.
<br><br>
<?php html_print_image('images/help/service3.png', false, ['width' => '550px']); ?>
<br><br>
You can add all items you need to monitor your service. For example we have added elements of this service with the proper weights and the result is like in the next picture.
<br><br>
<?php html_print_image('images/help/service4.png', false, ['width' => '550px']); ?>
<br><br>
Then the service opeartion list will appear like the image below. This view is calculated in real time and the parameters showed are:
<br><br>
<ul type=”disc”>
<li>    <i>Name:</i> name of the service.<br></li>
<li>    <i>Description:</i> description of the service<br></li>
<li>    <i>Group:</i> group the service belongs to<br></li>
<li>    <i>Critical:</i> limit value from which the service is in critical state.<br></li>
<li>    <i>Warning:</i> limit value from which the service is in warning state.<br></li>
<li>    <i>Value:</i> value of the service. It's calculated in real time.<br></li>
<li>    <i>Status:</i> state of the service depending on its value and the critical and warning limits. <br></li>
</ul>
<br><br>
<?php html_print_image('images/help/service5.png', false, ['width' => '550px']); ?>
<br><br>
If you click on a service name you will see the sepcific service view. As you know the value of a service is calculated as the addition of the weights associated to the state of each module. Services, same as modules, has associated an state depending on its value. This view shows the status of each service item and with following parameters:
<br><br>
<ol>
<li />    <i>AgentName:</i> name of the agent the module belongs to.<br>
<li />    <i>Module Name:</i> name of the module.<br>
<li />    <i>Description:</i> free description.<br>
<li />    <i>Weight Critical:</i> weight when the module is in a critical state.<br>
<li />    <i>Weight Warning:</i> weight when the module is in warning state<br>
<li />    <i>Weight Ok:</i> weight when the module is in normal state.<br>
<li />    <i>Data:</i> value of the module.<br>
<li />    <i>Status:</i> state of the module.<br>
</ol>

<br><br>
<?php html_print_image('images/help/service6.png', false, ['width' => '550px']); ?>
<br><br>
It's also possible to create modules associated to services, with the advantages that this implies (calculation periodicity, integration with the alert system, etc). The way to associate one module to a service is to follow the following steps:
<br><br>
<ol>
<li />    Create the individual monitors that make up the service and make sure that they work well.<br><br>
<li />    Fix the individual thresholds for each monitor to define CRITICAL and/or WARNING states.<br><br>
<li />    Create a servoce with those monitors that we want, and define thresholds for the service and weights for each monitor included in the service.<br><br>
<li />    Go to the agent where we want to "locate" the monitor associated to the service.<br><br>
<li />    Create a new module of "prediction" kind associated to this agent, using the module editor of the Prediction server, in order to associate it to one of the services of the list.<br><br>
<li />    If we want to associate alerts to the service, then we should do it on the module that is associated to the server. The server, as it is, has no possibilities of adding alerts, neither graphs or reports. All these has to be done through the monitor that is linked to the service, as we have described before. <br><br>
</ol>
<br><br>
<?php html_print_image('images/help/service7.png', false, ['width' => '550px']); ?>
<br><br>



