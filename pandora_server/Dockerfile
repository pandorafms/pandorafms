FROM centos:centos6
MAINTAINER Pandora FMS Team <info@pandorafms.com>

RUN { \
	echo '[EPEL]'; \
	echo 'name = CentOS Epel'; \
	echo 'baseurl = http://dl.fedoraproject.org/pub/epel/6/x86_64'; \
	echo 'enabled=1'; \
	echo 'gpgcheck=0'; \
} > /etc/yum.repos.d/extra_repos.repo

RUN { \
        echo '[artica_pandorafms]'; \
        echo 'name=CentOS6 - PandoraFMS official repo'; \
        echo 'baseurl=http://artica.es/centos6'; \
        echo 'gpgcheck=0'; \
        echo 'enabled=1'; \
} > /etc/yum.repos.d/pandorafms.repo

RUN yum -y update; yum clean all;
RUN yum install -y \ 
	git \
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
	perl-Encode-Locale \
	net-snmp \
	net-snmp-utils
	

#Clone the repo
RUN git clone -b develop https://github.com/pandorafms/pandorafms.git /tmp/pandorafms

#Exposing ports for: Tentacle protocol
EXPOSE 41121

# Simple startup script to avoid some issues observed with container restart
ADD docker_entrypoint.sh /entrypoint.sh
RUN chmod -v +x /entrypoint.sh

CMD ["/entrypoint.sh"]

