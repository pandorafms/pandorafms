FROM centos:centos7
MAINTAINER Pandora FMS Team <info@pandorafms.com>

# Add Pandora FMS agent installer
ADD unix /tmp/pandora_agent/unix

# Install dependencies
RUN yum -y install \
	epel-release \
	unzip \
	perl \
	sed \
	"perl(Sys::Syslog)"

# Install Pandora FMS agent
RUN cd /tmp/pandora_agent/unix \
	&& chmod +x pandora_agent_installer \
	&& ./pandora_agent_installer --install

# Set default variables
ENV SERVER_IP '127.0.0.1'
ENV REMOTE_CONFIG '0'
ENV GROUP 'Servers'
ENV DEBUG '0'
ENV AGENT_NAME 'agent_docker'
ENV AGENT_ALIAS 'agent_docker'
ENV TIMEZONE 'UTC'
ENV SECONDARY_GROUPS ''

# Create the entrypoint script.
RUN echo -e '#/bin/bash\n \
sed -i "s/^server_ip.*$/server_ip $SERVER_IP/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^remote_config.*$/remote_config $REMOTE_CONFIG/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^group.*$/group $GROUP/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^debug.*$/debug $DEBUG/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^#agent_name.*$/agent_name $AGENT_NAME/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^#agent_alias.*$/agent_alias $AGENT_ALIAS/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^# secondary_groups.*$/secondary_groups $SECONDARY_GROUPS/g" /etc/pandora/pandora_agent.conf\n \
if [ $TIMEZONE != "" ]; then\n \
\tln -sfn /usr/share/zoneinfo/$TIMEZONE /etc/localtime\n \
fi\n \
/etc/init.d/pandora_agent_daemon start\n \
rm -f $0\n \
tail -f /var/log/pandora/pandora_agent.log' \
>> /entrypoint.sh && \
chmod +x /entrypoint.sh

# Entrypoint + CMD
ENTRYPOINT ["bash"]
CMD ["/entrypoint.sh"]