#!/bin/bash
#######################################################
# PandoraFMS Community  online installation script 
#######################################################
## Tested versions ##
# Centos 8.4, 8.5
# Rocky 8.4, 8.5, 8.6, 8.7
# Almalinuz 8.4, 8.5
# RedHat 8.5

#Constants
S_VERSION='202302201'
LOGFILE="/tmp/deploy-ext-db-$(date +%F).log"


# define default variables
[ "$TZ" ]       || TZ="Europe/Madrid"
[ "$MYVER" ]    || MYVER=57
[ "$DBHOST" ]   || DBHOST=127.0.0.1
[ "$DBNAME" ]   || DBNAME=pandora
[ "$DBUSER" ]   || DBUSER=pandora
[ "$DBPASS" ]   || DBPASS=pandora
[ "$DBPORT" ]   || DBPORT=3306
[ "$DBROOTUSER" ] || DBROOTUSER=root
[ "$DBROOTPASS" ] || DBROOTPASS=pandora
[ "$SKIP_DATABASE_INSTALL" ]     || SKIP_DATABASE_INSTALL=0
[ "$SKIP_KERNEL_OPTIMIZATIONS" ] || SKIP_KERNEL_OPTIMIZATIONS=0
[ "$POOL_SIZE" ]    || POOL_SIZE=$(grep -i total /proc/meminfo | head -1 | awk '{printf "%.2f \n", $(NF-1)*0.4/1024}' | sed "s/\\..*$/M/g")

# Ansi color code variables
red="\e[0;91m"
green="\e[0;92m"
cyan="\e[0;36m"
reset="\e[0m"

# Functions
execute_cmd () {
    local cmd="$1"
    local msg="$2"

    echo -e "${cyan}$msg...${reset}"
    $cmd &>> "$LOGFILE"
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        [ "$3" ] && echo "$3"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        rm -rf "$HOME"/pandora_deploy_tmp &>> "$LOGFILE"
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
        rm -rf "$HOME"/pandora_deploy_tmp/*.rpm* &>> "$LOGFILE"
        exit 1
    else
        echo -e "${green}OK${reset}"
        return 0
    fi
}

check_root_permissions () {
    echo -en "${cyan}Checking root account... ${reset}"
    if [ "$(whoami)" != "root" ]; then
        echo -e "${red}Fail${reset}"
        echo "Please use a root account or sudo for installing Pandora FMS"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        exit 1

    else
        echo -e "${green}OK${reset}"
    fi
}

## Main
echo "Starting PandoraFMS External DB deployment EL8 ver. $S_VERSION"

# Centos Version
if [ ! "$(grep -Ei 'centos|rocky|Almalinux|Red Hat Enterprise' /etc/redhat-release)" ]; then
         printf "\n ${red}Error this is not a Centos/Rocky/Almalinux Base system, this installer is compatible with RHEL/Almalinux/Centos/Rockylinux systems only${reset}\n"
         exit 1
fi


echo -en "${cyan}Check Centos Version...${reset}"
[ $(sed -nr 's/VERSION_ID+=\s*"([0-9]).*"$/\1/p' /etc/os-release) -eq '8' ]
check_cmd_status 'Error OS version, RHEL/Almalinux/Centos/Rockylinux 8+ is expected'

#Detect OS
os_name=$(grep ^PRETTY_NAME= /etc/os-release | cut -d '=' -f2 | tr -d '"')
execute_cmd "echo $os_name" "OS detected: ${os_name}"

# initialice logfile
execute_cmd "echo 'Starting community deployment' > $LOGFILE" "All installer activity is logged on $LOGFILE"
echo "Community installer version: $S_VERSION" >> "$LOGFILE"

# Pre checks
# Root permisions
check_root_permissions

# Systemd
execute_cmd "systemctl status" "Checking SystemD" 'This is not a SystemD enable system, if tryng to use in a docker env please check: https://github.com/pandorafms/pandorafms/tree/develop/extras/docker/centos8'

# Check memomry greather or equal to 2G
execute_cmd  "[ $(grep MemTotal /proc/meminfo | awk '{print $2}') -ge 1700000 ]" 'Checking memory (required: 2 GB)'

# Check disk size at least 10 Gb free space
execute_cmd "[ $(df -BM / | tail -1 | awk '{print $4}' | tr -d M) -gt 10000 ]" 'Checking Disk (required: 10 GB free min)'

# Setting timezone
rm -rf /etc/localtime &>> "$LOGFILE"
execute_cmd "timedatectl set-timezone $TZ" "Setting Timezone $TZ"

# Execute tools check
execute_cmd "awk --version" 'Checking needed tools: awk'
execute_cmd "grep --version" 'Checking needed tools: grep'
execute_cmd "sed --version" 'Checking needed tools: sed'
execute_cmd "dnf --version" 'Checking needed tools: dnf'

# Creating working directory
rm -rf "$HOME"/pandora_deploy_tmp/*.rpm* &>> "$LOGFILE"
mkdir "$HOME"/pandora_deploy_tmp &>> "$LOGFILE"
execute_cmd "cd $HOME/pandora_deploy_tmp" "Moving to workspace:  $HOME/pandora_deploy_tmp"

## Extra steps on redhat envs
if [ "$(grep -Ei 'Red Hat Enterprise' /etc/redhat-release)" ]; then
    ## In case REDHAT
    # Check susbscription manager status:
    echo -en "${cyan}Checking Red Hat Enterprise subscription... ${reset}"
    subscription-manager list &>> "$LOGFILE"
    subscription-manager status &>> "$LOGFILE"
    check_cmd_status 'Error checking subscription status, make sure your server is activated and suscribed to Red Hat Enterprise repositories'

    # Ckeck repolist
    dnf repolist &>> "$LOGFILE"
    echo -en "${cyan}Checking Red Hat Enterprise repolist... ${reset}"
    dnf repolist | grep 'rhel-8-for-x86_64-baseos-rpms' &>> "$LOGFILE"
    check_cmd_status 'Error checking repositories status, could try a subscription-manager attach command or contact sysadmin'
    
    #install extra repos
    extra_repos=" \
        tar \
        dnf-utils \
        https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm \
        https://repo.percona.com/yum/percona-release-latest.noarch.rpm"

    execute_cmd "dnf install -y $extra_repos" "Installing extra repositories"
    execute_cmd "subscription-manager repos --enable codeready-builder-for-rhel-8-x86_64-rpms" "Enabling subscription to codeready-builder"
else
    # For alma/rocky/centos
    extra_repos=" \
        tar \
        dnf-utils \
        epel-release \
        https://repo.percona.com/yum/percona-release-latest.noarch.rpm"

    execute_cmd "dnf install -y $extra_repos" "Installing extra repositories"
    execute_cmd "dnf config-manager --set-enabled powertools" "Configuring Powertools"
fi


#Installing wget
execute_cmd "dnf install -y wget" "Installing wget"


# Install percona Database
execute_cmd "dnf module disable -y mysql" "Disabiling mysql module"

if [ "$MYVER" -eq '80' ] ; then
    execute_cmd "percona-release setup ps80 -y" "Enabling mysql80 module"
    execute_cmd "dnf install -y percona-server-server percona-xtrabackup-80" "Installing Percona Server 80"
fi

if [ "$MYVER" -ne '80' ] ; then
    execute_cmd "dnf install -y Percona-Server-server-57 percona-xtrabackup-24" "Installing Percona Server 57"
fi

# Disabling SELINUX and firewalld
setenforce 0  &>> "$LOGFILE"
sed -i -e "s/^SELINUX=.*/SELINUX=disabled/g" /etc/selinux/config  &>> "$LOGFILE"
systemctl disable firewalld --now &>> "$LOGFILE"

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


# Kernel optimization

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

[ -d /dev/lxd/ ] || execute_cmd "sysctl --system" "Applying Kernel optimization"
fi

execute_cmd "echo done" "Percona server installed"
cd
execute_cmd "rm -rf $HOME/pandora_deploy_tmp" "Removing temporary files"