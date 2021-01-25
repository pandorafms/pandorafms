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
LOGFILE="/tmp/pandora-deploy-community-$(date +%F).log"

# Ansi color code variables
red="\e[0;91m"
green="\e[0;92m"
bold="\e[1m"
cyan="\e[0;36m"
reset="\e[0m"

# initialice logfile 
echo 'Starting deploy' > $LOGFILE

# reference
# echo -e "${red}fail${reset}"
# echo -e "${green}adios${reset}"

# Functions

execute_cmd () {
    local cmd="$1"
    local msg="$2"
    
    echo -en "> ${cyan}$msg... ${reset}"
    $cmd &>> $LOGFILE
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        exit 1
    else
        echo -e "${green}OK${reset}"
        return 0
    fi
}

check_repo_connection () {
    execute_cmd "ping -c 2 8.8.8.8" "Checking internet connection"
    execute_cmd "ping -c 2 support.artica.es" "Checking Artica repo connection"
    execute_cmd "ping -c 2 firefly.artica.es" "Checking Firefly repo connection"
}

check_root_permissions () {
    echo -en "> ${cyan}Checking root account... ${reset}"
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

# Pre checks
check_repo_connection 
check_root_permissions

# Creating working directory

mkdir $HOME/pandora_deploy_tmp &>> $LOGFILE
execute_cmd "cd $HOME/pandora_deploy_tmp" "Moving to workspace:  $HOME/pandora_deploy_tmp"

rm -rf $HOME/pandora_deploy_tmp/*.rpm*

# Downloading Pandora Packages

execute_cmd "yum install -y wget" "Installing wget"
execute_cmd "wget http://firefly.artica.es/centos7/pandorafms_server-7.0NG.751.noarch.rpm" "Downloading Pandora FMS Server community"
execute_cmd "wget http://firefly.artica.es/centos7/pandorafms_console-7.0NG.751.noarch.rpm" "Downloading Pandora FMS Console community"
execute_cmd "wget http://firefly.artica.es/centos7/pandorafms_agent_unix-7.0NG.751_x86_64.rpm" "Downloading Pandora FMS Agent community"

#Installing extra repositiries
extra_repos=" \
tar \
yum-utils \
https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
http://rpms.remirepo.net/enterprise/remi-release-7.rpm \
https://repo.percona.com/yum/percona-release-latest.noarch.rpm"

execute_cmd "yum install -y $extra_repos" "Installing extra repositories"
execute_cmd "yum-config-manager --enable remi-php73" "Configuring PHP 7.3"

# Install percona Database
rm -rf /etc/my.cnf
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
    vim \
    fping \
    perl-IO-Compress \
    nmap \
    sudo \
    perl-Time-HiRes \
    nfdump \
    net-snmp-utils \
    http://www6.atomicorp.com/channels/atomic/centos/7/x86_64/RPMS/wmi-1.3.14-4.el7.art.x86_64.rpm"
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
oracle_dependencier=" \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-basic-19.8.0.0.0-1.x86_64.rpm \
    https://download.oracle.com/otn_software/linux/instantclient/19800/oracle-instantclient19.8-sqlplus-19.8.0.0.0-1.x86_64.rpm"
execute_cmd "yum install -y $vmware_dependencies" "Installing Oracle Instant client"

# Disabling SELINUX and firewalld
setenforce 0
sed -i -e "s/^SELINUX=.*/SELINUX=disabled/g" /etc/sysconfig/selinux
systemctl disable firewalld --now &>> $LOGFILE

cat > /etc/issue.net << EOF_banner

Welcome to Pandora FMS appliance on CentOS
------------------------------------------
$(ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v "172.17.0.1" | awk '{print $2}' | awk -F '/' '{print "Go to http://"$1"/pandora_console to manage this server"}')

You can find more information at http://pandorafms.com

EOF_banner

rm -f /etc/issue
ln -s /etc/issue.net /etc/issue

execute_cmd "echo 'Banner /etc/issue.net' >> /etc/ssh/sshd_config" "Adding SSH banner"

#Configuring Database
execute_cmd "systemctl start mysqld" "Starting database engine"
export MYSQL_PWD=$(grep "temporary password" /var/log/mysqld.log | rev | cut -d' ' -f1 | rev)
echo """ 
    SET PASSWORD FOR 'root'@'localhost' = PASSWORD('Pandor4!');
    UNINSTALL PLUGIN validate_password;
    SET PASSWORD FOR 'root'@'localhost' = PASSWORD('pandora');
    """ | mysql --connect-expired-password -uroot 

export MYSQL_PWD=$DBPASS
echo -en "> ${cyan}Creating Pandora FMS database...${reset}" 
echo "create database $DBNAME" | mysql -uroot -P$DBPORT -h$DBHOST

if [ $? -ne 0 ]; then
    echo -e "${red}Fail${reset}"
    echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
    exit 1
else
    echo -e "${green}OK${reset}"
fi

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

# Enable Services
execute_cmd "systemctl enable mysqld --now" "Enabling Database service"
execute_cmd "systemctl enable httpd --now" "Enabling HTTPD service"


# Install Pandora
execute_cmd "yum install -y $HOME/pandora_deploy_tmp/pandorafms*.rpm" "installing PandoraFMS packages"

# Populate Database
echo -en "> ${cyan}Loading pandoradb.sql to $DBNAME database...${reset}" 
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb.sql &>> $LOGFILE
if [ $? -ne 0 ]; then
    echo -e "${red}Fail${reset}"
    echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
    exit 1
else
    echo -e "${green}OK${reset}"
fi

echo -en "> ${cyan}Loading pandoradb_data.sql to $DBNAME database...${reset}" 
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb_data.sql &>> $LOGFILE
if [ $? -ne 0 ]; then
    echo -e "${red}Fail${reset}"
    echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
    exit 1
else
    echo -e "${green}OK${reset}"
fi

# configure console
cat > $CONSOLE_PATH/include/config.php << EO_CONFIG_F
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
<Directory "/var/www/html">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

EO_CONFIG_F

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
sed -i -e "s/^memory_limit.*/memory_limit = 500M/g" /etc/php.ini

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

# Enable pandora ha service
systemctl enable pandora_server --now &>> $LOGFILE
execute_cmd "systemctl start pandora_server" "Starting Pandora FMS Server"

# starting tentacle server
systemctl enable tentacle_serverd &>> $LOGFILE
execute_cmd "service tentacle_serverd start" "Starting Tentacle Server"

# Enabling condole cron
execute_cmd "echo \"* * * * * root wget -q -O - --no-check-certificate http://127.0.0.1/pandora_console/enterprise/cron.php >> $PANDORA_CONSOLE/log/cron.log\" >> /etc/crontab" "Enabling Pandora FMS Console cron"

## Enabling agent 
systemctl enable pandora_agent_daemon &>> $LOGFILE
execute_cmd "systemctl start pandora_agent_daemon" "starting Pandora FMS Agent"

execute_cmd "echo done" "Pandora FMS Community installed"

GREEN='\033[01;32m'
NONE='\033[0m'

ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v -e "172.1[0-9].0.1" | awk '{print $2}' | awk -v g=$GREEN -v n=$NONE -F '/' '{printf "\n-> Go to "g"http://"$1"/pandora_console"n" to manage this server \n"}'