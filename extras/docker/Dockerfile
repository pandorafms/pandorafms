FROM pandorafms/pandorafms-base:centos7

# Build variables.
ARG BRANCH=develop
ARG DB_PASS=pandora

# Clone the Pandora FMS repo.
RUN git clone --depth 1 -b "$BRANCH" https://github.com/pandorafms/pandorafms.git /tmp/pandorafms || \
    git clone --depth 1 -b develop https://github.com/pandorafms/pandorafms.git /tmp/pandorafms 

# Install the Pandora FMS Server.
RUN cd /tmp/pandorafms/pandora_server && \
yes | ./pandora_server_installer --install && \
sed -i "s/^dbuser.*/dbuser root/" /etc/pandora/pandora_server.conf && \
sed -i "s/^dbpass.*/dbpass $DB_PASS/" /etc/pandora/pandora_server.conf

# Install the Pandora FMS Agent.
RUN cd /tmp/pandorafms/pandora_agents/unix && \
./pandora_agent_installer --install

# Set the server's name in Apache's configuration file to avoid warnings.
RUN sed -i "s/#ServerName.*/ServerName localhost:80/" /etc/httpd/conf/httpd.conf

# Install the Pandora FMS Console.
RUN rm -rf /var/lib/mysql && mkdir -p /var/lib/mysql && \
    mkdir -p /var/log/mysql/ && chown mysql. /var/log/mysql && \
    chown mysql. -R /var/lib/mysql && \
    sudo -u mysql mysqld --initialize --explicit_defaults_for_timestamp && \
    sudo -u mysql mysqld --daemonize & \
    sleep 50 && \
    mysql_default_pass=$(cat /var/log/mysqld.log | grep "temporary password" | awk '{print $NF}')  && \
    mysqladmin -u root -p"$mysql_default_pass" --user=root password 'pandora' && \
    httpd -k start && \
    cp -r /tmp/pandorafms/pandora_console /var/www/html && \
    chown -R apache.apache /var/www/html/pandora_console/ && \
    python /tmp/pandorafms/tests/install_console.py

# Redirect HTTP requests to / to the Pandora FMS Console.
RUN echo '<meta http-equiv="refresh" content="0;url=/pandora_console">' > /var/www/html/index.html

# Create the entrypoint script.
RUN echo -e '#/bin/bash\n \
sudo -u mysql mysqld --daemonize &&\n \
httpd -k start &&\n \
/usr/sbin/crond &&\n \
/etc/init.d/pandora_agent_daemon start && \
/etc/init.d/pandora_server start && \
tail -f /var/log/pandora/pandora_server.log' \
>> /entrypoint.sh && \
chmod +x /entrypoint.sh

# Clean-up.
RUN rm -rf /tmp/pandorafms
RUN yum clean all

EXPOSE 80 3306 41121
ENTRYPOINT ["/bin/bash", "/entrypoint.sh"]
