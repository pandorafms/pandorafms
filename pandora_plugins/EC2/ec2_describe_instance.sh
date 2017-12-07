#!/bin/bash
# Amazon EC2 Pandora FMS Server plugin
# (c) Sancho Lerena 2011

# .. Config

# location of default config file
default_config_file=/usr/share/pandora_server/util/plugin/aws_plugin.conf
default_java_home=/usr
progname=`basename $0`

# .. Functions

function help {

	echo -e "Amazon EC2 Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
	echo " "
	echo "This plugin is used to get EC2 instance information via EC2 API"
	echo "Syntax:" 
	echo "   ./$progname [-A access-key -S secret-key][-R region]  -f config -i instance-id -n field-name"
	echo -e "\t\t-f path of configu file"
	echo -e "\t\t-R Region, p.e us-east-1"
	echo -e "\t\t-A Access KEY ID, p.e AKIAILTVCAS26GTKLD4A"
	echo -e "\t\t-S Secret Access Key, p.e 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"
	echo -e "\t\t-i Instance ID, p.e i-9d0b4af1"
	echo -e "\t\t-n Field Name, p.e type, public-dns, .."
	echo -e "\t\t-h Show this messages"
	echo " "
	echo -e "\t\t   field-nmae is one of following;"
	echo -e "\t\t\tami-id, public-dns, private-dns, state, type, available-zone,"
	echo -e "\t\t\tpublic-ip, private-ip, block-devices, security-groups,"
	echo -e "\t\t\ttag:\${your-tag-name}"
	echo "Samples:"
	echo "   ./$progname -f /usr/share/pandora/util/plugin/ec2_plugin.conf -i i-9d0b4af1 -n public-dns"
	echo "   ./$progname -f /usr/share/pandora/util/plugin/ec2_plugin.conf -i i-9d0b4af1 -n tag:Name"
	echo 

	exit 0
}

function check_required_variables {
	_result=OK;

# .. EC2_HOME
	if [ ! -d "$EC2_HOME" ] || [ ! -x $EC2_HOME/bin/ec2-describe-instances ]
	then
		echo "You need to define EC2_HOME settings."
		_result=NG;
	fi
	export EC2_HOME

# .. JAVA_HOME
	if [ -z "$JAVA_HOME" ] && [ -d "$default_java_home" ] && [ -x "$default_java_home/bin/java" ]
	then
		JAVA_HOME=$default_java_home
	fi
	if [ ! -d "$JAVA_HOME" ] || [ ! -x "$JAVA_HOME/bin/java" ]
	then
		echo "You need to define JAVA_HOME settings."
		_result=NG;
	fi
	export JAVA_HOME

# .. AWS_CREDENTIAL_FILE (or specify AWS_ACCESS_KEY/OPT_SECRET_KEY pair)
	if [ -z "${OPT_ACCESS_KEY}" ] && [ -z "${OPT_SECRET_KEY}" ]
	then
		if [ ! -f "$AWS_CREDENTIAL_FILE" ] || [ ! -r "$AWS_CREDENTIAL_FILE" ]
		then
			echo "You need to specify AWS_ACCESS_KEY/OPT_SECRET_KEY pair or define AWS_CREDENTIAL_FILE settings."
			_result=NG;
		else
			AWS_ACCESS_KEY=`sed -n -e '/^AWSAccessKeyId=/{s/^AWSAccessKeyId=\([^  ]*\).*$/\1/p;q}' $AWS_CREDENTIAL_FILE`
			AWS_SECRET_KEY=`sed -n -e '/^AWSSecretKey=/{s/^AWSSecretKey=\([^  ]*\).*$/\1/p;q}' $AWS_CREDENTIAL_FILE`
			export AWS_ACCESS_KEY AWS_SECRET_KEY
		fi
	else
		if [ -z "${OPT_ACCESS_KEY}" ] || [ -z "${OPT_SECRET_KEY}" ]
		then
			echo "You need to specify AWS_ACCESS_KEY/OPT_SECRET_KEY pair or define AWS_CREDENTIAL_FILE settings."
			_result=NG;
		fi
	fi

# .. EC2_REGION or EC2_URL
	if [ -z "$EC2_URL" ] && [ -z "$EC2_REGION" ]
	then
		echo "You need to define EC2_REGION or EC2_URL settings."
		_result=NG;
	fi
	if [ -z "$EC2_URL" ]
	then
		EC2_URL="http://ec2.${EC2_REGION}.amazonaws.com"
	fi
	export EC2_URL

# check the result and abort if shomething wrong

	if [ "$_result" != "OK" ]
	then
		echo "Please read the documentation."
		echo "aborting..."
		exit 1;
	fi

	# optional settings...

	[ -n "$SERVICE_JVM_ARGS" ] && export SERVICE_JVM_ARGS 
}


if [ $# -eq 0 ]
then
        help
fi

TIMEOUT_CHECK=0
DOMAIN_CHECK=""
IP_CHECK=""
DNS_CHECK=""


# Main parsing code
while getopts ":hf:A:S:R:i:n:" optname
  do
    case "$optname" in
      "h")
                help ;;
      "f")
                arg_config_file="$OPTARG" ;;
      "A")
                OPT_ACCESS_KEY="--I $OPTARG" ;;
      "S")
                OPT_SECRET_KEY="--S $OPTARG" ;;
      "R")
                OPT_REGION="--region $OPTARG" ;;
      "i")
                target="$OPTARG" ;;
      "n")
                field="$OPTARG" ;;
      *)
                help ;;
    esac
done

shift `expr $OPTIND - 1`

config_file=${arg_config_file:-$default_config_file}
# Read config file
if [ -f "$config_file" ] && [ -r "$config_file" ]
then
	. $config_file
else
	echo "Cannot read $config_file."
fi

check_required_variables

if [ -z "$field" ] || [ -z "$target" ]
then
        help
fi

description_lines=`${EC2_HOME}/bin/ec2-describe-instances ${OPT_ACCESS_KEY} ${OPT_SECRET_KEY} ${OPT_REGION} --show-empty-fields $target 2> /dev/null`

case $field in
ami-id|ami)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 3;;
public-dns)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 4;;
private-dns)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 5;;
state)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 6;;
type)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 10;;
available-zone|zone)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 12;;
public-ip)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 17;;
private-ip)
	echo "$description_lines" | grep "^INSTANCE" | cut -f 18;;
block-devices)
	echo "$description_lines" | grep "^BLOCKDEVICE" | cut -f 2,3;;
security-groups|groups)
	echo "$description_lines" | grep "^RESERVATION" | cut -f 4;;
tag:*)
	target_tag=${field/tag:/}
	echo "$description_lines" | grep -i "^TAG	instance	$target	$target_tag" | cut -f 5;;
esac

