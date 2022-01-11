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
rm -rf /var/lib/mysql/* && sudo -u mysql mysqld --initialize-insecure && mysqld --user=mysql --sql-mode="" --daemonize=ON && /usr/bin/mysqladmin -u root password 'pandora'
check "Starting the MySQL Server" $?

# PHP FPM
# Customize php.ini
echo "php_value[error_reporting] = E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_USER_WARNING" >> /etc/php-fpm.d/www.conf
echo "php_value[max_execution_time] = 0" >> /etc/php-fpm.d/www.conf
echo "php_value[max_input_time] = -1" >> /etc/php-fpm.d/www.conf
echo "php_value[upload_max_filesize] = 800M" >> /etc/php-fpm.d/www.conf
echo "php_value[post_max_size] = 800M" >> /etc/php-fpm.d/www.conf
echo "php_value[memory_limit] = -1" >> /etc/php-fpm.d/www.conf

mkdir -p /run/php-fpm/ 2>/dev/null
/usr/sbin/php-fpm
check "Starting PHP-FPM" $?

httpd
check "Starting the Apache Web Server" $?

# Install the Pandora FMS Console.
cd /tmp/pandorafms/pandora_console && chmod +x pandora_console_install && yes | ./pandora_console_install --install
check "Installing the Pandora FMS Console" $?

# Create the Pandora FMS database.
cd /tmp/pandorafms/tests && chmod +x install_console.py && ./install_console.py
check "Creating the Pandora FMS Database" $?

# Build and install the Pandora FMS Server.
cd /tmp/pandorafms/pandora_server && perl Makefile.PL && make # Do not run make test now. Some tests need files created by pandora_server_installer.
check "Building the Pandora FMS Server" $?
cd /tmp/pandorafms/pandora_server && chmod +x pandora_server_installer && ./pandora_server_installer --install
check "Installing the Pandora FMS Server" $?
sed -i -e 's/^dbuser.*/dbuser root/' /etc/pandora/pandora_server.conf
cd /tmp/pandorafms/pandora_server && make test
check "Running tests for the Pandora FMS Server" $?

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

# Disable the initial wizards.
echo "UPDATE tconfig SET value='1' WHERE token='initial_wizard'" | mysql -u root -ppandora -Dpandora
echo "UPDATE tconfig SET value='1' WHERE token='instance_registered'" | mysql -u root -ppandora -Dpandora
echo "INSERT INTO tconfig (token, value) VALUES ('skip_login_help_dialog', '1')" | mysql -u root -ppandora -Dpandora
echo "UPDATE tusuario SET middlename='1'" | mysql -u root -ppandora -Dpandora

# Run console tests.
#cd /tmp/pandorafms/tests && chmod +x run_console_tests.py && ./run_console_tests.py
#check "Running tests for the Pandora FMS Console" $?

exit 0
