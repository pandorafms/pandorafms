#docker build -t pandorafms/pandorafms-open-base-el8 -f $HOME/code/pandorafms/extras/docker/centos8/base/Dockerfile $HOME/code/pandorafms/extras/docker/centos8/base/
#docker push pandorafms/pandorafms-open-base-el8

FROM rockylinux:8

RUN dnf install -y --setopt=tsflags=nodocs \
        epel-release \
	dnf-utils \
        http://rpms.remirepo.net/enterprise/remi-release-8.rpm
		
RUN dnf module reset -y php && dnf module install -y php:remi-7.4
RUN dnf config-manager --set-enabled powertools

# Install console dependencies
RUN dnf install -y --setopt=tsflags=nodocs \
        php \
        php-mcrypt \php-cli \
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
        nfdump \
        xorg-x11-fonts-75dpi \
        xorg-x11-fonts-misc \
        poppler-data \
        php-yaml

RUN mkdir -p /run/php-fpm/ ; chown -R root:apache /run/php-fpm/
# Not installed perl-Net-Telnet gtk-update-icon-cach ghostscript-fonts

# Install server dependencies 

RUN dnf install -y  --setopt=tsflags=nodocs \
        GeoIP \
        GeoIP-GeoLite-data \
        dwz \
        efi-srpm-macros \
        ghc-srpm-macros \
        go-srpm-macros \
        ocaml-srpm-macros \
        openblas-srpm-macros \
        perl \
        perl-Algorithm-Diff \
        perl-Archive-Tar \
        perl-Archive-Zip \
        perl-Attribute-Handlers \
        perl-B-Debug \
        perl-CPAN \
        perl-CPAN-Meta \
        perl-CPAN-Meta-Requirements \
        perl-CPAN-Meta-YAML \
        perl-Compress-Bzip2 \
        perl-Config-Perl-V \
        perl-DBD-MySQL \
        perl-DBI \
        perl-DB_File \
        perl-Data-Dump \
        perl-Data-OptList \
        perl-Data-Section \
        perl-Devel-PPPort \
        perl-Devel-Peek \
        perl-Devel-SelfStubber \
        perl-Devel-Size \
        perl-Digest-HMAC \
        perl-Digest-SHA \
        perl-Encode-Locale \
        perl-Encode-devel \
        perl-Env \
        perl-ExtUtils-CBuilder \
        perl-ExtUtils-Command \
        perl-ExtUtils-Embed \
        perl-ExtUtils-Install \
        perl-ExtUtils-MM-Utils \
        perl-ExtUtils-MakeMaker \
        perl-ExtUtils-Manifest \
        perl-ExtUtils-Miniperl \
        perl-ExtUtils-ParseXS \
        perl-File-Fetch \
        perl-File-HomeDir \
        perl-File-Listing \
        perl-File-Which \
        perl-Filter \
        perl-Filter-Simple \
        perl-Geo-IP \
        perl-HTML-Parser \
        perl-HTML-Tagset \
        perl-HTML-Tree \
        perl-HTTP-Cookies \
        perl-HTTP-Date \
        perl-HTTP-Message \
        perl-HTTP-Negotiate \
        perl-IO-HTML \
        perl-IO-Socket-INET6 \
        perl-IO-Zlib \
        perl-IO-stringy \
        perl-IPC-Cmd \
        perl-IPC-SysV \
        perl-IPC-System-Simple \
        perl-JSON \
        perl-JSON-PP \
        perl-LWP-MediaTypes \
        perl-Locale-Codes \
        perl-Locale-Maketext \
        perl-Locale-Maketext-Simple \
        perl-MRO-Compat \
        perl-Math-BigInt \
        perl-Math-BigInt-FastCalc \
        perl-Math-BigRat \
        perl-Memoize \
        perl-Module-Build \
        perl-Module-CoreList \
        perl-Module-CoreList-tools \
        perl-Module-Load \
        perl-Module-Load-Conditional \
        perl-Module-Loaded \
        perl-Module-Metadata \
        perl-NTLM \
        perl-Net-HTTP \
        perl-Net-Ping \
        perl-NetAddr-IP \
        perl-Package-Generator \
        perl-Params-Check \
        perl-Params-Util \
        perl-Perl-OSType \
        perl-PerlIO-via-QuotedPrint \
        perl-Pod-Checker \
        perl-Pod-Html \
        perl-Pod-Parser \
        perl-SelfLoader \
        perl-Socket6 \
        perl-Software-License \
        perl-Sub-Exporter \
        perl-Sub-Install \
        perl-Sys-Syslog \
        perl-Test \
        perl-Test-Harness \
        perl-Test-Simple \
        perl-Text-Balanced \
        perl-Text-Diff \
        perl-Text-Glob \
        perl-Text-Template \
        perl-Thread-Queue \
        perl-Time-Piece \
        perl-TimeDate \
        perl-Try-Tiny \
        perl-Unicode-Collate \
        perl-WWW-RobotRules \
        perl-XML-NamespaceSupport \
        perl-XML-Parser \
        perl-XML-SAX \
        perl-XML-SAX-Base \
        perl-XML-Simple \
        perl-XML-Twig \
        perl-autodie \
        perl-bignum \
        perl-devel \
        perl-encoding \
        perl-experimental \
        perl-inc-latest \
        perl-libnetcfg \
        perl-libwww-perl \
        perl-local-lib \
        perl-open \
        perl-perlfaq \
        perl-srpm-macros \
        perl-utils \
        perl-version \
        python-srpm-macros \
        python3-pyparsing \
        python3-rpm-macros \
        qt5-srpm-macros \
        redhat-rpm-config \
        rust-srpm-macros \
        systemtap-sdt-devel \
        perl-TermReadKey \
        perl \
        perl-DBD-MySQL \
        perl-DBI \
        initscripts \
        vim \
        fping \
        perl-IO-Compress \
	perl-Time-HiRes \
	perl-Math-Complex \
	libnsl \
        mysql \
        java \
        net-snmp-utils \
        net-tools \
        nmap-ncat \
        nmap \
        net-snmp-utils \
        sudo \
        expect \
	openssh-clients \
        http://firefly.artica.es/centos8/perl-Net-Telnet-3.04-1.el8.noarch.rpm \
        http://firefly.artica.es/centos7/wmic-1.4-1.el7.x86_64.rpm

# Install utils
RUN dnf install -y supervisor crontabs http://firefly.artica.es/centos8/phantomjs-2.1.1-1.el7.x86_64.rpm --setopt=tsflags=nodocs
# SDK VMware perl dependencies
RUN dnf install -y http://firefly.artica.es/centos8/perl-Crypt-OpenSSL-AES-0.02-1.el8.x86_64.rpm http://firefly.artica.es/centos8/perl-Crypt-SSLeay-0.73_07-1.gf.el8.x86_64.rpm perl-Net-HTTP perl-libwww-perl openssl-devel perl-Crypt-CBC perl-Bytes-Random-Secure perl-Crypt-Random-Seed perl-Math-Random-ISAAC perl-JSON http://firefly.artica.es/centos8/VMware-vSphere-Perl-SDK-6.5.0-4566394.x86_64.rpm
# Instant client Oracle
RUN dnf install -y https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-basic-19.8.0.0.0-1.x86_64.rpm https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-sqlplus-19.8.0.0.0-1.x86_64.rpm
# Install Phantom
RUN dnf install -y supervisor crontabs http://firefly.artica.es/centos8/phantomjs-2.1.1-1.el7.x86_64.rpm --setopt=tsflags=nodocs


EXPOSE 80 443 41121 162/udp
