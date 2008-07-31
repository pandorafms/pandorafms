<h1>Plugin registration</h1>

Pandora FMS Plugin registration tool is used to define what parameters needs Pandora FMS Plugin server to use in each plugin, and what kind of data pass after that parameter.
<br><br>
For example, you have a plugin to check Informix tablespace called "Aleph", under IP address "192.168.50.2" and with username "Calabria" and password "malcolm45". This plugin could return if tablespace it's ok, number of queries per second, load, fragmentation level and memory usage.
<br><br>
This plugin has the following interface:
<br>
<pre>
	informix_plugin_pandora -H ip_address -U user -P password -T tablespace -O operation
</pre>
<br>
Operation could be status, qps, load, fragment and memory. It returns a single value, used by Pandora FMS.  To define this plugin in pandora, you need to fill fields in this way:
<br><br>

<table cellpadding=4 cellspacing=4 class=databox width=80%>
<tr>
<td valign='top'>Plugin Command<td>/usr/share/pandora/util/plugins/informix_plugin_pandora (default location for plugins)
<tr>
<td>Max_timeout:<td> 15 (for example).
<tr>
<td>IP Address option:<td> -H

<tr>
<td>Port option<td>Let it blank

<tr>
<td>User option<td>-U

<tr>
<td>Password option<td>-P

</table>
<br>

When you need to create a module that uses this plugin, you need to choose plugin (this new plugin will apear in the combo to select it). And you only need to fill IP Address, Username, and Password. Pandora FMS will put this data into appopiate fields when exec external plugin.
<br><br>
There is always some kind of parameters that cannot be "generic", in this example, you have "tablespace" parameter. This is very particular for Informix, but each example could have it's own exception. You have a field called "Plugin parameter", that it's used to pass "as is" to the plugin. In this particular case you need to put "-T tablespace" there. 
<br><br>
If you want to use another tablespace, just create another module with different string after "-T".
<br><br>
Of course, in "Plugin parameter" field, you can put more than one parameter. All data entered there is passed to the plugin "as is".







