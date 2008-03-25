Pandora FMS Agents
==================

Understanding what is a Pandora FMS Agent
-----------------------------------------

Pandora FMS agents collect all system's data. They are executed in each local system, although they can also collect remote information by installing monitoring systems for the agent in several different machines - called satellite agents.

They are developed to work under a given platform, making use of the specific tools of the language being used: VBSCript/Windows Scripting for Microsoft platforms (Win2000, WinXP y Win2003), ShellScripting for UNIX - which includes GNU/Linux, Solaris, AIX, HP-UX and BSD, as well as the Nokia's IPSO. Pandora agents can be developed in virtually any language, given its simple API system and being open source. There are branches of the Pandora project started for the creation of agents in Posix C, Perl and Java for those systems requiring closed agents.

Pandora Agents are Free Software, i.e., the way agents collect and sent information is documented. An agent can be recreated in any programming language, and can be upgraded easily, to improve aspects of the program not covered so far.

This document describes the installation of agents in machines running over Windows and Unix operating systems.

Generic role of the agents
--------------------------

Regardless the platform an agent is running on, this is formed of the following elements:

A script (or binary application in Windows) that collects and sends the data to the server. For UNIX machines the script is called pandora_agent.sh and is executed directly from the Pandora agent folder.

One or several configuration files where the values to be collected are defined. The file is called pandora_agent.conf both for Windows and Unix machines.

This simple structure makes it easy the customisation of an agent. There is no need to code again the agent to modify the way it works, as the configuration file holds most of the parameters needed to do so.

Pandora FMS Agent configuration
------------------------------

Main program
~~~~~~~~~~~~

The main script is the executable file that collects the data specified in the configuration file. It sends the data to the server in XML. In Windows machines application is installed as a service and is executed at the time intervals set in the configuration file. In machines running over UNIX the main script is run through a special script called pandora_agent, and runs continuously in the machine as a process.

Configuration File
~~~~~~~~~~~~~~~~~~

The data collection in the host system is the gathering of independent data units, which are defined in the /etc/pandora/pandora_agent.conf file. The pandora_agent.conf file is divided in two parts:

    * General parameters: Configure general options about server location, agent name, interval, and other general options. 

    * Module definitions: Configure and define the method of extraction for each piece of information that will be extracted from local host and sent to Pandora Server. 

General parameters
~~~~~~~~~~~~~~~~~~

The general parameters of the agent configuration are defined in this section. Some of these parameters are common for all systems and others specific for Windows or UNIX. The general parameters are:

    * server_path: The server path is the full path of the folder where the server stores the data sent by the agent. It is usually /var/spool/pandora/data_in. 

    * server_ip: The server IP is the IP address or the host name of the Pandora server, where the data will be stored. The host must be reachable and must be listening to port 22 (SSH). 

    * temporal: This is the full path of the folder where the agent stores the data locally, before it is sent to the server. It must be said that the data packages are deleted once the agent tries to contact Pandora server, no matter if the communication was successful or not. This is done to avoid over flooding hard drive of the host system where the agent runs. The location of the local folder varies with the architecture of the host system. In Unix systems this is usually /var/spool/pandora/data_out, and in Windows systems C:\program files\pandora\data_out. 

    * interval: This is the time interval in seconds in which the agent will collect data from the host system and send the data packages to the server. The recommended value ranges from 300 (5 minutes) to 600 (10 minutes). This number could be larger, although it is important to consider the impact of a larger number on the database. 

    * debug: This parameter is used to test the generation of data files, forcing the agent to do not copy data file to server, so you can check data file contents and copy XML data file manually. It does not delete any data when the process is finished, so data file will be in temp directory. The activity is written in a log file. The file is named pandora_agent.log. This log file can be used to test the system and to investigate potential issues. 

    * agent_name: This is an alternative host name. This parameter is optional as if it is not declared the name is obtained directly from the system. 

    * checksum: This parameter can take two values. If the value is 1, the checksums are performed through MD5. If the value is 0, the checksum is not performed at all. This may be useful for systems where a MD5 tool cannot be implemented. If the checksum is deactivated in the agent it must be also disconnected in the server. Otherwise it could create problems. 

    * Transfer Mode: This parametrer let you specify which transfer mode is going to be set up to send the agent data to the server. Modes available are: ssh (using scp), ftp or local. Local mode it is only for systems where the agent run in the same machine as the server does, cause it is basically a copy between directories. 

	* server_pwd: Specify password for FTP transfer mode (Windows only).
	
    * encoding: Set the encoding type of your local system, like iso-8859-15, or utf-8. 

    * Pandora Nice: This parametrer let you specify the priority the Pandora Agent process will have in your system. 

An example of the general parameters from a Unix configuration would be:

	server_ip    192.168.12.12
	server_path  /var/spool/pandora/data_in
	temporal     /var/spool/pandora/data_out
	interval     300
	agent_name   dakotaSR01
	debug 	   0
	checksum     0

Module definition
-----------------

Each data item that is to be collected must be defined precisely in each module, using the exact syntax. As many values as necessary can be set to be collected, adding at the end of the general parameters as many modules as the number of values to collect. Each module is made of several directives. Following is a descriptive relation of all module marks available for Unix agents (almost all of them are applicable to Windows Agent too).


module_begin

Defines the beginning of the module.

module_name <name>

Name of the module. This is the id for this module, choose a name without blank spaces and not very long. There is no practical limitation (max of 250 chars) but will be more easier to manage if you use short names. This name CANNOT be duplicated with a similar name in the same agent. This name could be duplicated with other modules in other agents.

module_type <type>

Data type the module will handle. There are four data types for agents:

    * Numeric (generic_data). Simple numeric data, float or integer. If the values are of the float type, they will be truncated to their integer value. 

    * Incremental (generic_date_inc). Integer numeric data equal to the differential between the actual value and the previous one. When this differential is negative the value is set to 0. 

    * Alphanumeric (generic_string). Text strings up to 255 characters. 

    * Monitors (generic_proc). Stores numerically the status of the processes. This data type is called monitor because it assigns 0 to an "Incorrect" status and any value above 0 to any "Correct" status. 

module_exec <command>

This is the generic "command to execute" directive. Both, for Unix and Windows agents there is only one directive to obtain data in a generic way, executing a single command (you could use pipes for redirecting execution to anoter command). This directive executes a command and stores the returned value. This method is also available on Windows agents. This is the "general purpose method" for both kind of agents.

For a Windows agent there are more directives to obtain data, who are described following this lines.


module_service <service>

(Win32 Only)

Checks if a given service name is running in this host. Remember to use " " characters if service name contains blank spaces.


module_proc <process>

(Win32 Only)

Checks if a given processname is running in this host. If the process name contains blank spaces do not use " ". Also notice that the process name must have the .exe extension. The module will return the number of process running with this name.

module_freedisk <drive_letter:>

(Win32 Only)

Checks free disk on drive letter (do not forget ":" after drive letter).

module_cpuusage <cpu id>

(Win32 Only)

Returns CPU usage on CPU number cpu. If you only have one cpu, use 0 as value.

module_freememory

(Win32 Only)

Return free memory in the whole system.

module_min <value>

This is the minimum valid value for the data generated in this module. If the module has not yet been defined in the web console this value will be taken from this directive. This directive is not compulsory. This value does not override the value defined in the agent if the module does not exist in the management console is created automatically when working on learning mode.

module_max <value>

It is the maximum valid value for the data generated in this module. If the module has not been defined in the web console this value will be taken from this directive. This directive is not compulsory and is not supported by the Windows agent. This value does not override the value defined in the agent if the module does not exist in the management console. This is created automatically when working on learning mode.


module_description <text>

This directive is used to add a comment to the module. This directive is not compulsory. This value does not override the value defined in the agent if the module does not exist in the management console. This is created automatically when working on learning mode.


module_interval <factor>

Since Pandora 1.2 introduces this new feature. You can, for each module, setup its own interval. This interval its calculated as a multiply factor for agent interval. For example, if your agent has interval 300 (5 minutes), and you want a module only be calculated each 15 minutes, you could add this line: module_interval 3. So this module will be calculated each 300sec x 3 = 900sec (15 minutes).


module_end

Ends module definition


Examples

An example of a Windows module, checking if EventLog service is alive, would be:

       module_begin
       module_name ServicioReg
       module_type generic_proc
       module_service Eventlog
       module_description Eventlog service availability
       module_end

An example of a Unix module would be:

       module_begin
       module_name cpu_user
       module_type generic_data
       module_exec vmstat | tail -1 | awk '{ print $14 }'
       module_min 0
       module_max 100
       module_description User CPU
       module_end


Agent types
===========

It is possible to monitor virtually any system with Pandora. This can be done either with a local agent collecting data directly from the system to be monitored, using a a satellite agent collecting data from a system by SNMP or using the new Pandora 1.2 agents, the remote agents, who can chack using remote network polling (TCP, UCP, ICMP/PING and SNMP) remote services, from the Pandora Network Server.

The local agents can be either Windows or Unix agents. The satellite agents can be implemented using any of the agents above. The modules are configured to collect data from the external system by, for example, an SNMPGET tool.

Pandora FMS Windows Agents
--------------------------

Build Pandora FMS Windows Agent from sources

In order to build from sources, you will need the latest Dev-Cpp IDE version, with the MinGW tools. Download from http://www.bloodshed.net/devcpp.html

Open PandoraService.dev with Dev-Cpp and construct the project. Everything should compile fine in a default installation.

Pandora FMS Windows Agent installation (installer)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Starting with Pandora FMS v1.2.0, Windows version comes with an automated installer, provided with excelent freesoftware Install Jammer, so install now is very easy. You only need to choose a destination path, install and generate manually SSH keys as described below. For personalized or corporate deployments, you also can create your own installer (we provide install jammer sources for creating your own installable, so you can include a set of SSH keys in your own installer package).

Creating SSH keys with Windows Agents

Go to .\util of your Pandora FMS agent for Windows and run puttygen.exe. Choose option "Generate keys, SSH-2_DSA, 1024".

Press Generate. Export key to OpenSSH key (Pandora's SSH implementation uses a port of OpenSSH).

We have no chosen password, so press YES:

Save it as C:\Program Files\Pandora_Agent\keys\id_dsa

Now let's copy the public key to clipboard and paste it as C:\Program Files\Pandora_Agent\keys\id_dsa.pub, and also to /home/pandora/.ssh/authorized_keys file in server to establish a correct SSH automatic key authentication.


Manual Pandora FMS Windows Agent installation (without installer)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Before running or installation of Pandora Windows service, you must create the configuration directory and extract the PandoraBin.zip file into it. It doesn't matter where it is installled, because Pandora Agent will adapt to any local directory. In the examples, the application will be installed in C:\Pandora\

This directory will hold the configuration files, which are:

c:\Pandora\pandora_agent.conf  :: Pandoramain configuration c:\Pandora\id_dsa  :: Private SSH key c:\Pandora\id_dsa.pub  :: Public SSH key

To install manually (without installer) the Pandora FMS Windows Agent execute this sentence in a Windows command line:

PandoraService.exe --install

The Agent will be installed into the Windows services system. You can check it on Control Panel -> Administrative tools -> Services.

To run the Agent open the "Services" dialog (Control Panel -> Administrative tools-> Services), search the "Pandora Service" service and run it clicking the play button. To stop the service, open the "Services" dialog, search the "Pandora Service" and click the stop button.

To uninstall the Pandora Windows Agent, execute this sentence in a Windows command line:

PandoraService.exe --uninstall

Windows Agent testing
~~~~~~~~~~~~~~~~~~~~~

You can check the Pandora Windows Agent output in the C:\pandora\pandora-debug.dbg file, that is a plain text file and includes info about the execution flow of the Agent.

To test that SSH is working correctly, you can use the --test-ssh parameter in the executable file. This force pandora to conect using internal SSH and copy a file called "ssh.test".

Windows Agent configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

All setup is made in pandora_agent.conf. This file is a list of keys/values pairs. Here is an example of this file.

         # General Parameters
         # ==================

         server_ip 127.0.0.1
         server_path /var/spool/pandora/data_in
         temporal "c:\windows\temp"
         interval 300
         agent_name localhost
         transfer_mode ftp
         server_pwd pandora123

         # Module Definition
         # =================


         # Counting OpenedConnections (check the language string)
         module_begin
         module_name OpenNetConnections
         module_type generic_data
         module_exec netstat -na | grep ESTAB | wc -l | tr -d " "
         module_description Conexiones abiertas (interval 2)
         module_interval 2
         module_end

         # Is Schedule service running ?
         module_begin
         module_name ServicioProg
         module_type generic_proc
         module_service Schedule
         module_description Servicio Programador de tareas
         module_end

         # Is Eventlog service running ?
         module_begin
         module_name ServicioReg
         module_type generic_proc
         module_service Eventlog
         module_description Servicio Registro de sucesos
         module_end

         # Is lsass.exe process alive ?
         module_begin
         module_name Proc_lsass
         module_type generic_proc
         module_proc lsass.exe
         module_description LSASS.exe process.
         module_end

         # Received packets.
         # Please notice that "Paquetes recibidos" string must be replaced by
         # the correct string in your Windows system language.
         module_begin
         module_name ReceivedPackets
         module_type generic_data
         module_exec netstat -s | grep  "Paquetes recibidos  "|
                     tr -d " " | cut -f 2 -d "=" | tr -d "\n"
         module_description Conexiones abiertas (interval 2)
         module_end

         # Free space on disk
         module_begin
         module_name FreeDiskC
         module_type generic_data
         module_freedisk C:
         module_description Free space on drive C:
         module_end

         # CPU usage percentage
         module_begin
         module_name CPUUse0
         module_type generic_data
         module_cpuusage 0
         module_description CPU#0 usage
         module_end

         module_begin
         module_name FreeMemory
         module_type generic_data
         module_freememory
         module_description Amount of free memory.
         module_end
