#!/bin/bash
# Amazon EC2 Pandora FMS Server plugin
# (c) Sancho Lerena 2011

# .. Config

# location of default config file
default_config_file=/usr/share/pandora_server/util/plugin/aws_plugin.conf
default_java_home=/usr


# .. Functions

function help {

	echo -e "Amazon EC2 Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
	echo " "
	echo "This plugin is used to check performance of Volumes and Instances in the EC2 Cloud"
	echo "Syntax:" 
	echo -e "\t\t-A Access KEY ID, p.e AKIAILTVCAS26GTKLD4A"
	echo -e "\t\t-S Secret Access Key, p.e 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"
	echo -e "\t\t-R Region, p.e us-east-1"
	echo -e "\t\t-m Metric to gather (see doc for a metric list) "
	echo -e "\t\t-n Namespace (p.e: AWS/EC2, AWS/EBS) "
	echo -e "\t\t-i Target Instance or Target LB name (p.e: i-9d0b4af1) "
	echo -e "\t\t-a Availability Zone option for AWS/ELB (p.e: us-west-1b) "
	echo -e "\t\t-z Show default metrics "
	echo -e "\t\t-h Show this messages "
	echo "Samples:"
	echo "   ./ec2_plugin.sh -A AKIAILTVCAS26GTKLD4A -S CgmQ6DxUWES05txju+alJLoM57acDudHogkLotWk -i i-9d0b4af1 -n AWS/EC2 -m CPUUtilization"
	echo 

	exit 0
}

function check_required_variables {
	_result=OK;

# .. AWS_CLOUDWATCH_HOME
	if [ ! -d "$AWS_CLOUDWATCH_HOME" ] || [ ! -x $AWS_CLOUDWATCH_HOME/bin/mon-get-stats ]
	then
		echo "You need to define AWS_CLOUDWATCH_HOME settings."
		_result=NG;
	fi
	export AWS_CLOUDWATCH_HOME

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

# .. AWS_CREDENTIAL_FILE
	if [ ! -f "$AWS_CREDENTIAL_FILE" ] || [ ! -r "$AWS_CREDENTIAL_FILE" ]
	then
		echo "You need to define AWS_CREDENTIAL_FILE settings."
		_result=NG;
	fi
	export AWS_CREDENTIAL_FILE 

# .. EC2_REGION or AWS_CLOUDWATCH_URL
	if [ -z "$AWS_CLOUDWATCH_URL" ] && [ -z "$EC2_REGION" ]
	then
		echo "You need to define EC2_REGION or AWS_CLOUDWATCH_URL settings."
		_result=NG;
	fi
	if [ -z "$AWS_CLOUDWATCH_URL" ]
	then
		AWS_CLOUDWATCH_URL="http://monitoring.${EC2_REGION}.amazonaws.com"
	fi
	export AWS_CLOUDWATCH_URL

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

function list_available_metrics {

	if [ -n "$list_metrics_in_raw_format" ]
	then
		${AWS_CLOUDWATCH_HOME}/bin/mon-list-metrics -show-long ${OPT_REGION} ${OPT_ACCESS_KEY} ${OPT_SECRET_KEY}
	else
		${AWS_CLOUDWATCH_HOME}/bin/mon-list-metrics -show-long ${OPT_REGION} ${OPT_ACCESS_KEY} ${OPT_SECRET_KEY} |
			sed -e 's/\([^,]*\),\([^,]*\),{*\([^{}]*\)}.*/\2  \3  \1/' | sort
	fi

	exit 
}

function help_metrics {
    echo -e "Amazon EC2 Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
    echo " "
    echo -e "This the default metric list, you can use any metric available these are the default"

    echo " "
    echo "For AWS/EC2 Namespace"
    echo " "
    echo "CPUUtilization"
    echo "DiskReadBytes"
    echo "DiskReadOps"
    echo "DiskWriteBytes"
    echo "DiskWriteOps"
    echo "NetworkIn"
    echo "NetworkOut "

    echo " " 
    echo "For AWS/EBS Namespace"
    echo " "
    echo "VolumeIdleTime"
    echo "VolumeQueueLength"
    echo "VolumeReadBytes"
    echo "VolumeReadOps"
    echo "VolumeTotalReadTime"
    echo "VolumeTotalWriteTime"
    echo "VolumeWriteBytes"
    echo "VolumeWriteOps"

    echo " " 
    echo "For AWS/RDS Namespace"
    echo " "
    echo "CPUUtilization"
    echo "DatabaseConnections"
    echo "DiskQueueDepth"
    echo "FreeStorageSpace"
    echo "FreeableMemory"
    echo "ReadIOPS"
    echo "ReadLatency"
    echo "ReadThroughput"
    echo "SwapUsage"
    echo "WriteIOPS"
    echo "WriteLatency"
    echo "WriteThroughput"

    echo " " 
    echo "For AWS/ELB Namespace"
    echo " "
    echo "HTTPCode_Backend_2XX"
    echo "HTTPCode_Backend_3XX"
    echo "HTTPCode_Backend_4XX"
    echo "HTTPCode_Backend_5XX"
    echo "HTTPCode_ELB_4XX"
    echo "HTTPCode_ELB_5XX"
    echo "HealthyHostCount"
    echo "Latency"
    echo "RequestCount"
    echo "UnHealthyHostCount"

    echo " "
    exit
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
while getopts ":zhlLf:d:i:n:m:A:S:R:a:C:" optname
  do
    case "$optname" in
      "f")
                arg_config_file="$OPTARG" ;;
      "h")
                help ;;
      "l")
                list_metrics=1 ;;
      "L")
		list_metrics_in_raw_format="yes"
                list_metrics=1 ;;
      "z")
                help_metrics ;;
      "A")
                OPT_ACCESS_KEY="--I $OPTARG" ;;
      "S")
                OPT_SECRET_KEY="--S $OPTARG" ;;
      "R")
                OPT_REGION="--region $OPTARG" ;;
      "n")
                NAMESPACE=$OPTARG ;;
      "d")
                arg_dimensions="$OPTARG" ;;
      "i")
                INSTANCE=$OPTARG ;;
      "m")
                METRIC=$OPTARG ;;
      "a")
                ZONE=$OPTARG ;;
      "C")
                CACHENODEID=$OPTARG ;;
      *)
                help ;;
    esac
done

config_file=${arg_config_file:-$default_config_file}
# Read config file
if [ -f "$config_file" ] && [ -r "$config_file" ]
then
	. $config_file
else
	echo "Cannot read $config_file."
fi

check_required_variables

if [ ! -z $list_metrics ]
then
        list_available_metrics
fi
if [ -z "$METRIC" ]
then
        help
fi
if [ -z "$NAMESPACE" ]
then
        help
fi

case "$NAMESPACE" in
  AWS/RDS)
	DIMENSIONS="${arg_dimensions:-DBInstanceIdentifier=$INSTANCE}" ;;
  AWS/ElastiCache)
	DIMENSIONS=${arg_dimensions:-"CacheClusterId=$INSTANCE,CacheNodeId=$CACHENODEID"} ;;
  AWS/ELB)
	if [ ! -z "$arg_dimensions" ]
	then
		DIMENSIONS=${arg_dimensions};
	else
		if [ ! -z "$INSTANCE" ]
		then
			DIMENSIONS="LoadBalancerName=$INSTANCE"
		fi
		if [ ! -z "$ZONE" ]
		then
			DIMENSIONS="${DIMENSIONS:+$DIMENSIONS,}AvailabilityZone=$ZONE"
		fi
	fi
	;;
  *)
	DIMENSIONS=
  #${arg_dimensions:-"InstanceId=$INSTANCE"}
  ;;
esac

if [ "$DIMENSIONS" == "" ]; then
  ${AWS_CLOUDWATCH_HOME}/bin/mon-get-stats ${METRIC} --namespace $NAMESPACE \
    ${OPT_REGION} ${OPT_ACCESS_KEY} ${OPT_SECRET_KEY} -s Average --period 300 | \
    tail -1 | \
    awk '$3 ~ /^[-]?[0-9]+[.][0-9]+[Ee][+-]?[0-9]+$/{$3 = sprintf("%.3f",$3)} {print $3}'
else
  ${AWS_CLOUDWATCH_HOME}/bin/mon-get-stats ${METRIC} --namespace $NAMESPACE \
  	${OPT_REGION} ${OPT_ACCESS_KEY} ${OPT_SECRET_KEY} -s Average --period 300 \
  	--dimensions "$DIMENSIONS" | tail -1 | \
  	awk '$3 ~ /^[-]?[0-9]+[.][0-9]+[Ee][+-]?[0-9]+$/{$3 = sprintf("%.3f",$3)} {print $3}'
fi

