#!/bin/bash

# define variables
PANDORA_CONSOLE=/var/www/html/pandora_console
CONSOLE_PATH=/var/www/html/pandora_console
PANDORA_SERVER_CONF=/etc/pandora/pandora_server.conf
PANDORA_SERVER_BIN=/usr/bin/pandora_server
PANDORA_HA_BIN=/usr/bin/pandora_ha
PANDORA_TABLES_MIN=160
DBHOST=127.0.0.1
DBNAME=pandora
DBUSER=pandora
DBPASS=pandora
DBPORT=3306
S_VERSION='2021012801'
LOGFILE="/tmp/pandora-deploy-community-$(date +%F).log"

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
    rpm -qa | grep pandora &>> /dev/null && local fail=true
    [ -d "$CONSOLE_PATH" ] && local fail=true
    [ -f /usr/bin/pandora_server ] && local fail=true
    echo "use $DBNAME" | mysql -uroot -P$DBPORT -h$DBHOST &>> /dev/null && local fail=true

    [ ! $fail ]
    check_cmd_status 'Error there is a current Pandora FMS installation on this node, please remove it to execute a clean install'
}

check_repo_connection () {
    execute_cmd "ping -c 2 8.8.8.8" "Checking internet connection"
    execute_cmd "ping -c 2 firefly.artica.es" "Checking Community repo"
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

# Centos Version
if [ ! "$(grep -i centos /etc/redhat-release)" ]; then
         printf "${red}Error this is not a Centos Base system, this installer is compatible with Centos systems only${reset}\n"
         exit 1
fi

execute_cmd "grep -i centos /etc/redhat-release" "Checking Centos" 'Error This is not a Centos Base system'

echo -en "${cyan}Check Centos Version...${reset}"
[ $(sed -nr 's/VERSION_ID+=\s*"([0-9])"$/\1/p' /etc/os-release) -eq '7' ]
check_cmd_status 'Error OS version, Centos 7 is expected'

# initialice logfile
execute_cmd "echo 'Starting community deployment' > $LOGFILE" "All installer activity is logged on $LOGFILE"
echo "Community installer version: $S_VERSION" >> $LOGFILE

# Pre checks
# Root permisions
check_root_permissions

# Pre installed pandora
check_pre_pandora

# Connectivity
check_repo_connection

# Systemd
execute_cmd "systemctl status" "Checking SystemD" 'This is not a SystemD enable system, if tryng to use in a docker env plese check: https://github.com/pandorafms/pandorafms/tree/develop/extras/docker/centos8'

# Check memomry greather or equal to 2G
execute_cmd  "[ $(grep MemTotal /proc/meminfo | awk '{print $2}') -ge 1700000 ]" 'Checking memory (required: 2 GB)'

# Check disk size at least 10 Gb free space
execute_cmd "[ $(df -BM / | tail -1 | awk '{print $4}' | tr -d M) -gt 10000 ]" 'Checking Disk (required: 10 GB free min)'

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
http://rpms.remirepo.net/enterprise/remi-release-7.rpm \
https://repo.percona.com/yum/percona-release-latest.noarch.rpm"

execute_cmd "yum install -y $extra_repos" "Installing extra repositories"
execute_cmd "yum-config-manager --enable remi-php73" "Configuring PHP"

# Install percona Database
[ -f /etc/resolv.conf ] && rm -rf /etc/my.cnf
execute_cmd "yum install -y Percona-Server-server-57" "Installing Percona Server"

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
    http://firefly.artica.es/centos8/phantomjs-2.1.1-1.el7.x86_64.rpm"
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
    perl(Geo::IP) \
    perl(IO::Socket::INET6) \
    perl(XML::Twig) \
    expect \
	openssh-clients \
    http://firefly.artica.es/centos7/xprobe2-0.3-12.2.x86_64.rpm \
    http://firefly.artica.es/centos7/wmic-1.4-1.el7.x86_64.rpm"
execute_cmd "yum install -y $server_dependencies" "Installing Pandora FMS Server dependencies"

# SDK VMware perl dependencies
vmware_dependencies=" \
    http://firefly.artica.es/centos8/VMware-vSphere-Perl-SDK-6.5.0-4566394.x86_64.rpm \
    perl-JSON \
    perl-Archive-Zip \
    openssl-devel \
    perl-Crypt-CBC \
    perl-Digest-SHA \
    http://firefly.artica.es/centos7/perl-Crypt-OpenSSL-AES-0.02-1.el7.x86_64.rpm"
execute_cmd "yum install -y $vmware_dependencies" "Installing SDK VMware perl dependencies"

# Instant client Oracle
oracle_dependencies=" \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-basic-19.8.0.0.0-1.x86_64.rpm \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-sqlplus-19.8.0.0.0-1.x86_64.rpm"
execute_cmd "yum install -y $oracle_dependencies" "Installing Oracle Instant client"

# Disabling SELINUX and firewalld
setenforce 0
sed -i -e "s/^SELINUX=.*/SELINUX=disabled/g" /etc/selinux/config 
systemctl disable firewalld --now &>> $LOGFILE


#Configuring Database
execute_cmd "systemctl start mysqld" "Starting database engine"
export MYSQL_PWD=$(grep "temporary password" /var/log/mysqld.log | rev | cut -d' ' -f1 | rev)
echo """
    SET PASSWORD FOR 'root'@'localhost' = PASSWORD('Pandor4!');
    UNINSTALL PLUGIN validate_password;
    SET PASSWORD FOR 'root'@'localhost' = PASSWORD('pandora');
    """ | mysql --connect-expired-password -uroot

export MYSQL_PWD=$DBPASS
echo -en "${cyan}Creating Pandora FMS database...${reset}"
echo "create database $DBNAME" | mysql -uroot -P$DBPORT -h$DBHOST
check_cmd_status 'Error creating database pandora, is this an empty node? if you have a previus installation please contact with support.'

echo "GRANT ALL PRIVILEGES ON $DBNAME.* TO \"$DBUSER\"@'%' identified by \"$DBPASS\"" | mysql -uroot -P$DBPORT -h$DBHOST

#Generating my.cnf
POOL_SIZE=$(grep -i total /proc/meminfo | head -1 | awk '{printf "%.2f \n", $(NF-1)*0.4/1024}' | sed "s/\\..*$/M/g")
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
# Please check the documentation in http://pandorafms.com for better results

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

sql_mode=""

[mysqld_safe]
log-error=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EO_CONFIG_F

execute_cmd "systemctl restart mysqld" "Configuring database engine"

# Downloading Pandora Packages
execute_cmd "wget http://firefly.artica.es/pandorafms/latest/RHEL_CentOS/pandorafms_server-7.0NG.noarch.rpm" "Downloading Pandora FMS Server community"
execute_cmd "wget http://firefly.artica.es/pandorafms/latest/RHEL_CentOS/pandorafms_console-7.0NG.noarch.rpm" "Downloading Pandora FMS Console community"
execute_cmd "wget http://firefly.artica.es/centos7/pandorafms_agent_unix-7.0NG.751_x86_64.rpm" "Downloading Pandora FMS Agent community"

# Install Pandora
execute_cmd "yum install -y $HOME/pandora_deploy_tmp/pandorafms*.rpm" "installing PandoraFMS packages"

# Copy gotty utility
execute_cmd "wget https://github.com/yudai/gotty/releases/download/v1.0.1/gotty_linux_amd64.tar.gz" 'Dowloading gotty util'
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
cat > $CONSOLE_PATH/include/config.php << EO_CONFIG_F
<?php
\$config["dbtype"] = "mysql";
\$config["dbname"]="$DBNAME";
\$config["dbuser"]="$DBUSER";
\$config["dbpass"]="$DBPASS";
\$config["dbhost"]="localhost";
\$config["homedir"]="$PANDORA_CONSOLE";
\$config["homeurl"]="/pandora_console";
error_reporting(0);
\$ownDir = dirname(__FILE__) . '/';
include (\$ownDir . "config_process.php");

EO_CONFIG_F

cat > /etc/httpd/conf.d/pandora.conf << EO_CONFIG_F
<Directory "/var/www/html">
    Options Indexes FollowSymLinks
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
chmod 600 $CONSOLE_PATH/include/config.php
chown apache. $CONSOLE_PATH/include/config.php
mv $CONSOLE_PATH/install.php $CONSOLE_PATH/install.done

# Prepare php.ini
sed -i -e "s/^max_input_time.*/max_input_time = -1/g" /etc/php.ini
sed -i -e "s/^max_execution_time.*/max_execution_time = 0/g" /etc/php.ini
sed -i -e "s/^upload_max_filesize.*/upload_max_filesize = 800M/g" /etc/php.ini
sed -i -e "s/^memory_limit.*/memory_limit = 800M/g" /etc/php.ini

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

# Set Oracle environment for pandora_server
cat > /etc/pandora/pandora_server.env << 'EOF_ENV'
#!/bin/bash
VERSION=19.8
export PATH=$PATH:$HOME/bin:/usr/lib/oracle/$VERSION/client64/bin
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/lib/oracle/$VERSION/client64/lib
export ORACLE_HOME=/usr/lib/oracle/$VERSION/client64
EOF_ENV

# Kernel optimization
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

[ -d /dev/lxd/ ] || execute_cmd "sysctl --system" "Applying Kernel optimization"

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
execute_cmd "echo \"* * * * * root wget -q -O - --no-check-certificate http://127.0.0.1/pandora_console/enterprise/cron.php >> $PANDORA_CONSOLE/log/cron.log\" >> /etc/crontab" "Enabling Pandora FMS Console cron"
echo "* * * * * root wget -q -O - --no-check-certificate http://127.0.0.1/pandora_console/enterprise/cron.php >> $PANDORA_CONSOLE/log/cron.log" >> /etc/crontab
## Enabling agent
systemctl enable pandora_agent_daemon &>> $LOGFILE
execute_cmd "systemctl start pandora_agent_daemon" "starting Pandora FMS Agent"

#SSH banner
[ "$(curl -s ifconfig.me)" ] && ipplublic=$(curl -s ifconfig.me)

cat > /etc/issue.net << EOF_banner

Welcome to Pandora FMS appliance on CentOS
------------------------------------------
Go to Public http://$ipplublic/pandora_console$to to login web console
$(ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v "172.17.0.1" | awk '{print $2}' | awk -F '/' '{print "Go to Local http://"$1"/pandora_console to login web console"}')

You can find more information at http://pandorafms.com

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
ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v -e "172.1[0-9].0.1" | awk '{print $2}' | awk -v g=$GREEN -v n=$NONE -F '/' '{printf "\n -> Go to Local "g"http://"$1"/pandora_console"n" to manage this server \n -> Use this credentials to login in the console "g"[ User: admin / Password: pandora ]"n" \n"}'