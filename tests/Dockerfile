FROM rockylinux:8.5

RUN { \
    echo '[artica_pandorafms]'; \
    echo 'name=CentOS7 - PandoraFMS official repo'; \
    echo 'baseurl=http://firefly.artica.es/centos7'; \
    echo 'gpgcheck=0'; \
    echo 'enabled=1'; \
    } > /etc/yum.repos.d/pandorafms.repo

# Pandora FMS dependencies.
RUN dnf install -y vim wget bzip2 curl && \
    dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm && \
    dnf install -y yum-utils && \
    dnf install -y https://dev.mysql.com/get/mysql80-community-release-el8-4.noarch.rpm && \
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm && \
	dnf config-manager --set-enabled powertools && \
    dnf module reset -y php && \
    dnf module install -y php:remi-8.0 && \
    dnf update -y && \
    dnf install -y gtk3 python39 python39-pip \
    firefox \
    xorg-x11-server-Xvfb \
    x11vnc && \
    wget https://github.com/mozilla/geckodriver/releases/download/v0.26.0/geckodriver-v0.26.0-linux64.tar.gz && \
    tar xvzf geckodriver-v0.26.0-linux64.tar.gz && \
    mv geckodriver /usr/bin/ && rm -f geckodriver-v0.26.0-linux64.tar.gz && \
    pip3 install pyvirtualdisplay && \
    pip3 install "selenium==3.141" && \
    pip3 install unittest2 && \
    pip3 install testtools && \
    dnf install -y git \
	git-lfs \
    passwd \
    openssh-server \
    httpd \
    cronie \
    openldap \
    nfdump \
    openldap \
    plymouth \
    xterm \
    php \
    php-gd \
    graphviz \
    php-mysqlnd \
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
    mysql-server \
    mysql \
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
    perl-IO-Socket-SSL \
    nmap \
    sudo \
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
	wmic \
    chromium \
    mkdir -p /opt/phantomjs/bin && cd /opt/phantomjs/bin && \
    wget https://netcologne.dl.sourceforge.net/project/pandora/Tools%20and%20dependencies%20%28All%20versions%29/DEB%20Debian%2C%20Ubuntu/phantomjs && \
    chmod +x phantomjs && \
    ln -s /opt/phantomjs/bin/phantomjs /usr/bin/ && \
    yum update -y && \
    echo -e '#!/bin/bash\nhttpd -k $1\n' > /etc/init.d/httpd  && \
    chmod +x /etc/init.d/httpd && \
	setcap -r /usr/libexec/mysqld && \
    yum clean all

# Install debugg dependencies.
RUN dnf install -y \
	initscripts \
	unzip tree \
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
