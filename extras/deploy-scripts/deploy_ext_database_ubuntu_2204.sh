#!/bin/bash
##############################################################################################################
# PandoraFMS Community  online installation script for Ubuntu 22.04
##############################################################################################################
## Tested versions ##
# Ubuntu 22.04.1
# Ubuntu 22.04.2  

#avoid promps
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_SUSPEND=1

#Constants
PANDORA_CONSOLE=/var/www/html/pandora_console
PANDORA_SERVER_CONF=/etc/pandora/pandora_server.conf
PANDORA_AGENT_CONF=/etc/pandora/pandora_agent.conf
WORKDIR=/opt/pandora/deploy


S_VERSION='202302201'
LOGFILE="/tmp/deploy-ext-db-$(date +%F).log"
rm -f $LOGFILE &> /dev/null # remove last log before start

# define default variables
[ "$TZ" ] || TZ="Europe/Madrid"
[ "$DBHOST" ] || DBHOST=127.0.0.1
[ "$DBNAME" ] || DBNAME=pandora
[ "$DBUSER" ] || DBUSER=pandora
[ "$DBPASS" ] || DBPASS=pandora
[ "$DBPORT" ] || DBPORT=3306
[ "$DBROOTPASS" ] || DBROOTPASS=pandora
[ "$SKIP_DATABASE_INSTALL" ]     || SKIP_DATABASE_INSTALL=0
[ "$SKIP_KERNEL_OPTIMIZATIONS" ] || SKIP_KERNEL_OPTIMIZATIONS=0
[ "$POOL_SIZE" ] || POOL_SIZE=$(grep -i total /proc/meminfo | head -1 | awk '{printf "%.2f \n", $(NF-1)*0.4/1024}' | sed "s/\\..*$/M/g")


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
        rm -rf "$WORKDIR" &>> "$LOGFILE"
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
        rm -rf "$WORKDIR" &>> "$LOGFILE"
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
echo "Starting PandoraFMS External DB deployment Ubuntu 22.04 ver. $S_VERSION"

# Ubuntu Version
if [ ! "$(grep -Ei 'Ubuntu' /etc/lsb-release)" ]; then
         printf "\n ${red}Error this is not a Ubuntu system, this installer is compatible with Ubuntu systems only${reset}\n"
         exit 1
fi


echo -en "${cyan}Check Ubuntu Version...${reset}"
[ $(sed -nr 's/VERSION_ID+=\s*"([0-9][0-9].[0-9][0-9])"$/\1/p' /etc/os-release) == "22.04" ]
check_cmd_status 'Error OS version, Ubuntu 22.04 is expected'

#Detect OS
os_name=$(grep ^PRETTY_NAME= /etc/os-release | cut -d '=' -f2 | tr -d '"')
execute_cmd "echo $os_name" "OS detected: ${os_name}"

# initialice logfile
execute_cmd "echo 'Starting community deployment' > $LOGFILE" "All installer activity is logged on $LOGFILE"
echo "Community installer version: $S_VERSION" >> "$LOGFILE"

# Pre checks
# Root permisions
check_root_permissions

#Install awk, sed, grep  if not present
execute_cmd "apt install -y gawk sed grep" 'Installing needed tools'

# Systemd
execute_cmd "systemctl --version" "Checking SystemD" 'This is not a SystemD enable system, if tryng to use in a docker env please check: https://github.com/pandorafms/pandorafms/tree/develop/extras/docker/centos8'

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
execute_cmd "apt --version" 'Checking needed tools: apt'

# Creating working directory
rm -rf "$WORKDIR" &>> "$LOGFILE"
mkdir -p "$WORKDIR" &>> "$LOGFILE"
execute_cmd "cd $WORKDIR" "Moving to workdir:  $WORKDIR"

## Install utils
execute_cmd "apt update" "Updating repos"
execute_cmd "apt install -y net-tools vim curl wget software-properties-common apt-transport-https" "Installing utils"

# Disabling apparmor and ufw
systemctl stop ufw.service &>> "$LOGFILE"
systemctl disable ufw &>> "$LOGFILE"
systemctl stop apparmor &>> "$LOGFILE"
systemctl disable apparmor &>> "$LOGFILE"

#install mysql
execute_cmd "curl -O https://repo.percona.com/apt/percona-release_latest.generic_all.deb" "Downloading Percona repository for MySQL8"
execute_cmd "apt install -y gnupg2 lsb-release ./percona-release_latest.generic_all.deb" "Installing Percona repository for MySQL8"
execute_cmd "percona-release setup ps80" "Configuring Percona repository for MySQL8"

echo -en "${cyan}Installing Percona Server for MySQL8...${reset}"
    env DEBIAN_FRONTEND=noninteractive apt install -y percona-server-server percona-xtrabackup-80 &>> "$LOGFILE"
check_cmd_status "Error Installing MySql Server"

#Configuring Database
if [ "$SKIP_DATABASE_INSTALL" -eq '0' ] ; then
    execute_cmd "systemctl start mysql" "Starting database engine"
    
    echo """
    ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DBROOTPASS';
    """ | mysql -uroot &>> "$LOGFILE"

    export MYSQL_PWD=$DBROOTPASS
    echo -en "${cyan}Creating Pandora FMS database...${reset}"
    echo "create database $DBNAME" | mysql -uroot -P$DBPORT -h$DBHOST
    check_cmd_status "Error creating database $DBNAME, is this an empty node? if you have a previus installation please contact with support."

    echo "CREATE USER  \"$DBUSER\"@'%' IDENTIFIED BY \"$DBPASS\";" | mysql -uroot -P$DBPORT -h$DBHOST
    echo "ALTER USER \"$DBUSER\"@'%' IDENTIFIED WITH mysql_native_password BY \"$DBPASS\"" | mysql -uroot -P$DBPORT -h$DBHOST        
    echo "GRANT ALL PRIVILEGES ON $DBNAME.* TO \"$DBUSER\"@'%'" | mysql -uroot -P$DBPORT -h$DBHOST
fi
export MYSQL_PWD=$DBPASS

#Generating my.cnf
cat > /etc/mysql/my.cnf << EOF_DB
[mysqld]
datadir=/var/lib/mysql
user=mysql
character-set-server=utf8mb4
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
innodb_io_capacity = 300
thread_cache_size = 8
thread_stack    = 256K
max_connections = 100

key_buffer_size=4M
read_buffer_size=128K
read_rnd_buffer_size=128K
sort_buffer_size=128K
join_buffer_size=4M

skip-log-bin

sql_mode=""

log-error=/var/log/mysql/error.log
[mysqld_safe]
log-error=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EOF_DB

execute_cmd "systemctl restart mysql" "Configuring and restarting database engine"

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

# Remove temporary files
execute_cmd "echo done" "Percona server installed"
cd "$HOME"
execute_cmd "rm -rf $WORKDIR" "Removing temporary files"