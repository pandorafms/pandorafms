#!/bin/bash

#export PANDORA_SERVER_IP='newdemos.artica.es' && curl -sSL http://firefly.pandorafms.com/projects/pandora_deploy_agent.sh | bash

# define variables
PANDORA_AGENT_CONF=/etc/pandora/pandora_agent.conf
S_VERSION='2023050901'
LOGFILE="/tmp/pandora-agent-deploy-$(date +%F).log"

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
yellow="\e[0;33m"
reset="\e[0m"

# Functions

execute_cmd () {
    local cmd="$1"
    local msg="$2"

    echo -e "${cyan}$msg...${reset}"
    $cmd &>> $LOGFILE
    if [ $? -ne 0 ]; then
        echo -e "${red}Fail${reset}"
        [[ $3 ]] && echo "$3 "
        echo "Error installing Pandora FMS Agent for detailed error please check log: $LOGFILE"
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
        echo "Error installing Pandora FMS Agent for detailed error please check log: $LOGFILE"
        rm -rf $HOME/pandora_deploy_tmp/*.rpm* &>> $LOGFILE
        exit 1
    else            
        echo -e "${green}OK${reset}"
        return 0
    fi
}

check_repo_connection () {
    execute_cmd "ping -c 2 firefly.pandorafms.com" "Checking Community repo"
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

install_tarball () {
tar xvzf $1
cd unix && ./pandora_agent_installer --install
}


# install_autodiscover () {
#     local arch=$1
#     wget http://firefly.pandorafms.com/projects/autodiscover-linux.zip
#     unzip autodiscover-linux.zip
#     chmod +x $arch/autodiscover 
#     mv -f $arch/autodiscover /etc/pandora/plugins/autodiscover
# }

## Main
echo "Starting PandoraFMS Agent binary deployment ver. $S_VERSION"

execute_cmd  "[ $PANDORA_SERVER_IP ]" 'Check Server IP Address' 'Please define env variable PANDORA_SERVER_IP'

if ! grep --version &>> $LOGFILE ; then echo 'Error grep is not detected on the system, grep tool is needed for installation.'; exit -1 ;fi 
if ! sed --version &>> $LOGFILE ; then echo 'Error sed is not detected on the system, sed tool is needed for installation.'; exit -1 ;fi 

#Detect OS
os_name=$(grep ^PRETTY_NAME= /etc/os-release | cut -d '=' -f2 | tr -d '"')
execute_cmd "echo $os_name" "OS detected: ${os_name}"

# Check OS.
OS=$([[ $(grep '^ID_LIKE=' /etc/os-release) ]] && grep ^ID_LIKE= /etc/os-release | cut -d '=' -f2 | tr -d '"' || grep ^ID= /etc/os-release | cut -d '=' -f2 | tr -d '"')

[[ $OS =~ 'rhel' ]] &&  OS_RELEASE=$OS
[[ $OS =~ 'fedora' ]] &&  OS_RELEASE=$OS
[[ $OS =~ 'debian' ]] &&  OS_RELEASE=$OS

# initialice logfile
execute_cmd "echo 'Starting community deployment' > $LOGFILE" "All installer activity is logged on $LOGFILE"
echo "Community installer version: $S_VERSION" >> $LOGFILE

# Pre checks
echo -en "${cyan}Checking compatible OS... ${reset}"
[[ $OS_RELEASE ]]
check_cmd_status "Error not compatible OS, $OS"

# Root permisions
check_root_permissions

# Connectivity
check_repo_connection

# Execute tools check
execute_cmd "grep --version" 'Checking needed tools: grep'
execute_cmd "sed --version" 'Checking needed tools: sed'

# Arch check
arch=$(uname -m)
case $arch in

  x86_64)
    echo -e "${cyan}Arch: $arch ${reset} "
    ;;

  x86)
    echo -e "${yellow}Skiping installation arch: $arch not suported by binary agent please consider to install source agent${reset}"
    exit -1
    ;;

  armv7l)
    echo -e "${yellow}Skiping installation arch: $arch not suported by binary agent please consider to install source agent${reset}"
    exit -1
    ;;

  *)
    echo -e "${yellow}Skiping installation arch: $arch not suported by binary agent please consider to install source agent${reset}"
    exit -1
    ;;
esac

# Creating working directory
rm -rf $HOME/pandora_deploy_tmp/ &>> $LOGFILE
mkdir $HOME/pandora_deploy_tmp &>> $LOGFILE
execute_cmd "cd $HOME/pandora_deploy_tmp" "Moving to workspace:  $HOME/pandora_deploy_tmp"

# Downloading and installing packages

if [[ $OS_RELEASE =~ 'rhel' ]] || [[ $OS_RELEASE =~ 'fedora' ]]; then
    ## Extra steps on redhat
    if [ "$(grep -Ei 'Red Hat Enterprise' /etc/redhat-release)" ]; then
        ## In case REDHAT
        # Check susbscription manager status:
        echo -en "${cyan}Checking Red Hat Enterprise subscription... ${reset}"
        subscription-manager list &>> "$LOGFILE"
        subscription-manager status &>> "$LOGFILE"
        check_cmd_status 'Error checking subscription status, make sure your server is activated and suscribed to Red Hat Enterprise repositories'

    fi

    # Check rh version
    if [ $(sed -nr 's/VERSION_ID+=\s*"([0-9]).*"$/\1/p' /etc/os-release) -eq '8' ] ; then
        package_manager_cmd=dnf
        execute_cmd "$package_manager_cmd install -y libnsl" "Installing dependencies" 
    elif [ $(sed -nr 's/VERSION_ID+=\s*"([0-9]).*"$/\1/p' /etc/os-release) -eq '9' ] ; then
        package_manager_cmd=dnf
        execute_cmd "$package_manager_cmd install -y libnsl libxcrypt-compat" "Installing dependencies" 
    elif [ $(sed -nr 's/VERSION_ID+=\s*"([0-9]).*"$/\1/p' /etc/os-release) -eq '7' ] ; then
        package_manager_cmd=yum

    fi

    # Install dependencies
    $package_manager_cmd install -y perl wget curl perl-Sys-Syslog unzip &>> $LOGFILE 
    echo -e "${cyan}Installing agent dependencies...${reset}" ${green}OK${reset}
    
    # Insatall pandora agent  
    [ "$PANDORA_AGENT_PACKAGE_EL" ] || PANDORA_AGENT_PACKAGE_EL="https://firefly.pandorafms.com/pandorafms/latest/RHEL_CentOS/pandorafms_agent_linux_bin-7.0NG.x86_64.rpm "
    execute_cmd "$package_manager_cmd install -y ${PANDORA_AGENT_PACKAGE_EL}" 'Installing Pandora FMS agent package'
    #[[ $PANDORA_AGENT_SSL ]] && execute_cmd "$package_manager_cmd install -y perl-IO-Socket-SSL" "Installing SSL libraries for encrypted connection"

fi

if [[ $OS_RELEASE == 'debian' ]]; then
    [ "$PANDORA_AGENT_PACKAGE_UBUNTU" ] || PANDORA_AGENT_PACKAGE_UBUNTU='https://firefly.pandorafms.com/pandorafms/latest/Tarball/pandorafms_agent_linux-7.0NG_x86_64.tar.gz'
    execute_cmd "apt update" 'Updating repos'
    execute_cmd "apt install -y perl wget curl unzip procps python3 python3-pip" 'Installing agent dependencies'
    execute_cmd "curl --output pandorafms_agent_linux-7.0NG.tar.gz ${PANDORA_AGENT_PACKAGE_UBUNTU}" 'Downloading Pandora FMS agent package'
    execute_cmd 'install_tarball pandorafms_agent_linux-7.0NG.tar.gz' 'Installing Pandora FMS agent'
    #[[ $PANDORA_AGENT_SSL ]] && execute_cmd 'apt install -y libio-socket-ssl-perl' "Installing SSL libraries for encrypted connection"
    cd $HOME/pandora_deploy_tmp
fi

# Configuring Agente
[[ $PANDORA_SERVER_IP ]] && sed -i "s/^server_ip.*$/server_ip $PANDORA_SERVER_IP/g" $PANDORA_AGENT_CONF 
[[ $PANDORA_REMOTE_CONFIG ]] && sed -i "s/^remote_config.*$/remote_config $PANDORA_REMOTE_CONFIG/g" $PANDORA_AGENT_CONF 
[[ $PANDORA_GROUP ]] && sed -i "s/^group.*$/group $PANDORA_GROUP/g" $PANDORA_AGENT_CONF
[[ $PANDORA_DEBUG ]] && sed -i "s/^debug.*$/debug $PANDORA_DEBUG/g" $PANDORA_AGENT_CONF
[[ $PANDORA_AGENT_NAME ]] && sed -i "s/^#agent_name.*$/agent_name $PANDORA_AGENT_NAME/g" $PANDORA_AGENT_CONF
[[ $PANDORA_AGENT_ALIAS ]] && sed -i "s/^#agent_alias.*$/agent_alias $PANDORA_AGENT_ALIAS/g" $PANDORA_AGENT_CONF
[[ $PANDORA_SECONDARY_GROUPS ]] && sed -i "s/^# secondary_groups.*$/secondary_groups $PANDORA_SECONDARY_GROUPS/g" $PANDORA_AGENT_CONF
[[ $TIMEZONE ]] && ln -sfn /usr/share/zoneinfo/$TIMEZONE /etc/localtime
[[ $PANDORA_AGENT_SSL ]] && sed -i "s/^#server_ssl.*$/server_ssl $PANDORA_AGENT_SSL/g" $PANDORA_AGENT_CONF 



#Starting pandora agent daemon.
execute_cmd '/etc/init.d/pandora_agent_daemon restart' 'Starting Pandora Agent'
cd
execute_cmd 'rm -rf $HOME/pandora_deploy_tmp' 'Cleaning up temporay files'

echo -e "${green}PandoraFMS Agent installed and running, sending data to: $PANDORA_SERVER_IP${reset}"
