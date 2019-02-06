<?php
/*
    Include package help/en
*/
?>
 <h1>Modules definition</h1>
<br><br>
Each piece of information that is collected should be perfectly defined in each module, using the most precise syntax. You can implement as many values as it would be necessary in order they could be collected, adding, at the end of the general parameters as many modules as the number of values to compile. Each module is composed by several directives. The list that appears bellow is a descriptive list of all available modules signals for UNIX agents (almost all of them could be also apply to the Window agent).
<br><br>
The general syntax is the following: 
<br><br>
module_begin<br>
module_name NombreDelMódulo<br>
module_type generic_data<br>
.<br>
.<br>
.<br>
module_description Ejecución del comando<br>
module_interval Número<br>
module_end <br>
<br>
There are different kinds of modules, with different suboptions, but all modules have an structure similar to this. The parameters module_interval and module_description are optionals and the rest completely compulsories. We are going to see first the common elements.
<br><br>
<h2>Common elements of all modules </h2>
<br><br>    
<b>module_begin</b>
<br><br>
Defines the beginning of the module.
<br><br>
<b>module_name 'name'</b>
<br><br>
Name of the module.
<br><br>
<b>module_type 'type'</b>
<br><br>
The data type that the module will use. There are several data types for agents: 
<br><br>
    <i>Numerical (generic_data).</i> Simple numerical data, in floating comma or wholes. If the values are floating type, these will be cut to its whole value. 
<br><br>
    <i>Incremental (generic_data_inc).</i> The whole numeric data equals to the differential being between the current value and the previous one. When this differential is negative, the value is fixed to 0. 
<br><br>
    <i>Alphanumeric (generic_data_string).</i> Collect alphanumeric text strings. 
<br><br>
    <i>Monitors (generic_proc).</i> Useful to evaluate the state of a process or service. This type of data is called monitor because it assigns 0 to a «Wrong» state and any value higher to 1 to a «Right» state. 
<br><br>
    <i>Asynchronous Alphanumeric (async_string).</i> Collect alphanumeric text string that could entry at any moment without a fixed periodicity. The rest of parameters (generic*) have a synchronous working, this is, they expect the data entry every XX time, and if they don't come then it's said that they are in an unknown state (unknown). The asynchronous modules can not be in this state. 
<br><br>
    <i>Asynchronous Monitor (async_proc).</i> Similar to the generic_proc but asynchronous.  
<br><br>
    <i>Asynchronous Numerical (async_data).</i> Similar to generic_data but asynchronous. 
<br><br>


<b>module_min 'value'</b>
<br><br>
This is the minimum valid value to data generated in this module.
<br><br>
<b>module_max 'value'</b>
<br><br>
This is the maximum valid value for data generated in this module.
<br><br>
<b>module_min_warning 'value'</b>
<br><br>
This is the minimum value that will make the module state goes to warning.
<br><br>
<b>module_max_warning 'value'</b>
<br><br>
This is the maximum value that will make the module state goes to warning.
<br><br>
<b>module_min_critical 'value'</b>
<br><br>
This is the minimum value that will make the module state goes to critical. 
<br><br>
<b>module_max_critical 'value'</b>
<br><br>
This is the maximum value that will make the module state goes to critical.
<br><br>
<b>module_disabled 'value'</b>
<br><br>
Indicates if the module is enabled (0) or disabled (1).
<br><br>
<b>module_min_ff_event 'value'</b>
<br><br>
This is the interval between new changes of state will be filtered avoiding continuos changes of module state. 
<br><br>
<b>module_description 'text'</b>
<br><br>
This guideline will be employed to add a comment to the module.
<br><br>
<b>module_interval 'factor'</b>
<br><br>
This interval is calculated as a multiplier factor for the agent interval.
<br><br>
<b>module_timeout 'secs'</b>
<br><br>
The total of seconds, agent will wait for the execution of the module, so if it takes more than XX seconds, it will abort the execution of the module
<br><br>
<b>module_postprocess 'factor'</b>
<br><br>
Same as in the definition of post processing of a module that is done from the console, here could be defined a numeric value of floating comma that will send this value to <?php echo get_product_name(); ?> in order the server will use it to multiply the received (raw) by the agent.
<br><br>
<b>module_save 'variable name'</b>
<br><br>
From version 3.2 it's possible to save the module returned value in an environment mode variable, so it could be used later in other modules. 
<br><br>
<b>module_crontab 'minute' 'hour' 'day' 'month' 'day of the week'</b>
<br><br>
Schedule modules in order they'll be executed in an specific date.
<br><br>
<b>module_condition 'operation' 'command'</b>
<br><br>
it's possible to define commands that will be executed when the module returns some specific values. ( >, < , =, !=, =~,(value, value)
<br><br>
Ex.<br>
module_begin<br>
module_name condition_test<br>
module_type generic_data<br>
module_exec echo 5<br>
module_condition (2, 8) cmd.exe /c script.bat<br>
module_end<br>
<br>
<b>module_precondition 'operación' 'comando'</b>
<br><br>
If the precondition is true, the module will run.
<br><br>
Ej.<br>

module_begin<br>
module_name Precondition_test1<br>
module_type generic_data<br>
module_precondition (2, 8) echo 5<br>
module_exec monitoring_variable.bat<br>
module_end<br>
<br>
<b>module_unit 'value'</b>
<br><br>
This is a the unit of the value retrieved by the module. 
<br><br>
<b>module_group 'value'</b>
<br><br>
This is the name of the module group. If the group doesnt exist the module will be created without module assigned. 
<br><br>
<b>module_custom_id 'value'</b>
<br><br>
This is a custom identifier for the module. 
<br><br>
<b>module_str_warning 'value'</b>
<br><br>
This is a regular expression to define the Warning status in the string types modules.
<br><br>
<b>module_str_critical 'value'</b>
<br><br>
This is a regular expression to define the Critical status in the string types modules. 
<br><br>
<b>module_warning_instructions 'value'</b>
<br><br>
This is instructions to the operator when the modules changes to Warning status.
<br><br>
<b>module_critical_instructions 'value'</b>
<br><br>
This is instructions to the operator when the modules changes to Critical status.
<br><br>
<b>module_unknown_instructions 'value'</b>
<br><br>
This is instructions to the operator when the modules changes to Unknown status. 
<br><br>
<b>module_tags 'value'</b>
<br><br>
This is the tags that will be assigned to module separated by commas. Will be assigned only the tags that exist in system.
<br><br>
<b>module_warning_inverse 'value'</b>
<br><br>
This is a flag (0/1) that when is activated the Warning threshold will be the inverse of the defined 
<br><br>
<b>module_critical_inverse 'value'</b>
<br><br>
This is a flag (0/1) that when is activated the Critical threshold will be the inverse of the defined 
<br><br>
<b>module_quiet 'value'</b>
<br><br>
This is a flag (0/1) that when is activated the module will be in quiet mode (it will not generate event or alerts)
<br><br>
<b>module_ff_event 'value'</b>
<br><br>
This is the flip flip execution threshold of the module (in seconds)
<br><br>
<b>module_macro'macro' 'value'</b>
<br><br>
This is a macro generated by the console with the components macros system. Set this parameter from the configuration file is useless because it is only for modules created with local components. 
<br><br>

<b>module_end</b>
<br><br>
Defines the end of the module.
<br><br>
<h2>Specific guidelines to obtain information </h2>
<br><br>
<b>module_exec 'comando'</b>
<br><br>
This is the general way to gather information by executing a command. 
<br><br>
<b>module_service 'service'</b>
<br><br>
Checks if an specific service is being executed at the machine.
<br><br>
<b>module_watchdog</b>
<br><br>
A Watchdog is a system that allows to act immediately when an agent is down, usually picking up the process that is down . 
<br><br>
Ex.<br>
module_begin<br>
module_name ServiceSched<br>
module_type generic_proc<br>
module_service Schedule<br>
module_description Service Task scheduler<br>
module_async yes<br>
module_watchdog yes<br>
module_end<br>
<br>
Only works in Windows system.
<br><br>
<b>module_proc 'proceso'</b>
<br><br>
Checks if an specific name of process is working in this machine
<br><br>
Ex.<br>
module_begin<br>
module_name CMDProcess<br>
module_type generic_proc<br>
module_proc cmd.exe<br>
module_description Process Command line<br>
module_end<br>
<br>

<b>Modo asíncrono<br><br></b>

In a similar way to the services, monitoring processes can be critical in some cases.
<br><br>
module_begin<br>
module_name Notepad<br>
module_type generic_data<br>
module_proc notepad.exe<br>
module_description Notepad<br>
module_async yes<br>
module_end<br>
<br>
<b>Processes Watchdog</b>
<br><br>
A Watchdog is a system that allows to act immediately when an agent is down, usually picking up the process that is down . The <?php echo get_product_name(); ?> Windows agent could act as Watchdog when a process is down. This is called watchdog mode for the process: 
<br><br>
Executing a process could need some parameters, so there are some additional configuration options for these kind of modules. It is important to say that the watchdog mode only works when the module type is asynchronous. Let's see an example of configuration of a module_proc with watchdog.
<br><br>
module_begin<br>
module_name Notepad<br>
module_type generic_data<br>
module_proc notepad.exe<br>
module_description Notepad<br>
module_async yes<br>
module_watchdog yes<br>
module_start_command c:\windows\notepad.exe<br>
module_startdelay 3000<br>
module_retrydelay 2000<br>
module_retries 5<br>
module_end<br>
<br>
This is the definition of the additional parameters for module_proc with watchdog: 
<br><br>
    <i>module_retries:</i> Number of consecutive attempts for the module will try to start the process before deactivating the watchdog. If the limit is achieved , then the watchdog device for this module will be deactivated and will never try to start the process, even if the process is recovered by the user ( at last until the agent will be reboot). By default there is no limit for the nº of reattempts of the watchdog. 
<br><br>
    <i>module_startdelay:</i> Number of milliseconds the module will wait before starting the process by first time. If the process takes lot of time at starting , then it will be a great idea to order the agent through this parameter that it "wait" until start checking again if the process has got up. In this example wait 3 seconds.
<br><br>
    <i>module_retrydelay:</i> Similar to the previous one but for subsequent falls/reattempts, after having detect a fall. When <?php echo get_product_name(); ?> detects a fall, relaunch the process, wait the nº of milliseconds pointed out in this parameter and check again if the process is already up.  
<br><br>
<b>module_cpuproc 'process'</b>
<br><br>
Return the CPU usage of a specific process.(Unix)
<br><br>
<b>module_memproc 'process'</b>
<br><br>
Return the memory used by a specific process. (Unix)
<br><br>
<b>module_freedisk 'unit_letter:'|'volume'</b>
<br><br>
It checks the free space in the disk unit
<br><br>
<b>module_freepercentdisk 'unit_letter:'|'volume'</b>
<br><br>
This module returns the free disk percentage in a windows unit: (don't forget the ":") or on a Unix system, the volume, like /var.
<br><br>
<b>module_occupiedpercentdisk 'volume'</b>
<br><br>
This module returns the occupied disk percentage in a Unix volume, like /var. 
<br><br>
<b>module_cpuusage ['cpu id']</b>
<br><br>
It gives back the CPU usage in a CPU number. If there is only one CPU, let it blank or use the 'all'.
<br><br>
<b>module_freememory</b>
<br><br>
Gives back the free memory in the whole system.
<br><br>
<b>module_freepercentmemory</b>
<br><br>
This module gives back the free memory percentage in one system: 
<br><br>
<b>module_tcpcheck</b>
<br><br>
This module tries to connect with the IP and port specified.It returns 1 if it had success and 0 if it had other way.You should specify a time out. (module_timeout)(Win)
<br><br>
<b>module_regexp</b>
<br><br>
This module monitors a record file (log) looking for coincidences using regular expressions, ruling out the already existing lines when starting the monitoring . The data returned by the module depends on the module type: 
<br><br>
    <i>generic_data_string, async_string:</i> Gives back all the lines that fit with the regular expression. <br>
    <i>generic_data:</i> Gives back the number of lines that fit with the regular expression. <br>
    <i>generic_proc:</i> Gives back 1 if there is any coincidence, 0 if other way. <br>
    <i>module_noseekeof:</i> With a 0 value by default, with this configuration token active, in each module execution, independently from any modification the target file suffers, the module will restart its check process without searching for the EOF flag of the file, so it will always extract to the XML output all those lines matching our search pattern. 
<br><br>
<b>module_wmiquery</b>
<br><br>
The WMI modules allow to execute locally any WMI query without using an external tool. It is configured through two parameters:
<br><br>
    <i>module_wmiquery:</i> WQL query used.Several lines could be obtained as a result, that will be placed as several data.<br>
    <i>module_wmicolumn:</i> Name of the column that that is going to be used as a data source.
<br><br>
<b>module_perfcounter</b>
<br><br>
Obtains data from the performance counter (Win)
<br><br>
<b>module_inventory</b>
<br><br>
This module obtains information about the different aspects of a machine. From software to hardware. (Win)
<br><br>
Ex<br>
module_begin<br>
module_name Inventory<br>
module_interval 7 (dias)<br>
module_type generic_data_string<br>
module_inventory RAM Patches Software Services Cpu CDROM Video NICs <br>
module_description Inventory<br>
module_end<br>
<br>
<b>module_logevent</b>
<br><br>
Allows to obtain information from the Window event log file.
<br><br>
module_begin<br>
module_name MyEvent<br>
module_type async_string<br>
module_logevent<br>
module_source 'logName'<br>
module_eventtype 'event_type/level'<br>
module_eventcode 'event_id'<br>
module_application 'source'<br>
module_pattern 'text substring to match'<br>
module_description<br>
module_end<br>
<br>
<b>module_plugin</b>
<br><br>
Is a parameter to define the data that is obtained as an exit of a plugin agent. It is an special case of module. 
<br><br>
<b>module_ping 'host'</b>
<br><br>
This module pings the given host and returns 1 if it is up, 0 otherwise.(Win)
<br><br>
Ex.<br>

module_begin<br>
module_name Ping<br>
module_type generic_proc<br>
module_ping 192.168.1.1<br>
module_ping_count 2 (Number of ECHO_REQUEST packets to be sent)<br>
module_ping_timeout 500 (Timeout in milliseconds to wait for each reply)<br>
module_end<br>
<br>
<b>module_snmpget<br></b>
<br>
This module performs an SNMP get query and returns the requested value.<br>
<br>
Ex.<br>
module_begin<br>
module_name SNMP get<br>
module_type generic_data<br>
module_snmpget<br>
module_snmpversion 1<br>
module_snmp_community public<br>
module_snmp_agent 192.168.1.1<br>
module_snmp_oid .1.3.6.1.2.1.2.2.1.1.148<br>
module_end<br>




