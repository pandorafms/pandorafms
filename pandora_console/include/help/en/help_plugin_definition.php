<h1>Plugin registration</h1>

Pandora FMS Plugin registration tool is used to define what parameters Pandora FMS Plugin server needs to be used in each plugin, and what kind of data will pass after that parameter.
<br><br>
For example, if you have a plugin to check Informix tablespace called "Aleph", under IP address "192.168.50.2" and with username "Calabria" and password "malcolm45". This plugin could return if tablespace is ok, the number of queries per second, load, fragmentation level and memory usage.
<br><br>
This plugin has the following interface:
<br>
<pre>
	informix_plugin_pandora -H ip_address -U user -P password -T tablespace -O operation
</pre>
<br>
Operation could be status, qps, load, fragment and memory. It returns a single value, used by Pandora FMS.  To define this plugin in pandora, you would need to fill fields as follows:
<br><br>

<table cellpadding=4 cellspacing=4 class=databox width=80%>
<tr>
<td valign='top'>Plugin Command<td>/usr/share/pandora/util/plugins/informix_plugin_pandora (default location for plugins)
<tr>
<td>Max_timeout:<td> 15 (for example).
<tr>
<td>IP Address option:<td> -H

<tr>
<td>Port option<td>Left it blank

<tr>
<td>User option<td>-U

<tr>
<td>Password option<td>-P

</table>
<br>

If you needed to create a module that uses this plugin, you would need to choose the plugin (this new plugin would apear in the combo to be selected). Afterwards only to fill the IP Address, Username, and Password would be required. Pandora FMS will put this data into the appropiate fields when executing external plugins.
<br><br>
There are always some sort of parameters which cannot be "generic", in this scenario, you would have "tablespace" parameter. This one is very particular for Informix, but each example could have its own exception. You would have a field called "Plugin parameter", that is used to pass "as is" to the plugin. In this particular case you would need to put "-T tablespace" there. 
<br><br>
If you want to use another tablespace, just create another module with different string after "-T".
<br><br>
Of course, in "Plugin parameter" field, you can put more than one parameter. All data entered there is passed to the plugin "as is".







