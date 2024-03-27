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


S_VERSION='2023062901'
LOGFILE="/tmp/pandora-deploy-community-$(date +%F).log"
rm -f $LOGFILE &> /dev/null # remove last log before start

# define default variables
[ "$TZ" ] || TZ="Europe/Madrid"
[ "$PHPVER" ] || PHPVER=8.2
[ "$DBHOST" ] || DBHOST=127.0.0.1
[ "$DBNAME" ] || DBNAME=pandora
[ "$DBUSER" ] || DBUSER=pandora
[ "$DBPASS" ] || DBPASS='Pandor4!'
[ "$DBPORT" ] || DBPORT=3306
[ "$DBROOTPASS" ] || DBROOTPASS='Pandor4!'
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
cyan="\e[0;36m"
reset="\e[0m"

#force lts to install php 8.0
[ "$PANDORA_LTS" -eq '1' ] && PHPVER=8.0


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

check_pre_pandora () {
    export MYSQL_PWD=$DBPASS

    echo -en "${cyan}Checking environment ... ${reset}"
    [ -d "$PANDORA_CONSOLE" ] && local fail=true
    [ -f /usr/bin/pandora_server ] && local fail=true
    echo "use $DBNAME" | mysql -u$DBUSER -P$DBPORT -h$DBHOST &>> /dev/null && local fail=true

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
        echo "Please use a root account or sudo for installing Pandora FMS"
        echo "Error installing Pandora FMS for detailed error please check log: $LOGFILE"
        exit 1

    else
        echo -e "${green}OK${reset}"
    fi
}

# Function to check if a password meets the MySQL secure password requirements
is_mysql_secure_password() {
    local password=$1

    # Check password length (at least 8 characters)
    if [[ ${#password} -lt 8 ]]; then
        echo "Password length should be at least 8 characters."
        return 1
    fi

    # Check if password contains at least one uppercase letter
    if [[ $password == ${password,,} ]]; then
        echo "Password should contain at least one uppercase letter."
        return 1
    fi

    # Check if password contains at least one lowercase letter
    if [[ $password == ${password^^} ]]; then
        echo "Password should contain at least one lowercase letter."
        return 1
    fi

    # Check if password contains at least one digit
    if ! [[ $password =~ [0-9] ]]; then
        echo "Password should contain at least one digit."
        return 1
    fi

    # Check if password contains at least one special character
    if ! [[ $password =~ [[:punct:]] ]]; then
        echo "Password should contain at least one special character."
        return 1
    fi

    # Check if password is not a common pattern (e.g., "password", "123456")
    local common_patterns=("password" "123456" "qwerty")
    for pattern in "${common_patterns[@]}"; do
        if [[ $password == *"$pattern"* ]]; then
            echo "Password should not contain common patterns."
            return 1
        fi
    done

    # If all checks pass, the password is MySQL secure compliant
    return 0
}

installing_docker () {
    #Installing docker for debug
    echo "Start installig docker" &>> "$LOGFILE"
    mkdir -m 0755 -p /etc/apt/keyrings &>> "$LOGFILE"
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --yes --dearmor -o /etc/apt/keyrings/docker.gpg &>> "$LOGFILE"
    echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
        $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list &>> "$LOGFILE"
    apt update -y &>> "$LOGFILE"
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin &>> "$LOGFILE"
    systemctl disable docker --now &>> "$LOGFILE"
    systemctl disable docker.socket --now &>> "$LOGFILE"
    echo "End installig docker" &>> "$LOGFILE"
}

## Main
echo "Starting PandoraFMS Community deployment Ubuntu 22.04 ver. $S_VERSION"

#check tools
if ! grep --version &>> $LOGFILE ; then echo 'Error grep is not detected on the system, grep tool is needed for installation.'; exit -1 ;fi 
if ! sed --version &>> $LOGFILE ; then echo 'Error sed is not detected on the system, sed tool is needed for installation.'; exit -1 ;fi 
if ! curl --version &>> $LOGFILE ; then echo 'Error curl is not detected on the system, curl tool is needed for installation.'; exit -1 ;fi 
if ! ping -V &>> $LOGFILE ; then echo 'Error ping is not detected on the system, ping tool is needed for installation.'; exit -1 ;fi 

# Ubuntu Version
if [ ! "$(grep -Ei 'Ubuntu' /etc/lsb-release)" ]; then
         printf "\n ${red}Error this is not a Ubuntu system, this installer is compatible with Ubuntu systems only${reset}\n"
         exit 1
fi


echo -en "${cyan}Check Ubuntu Version...${reset}"
[[ $(sed -nr 's/VERSION_ID+=\s*"([0-9][0-9].[0-9][0-9])"$/\1/p' /etc/os-release) == "22.04" ]]
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

# Pre installed pandora
[ "$SKIP_PRECHECK" == 1 ] || check_pre_pandora

#advicing BETA PROGRAM
INSTALLING_VER="${green}RRR version enable using RRR PandoraFMS packages${reset}"
[ "$PANDORA_LTS" -ne '0' ] && INSTALLING_VER="${green}LTS version enable using LTS PandoraFMS packages${reset}"
[ "$PANDORA_BETA" -ne '0' ] && INSTALLING_VER="${red}BETA version enable using nightly PandoraFMS packages${reset}"
echo -e $INSTALLING_VER

# Connectivity
check_repo_connection

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

#Check mysql pass
execute_cmd "is_mysql_secure_password $DBROOTPASS" "Checking DBROOTPASS password match policy" 'This password do not match minimum MySQL policy requirements, more info in: https://dev.mysql.com/doc/refman/8.0/en/validate-password.html'
execute_cmd "is_mysql_secure_password $DBPASS" "Checking DBPASS password match policy" 'This password do not match minimum MySQL policy requirements, more info in: https://dev.mysql.com/doc/refman/8.0/en/validate-password.html'

# Creating working directory
rm -rf "$WORKDIR" &>> "$LOGFILE"
mkdir -p "$WORKDIR" &>> "$LOGFILE"
execute_cmd "cd $WORKDIR" "Moving to workdir:  $WORKDIR"

## Install utils
execute_cmd "apt update" "Updating repos"
execute_cmd "apt install -y net-tools vim curl wget software-properties-common apt-transport-https ca-certificates gnupg lsb-release" "Installing utils"

#Installing Apache and php-fpm
[ -e "/etc/apt/sources.list.d/ondrej-ubuntu-php-jammy.list" ] || execute_cmd "add-apt-repository ppa:ondrej/php -y" "Enable ppa:ondrej/php repo"
execute_cmd "apt update" "Updating repos"
execute_cmd "apt install -y php$PHPVER-fpm php$PHPVER-common libapache2-mod-fcgid php$PHPVER-cli apache2" "Installing apache and php-fpm"
#execute_cmd "a2enmod proxy_fcgi setenvif && a2enconf php$PHPVER-fpm" "Enabling php-fpm"
echo -en "${cyan}Enabling php$PHPVER-fpm...${reset}"
    a2enmod proxy_fcgi setenvif &>> "$LOGFILE" && a2enconf php$PHPVER-fpm &>> "$LOGFILE"
check_cmd_status "Error enabling php$PHPVER-fpm "
systemctl restart php$PHPVER-fpm &>> "$LOGFILE"

# Console dependencies
	console_dependencies=" \
	ldap-utils \
	postfix \
	wget \
	graphviz  \
	xfonts-75dpi \
	xfonts-100dpi \
	xfonts-ayu \
	xfonts-intl-arabic \
	xfonts-intl-asian \
	xfonts-intl-phonetic \
	xfonts-intl-japanese-big \
	xfonts-intl-european \
	xfonts-intl-chinese \
	xfonts-intl-japanese \
	xfonts-intl-chinese-big \
	libzstd1 \
	gir1.2-atk-1.0 \
	libavahi-common-data \
	cairo-perf-utils \
	libfribidi-bin \
	php$PHPVER-mcrypt \
	php$PHPVER-gd  \
	php$PHPVER-curl \
	php$PHPVER-mysql \
	php$PHPVER-ldap \
	php$PHPVER-fileinfo \
	php$PHPVER-gettext \
	php$PHPVER-snmp  \
	php$PHPVER-mbstring \
	php$PHPVER-zip  \
	php$PHPVER-xmlrpc \
	php$PHPVER-xml \
	php$PHPVER-yaml \
	libnet-telnet-perl \
    whois \
    cron"
execute_cmd "apt install -y $console_dependencies" "Installing Pandora FMS Console dependencies"

# Server dependencies
server_dependencies=" \
	perl  \
	nmap  \
	fping \
	sudo \
	net-tools \
	nfdump \
	expect \
	openssh-client \
	postfix \
	unzip \
	coreutils \
	libio-compress-perl \
	libmoosex-role-timer-perl \
	libdbd-mysql-perl \
	libcrypt-mysql-perl \
	libhttp-request-ascgi-perl \
	liblwp-useragent-chicaching-perl \
	liblwp-protocol-https-perl \
	snmp \
	libnetaddr-ip-perl \
	libio-socket-ssl-perl \
	libio-socket-socks-perl \
	libio-socket-ip-perl \
	libio-socket-inet6-perl \
	libnet-telnet-perl \
	libjson-perl \
	libencode-perl \
    cron \
	libgeo-ip-perl \
    arping \
    snmp-mibs-downloader \
    snmptrapd \
    libnsl2 \
	openjdk-8-jdk "
execute_cmd "apt install -y $server_dependencies" "Installing Pandora FMS Server dependencies"

execute_cmd "installing_docker" "Installing Docker for debug"

# Installing pandora_gotty
execute_cmd "curl --output pandora_gotty.deb https://firefly.pandorafms.com/ubuntu/pandora_gotty_1.0.0.deb" "Downloading pandora_gotty"
execute_cmd "apt install -y ./pandora_gotty.deb" "Intalling pandora_gotty"

# Installing MADE
execute_cmd "curl --output pandora_made.deb https://firefly.pandorafms.com/ubuntu/pandorafms-made_0.1.0-2_amd64.deb" "Downloading pandora MADE"
execute_cmd "apt install -y ./pandora_made.deb" "Intalling pandora MADE"

# wmic and pandorawmic
execute_cmd "curl -O https://firefly.pandorafms.com/pandorafms/utils/bin/wmic" "Downloading wmic"
execute_cmd "curl -O https://firefly.pandorafms.com/pandorafms/utils/bin/pandorawmic" "Downloading pandorawmic"
echo -en "${cyan}Installing wmic and pandorawmic...${reset}"
    chmod +x pandorawmic wmic &>> "$LOGFILE" && \
    cp -a wmic /usr/bin/ &>> "$LOGFILE" && \
    cp -a pandorawmic /usr/bin/ &>> "$LOGFILE"
check_cmd_status "Error Installing pandorawmic/wmic"

# create symlink for fping
rm -f /usr/sbin/fping &>> "$LOGFILE"
ln -s /usr/bin/fping /usr/sbin/fping &>> "$LOGFILE"

# Chrome
rm -f /usr/bin/chromium-browser &>> "$LOGFILE"
CHROME_VERSION=google-chrome-stable_110.0.5481.177-1_amd64.deb
execute_cmd "wget https://dl.google.com/linux/deb/pool/main/g/google-chrome-stable/${CHROME_VERSION}" "Downloading google chrome"
execute_cmd "apt install -y ./${CHROME_VERSION}" "Intalling google chrome"
execute_cmd "ln -s /usr/bin/google-chrome /usr/bin/chromium-browser" "Creating /usr/bin/chromium-browser Symlink"

# SDK VMware perl dependencies
vmware_dependencies="\
    lib32z1  \
    lib32z1 \
    build-essential \
    uuid uuid-dev \
    libssl-dev \
    perl-doc \
    libxml-libxml-perl \
    libcrypt-ssleay-perl \
    libsoap-lite-perl \
    libmodule-build-perl"
execute_cmd "apt install -y $vmware_dependencies" "Installing VMware SDK dependencies"
execute_cmd "wget https://firefly.pandorafms.com/pandorafms/utils/VMware-vSphere-Perl-SDK-7.0.0-16453907.x86_64.tar.gz" "Downloading VMware SDK"
echo -en "${cyan}Installing VMware SDK...${reset}"
    tar xvzf VMware-vSphere-Perl-SDK-7.0.0-16453907.x86_64.tar.gz &>> "$LOGFILE"
    cd vmware-vsphere-cli-distrib/ &>> "$LOGFILE"
    sed --follow-symlinks -i -e "s/[^#].*show_EULA().*/  #show_EULA();/g" vmware-install.pl &>> "$LOGFILE"
    ./vmware-install.pl --default &>> "$LOGFILE"
check_cmd_status "Error Installing VMware SDK"
execute_cmd "cpan Crypt::OpenSSL::AES" "Installing extra vmware dependencie" 
cd $WORKDIR &>> "$LOGFILE"



# Instant client Oracle
execute_cmd "mkdir -p /opt/oracle" "Creating Oracle instant client directory /opt/oracle"
execute_cmd "wget https://download.oracle.com/otn_software/linux/instantclient/19800/instantclient-basic-linux.x64-19.8.0.0.0dbru.zip" "Downloading Oracle instant client"
execute_cmd "wget https://download.oracle.com/otn_software/linux/instantclient/19800/instantclient-sqlplus-linux.x64-19.8.0.0.0dbru.zip" "Downloading Oracle sqlplus"
echo -en "${cyan}Installing Oracle instant client...${reset}"
    rm -fr /opt/oracle/* &>> "$LOGFILE"
    unzip instantclient-basic-linux.x64-19.8.0.0.0dbru.zip -d /opt/oracle/ &>> "$LOGFILE"
    unzip instantclient-sqlplus-linux.x64-19.8.0.0.0dbru.zip -d /opt/oracle/ &>> "$LOGFILE"
check_cmd_status "Error Installing Oracle instant client"

#Configuring env variables
cat >> /root/.profile << 'EOF_ENV'
#!/bin/bash
VERSION=19.8
export PATH=$PATH:/opt/oracle/instantclient_19_8
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/opt/oracle/instantclient_19_8
export ORACLE_HOME=/opt/oracle/instantclient_19_8
EOF_ENV

source '/root/.profile' &>> "$LOGFILE"

#ipam dependencies
ipam_dependencies=" \
    libnetaddr-ip-perl \
    coreutils \
    libdbd-mysql-perl \
    libxml-simple-perl \
    libgeo-ip-perl \
    libio-socket-inet6-perl \
    libxml-twig-perl \
    libnetaddr-ip-perl"
execute_cmd "apt install -y $ipam_dependencies" "Installing IPAM Dependencies"

# MSSQL dependencies el8
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | tee /etc/apt/trusted.gpg.d/microsoft.asc &>> "$LOGFILE"
curl -sSL https://packages.microsoft.com/config/ubuntu/20.04/prod.list | tee /etc/apt/sources.list.d/microsoft-prod.list &>> "$LOGFILE"
apt update &>> "$LOGFILE"
execute_cmd "env ACCEPT_EULA=Y apt install -y msodbcsql17" "Installing ODBC Driver for Microsoft(R) SQL Server(R)"
MS_ID=$(head -1 /etc/odbcinst.ini | tr -d '[]') &>> "$LOGFILE"

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
    echo "INSTALL COMPONENT 'file://component_validate_password';" | mysql -uroot -P$DBPORT -h$DBHOST &>> "$LOGFILE"
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


#Define packages
if [ "$PANDORA_LTS" -eq '1' ] ; then
    [ "$PANDORA_SERVER_PACKAGE" ]       || PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/LTS/pandorafms_server-7.0NG.tar.gz"
    [ "$PANDORA_CONSOLE_PACKAGE" ]      || PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/LTS/pandorafms_console-7.0NG.tar.gz"
    [ "$PANDORA_AGENT_PACKAGE" ]        || PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_agent_linux-7.0NG.x86_64.tar.gz"
elif [ "$PANDORA_LTS" -ne '1' ] ; then
    [ "$PANDORA_SERVER_PACKAGE" ]       || PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_server-7.0NG.tar.gz"
    [ "$PANDORA_CONSOLE_PACKAGE" ]      || PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_console-7.0NG.tar.gz"
    [ "$PANDORA_AGENT_PACKAGE" ]        || PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_agent_linux-7.0NG.x86_64.tar.gz"
fi

if [ "$PANDORA_BETA" -eq '1' ] ; then
    PANDORA_SERVER_PACKAGE="https://firefly.pandorafms.com/pandora_enterprise_nightlies/pandorafms_server-latest.tar.gz"
    PANDORA_CONSOLE_PACKAGE="https://firefly.pandorafms.com/pandora_enterprise_nightlies/pandorafms_console-latest.tar.gz"
    PANDORA_AGENT_PACKAGE="https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_agent_linux-7.0NG.x86_64.tar.gz"
fi

# Downloading Pandora Packages
cd $WORKDIR &>> "$LOGFILE"

curl -LSs --output pandorafms_console-7.0NG.tar.gz "${PANDORA_CONSOLE_PACKAGE}" &>> "$LOGFILE"
curl -LSs --output pandorafms_server-7.0NG.tar.gz "${PANDORA_SERVER_PACKAGE}" &>> "$LOGFILE"
curl -LSs --output pandorafms_agent_linux-7.0NG.tar.gz "${PANDORA_AGENT_PACKAGE}" &>> "$LOGFILE"

# Install PandoraFMS Console
echo -en "${cyan}Installing PandoraFMS Console...${reset}"
    tar xvzf pandorafms_console-7.0NG.tar.gz &>> "$LOGFILE" && cp -Ra pandora_console /var/www/html/ &>> "$LOGFILE"
check_cmd_status "Error installing PandoraFMS  Console"
rm -f $PANDORA_CONSOLE/*.spec &>> "$LOGFILE"

# Install Pandora FMS Server
echo -en "${cyan}Installing PandoraFMS Server...${reset}"
    useradd pandora  &>> "$LOGFILE"
    tar xvfz $WORKDIR/pandorafms_server-7.0NG.tar.gz &>> $LOGFILE && cd pandora_server && ./pandora_server_installer --install &>> $LOGFILE && cd $WORKDIR &>> $LOGFILE
check_cmd_status "Error installing PandoraFMS  Server"

#Install agent:
execute_cmd "apt install -y libyaml-tiny-perl perl coreutils wget curl unzip procps python3 python3-pip" "Installing PandoraFMS Agent Dependencies"
echo -en "${cyan}Installing PandoraFMS Agent...${reset}"
    tar xvzf $WORKDIR/pandorafms_agent_linux-7.0NG.tar.gz &>> "$LOGFILE" && cd unix && ./pandora_agent_installer --install &>> $LOGFILE && cp -a tentacle_client /usr/local/bin/ &>> $LOGFILE && cd $WORKDIR
check_cmd_status "Error installing PandoraFMS Agent"

# Copy gotty utility
cd $WORKDIR &>> "$LOGFILE"
execute_cmd "wget https://firefly.pandorafms.com/pandorafms/utils/gotty_linux_amd64.tar.gz" 'Dowloading gotty util'
tar xvzf gotty_linux_amd64.tar.gz &>> $LOGFILE
execute_cmd "mv gotty /usr/bin/" 'Installing gotty util'

# Config servicesa
#Configure apache2
#Enable SSL connections
cat > /etc/apache2/conf-available/ssl-params.conf << EOF_PARAM
SSLCipherSuite EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH
    
    SSLProtocol All -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    
    SSLHonorCipherOrder On
    
    
    Header always set X-Frame-Options DENY
    
    Header always set X-Content-Type-Options nosniff
    
    # Requires Apache >= 2.4
    
    SSLCompression off
    
    SSLUseStapling on
    
    SSLStaplingCache "shmcb:logs/stapling-cache(150000)"
    
    
    # Requires Apache >= 2.4.11
    
    SSLSessionTickets Off
EOF_PARAM

a2enmod ssl &>> "$LOGFILE"
a2enmod headers &>> "$LOGFILE"
a2enconf ssl-params &>> "$LOGFILE"
a2ensite default-ssl &>> "$LOGFILE"
a2enconf ssl-params &>> "$LOGFILE"
apache2ctl configtest &>> "$LOGFILE"

execute_cmd "systemctl restart apache2" "Enable SSL mod and Restarting Apache2"

execute_cmd "systemctl enable mysql --now" "Enabling Database service"
execute_cmd "systemctl enable apache2 --now" "Enabling Apache2 service"
execute_cmd "systemctl enable php$PHPVER-fpm --now" "Enabling php$PHPVER-fpm service"


# Populate Database
echo -en "${cyan}Loading pandoradb.sql to $DBNAME database...${reset}"
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb.sql &>> "$LOGFILE"
check_cmd_status 'Error Loading database schema'

echo -en "${cyan}Loading pandoradb_data.sql to $DBNAME database...${reset}"
mysql -u$DBUSER -P$DBPORT -h$DBHOST $DBNAME < $PANDORA_CONSOLE/pandoradb_data.sql &>> "$LOGFILE"
check_cmd_status 'Error Loading database schema data'

# Configure console
# Set console config file
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

#Enable allow Override
cat > /etc/apache2/conf-enabled/pandora_security.conf << EO_CONFIG_F
ServerTokens Prod
<Directory "/var/www/html">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EO_CONFIG_F

#Enable quickshell proxy
cat >> /etc/apache2/mods-enabled/00-proxy.conf << 'EO_HTTPD_WSTUNNEL'
ProxyRequests Off
<Proxy *>
    Require all granted
</Proxy>

ProxyPass /ws ws://127.0.0.1:8080
ProxyPassReverse /ws ws://127.0.0.1:8080
EO_HTTPD_WSTUNNEL

# Fixing console permissions
chmod 600 $PANDORA_CONSOLE/include/config.php &>> "$LOGFILE"
chown -R www-data:www-data $PANDORA_CONSOLE &>> "$LOGFILE"
mv $PANDORA_CONSOLE/install.php $PANDORA_CONSOLE/install.done &>> "$LOGFILE"

# Prepare php.ini
## Prepare php config
ln -s /etc/php/$PHPVER/fpm/php.ini /etc/
sed --follow-symlinks -i -e "s/^max_input_time.*/max_input_time = -1/g" /etc/php.ini
sed --follow-symlinks -i -e "s/^max_execution_time.*/max_execution_time = 0/g" /etc/php.ini
sed --follow-symlinks -i -e "s/^upload_max_filesize.*/upload_max_filesize = 800M/g" /etc/php.ini
sed --follow-symlinks -i -e "s/^memory_limit.*/memory_limit = 800M/g" /etc/php.ini
sed --follow-symlinks -i -e "s/.*post_max_size =.*/post_max_size = 800M/" /etc/php.ini
sed --follow-symlinks -i -e "s/^disable_functions/;disable_functions/" /etc/php.ini

#adding 900s to httpd timeout and 300 to ProxyTimeout
echo 'TimeOut 900' > /etc/apache2/conf-enabled/timeout.conf
echo 'ProxyTimeout 300' >> /etc/apache2/conf-enabled/timeout.conf

cat > /var/www/html/index.html << EOF_INDEX
<meta HTTP-EQUIV="REFRESH" content="0; url=/pandora_console/">
EOF_INDEX

execute_cmd "systemctl restart apache2" "Restarting apache2 after configuration"
execute_cmd "systemctl restart php$PHPVER-fpm" "Restarting php$PHPVER-fpm after configuration"

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

# Adding group www-data to pandora server conf.
grep -q "group www-data" $PANDORA_SERVER_CONF || \
cat >> $PANDORA_SERVER_CONF << EOF_G

#Adding group www-data to assing remote-config permission correctly for ubuntu 22.04
group www-data
EOF_G

# Enable agent remote config
sed -i "s/^remote_config.*$/remote_config 1/g" $PANDORA_AGENT_CONF 

# Set Oracle environment for pandora_server
cat > /etc/pandora/pandora_server.env << 'EOF_ENV'
#!/bin/bash
VERSION=19.8
export PATH=$PATH:/opt/oracle/instantclient_19_8
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/opt/oracle/instantclient_19_8
export ORACLE_HOME=/opt/oracle/instantclient_19_8
export OPENSSL_CONF=/etc/ssl
EOF_ENV

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
chown pandora:www-data /var/log/pandora
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
mv /var/www/html/pandora_console/pandora_websocket_engine /etc/init.d/ &>> "$LOGFILE"
chmod +x /etc/init.d/pandora_websocket_engine &>> "$LOGFILE"

# Start Websocket engine
/etc/init.d/pandora_websocket_engine start &>> "$LOGFILE"

# Configure websocket to be started at start.
systemctl enable pandora_websocket_engine &>> "$LOGFILE"

# Enable pandora ha service
execute_cmd "/etc/init.d/pandora_server start" "Starting Pandora FMS Server"
systemctl enable pandora_server &>> "$LOGFILE"

# starting tentacle server
execute_cmd "service tentacle_serverd start" "Starting Tentacle Server"
systemctl enable tentacle_serverd &>> "$LOGFILE"

# Enabling console cron
execute_cmd "echo \"* * * * * root wget -q -O - --no-check-certificate --load-cookies /tmp/cron-session-cookies --save-cookies /tmp/cron-session-cookies --keep-session-cookies http://127.0.0.1/pandora_console/cron.php >> $PANDORA_CONSOLE/log/cron.log\" >> /etc/crontab" "Enabling Pandora FMS Console cron"
echo "* * * * * root wget -q -O - --no-check-certificate --load-cookies /tmp/cron-session-cookies --save-cookies /tmp/cron-session-cookies --keep-session-cookies http://127.0.0.1/pandora_console/cron.php >> $PANDORA_CONSOLE/log/cron.log" >> /etc/crontab

# Enabling pandoradb cron
execute_cmd "echo 'enabling pandoradb cron' >> $PANDORA_CONSOLE/log/cron.log\" >> /etc/crontab" "Enabling Pandora FMS pandoradb cron"
echo "@hourly         root    bash -c /etc/cron.hourly/pandora_db" >> /etc/crontab


## Enabling agent adn configuring Agente
sed -i "s/^remote_config.*$/remote_config 1/g" $PANDORA_AGENT_CONF &>> "$LOGFILE"
execute_cmd "/etc/init.d/pandora_agent_daemon restart" "Starting PandoraFSM Agent"
systemctl enable pandora_agent_daemon &>> "$LOGFILE"

#fix path phantomjs
sed --follow-symlinks -i -e "s/^openssl_conf = openssl_init/#openssl_conf = openssl_init/g" /etc/ssl/openssl.cnf &>> "$LOGFILE"

# Enable postfix
systemctl enable postfix --now &>> "$LOGFILE"

# Disable snmptrapd
systemctl disable --now snmptrapd &>> "$LOGFILE"
systemctl disable --now snmptrapd.socket &>> "$LOGFILE"

# Adding legacy to openssl
sed -i '/default = default_sect/a legacy = legacy_sect' /etc/ssl/openssl.cnf
sed -i 's/# activate = 1/activate = 1/' /etc/ssl/openssl.cnf
sed -i '/activate = 1/a [legacy_sect]\nactivate = 1' /etc/ssl/openssl.cnf

#SSH banner
[ "$(curl -s ifconfig.me)" ] && ipplublic=$(curl -s ifconfig.me)

cat > /etc/issue.net << EOF_banner

Welcome to Pandora FMS appliance on Ubuntu
------------------------------------------
Go to Public http://$ipplublic/pandora_console to login web console
$(ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v "172.17.0.1" | awk '{print $2}' | awk -F '/' '{print "Go to Local http://"$1"/pandora_console to login web console"}')

You can find more information at http://pandorafms.com

EOF_banner

rm -f /etc/issue
ln -s /etc/issue.net /etc/issue

echo 'Banner /etc/issue.net' >> /etc/ssh/sshd_config

# Remove temporary files
execute_cmd "echo done" "Pandora FMS Community installed"
cd "$HOME"
execute_cmd "rm -rf $WORKDIR" "Removing temporary files"

# Print nice finish message
GREEN='\033[01;32m'
NONE='\033[0m'
printf " -> Go to Public ${green}http://"$ipplublic"/pandora_console${reset} to manage this server"
ip addr | grep -w "inet" | grep -v "127.0.0.1" | grep -v -e "172.1[0-9].0.1" | awk '{print $2}' | awk -v g=$GREEN -v n=$NONE -F '/' '{printf "\n -> Go to Local "g"http://"$1"/pandora_console"n" to manage this server \n -> Use these credentials to log in Pandora Console "g"[ User: admin / Password: pandora ]"n" \n"}'
