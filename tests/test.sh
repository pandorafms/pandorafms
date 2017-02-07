#!/bin/bash
SOURCE_DIR="/tmp/pandorafms"

# Work on a clean directory when using GitLab CI.
if [ "$CI_PROJECT_DIR" != "" ]; then
	cp -r "$CI_PROJECT_DIR" "$SOURCE_DIR"
fi

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
cd $SOURCE_DIR/pandora_console && chmod +x pandora_console_install && yes | ./pandora_console_install --install
check "Installing the Pandora FMS Console" $?

# Create the Pandora FMS database.
cd $SOURCE_DIR/tests && chmod +x install_console.py && ./install_console.py
check "Creating the Pandora FMS Database" $?

# Build and install the Pandora FMS Server.
cd $SOURCE_DIR/pandora_server && perl Makefile.PL && make # Do not run make test now. Some tests need files created by pandora_server_installer.
check "Building the Pandora FMS Server" $?
cd $SOURCE_DIR/pandora_server && chmod +x pandora_server_installer && ./pandora_server_installer --install
check "Installing the Pandora FMS Server" $?
sed -i -e 's/^dbuser.*/dbuser root/' /etc/pandora/pandora_server.conf
cd $SOURCE_DIR/pandora_server && make test
check "Running tests for the Pandora FMS Server" $?

# Install the Pandora FMS Agent.
cd $SOURCE_DIR/pandora_agents/unix && chmod +x pandora_agent_installer && ./pandora_agent_installer --install
check "Installing the Pandora FMS Agent" $?

# Start Pandora FMS services.
service tentacle_serverd start
check "Starting the Tentacle Server" $?
service pandora_server start
check "Starting the Pandora FMS Server" $?
service pandora_agent_daemon start
check "Starting the Pandora FMS Agent" $?

# Disable the initial wizards.
echo "UPDATE tconfig SET value='1' WHERE token='initial_wizard'" | mysql -u root -ppandora -Dpandora
echo "UPDATE tconfig SET value='1' WHERE token='instance_registered'" | mysql -u root -ppandora -Dpandora
echo "INSERT INTO tconfig (token, value) VALUES ('skip_login_help_dialog', '1')" | mysql -u root -ppandora -Dpandora
echo "UPDATE tusuario SET middlename='1'" | mysql -u root -ppandora -Dpandora

# Run console tests.
#cd /tmp/pandorafms/tests && chmod +x run_console_tests.py && ./run_console_tests.py
#check "Running tests for the Pandora FMS Console" $?

exit 0
