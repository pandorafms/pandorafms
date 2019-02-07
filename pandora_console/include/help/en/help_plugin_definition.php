<?php
/**
 * @package Include/help/en
 */
?>
<h1>Plugin registration</h1>

Unlike with the rest of components, in a default way <?php echo get_product_name(); ?> does not include any pre-configured complement, so first you should create and configure a complement to could after add it to the module of an agent. But <?php echo get_product_name(); ?> includes plugins in the installation directories, but as have already been said, they are not configured in the database. 
<br><br>
To add a plugin that already exists to <?php echo get_product_name(); ?>, go to the console administration section, and in it, click on Manage servers. After doing this, click on Manage plugins: 
<br><br>
Once you are in the screen of the plugin management, click on Create a new plugin, so there will be no one. 
<br><br>
Fill in the plugin creation form with the following data:
<?php html_print_image('images/help/plugin1.png', false, ['width' => '550px']); ?>
<br><br>
<b>Name</b><br>
Name of the plugin, in this case Nmap.
<br><br>
<b>Plugin type </b><br>
There are two kinds of plugins, the standard ones and the kind Nagios. The standard plugins are scripts that execute actions and accept parameters. The Nagios plugins are, as their name shows, Nagios plugins that could be being used in <?php echo get_product_name(); ?>.The difference is mainly on that the Nagios plugins return an error level to show if the test has been successful or not.
<br><br>
If you want to use a plugin kind Nagios and you want to get a data, not an state (good/Bad), then you can use a plugin kind Nagios is the "Standard" mode.
<br><br>
In this case (for the NMAP example plugin), we have to select Standard. 
<br><br>
<b>Max. timeout</b><br>

It is the time of expiration of the plugin. If you do not receive a response in this time, you should select the module as unknown, and its value will be not updated. It is a very important factor when implementing monitoring with plugins, so if the time it takes at executing the plugin is bigger than this number, we never could obtain values with it. This value should always be bigger than the time it takes usually to return a value the script/executable that is used as plugin. In there is nothing said, then you should used the value that in the configuration is named plugin_timeout.
<br><br>
In this case, we write 15.
<br><br>
<b>Description</b><br>

Plugin description. Write a short description, as for example:Test # UDP open ports, and if it is possible, specify the complete interface of parameters to help to someone that will after check the plugin definition to know which parameters accept.
<br><br>
<b>Plug-in command</b><br>

It is the path where the plugin command is. In a default way, if the installation has been an standard one, there will be in the directory /usr/share/pandora_server/util/plugin/. Though it could be any path of the system. For this case, writte /usr/share/pandora_server/util/plugin/udp_nmap_plugin.shin the field.
<br><br>
<?php echo get_product_name(); ?> server will execute this script, so this should have permissions of access and execution on it.
<br><br>
<b>Plug-in parameters</b><br>

A string with the parameters of the command that will be after command and a blank space. This parameters field accepts macros as _field1_ _field2_ ... _fieldN_.
<br><br>
<b>Parameters macros</b><br>

Is possible to add unlimited macros to use it in Plug-in parameters field. This macros will appear as normal text fields in the module configuration.
<br><br>
Each macro has 3 fields:
<br><br>
    <i>Description:</i> A short string descripting the macro. Will be the label near the field.<br>
    <i>Default value:</i> Value asigned to the field by default<br>
    <i>Help:</i> A text with a explanation of the macro. <br>
<br><br>
Example of a macro configuration: 
<br><br>
<?php html_print_image('images/help/plugin2.png', false, ['width' => '550px']); ?>
<br><br>
Example of this macro in the module editor:
<br><br>
<?php
html_print_image('images/help/plugin3.png', false, ['width' => '550px']);
