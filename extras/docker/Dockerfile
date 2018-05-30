FROM pandorafms/pandorafms-base

# Build variables.
ARG BRANCH=develop
ARG DB_PASS=pandora

# Clone the Pandora FMS repo.
RUN git clone --depth 1 -b "$BRANCH" https://github.com/pandorafms/pandorafms.git /tmp/pandorafms

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
RUN service mysqld start && \
/usr/bin/mysqladmin -u root password "$DB_PASS" && \
service httpd start && \
cp -r /tmp/pandorafms/pandora_console /var/www/html && \
chown -R apache.apache /var/www/html/pandora_console/ && \
python /tmp/pandorafms/tests/install_console.py

# Redirect HTTP requests to / to the Pandora FMS Console.
RUN echo '<meta http-equiv="refresh" content="0;url=/pandora_console">' > /var/www/html/index.html

# Create the entrypoint script.
RUN echo -e '#/bin/bash\n \
service mysqld start &&\n \
service httpd start &&\n \
service crond start &&\n \
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
