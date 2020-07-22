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
	echo '[EPEL]'; \
	echo 'name = CentOS Epel'; \
	echo 'baseurl = http://dl.fedoraproject.org/pub/epel/6/x86_64'; \
	echo 'enabled=1'; \
	echo 'gpgcheck=0'; \
	echo  ''; \
	echo '[remi-php72]'; \
	echo 'name=Remi php72'; \
	echo 'baseurl=http://rpms.remirepo.net/enterprise/6/php72/x86_64/'; \
	echo 'enabled=1'; \
	echo 'gpgcheck=0'; \
	echo  ''; \
	echo '[remi-safe]'; \
	echo 'name=Safe Remis RPM repository'; \
	echo 'mirrorlist=http://cdn.remirepo.net/enterprise/$releasever/safe/mirror'; \
	echo 'enabled=1'; \
	echo 'gpgcheck=0'; \
	echo  ''; \
} > /etc/yum.repos.d/extra_repos.repo

RUN yum -y update && \
	yum install -y \
	fontconfig freetype freetype-devel fontconfig-devel libstdc++ \ 
	httpd \
	cronie \
	ntp \
	openldap \
	nfdump \
	wget \
	curl \
	plymouth \
	xterm \
	php \ 
	php-gd \ 
	graphviz \ 
	php-mysql \ 
	php-pear-DB \ 
	php-pear \
	php-pdo \
	php-mbstring \ 
	php-ldap \ 
	php-snmp \ 
	php-ldap \ 
	php-common \ 
	php-zip \ 
	nmap \
	net-snmp-utils \
	mod_ssl \
	xprobe2 && \
	yum clean all

COPY --from=develop /tmp/pandorafms/pandora_console --chown apache:apache  /var/www/html/pandora_console/

#Exposing ports for: HTTP, SNMP Traps, Tentacle protocol
EXPOSE 80 162/udp 443 41121

# Simple startup script to avoid some issues observed with container restart
ADD docker_entrypoint.sh /entrypoint.sh
RUN rm -rf /tmp/pandorafms && rm -rf /tmp/* && rm -rf /var/tmp/* && \
    chmod -v +x /entrypoint.sh

#Install phantomjs required for export graph pdf.
RUN mkdir -p /opt/phantomjs/bin && cd /opt/phantomjs/bin; \
	wget https://netcologne.dl.sourceforge.net/project/pandora/Tools%20and%20dependencies%20%28All%20versions%29/DEB%20Debian%2C%20Ubuntu/phantomjs; \
	chmod +x phantomjs; \
	ln -s /opt/phantomjs/bin/phantomjs /usr/bin/;

CMD ["/entrypoint.sh"]

