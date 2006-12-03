Pandora FMS Windows Agent v1.2
==============================

For mode information, please refer to documentation, you can get it on our proyect website at http://pandora.sourceforge.net

This is the Windows agent installer, programmed in C++ with GNU tools and gcc.

The binary included is PandoraAgent.exe, that will run as a Windows Service.
This should be done by the installer, if you have any problem, run:

	PandoraAgent.exe --install

You can test the SSH configuration of the agent with command line:

	PandoraAgent.exe --test-ssh
	
The directory structure that will be created is something like:

Pandora_Agent\pandora_agent.conf   :: Pandora Windows Agent main configuration
Pandora_Agent\key\                 :: Directory which holds the private and 
				      public key files
Pandora_Agent\key\id_dsa           :: Private key to access the Pandora server 
				      using SSH
Pandora_Agent\key\id_dsa.pub       :: Public key to access the Pandora server 
			              using SSH

You also have:

Pandora_Agent\utils\	           :: Directory where the user could put 
                                      misc utils to use with exec type modules  
                                      I.e. UNIX-like tools (cut, grep, etc...)
										
In the configuration file pandora_agent.conf, you can find 
these directives (this is a small resume, more information at
 http://pandora.sourceforge.net):

server_ip - Hostname or Pandora Server IP where the gathered data will be sent.

server_path - Path where the server will store the data sent by agent. 
Usually is "/var/spool/pandora/data_in".

temporal - Path where the agent stores locally data before send them 
to the Pandora Server. The agent deletes the data every time it tries 
to connect to Pandora Server.

interval - Period in seconds between every time the agent sends data 
to Pandora Server. Usually its value is 300 (5 minutes).

agent_name - Alternative name of the Agent. This directive is optional,
 the name is taken from the system where the agent runs.

hostname - Alternative name of the host. This directive is optional,
 the name is taken from the system where the agent runs.

private_key - Path to the pandora agent private key.

module_begin - Beginning of a module

module_end - End of a module

module_name - Name for the identification of the module

module_type - Type of data of the module:

	- generic_data - It's a simple numeric, floating point or integer.

	- generic_data_inc - It's an integer numeric, difference between the data 
          collected previously with the data collected at that moment.

	- generic_data_string - Text String.

	- generic_proc - It stores the state of processes numerically. 
          Its value is 0 for a "bad" state and any number greater than 0 for the 
          "good" state.

module_description - Description of the module :-D

For mode information, please refer to documentation, you can get it on our proyect website at http://pandora.sourceforge.net