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

# Generic dependencies
RUN yum install -y firefox
RUN yum install -y python-pip
RUN yum install -y xorg-x11-server-Xvfb
RUN pip install pyvirtualdisplay
RUN pip install selenium

# Pandora FMS Console dependencies
RUN yum install -y git
RUN yum install -y httpd
RUN yum install -y cronie
RUN yum install -y ntp
RUN yum install -y openldap
RUN yum install -y anytermd
RUN yum install -y nfdump
RUN yum install -y wget
RUN yum install -y curl
RUN yum install -y openldap
RUN yum install -y plymouth
RUN yum install -y xterm
RUN yum install -y php 
RUN yum install -y php-gd 
RUN yum install -y graphviz 
RUN yum install -y php-mysql 
RUN yum install -y php-pear-DB 
RUN yum install -y php-pear
RUN yum install -y php-pdo
RUN yum install -y php-mbstring 
RUN yum install -y php-ldap 
RUN yum install -y php-snmp 
RUN yum install -y php-ldap 
RUN yum install -y php-common 
RUN yum install -y php-zip 
RUN yum install -y nmap
RUN yum install -y xprobe2
RUN yum install -y mysql-server
RUN yum install -y mysql

# Pandora FMS Server dependencies
RUN yum install -y git
RUN yum install -y cronie
RUN yum install -y ntp
RUN yum install -y wget
RUN yum install -y curl
RUN yum install -y xterm
RUN yum install -y postfix
RUN yum install -y wmic
RUN yum install -y perl-HTML-Tree 
RUN yum install -y perl-DBI 
RUN yum install -y perl-DBD-mysql 
RUN yum install -y perl-libwww-perl 
RUN yum install -y perl-XML-Simple 
RUN yum install -y perl-XML-SAX 
RUN yum install -y perl-NetAddr-IP 
RUN yum install -y net-snmp 
RUN yum install -y net-tools 
RUN yum install -y perl-IO-Socket-INET6 
RUN yum install -y perl-Socket6 
RUN yum install -y nmap 
RUN yum install -y sudo 
RUN yum install -y xprobe2 
RUN yum install -y make 
RUN yum install -y perl-CPAN 
RUN yum install -y perl-JSON 
RUN yum install -y net-snmp-perl 
RUN yum install -y perl-Time-HiRes 
RUN yum install -y perl-XML-Twig 
RUN yum install -y perl-Encode-Locale
RUN yum install -y net-snmp
RUN yum install -y net-snmp-utils

