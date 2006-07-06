==== WARNING ====

This binary files should only be used in testing and not in a stable system. We are
not responsable of the damage it can provoke.

==== WARNING ====


== Pre-installation ==

Before running or installation of Pandora Windows agent, you must create the
configuration directory and extract the PandoraBin.zip file into it.

It does not matter where it is installled, because Pandora Agent will 
adapt to any local directory. This directory should have this content:

\pandora_agent.conf   :: Pandora Windows Agent main configuration
\key\                 :: Directory which holds the private and public key files
\key\id_dsa           :: Private key to access the Pandora server using SSH
\key\id_dsa.pub       :: Public key to access the Pandora server using SSH

Optionally, it could have:

\utils\	       	      :: Directory where the user could put misc utils to use
		         whith modules exec type. I.e. UNIX-like tools (cut,
			 grep, etc...)

== Installation ==

Notice: At this moment, the installation of the Pandora Windows Agent must
        be done manually.

To install the Pandora Windows Agent execute this sentence in a Windows command
line:

    PandoraAgent.exe --install

The Agent will be installed into the Windows services system. You can check it
on Control Panel -> Administrative tools -> Services.

To run the Agent open the "Services" dialog (Control Panel -> Administrative
tools-> Services), search the "Pandora Agent" service and run it clicking the
play button.

To stop the service, open the "Services" dialog, search the "Pandora Agent" and
click the stop button.

To uninstall the Pandora Windows Agent, execute this sentence in a Windows
command line:

    PandoraAgent.exe --uninstall


== Output ==

You can check the Pandora Windows Agent output in the
C:\Pandora\pandora-debug.dbg file, that is a plain text file and includes info
about the execution flow of the Agent.


== Configuration files ==

  * pandora_agent.conf

   This file is a list of keys/values pairs. Here is an example of this file.

   	# Begin of pandora_agent.conf example
	# The comments begin with the '#' character
	# IP of the Pandora server
	server_ip       192.168.50.1
	# Remote path to copy the data
	server_path     /opt/pandora/data_in/
	# Local path to the temporal directory
	temporal        "C:\temp files"
	# Interval between executions (in seconds)
	interval        60
	# Name of the agent
	agent_name      antiriad
	# End of pandora_agent.conf example

  * id_dsa, id_dsa.pub

   These files must be generated using SSH utils and the Pandora server must
   be configured to allow accessing the Pandora user ("pandora" by default)
   using this pair of public/private keys.
