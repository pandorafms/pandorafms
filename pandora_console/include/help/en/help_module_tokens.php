<?php
/* Include package help/en
*/
?>

<p> When the data module is created from the console, the Configuration data is 
copied in the agent configuration file to be used <b>in the module update. 
Not in creation.</b><br><br>

Most of the tokens of a Configuration data are used only in creation.
An exception is the description, that is updated if change. So if we set
a description in form and other different description in Configuration data, 
the module will has the first description from the creaton until the first 
update.<br><br>

In this way, we will use the Configuration data for 
the mandatory tokens (name, type...), the tokens that can be updated when 
the agent be executed (description) and the tokens to obtain information 
(module_exec, module_proc...).<br><br>

The rest of the fields should be setted from the module form like any 
other kind of module.<br><br>

Example:<br><br>

<i>
To create a module with an interval of 2 (double of the agent interval) 
we need to set it in the form, not in Configuration data.<br><br>

Te Configuration data can be simply like:<br><br>

module_begin<br>
module_name test_module<br>
module_type generic_data_string<br>
module_exec echo "TEST"<br>
module_end<br><br>

And the Interval field should be filled with a "2".
</i>
</p>

