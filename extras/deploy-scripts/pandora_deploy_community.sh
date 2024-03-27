#!/bin/bash
#######################################################
# PandoraFMS Community  online installation script 
#######################################################
## Tested versions ##
# Centos 7.9

#Constants
PANDORA_CONSOLE=/var/www/html/pandora_console
PANDORA_SERVER_CONF=/etc/pandora/pandora_server.conf
PANDORA_AGENT_CONF=/etc/pandora/pandora_agent.conf


S_VERSION='2023101101'
LOGFILE="/tmp/pandora-deploy-community-$(date +%F).log"

# define default variables
[ "$TZ" ] || TZ="Europe/Madrid"
[ "$DBHOST" ] || DBHOST=127.0.0.1
[ "$MYVER" ]  || MYVER=80
[ "$DBNAME" ] || DBNAME=pandora
[ "$DBUSER" ] || DBUSER=pandora
[ "$DBPASS" ] || DBPASS=pandora
[ "$DBPORT" ] || DBPORT=3306
[ "$DBROOTUSER" ] || DBROOTUSER=root
[ "$DBROOTPASS" ] || DBROOTPASS=pandora
[ "$SKIP_PRECHECK" ] || SKIP_PRECHECK=0
[ "$SKIP_DATABASE_INSTALL" ] || SKIP_DATABASE_INSTALL=0
[ "$SKIP_KERNEL_OPTIMIZATIONS" ] || SKIP_KERNEL_OPTIMIZATIONS=0
[ "$POOL_SIZE" ] || POOL_SIZE=$(grep -i total /proc/meminfo | head -1 | awk '{printf "%.2f \n", $(NF-1)*0.4/1024}' | sed "s/\\..*$/M/g")
[ "$PANDORA_BETA" ] || PANDORA_BETA=0
[ "$PANDORA_LTS" ]  || PANDORA_LTS=1

#Check if possible to get os version
if [ ! -e /etc/os-release ]; then
    echo ' > Imposible to determinate the OS version for this machine, please make sure you are intalling in a compatible OS'
    echo ' > More info: https://pandorafms.com/manual/en/documentation/02_installation/01_installing#minimum_software_requirements'
    exit -1
fi

# Ansi color code variables
red="\e[0;91m"
green="\e[0;92m"
bold="\e[1m"
cyan="\e[0;36m"
reset="\e[0m"

# Functions

execute_cmd () {
    local cmd="$1"
    local msg="$2"

    echo -e "${cyan}$msg...${reset}"
    $cmd &>> $LOGFILE
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        [ "$3" ] && echo "$3"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        rm -rf $HOME/pandora_deploy_tmp &>> $LOGFILE
        exit 1
    else
        echo -e "\e[1A\e ${cyan}$msg...${reset} ${green}OK${reset}"
        return 0
    fi
}

check_cmd_status () {
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        [ "$1" ] && echo "$1"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        rm -rf $HOME/pandora_deploy_tmp/*.rpm* &>> $LOGFILE
        exit 1
    else
        echo -e "${green}OK${reset}"
        return 0
    fi
}

check_pre_pandora () {
    export MYSQL_PWD=$DBPASS
    
    echo -en "${cyan}Checking environment ... ${reset}"
    rpm -qa | grep 'pandorafms_' | grep -v pandorafms_agent_* | grep -v "pandorawmic"  &>> /dev/null && local fail=true
    [ -d "$PANDORA_CONSOLE" ] && local fail=true
    [ -f /usr/bin/pandora_server ] && local fail=true
    echo "use $DBNAME" | mysql -uroot -P$DBPORT -h$DBHOST &>> /dev/null && local fail=true

    [ ! $fail ]
    check_cmd_status 'Error there is a current Pandora FMS installation on this node, please remove it to execute a clean install'
}

check_repo_connection () {
    execute_cmd "ping -c 2 firefly.pandorafms.com" "Checking Community repo"
    execute_cmd "ping -c 2 support.pandorafms.com" "Checking Enterprise repo"
}

check_root_permissions () {
    echo -en "${cyan}Checking root account... ${reset}"
    if [ "$(whoami)" != "root" ]; then
        echo -e "${red}Fail${reset}"
        echo "Please use a root account or sudo for installing PandoraFMS"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        exit 1

    else
        echo -e "${green}OK${reset}"
    fi
}

## Main
echo "Starting PandoraFMS Community deployment ver. $S_VERSION"

#check tools
if ! grep --version &>> $LOGFILE ; then echo 'Error grep is not detected on the system, grep tool is needed for installation.'; exit -1 ;fi 
if ! sed --version &>> $LOGFILE ; then echo 'Error sed is not detected on the system, sed tool is needed for installation.'; exit -1 ;fi 
if ! curl --version &>> $LOGFILE ; then echo 'Error curl is not detected on the system, curl tool is needed for installation.'; exit -1 ;fi 
if ! ping -V &>> $LOGFILE ; then echo 'Error ping is not detected on the system, ping tool is needed for installation.'; exit -1 ;fi 

# Centos Version
if [ ! "$(grep -i centos /etc/redhat-release)" ]; then
         printf "${red}Error this is not a Centos Base system, this installer is compatible with Centos systems only${reset}\n"
         exit 1
fi

execute_cmd "grep -i centos /etc/redhat-release" "Checking Centos" 'Error This is not a Centos Base system'

#Detect OS
os_name=$(grep ^PRETTY_NAME= /etc/os-release | cut -d '=' -f2 | tr -d '"')
execute_cmd "echo $os_name" "OS detected: ${os_name}"

echo -en "${cyan}Check Centos Version...${reset}"
[[ $(sed -nr 's/VERSION_ID+=\s*"([0-9])"$/\1/p' /etc/os-release) -eq '7' ]]
check_cmd_status 'Error OS version, Centos 7 is expected'

# initialice logfile
execute_cmd "echo 'Starting community deployment' > $LOGFILE" "All installer activity is logged on $LOGFILE"
echo "Community installer version: $S_VERSION" >> $LOGFILE

# Pre checks
# Root permisions
check_root_permissions

# Pre installed pandora
[ "$SKIP_PRECHECK" == 1 ] || check_pre_pandora

#advicing BETA PROGRAM
INSTALLING_VER="${green}RRR version enable using RRR PandoraFMS packages${reset}"
[ "$PANDORA_LTS" -ne '0' ] && INSTALLING_VER="${green}LTS version enable using LTS PandoraFMS packages${reset}"
[ "$PANDORA_BETA" -ne '0' ] && INSTALLING_VER="${red}BETA version enable using nightly PandoraFMS packages${reset}"
echo -e $INSTALLING_VER

# Connectivity
check_repo_connection

# Systemd
execute_cmd "systemctl status" "Checking SystemD" 'This is not a SystemD enable system, if tryng to use in a docker env plese check: https://github.com/pandorafms/pandorafms/tree/develop/extras/docker/centos8'

# Check memomry greather or equal to 2G
execute_cmd  "[ $(grep MemTotal /proc/meminfo | awk '{print $2}') -ge 1700000 ]" 'Checking memory (required: 2 GB)'

# Check disk size at least 10 Gb free space
execute_cmd "[ $(df -BM / | tail -1 | awk '{print $4}' | tr -d M) -gt 10000 ]" 'Checking Disk (required: 10 GB free min)'

# Setting timezone
execute_cmd "timedatectl set-timezone $TZ" "Setting Timezone $TZ"

# Execute tools check
execute_cmd "awk --version" 'Checking needed tools: awk'
execute_cmd "grep --version" 'Checking needed tools: grep'
execute_cmd "sed --version" 'Checking needed tools: sed'
execute_cmd "yum --version" 'Checking needed tools: yum'

# Creating working directory
rm -rf $HOME/pandora_deploy_tmp/*.rpm* &>> $LOGFILE
mkdir $HOME/pandora_deploy_tmp &>> $LOGFILE
execute_cmd "cd $HOME/pandora_deploy_tmp" "Moving to workspace:  $HOME/pandora_deploy_tmp"

#Installing wget
execute_cmd "yum install -y wget" "Installing wget"

#Installing extra repositiries
extra_repos=" \
tar \
yum-utils \
https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
https://rpms.remirepo.net/enterprise/remi-release-7.rpm \
https://repo.percona.com/yum/percona-release-latest.noarch.rpm"

execute_cmd "yum install -y $extra_repos" "Installing extra repositories"
execute_cmd "yum-config-manager --enable remi-php80" "Configuring PHP"

# Install percona Database
#[ -f /etc/my.cnf ] && rm -rf /etc/my.cnf

if [ "$MYVER" -eq '80' ] ; then
    execute_cmd "percona-release setup ps80 -y" "Enabling mysql80 module"
    execute_cmd "yum install -y percona-server-server percona-xtrabackup-80" "Installing Percona Server 80"
fi

if [ "$MYVER" -ne '80' ] ; then
    execute_cmd "yum install -y Percona-Server-server-57 percona-xtrabackup-24" "Installing Percona Server 57"
fi

# Console dependencies
console_dependencies=" \
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
    mod_ssl \
    libzstd \
    openldap-clients \
    https://firefly.pandorafms.com/centos8/pandora_gotty-1.0-1.el8.x86_64.rpm \
    chromium"
execute_cmd "yum install -y $console_dependencies" "Installing Pandora FMS Console dependencies"

# Server dependencies
server_dependencies=" \
    perl \
    vim \
    fping \
    perl-IO-Compress \
    nmap \
    sudo \
    perl-Time-HiRes \
    nfdump \
    net-snmp-utils \
    perl(NetAddr::IP) \
    perl(Sys::Syslog) \
    perl(DBI) \
    perl(XML::Simple) \
    perl(IO::Socket::INET6) \
    perl(XML::Twig) \
    expect \
	openssh-clients \
    java \
    bind-utils \
    whois \
    cpanminus \
    https://firefly.pandorafms.com/centos7/wmic-1.4-1.el7.x86_64.rpm \
    https://firefly.pandorafms.com/centos7/pandorawmic-1.0.0-1.x86_64.rpm"
execute_cmd "yum install -y $server_dependencies" "Installing Pandora FMS Server dependencies"

# install cpan dependencies
execute_cmd "cpanm -i Thread::Semaphore"  "Installing Thread::Semaphore"


# SDK VMware perl dependencies
vmware_dependencies=" \
    https://firefly.pandorafms.com/centos8/VMware-vSphere-Perl-SDK-6.5.0-4566394.x86_64.rpm \
    perl-JSON \
    perl-Archive-Zip \
    openssl-devel \
    perl-Crypt-CBC \
    perl-Digest-SHA \
    https://firefly.pandorafms.com/centos7/perl-Crypt-OpenSSL-AES-0.02-1.el7.x86_64.rpm"
execute_cmd "yum install -y $vmware_dependencies" "Installing SDK VMware perl dependencies"

# Instant client Oracle
oracle_dependencies=" \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-basic-19.8.0.0.0-1.x86_64.rpm \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-sqlplus-19.8.0.0.0-1.x86_64.rpm"
execute_cmd "yum install -y $oracle_dependencies || yum reinstall -y $oracle_dependencies" "Installing Oracle Instant client"

#ipam dependencies
ipam_dependencies=" \
    perl(NetAddr::IP) \
    perl(Sys::Syslog) \
    perl(DBI) \
    perl(XML::Simple) \
    perl(IO::Socket::INET6) \
    perl(XML::Twig)"
execute_cmd "yum install -y $ipam_dependencies" "Installing IPAM Instant client"

# MSSQL dependencies el7
execute_cmd "curl https://packages.microsoft.com/config/rhel/7/prod.repo -o /etc/yum.repos.d/mssql-release.repo" "Configuring Microsoft repositories" 
execute_cmd "yum remove unixODBC-utf16 unixODBC-utf16-devel" "Removing default unixODBC packages"
execute_cmd "env ACCEPT_EULA=Y yum install -y msodbcsql17" "Installing ODBC Driver for Microsoft(R) SQL Server(R)"
MS_ID=$(head -1 /etc/odbcinst.ini | tr -d '[]') &>> "$LOGFILE"
#yum config-manager --set-disable packages-microsoft-com-prod

# Disabling SELINUX and firewalld
setenforce 0
sed -i -e "s/^SELINUX=.*/SELINUX=disabled/g" /etc/selinux/config 
systemctl disable firewalld --now &>> $LOGFILE

# Adding standar cnf for initial setup.
cat > /etc/my.cnf << EO_CONFIG_TMP
[mysqld]
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
symbolic-links=0
log-error=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid
EO_CONFIG_TMP

#Configuring Database
if [ "$SKIP_DATABASE_INSTALL" -eq '0' ] ; then
    execute_cmd "systemctl start mysqld" "Starting database engine"
    export MYSQL_PWD=$(grep "temporary password" /var/log/mysqld.log | rev | cut -d' ' -f1 | rev)
    if [ "$MYVER" -eq '80' ] ; then
        echo """
        SET PASSWORD FOR '$DBROOTUSER'@'localhost' = 'Pandor4!';
        UNINSTALL COMPONENT 'file://component_validate_password';
        SET PASSWORD FOR '$DBROOTUSER'@'localhost' = '$DBROOTPASS';
        """ | mysql --connect-expired-password -u$DBROOTUSER &>> "$LOGFILE"
    fi

    if [ "$MYVER" -ne '80' ] ; then
        echo """
        SET PASSWORD FOR '$DBROOTUSER'@'localhost' = PASSWORD('Pandor4!');
        UNINSTALL PLUGIN validate_password;
        SET PASSWORD FOR '$DBROOTUSER'@'localhost' = PASSWORD('$DBROOTPASS');
        """ | mysql --connect-expired-password -u$DBROOTUSER &>> "$LOGFILE"fi
    fi

    export MYSQL_PWD=$DBROOTPASS
    echo -en "${cyan}Creating Pandora FMS database...${reset}"
    echo "create database $DBNAME" | mysql -u$DBROOTUSER -P$DBPORT -h$DBHOST
    check_cmd_status "Error creating database $DBNAME, is this an empty node? if you have a previus installation please contact with support."

    echo "CREATE USER  \"$DBUSER\"@'%' IDENTIFIED BY \"$DBPASS\";" | mysql -u$DBROOTUSER -P$DBPORT -h$DBHOST
    echo "ALTER USER \"$DBUSER\"@'%' IDENTIFIED WITH mysql_native_password BY \"$DBPASS\"" | mysql -u$DBROOTUSER -P$DBPORT -h$DBHOST        
    echo "GRANT ALL PRIVILEGES ON $DBNAME.* TO \"$DBUSER\"@'%'" | mysql -u$DBROOTUSER -P$DBPORT -h$DBHOST

#Generating my.cnf
cat > /etc/my.cnf << EO_CONFIG_F
[mysqld]
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
user=mysql
character-set-server=utf8
skip-character-set-client-handshake
# Disabling symbolic-links is recommended to prevent assorted security risks
symbolic-links=0
# Mysql optimizations for Pandora FMS
# Please check the documentation in https://pandorafms.com for better results

max_allowed_packet = 64M
innodb_buffer_pool_size = $POOL_SIZE
innodb_lock_wait_timeout = 90
innodb_file_per_table
innodb_flush_log_at_trx_commit = 0
innodb_flush_method = O_DIRECT
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_io_capacity = 100
thread_cache_size = 8
thread_stack    = 256K
max_connections = 100

key_buffer_size=4M
read_buffer_size=128K
read_rnd_buffer_size=128K
sort_buffer_size=128K
join_buffer_size=4M

query_cache_type = 1
query_cache_size = 64M
query_cache_min_res_unit = 2k
query_cache_limit = 256K

#skip-log-bin

sql_mode=""

[mysqld_safe]
log-error=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EO_CONFIG_F

    if [ "$MYVER" -eq '80' ] ; then
        sed -i -e "/query_cache.*/ s/^#*/#/g" /etc/my.cnf
        sed -i -e "s/#skip-log-bin/skip-log-bin/g" /etc/my.cnf
        sed -i -e "s/character-set-server=utf8/character-set-server=utf8mb4/g" /etc/my.cnf

    fi

    execute_cmd "systemctl restart mysqld" "Configuring database engine"
    execute_cmd "systemctl enable mysqld --now" "Enabling Database service"
fi
export MYSQL_PWD=$DBPASS

#Define packages
#Define packages
if [ "$PANDORA_LTS" -eq '1' ] ; then
    [ "$PANDORA_SERVER_PACKAGE" ]       || PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/LTS/pandorafms_server-7.0NG.noarch.rpm"
    [ "$PANDORA_CONSOLE_PACKAGE" ]      || PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/LTS/pandorafms_console-7.0NG.noarch.rpm"
    [ "$PANDORA_AGENT_PACKAGE" ]        || PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/LTS/pandorafms_agent_linux-7.0NG.noarch.rpm"
elif [ "$PANDORA_LTS" -ne '1' ] ; then
    [ "$PANDORA_SERVER_PACKAGE" ]       || PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/pandorafms_server-7.0NG.x86_64.rpm"
    [ "$PANDORA_CONSOLE_PACKAGE" ]      || PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/pandorafms_console-7.0NG.x86_64.rpm"
    [ "$PANDORA_AGENT_PACKAGE" ]        || PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/pandorafms_agent_linux-7.0NG.noarch.rpm"
fi

# if beta is enable
if [ "$PANDORA_BETA" -eq '1' ] ; then
    PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandora_enterprise_nightlies/pandorafms_server-latest.x86_64.rpm"
    PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandora_enterprise_nightlies/pandorafms_console-latest.x86_64.rpm"
    PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/pandorafms_agent_linux-7.0NG.noarch.rpm"
fi

# Downloading Pandora Packages
execute_cmd "curl -LSs --output pandorafms_server-7.0NG.noarch.rpm ${PANDORA_SERVER_PACKAGE}" "Downloading Pandora FMS Server community"
execute_cmd "curl -LSs --output pandorafms_console-7.0NG.noarch.rpm ${PANDORA_CONSOLE_PACKAGE}" "Downloading Pandora FMS Console community"
execute_cmd "curl -LSs --output pandorafms_agent_linux-7.0NG.noarch.rpm ${PANDORA_AGENT_PACKAGE}" "Downloading Pandora FMS Agent community"

# Install Pandora
execute_cmd "yum install -y $HOME/pandora_deploy_tmp/pandorafms*.rpm" "installing PandoraFMS packages"

# Copy gotty utility
execute_cmd "wget https://firefly.pandorafms.com/pandorafms/utils/gotty_linux_amd64.tar.gz" 'Dowloading gotty util'
tar xvzf gotty_linux_amd64.tar.gz &>> $LOGFILE
execute_cmd "mv gotty /usr/bin/" 'Installing gotty util'

# Enable Services
execute_cmd "systemctl enable mysqld --now" "Enabling Database service"
execute_cmd "systemctl enable httpd --now" "Enabling HTTPD service"

# Populate Database
echo -en "${cyan}Loading pandoradb.sql to $DBNAME database...${reset}"
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb.sql &>> $LOGFILE
check_cmd_status 'Error Loading database schema'

echo -en "${cyan}Loading pandoradb_data.sql to $DBNAME database...${reset}"
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb_data.sql &>> $LOGFILE
check_cmd_status 'Error Loading database schema data'

# Configure console
cat > $PANDORA_CONSOLE/include/config.php << EO_CONFIG_F
<?php
\$config["dbtype"] = "mysql";
\$config["dbname"]="$DBNAME";
\$config["dbuser"]="$DBUSER";
\$config["dbpass"]="$DBPASS";
\$config["dbhost"]="$DBHOST";
\$config["homedir"]="$PANDORA_CONSOLE";
\$config["homeurl"]="/pandora_console";
error_reporting(0);
\$ownDir = dirname(__FILE__) . '/';
include (\$ownDir . "config_process.php");

EO_CONFIG_F

cat > /etc/httpd/conf.d/pandora.conf << EO_CONFIG_F
ServerTokens Prod
<Directory "/var/www/html">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

EO_CONFIG_F

# Add ws proxy options to apache.
cat >> /etc/httpd/conf.modules.d/00-proxy.conf << 'EO_HTTPD_MOD'
LoadModule proxy_wstunnel_module modules/mod_proxy_wstunnel.so

EO_HTTPD_MOD

cat >> /etc/httpd/conf.d/wstunnel.conf << 'EO_HTTPD_WSTUNNEL'
ProxyRequests Off
<Proxy *>
    Require all granted
</Proxy>

ProxyPass /ws ws://127.0.0.1:8080
ProxyPassReverse /ws ws://127.0.0.1:8080

EO_HTTPD_WSTUNNEL

# Temporal quitar htaccess
sed -i -e "s/php_flag engine off//g" $PANDORA_CONSOLE/images/.htaccess
sed -i -e "s/php_flag engine off//g" $PANDORA_CONSOLE/attachment/.htaccess

# Fixing console permissions
chmod 600 $PANDORA_CONSOLE/include/config.php
chown apache. $PANDORA_CONSOLE/include/config.php
mv $PANDORA_CONSOLE/install.php $PANDORA_CONSOLE/install.done

# Prepare php.ini
sed -i -e "s/^max_input_time.*/max_input_time = -1/g" /etc/php.ini
sed -i -e "s/^max_execution_time.*/max_execution_time = 0/g" /etc/php.ini
sed -i -e "s/^upload_max_filesize.*/upload_max_filesize = 800M/g" /etc/php.ini
sed -i -e "s/^memory_limit.*/memory_limit = 800M/g" /etc/php.ini
sed -i -e "s/.*post_max_size =.*/post_max_size = 800M/" /etc/php.ini

cat > /var/www/html/index.html << EOF_INDEX
<meta HTTP-EQUIV="REFRESH" content="0; url=/pandora_console/">
EOF_INDEX

execute_cmd "systemctl restart httpd" "Restarting httpd after configuration"

# prepare snmptrapd
cat > /etc/snmp/snmptrapd.conf << EOF
authCommunity log public
disableAuthorization yes
EOF

# Prepare Server conf
sed -i -e "s/^dbhost.*/dbhost $DBHOST/g" $PANDORA_SERVER_CONF
sed -i -e "s/^dbname.*/dbname $DBNAME/g" $PANDORA_SERVER_CONF
sed -i -e "s/^dbuser.*/dbuser $DBUSER/g" $PANDORA_SERVER_CONF
sed -i -e "s|^dbpass.*|dbpass $DBPASS|g" $PANDORA_SERVER_CONF
sed -i -e "s/^dbport.*/dbport $DBPORT/g" $PANDORA_SERVER_CONF
sed -i -e "s/^#.mssql_driver.*/mssql_driver $MS_ID/g" $PANDORA_SERVER_CONF

#check fping
fping_bin=$(which fping)
execute_cmd "[ $fping_bin ]" "Check fping location: $fping_bin"
if [ "$fping_bin" != "" ]; then
  sed -i -e "s|^fping.*|fping $fping_bin|g" $PANDORA_SERVER_CONF
fi

# Enable agent remote config
sed -i "s/^remote_config.*$/remote_config 1/g" $PANDORA_AGENT_CONF 

# Set Oracle environment for pandora_server
cat > /etc/pandora/pandora_server.env << 'EOF_ENV'
#!/bin/bash
VERSION=19.8
export PATH=$PATH:$HOME/bin:/usr/lib/oracle/$VERSION/client64/bin
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/lib/oracle/$VERSION/client64/lib
export ORACLE_HOME=/usr/lib/oracle/$VERSION/client64
EOF_ENV

if [ "$SKIP_KERNEL_OPTIMIZATIONS" -eq '0' ] ; then
cat >> /etc/sysctl.conf <<EO_KO
# Pandora FMS Optimization

# default=5
net.ipv4.tcp_syn_retries = 3

# default=5
net.ipv4.tcp_synack_retries = 3

# default=1024
net.ipv4.tcp_max_syn_backlog = 65536

# default=124928
net.core.wmem_max = 8388608

# default=131071
net.core.rmem_max = 8388608

# default = 128
net.core.somaxconn = 1024

# default = 20480
net.core.optmem_max = 81920

EO_KO

   echo -en "${cyan}Applying Kernel optimization... ${reset}"
    sysctl --system &>> $LOGFILE
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        echo -e "${yellow}Your kernel could not be optimized, you may be running this script in a virtualized environment with no support for accessing the kernel.${reset}"
        echo -e "${yellow}This system can be used for testing but is not recommended for a production environment.${reset}"
        echo "$old_sysctl_file" >  old_sysctl_file
    else
        echo -e "${green}OK${reset}"
    fi
fi

# Fix pandora_server.{log,error} permissions to allow Console check them
chown pandora:apache /var/log/pandora
chmod g+s /var/log/pandora

cat > /etc/logrotate.d/pandora_server <<EO_LR
/var/log/pandora/pandora_server.log 
/var/log/pandora/web_socket.log
/var/log/pandora/pandora_server.error {
	su root apache
	weekly
	missingok
	size 300000
	rotate 3
	maxage 90
	compress
	notifempty
	copytruncate
	create 660 pandora apache
}

/var/log/pandora/pandora_snmptrap.log {
	su root apache
	weekly
	missingok
	size 500000
	rotate 1
	maxage 30
	notifempty
	copytruncate
	create 660 pandora apache
}

EO_LR

cat > /etc/logrotate.d/pandora_agent <<EO_LRA
/var/log/pandora/pandora_agent.log {
	su root apache
	weekly
	missingok
	size 300000
	rotate 3
	maxage 90
	compress
	notifempty
	copytruncate
}

EO_LRA

chmod 0644 /etc/logrotate.d/pandora_server
chmod 0644 /etc/logrotate.d/pandora_agent

# Add websocket engine start script.
mv /var/www/html/pandora_console/pandora_websocket_engine /etc/init.d/
chmod +x /etc/init.d/pandora_websocket_engine

# Start Websocket engine
/etc/init.d/pandora_websocket_engine start &>> $LOGFILE

# Configure websocket to be started at start.
systemctl enable pandora_websocket_engine &>> $LOGFILE

# Enable pandora ha service
systemctl enable pandora_server --now &>> $LOGFILE
execute_cmd "systemctl start pandora_server" "Starting Pandora FMS Server"

# starting tentacle server
systemctl enable tentacle_serverd &>> $LOGFILE
execute_cmd "service tentacle_serverd start" "Starting Tentacle Server"

# Enabling condole cron
execute_cmd "echo \"* * * * * root wget -q -O - --no-check-certificate --load-cookies /tmp/cron-session-cookies --save-cookies /tmp/cron-session-cookies --keep-session-cookies http://127.0.0.1/pandora_console/cron.php >> $PANDORA_CONSOLE/log/cron.log\" >> /etc/crontab" "Enabling Pandora FMS Console cron"
echo "* * * * * root wget -q -O - --no-check-certificate --load-cookies /tmp/cron-session-cookies --save-cookies /tmp/cron-session-cookies --keep-session-cookies http://127.0.0.1/pandora_console/cron.php >> $PANDORA_CONSOLE/log/cron.log" >> /etc/crontab
## Enabling agent
systemctl enable pandora_agent_daemon &>> $LOGFILE
execute_cmd "systemctl start pandora_agent_daemon" "Starting Pandora FMS Agent"

# Enable postrix
systemctl enable postfix --now &>> "$LOGFILE"

#SSH banner
[ "$(curl -s ifconfig.me)" ] && ipplublic=$(curl -s ifconfig.me)

cat > /etc/issue.net << EOF_banner

Welcome to Pandora FMS appliance on CentOS
------------------------------------------
Go to Public http://$ipplublic/pandora_console to login web console
$(ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v "172.17.0.1" | awk '{print $2}' | awk -F '/' '{print "Go to Local http://"$1"/pandora_console to login web console"}')

You can find more information at https://pandorafms.com

EOF_banner

rm -f /etc/issue
ln -s /etc/issue.net /etc/issue

echo 'Banner /etc/issue.net' >> /etc/ssh/sshd_config

# Remove temporary files
execute_cmd "echo done" "Pandora FMS Community installed"
cd
execute_cmd "rm -rf $HOME/pandora_deploy_tmp" "Removing temporary files"

# Print nice finish message
GREEN='\033[01;32m'
NONE='\033[0m'
printf " -> Go to Public ${green}http://"$ipplublic"/pandora_console${reset} to manage this server"
ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v -e "172.1[0-9].0.1" | awk '{print $2}' | awk -v g=$GREEN -v n=$NONE -F '/' '{printf "\n -> Go to Local "g"http://"$1"/pandora_console"n" to manage this server \n -> Use these credentials to log in Pandora Console "g"[ User: admin / Password: pandora ]"n" \n"}'
