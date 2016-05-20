#!/bin/bash
SOURCE_DIR="/tmp/pandorafms"

################################################
# Check the exit status of the last run command.
# Exits if it different from 0.
################################################
function check {
    MESSAGE=$1
    RC=$2
    if [ $RC == 0 ]; then
        echo ">$MESSAGE... [OK]"
    else
        echo ">$MESSAGE... [ERR $RC]"
        exit 1
    fi
}

# Start the required services.
service mysqld start && /usr/bin/mysqladmin -u root password 'pandora'
check "Starting the MySQL Server" $?
service httpd start
check "Starting the Apache Web Server" $?

# Install the Pandora FMS Console.
cd /tmp/pandorafms/pandora_console && chmod +x pandora_console_install && yes | ./pandora_console_install --install
check "Installing the Pandora FMS Console" $?

# Create the Pandora FMS database.
cd /tmp/pandorafms/tests && chmod +x install_console.py && ./install_console.py
check "Creating the Pandora FMS Database" $?

# Build and install the Pandora FMS Server.
cd /tmp/pandorafms/pandora_server && perl Makefile.PL && make && make test
check "Building the Pandora FMS Server" $?
cd /tmp/pandorafms/pandora_server && chmod +x pandora_server_installer && ./pandora_server_installer --install
check "Installing the Pandora FMS Server" $?

# Install the Pandora FMS Agent.
cd /tmp/pandorafms/pandora_agents/unix && chmod +x pandora_agent_installer && ./pandora_agent_installer --install
check "Installing the Pandora FMS Agent" $?

# Start Pandora FMS services.
service tentacle_serverd start
check "Starting the Tentacle Server" $?
service pandora_server start
check "Starting the Pandora FMS Server" $?
service pandora_agent_daemon start
check "Starting the Pandora FMS Agent" $?

exit 0
