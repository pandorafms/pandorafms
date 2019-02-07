<?php
/**
 * @package Include/help/en
 */
?>
<h1>Macros</h1>

Is possible configure macros in the module execution (module_exec) or in a plugin parameters.
<br /><br />
Each macro has 3 parameters:
<ul>
    <li>Description</li>
    <li>Default value (optional)</li>
    <li>Help (optional)</li>
</ul>

In example, to configure a module that returns the apache's running process
number in a machine, we configure the next command:
<br /><br />
ps -A | grep apache2 | wc -l
<br /><br />
We can replace the name of the process by a macro:
<br /><br />
ps -A | grep _field1_ | wc -l
<br /><br />
And configure the parameters of the macro as:

<ul>
    <li>Description: Process</li>
    <li>Default value: apache2</li>
    <li>Help: Name of substring of the running processes counted by the module</li>
</ul>

When we configure the module from this component, will appear a text field "Process" 
with a default value "apache2" that we can modify, and a help that will show more information
to user.
<br /><br />
Is possible to configure as many macros as you want.
