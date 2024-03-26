#docker build -t pandorafms/pandorafms-open-base-el8 -f $HOME/code/pandorafms/extras/docker/centos8/base/Dockerfile $HOME/code/pandorafms/extras/docker/centos8/base/
#docker push pandorafms/pandorafms-open-base-el8

FROM rockylinux:8

RUN dnf install -y --setopt=tsflags=nodocs \
        epel-release \
	dnf-utils \
        http://rpms.remirepo.net/enterprise/remi-release-8.rpm
		
RUN dnf module reset -y php && dnf module install -y php:remi-8.0
RUN dnf config-manager --set-enabled powertools

# Install console dependencies
RUN dnf install -y --setopt=tsflags=nodocs \
    php \
    postfix \
    php-mcrypt \
    php-cli \
    php-gd \
    php-curl \
    php-session \
    php-mysqlnd \
    php-ldap \
    php-zip \
    php-zlib \
    php-fileinfo \
    php-gettext \
    php-snmp \
    php-mbstring \
    php-pecl-zip \
    php-xmlrpc \
    libxslt \
    wget \
    php-xml \
    httpd \
    mod_php \
    atk \
    avahi-libs \
    cairo \
    cups-libs \
    fribidi \
    gd \
    gdk-pixbuf2 \
    ghostscript \
    graphite2 \
    graphviz \
    gtk2 \
    harfbuzz \
    hicolor-icon-theme \
    hwdata \
    jasper-libs \
    lcms2 \
    libICE \
    libSM \
    libXaw \
    libXcomposite \
    libXcursor \
    libXdamage \
    libXext \
    libXfixes \
    libXft \
    libXi \
    libXinerama \
    libXmu \
    libXrandr \
    libXrender \
    libXt \
    libXxf86vm \
    libcroco \
    libdrm \
    libfontenc \
    libglvnd \
    libglvnd-egl \
    libglvnd-glx \
    libpciaccess \
    librsvg2 \
    libthai \
    libtool-ltdl \
    libwayland-client \
    libwayland-server \
    libxshmfence \
    mesa-libEGL \
    mesa-libGL \
    mesa-libgbm \
    mesa-libglapi \
    pango \
    pixman \
    xorg-x11-fonts-75dpi \
    xorg-x11-fonts-misc \
    poppler-data \
    php-yaml \
    libzstd \
    openldap-clients \
    http://firefly.artica.es/centos8/chromium-110.0.5481.177-1.el7.x86_64.rpm \
    http://firefly.artica.es/centos8/chromium-common-110.0.5481.177-1.el7.x86_64.rpm \
    http://firefly.artica.es/centos8/perl-Net-Telnet-3.04-1.el8.noarch.rpm \
    http://firefly.artica.es/centos7/wmic-1.4-1.el7.x86_64.rpm \
    http://firefly.artica.es/centos8/phantomjs-2.1.1-1.el7.x86_64.rpm

RUN mkdir -p /run/php-fpm/ ; chown -R root:apache /run/php-fpm/
# Not installed perl-Net-Telnet gtk-update-icon-cach ghostscript-fonts

# Install server dependencies
RUN dnf install -y --setopt=tsflags=nodocs \
    perl \
    vim \
    fping \
    perl-IO-Compress \
    nmap \
    sudo \
    perl-Time-HiRes \
    nfdump \
    net-snmp-utils \
    "perl(NetAddr::IP)" \
    "perl(Sys::Syslog)" \
    "perl(DBI)" \
    "perl(XML::Simple)" \
    "perl(IO::Socket::INET6)" \
    "perl(XML::Twig)" \
    expect \
    openssh-clients \
    java \
    bind-utils \
    whois \
    libnsl \
    http://firefly.artica.es/centos7/wmic-1.4-1.el7.x86_64.rpm \
    https://firefly.artica.es/centos8/pandorawmic-1.0.0-1.x86_64.rpm ; dnf clean all

# Install utils
RUN dnf install -y supervisor mysql chromium crontabs http://firefly.artica.es/centos8/phantomjs-2.1.1-1.el7.x86_64.rpm --setopt=tsflags=nodocs
# SDK VMware perl dependencies
RUN dnf install -y http://firefly.artica.es/centos8/perl-Crypt-OpenSSL-AES-0.02-1.el8.x86_64.rpm http://firefly.artica.es/centos8/perl-Crypt-SSLeay-0.73_07-1.gf.el8.x86_64.rpm perl-Net-HTTP perl-libwww-perl openssl-devel perl-Crypt-CBC perl-Bytes-Random-Secure perl-Crypt-Random-Seed perl-Math-Random-ISAAC perl-JSON http://firefly.artica.es/centos8/VMware-vSphere-Perl-SDK-6.5.0-4566394.x86_64.rpm
# Instant client Oracle
RUN dnf install -y https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-basic-19.8.0.0.0-1.x86_64.rpm https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-sqlplus-19.8.0.0.0-1.x86_64.rpm
# Install IPAM dependencies
RUN dnf install -y "perl(NetAddr::IP)" "perl(Sys::Syslog)" "perl(DBI)" "perl(XML::Simple)" "perl(IO::Socket::INET6)" "perl(XML::Twig)" "perl(DBD::mysql)" --setopt=tsflags=nodocs


EXPOSE 80 443 41121 162/udp
