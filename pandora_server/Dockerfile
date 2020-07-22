FROM centos:centos6 AS develop
LABEL maintainer="Pandora FMS Team <info@pandorafms.com>"

#Clone the repo and perform initial install
RUN yum install -y git && \
	mkdir -p /tmp/pandorafms && cd /tmp/pandorafms && git init && \
	git remote add origin https://github.com/pandorafms/pandorafms.git && git config core.sparsecheckout true && \
	echo "pandora_console/" >> .git/info/sparse-checkout && echo "pandora_server/" >> .git/info/sparse-checkout && \
	git pull --depth=1 origin develop

FROM centos:centos6
LABEL maintainer="Pandora FMS Team <info@pandorafms.com>"

RUN { \
	echo '[rpmforge]'; \
	echo 'name = RHEL $releasever - RPMforge.net - dag'; \
	echo 'baseurl = http://apt.sw.be/redhat/el6/en/$basearch/rpmforge'; \
	echo 'mirrorlist = http://mirrorlist.repoforge.org/el6/mirrors-rpmforge'; \
	echo 'enabled=1'; \
	echo 'gpgcheck=0'; \
} > /etc/yum.repos.d/rpmforge.repo

RUN yum -y update && \
	yum install -y \
	cronie \
	ntp \
	wget \
	curl \
	xterm \
	postfix \
	wmic \
	perl-HTML-Tree \
	perl-DBI \
	perl-DBD-mysql \
	perl-libwww-perl \
	perl-XML-Simple \
	perl-XML-SAX \
	perl-NetAddr-IP \
	perl-Scope-Guard \
	net-snmp \
	net-tools \
	perl-IO-Socket-INET6 \
	perl-Socket6 \
	nmap \
	sudo \
	xprobe2 \
	make \
	perl-CPAN \
	perl-JSON \
	net-snmp-perl \
	perl-Time-HiRes \
	perl-XML-Twig \
	net-snmp \
	net-snmp-utils \
	https://rpmfind.net/linux/epel/6/x86_64/Packages/p/perl-Encode-Locale-1.03-5.el6.noarch.rpm \
	http://rpmfind.net/linux/centos/6.10/os/x86_64/Packages/gettext-0.17-18.el6.x86_64.rpm \
	http://ftp.tu-chemnitz.de/pub/linux/dag/redhat/el6/en/x86_64/rpmforge/RPMS/perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm && \
	yum clean all

COPY --from=develop /tmp/pandorafms/pandora_server /tmp/pandorafms/pandora_server

# Install
RUN /usr/sbin/useradd -d /home/pandora -s /bin/false -M -g 0 pandora && \
    cd /tmp/pandorafms/pandora_server && ./pandora_server_installer --install

#Exposing ports for: Tentacle protocol
EXPOSE 41121

# Simple startup script to avoid some issues observed with container restart
ADD docker_entrypoint.sh /entrypoint.sh
ADD generate_conf_from_env.sh /generate_conf_from_env.sh
ADD run_cron.sh /run_cron.sh
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /wait-for-it.sh
RUN rm -rf /tmp/pandorafms && rm -rf /tmp/* && rm -rf /var/tmp/* && \
    chmod -v +x /entrypoint.sh && chmod -v +x /wait-for-it.sh

RUN wget http://rpmfind.net/linux/centos/6.10/os/x86_64/Packages/gettext-0.17-18.el6.x86_64.rpm; \
	yum localinstall -y gettext-0.17-18.el6.x86_64.rpm; \
	rm -rf gettext-0.17-18.el6.x86_64.rpm; \
	wget http://ftp.tu-chemnitz.de/pub/linux/dag/redhat/el6/en/x86_64/rpmforge/RPMS/perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm; \
	yum localinstall -y perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm; \
	rm -rf perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm;

CMD ["/entrypoint.sh"]

