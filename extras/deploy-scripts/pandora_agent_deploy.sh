#!/bin/bash

#export PANDORA_SERVER_IP='newdemos.artica.es' && curl -sSL http://firefly.artica.es/projects/pandora_deploy_agent.sh | bash

# define variables
PANDORA_AGENT_CONF=/etc/pandora/pandora_agent.conf
S_VERSION='2021012801'
LOGFILE="/tmp/pandora-agent-deploy-$(date +%F).log"

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
    execute_cmd "ping -c 2 8.8.8.8" "Checking internet connection"
    execute_cmd "ping -c 2 firefly.artica.es" "Checking Community repo"
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

install_autodiscover () {
    local arch=$1
    wget http://firefly.artica.es/projects/autodiscover-linux.zip
    unzip autodiscover-linux.zip
    chmod +x $arch/autodiscover 
    mv -f $arch/autodiscover /etc/pandora/plugins/autodiscover
}

## Main
echo "Starting PandoraFMS Agent deployment ver. $S_VERSION"

execute_cmd  "[ $PANDORA_SERVER_IP ]" 'Check Server IP Address' 'Please define env variable PANDORA_SERVER_IP'

# Check OS.
OS=$([[ $(grep '^ID_LIKE=' /etc/os-release) ]] && grep ^ID_LIKE= /etc/os-release | cut -d '=' -f2 | tr -d '"' || grep ^ID= /etc/os-release | cut -d '=' -f2 | tr -d '"')

[[ $OS == 'rhel fedora' ]] &&  OS_RELEASE=$OS
[[ $OS == 'centos rhel fedora' ]] &&  OS_RELEASE=$OS
[[ $OS == 'debian' ]] &&  OS_RELEASE=$OS

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

# Creating working directory
rm -rf $HOME/pandora_deploy_tmp/ &>> $LOGFILE
mkdir $HOME/pandora_deploy_tmp &>> $LOGFILE
execute_cmd "cd $HOME/pandora_deploy_tmp" "Moving to workspace:  $HOME/pandora_deploy_tmp"

# Downloading and installing packages

if [[ $OS_RELEASE == 'rhel fedora' ]] || [[ $OS_RELEASE == 'centos rhel fedora' ]]; then
    yum install -y perl wget curl perl-Sys-Syslog unzip &>> $LOGFILE 
    echo -e "${cyan}Instaling agent dependencies...${reset}" ${green}OK${reset}
    
    yum install -y http://firefly.artica.es/pandorafms/latest/RHEL_CentOS/pandorafms_agent_unix-7.0NG.noarch.rpm &>> $LOGFILE
    echo -e "${cyan}Instaling Pandora FMS agent...${reset}" ${green}OK${reset}
fi

if [[ $OS_RELEASE == 'debian' ]]; then
    execute_cmd "apt update" 'Updating repos'
    execute_cmd "apt install -y perl wget curl unzip procps python3 python3-pip" 'Instaling agent dependencies' 
    execute_cmd 'wget http://firefly.artica.es/pandorafms/latest/Debian_Ubuntu/pandorafms.agent_unix_7.0NG.deb' 'Downloading Pandora FMS agent dependencies'
    execute_cmd 'apt install -y ./pandorafms.agent_unix_7.0NG.deb' 'Installing Pandora FMS agent'
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


#installing autodiscover

arch=$(uname -m)
case $arch in

  x86_64)
    execute_cmd 'install_autodiscover x86_64' "installing service autodiscover on $arch" 'Error unable to install autodiscovery'
    ;;

  x86)
    execute_cmd 'install_autodiscover x84' "installing service autodiscover on $arch" 'Error unable to install autodiscovery'
    ;;

  armv7l)
    echo -e "${cyan}Skiping autodiscover installation arch $arch not suported${reset}"
    ;;

  *)
    echo -e "${yellow}Skiping autodiscover installation arch $arch not suported${reset}"
    ;;
esac

#Starting pandora agent daemon.
execute_cmd '/etc/init.d/pandora_agent_daemon restart' 'Starting Pandora Agent'
cd
execute_cmd 'rm -rf $HOME/pandora_deploy_tmp' 'Cleaning up temporay files'

echo -e "${green}PandoraFMS Agent installed and running, sending data to: $PANDORA_SERVER_IP${reset}"