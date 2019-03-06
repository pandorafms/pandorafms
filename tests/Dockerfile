FROM centos:centos6
MAINTAINER Pandora FMS Team <info@pandorafms.com>

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

RUN { \
        echo '[artica_pandorafms]'; \
        echo 'name=CentOS6 - PandoraFMS official repo'; \
        echo 'baseurl=http://artica.es/centos6'; \
        echo 'gpgcheck=0'; \
        echo 'enabled=1'; \
} > /etc/yum.repos.d/pandorafms.repo

RUN yum -y update; yum clean all;

RUN yum --disablerepo=updates install -y firefox

# Generic dependencies
RUN yum install -y \
	python-pip \
	xorg-x11-server-Xvfb; yum clean all;
RUN pip install pyvirtualdisplay
RUN pip install 'selenium==2.53.0'
RUN pip install unittest2
RUN pip install testtools

# Pandora FMS Console dependencies
RUN yum install -y \ 
	git \
	httpd \
	cronie \
	ntp \
	openldap \
	nfdump \
	wget \
	curl \
	openldap \
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
	php-xmlrpc \ 
	nmap \
	xprobe2 \
	mysql-server \
	mysql; yum clean all;

# Pandora FMS Server dependencies
RUN yum install -y \ 
	ntp \
	vim \
	htop \
	nano \
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
	perl-Encode-Locale \
	net-snmp-utils \
	fontconfig \
	freetype \
	freetype-devel \
	fontconfig-devel \
	libstdc++ \
	perl-Test-Simple; yum clean all;

RUN wget http://rpmfind.net/linux/centos/6.9/os/i386/Packages/gettext-0.17-18.el6.i686.rpm; \
	yum localinstall -y gettext-0.17-18.el6.i686.rpm; \
	rm -rf gettext-0.17-18.el6.i686.rpm; \
	wget http://ftp.tu-chemnitz.de/pub/linux/dag/redhat/el6/en/x86_64/rpmforge/RPMS/perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm; \
	yum localinstall -y perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm; \
	rm -rf perl-Geo-IP-1.38-1.el6.rf.x86_64.rpm;

#Install phantomjs required for export graph pdf.
RUN mkdir -p /opt/phantomjs/bin && cd /opt/phantomjs/bin; \
	wget https://netcologne.dl.sourceforge.net/project/pandora/Tools%20and%20dependencies%20%28All%20versions%29/DEB%20Debian%2C%20Ubuntu/phantomjs; \
	chmod +x phantomjs; \
	ln -s /opt/phantomjs/bin/phantomjs /usr/bin/;

# Install debugg dependencies.
RUN yum install -y \
	php-devel \
	php-pear \
	gcc \
	gcc-c++ \
	autoconf \
	automake  && \
	pecl install Xdebug && \
	git clone https://github.com/tideways/php-xhprof-extension && \
	cd php-xhprof-extension && \
	phpize && \
	./configure && \
	make && \
	make install && \
	cd .. && \
	rm -rf php-xhprof-extension

#Exposing ports for: HTTP, SNMP Traps, Tentacle protocol
EXPOSE 80 162/udp 41121
