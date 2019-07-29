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
	&& ./pandora_agent_installer --install

# Set default variables
ENV SERV_DEST '127.0.0.1'
ENV REMOTE_CONF '0'
ENV AGROUP 'Servers'
ENV DEBUG '0'
ENV NAME 'agent_docker'
ENV TIMEZONE 'UTC'

# Create the entrypoint script.
RUN echo -e '#/bin/bash\n \
sed -i "s/^server_ip.*$/server_ip $SERV_DEST/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^remote_config.*$/remote_config $REMOTE_CONF/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^group.*$/group $AGROUP/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^debug.*$/debug $DEBUG/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^#agent_name.*$/agent_name $NAME/g" /etc/pandora/pandora_agent.conf\n \
sed -i "s/^#agent_alias.*$/agent_alias $NAME/g" /etc/pandora/pandora_agent.conf\n \
if [ $TIMEZONE != "" ]; then\n \
\tln -sfn /usr/share/zoneinfo/$TIMEZONE /etc/localtime\n \
fi\n \
/etc/init.d/pandora_agent_daemon start\n \
rm -f $0\n \
bash' \
>> /entrypoint.sh && \
chmod +x /entrypoint.sh

# Entrypoint + CMD
ENTRYPOINT ["bash"]
CMD ["/entrypoint.sh"]