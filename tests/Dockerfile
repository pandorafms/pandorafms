FROM centos:7

RUN { \
	echo '[artica_pandorafms]'; \
	echo 'name=CentOS7 - PandoraFMS official repo'; \
	echo 'baseurl=http://firefly.artica.es/centos7'; \
	echo 'gpgcheck=0'; \
	echo 'enabled=1'; \
	} > /etc/yum.repos.d/pandorafms.repo

# Pandora FMS dependencies.
RUN yum install -y epel-release vim wget bzip2 curl && \
	yum install -y yum-utils && \
	yum install -y https://dev.mysql.com/get/mysql80-community-release-el7-3.noarch.rpm && \
	yum-config-manager --disable mysql80-community && \
	yum-config-manager --enable mysql57-community && \
	yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
	yum-config-manager --enable remi-php72 && \
	yum install -y gtk3 python-pip \
	python-pip \
	firefox \
	xorg-x11-server-Xvfb \
	x11vnc && \
	wget https://github.com/mozilla/geckodriver/releases/download/v0.26.0/geckodriver-v0.26.0-linux64.tar.gz && \
	tar xvzf geckodriver-v0.26.0-linux64.tar.gz && \
	mv geckodriver /usr/bin/ && rm geckodriver-v0.26.0-linux64.tar.gz && \
	pip install pyvirtualdisplay && \
	pip install "selenium==3.141" && \
	pip install unittest2 && \
	pip install testtools && \
	yum install -y git \
	httpd \
	cronie \
	ntp \
	openldap \
	nfdump \
	openldap \
	plymouth \
	xterm \
	php \
	php-gd \
	graphviz \
	php-mysqlnd \
	php-pear-DB \
	php-pear \
	php-pdo \
	php-mbstring \
	php-ldap \
	php-snmp \
	php-ldap \
	php-common \
	php-zip \
	php-xmlrpc \
	nmap \
	xprobe2 \
	mysql-server \
	mysql \
	ntp \
	htop \
	nano \
	postfix \
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
	perl-Sys-Syslog \
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
	net-snmp-utils \
	fontconfig \
	freetype \
	freetype-devel \
	fontconfig-devel \
	libstdc++ \
	gettext \
	cpanminus && \
	cpanm Geo::IP && \
	mkdir -p /opt/phantomjs/bin && cd /opt/phantomjs/bin && \
	wget https://netcologne.dl.sourceforge.net/project/pandora/Tools%20and%20dependencies%20%28All%20versions%29/DEB%20Debian%2C%20Ubuntu/phantomjs && \
	chmod +x phantomjs && \
	ln -s /opt/phantomjs/bin/phantomjs /usr/bin/ && \
	yum update -y && \
	echo -e '#!/bin/bash\nhttpd -k $1\n' > /etc/init.d/httpd  && \
	chmod +x /etc/init.d/httpd && \
	yum clean all

ADD wmic /usr/bin/

# Install debugg dependencies.
RUN yum install -y \
	php-devel \
	php-pear \
	gcc \
	gcc-c++ \
	autoconf \
	file \
	automake  && \
	pecl install Xdebug && \
	git clone https://github.com/tideways/php-xhprof-extension && \
	cd php-xhprof-extension && \
	phpize && \
	./configure && \
	make && \
	make install && \
	cd .. && \
	rm -rf php-xhprof-extension && \
	yum clean all

#Exposing ports for: HTTP, SNMP Traps, Tentacle protocol
EXPOSE 80 162/udp 41121
