#!/bin/bash
set -e
if [ -n "$MYSQL_PORT_3306_TCP" ]; then
		if [ -z "$PANDORA_DB_HOST" ]; then
			PANDORA_DB_HOST='mysql'
		else
			echo >&2 'warning: both PANDORA_DB_HOST and MYSQL_PORT_3306_TCP found'
			echo >&2 "  Connecting to PANDORA_DB_HOST ($PANDORA_DB_HOST)"
			echo >&2 '  instead of the linked mysql container'
		fi
fi

if [ -z "$PANDORA_DB_HOST" ]; then
	echo >&2 'error: missing PANDORA_DB_HOST and MYSQL_PORT_3306_TCP environment variables'
	echo >&2 '  Did you forget to --link some_mysql_container:mysql or set an external db'
	echo >&2 '  with -e PANDORA_DB_HOST=hostname:port?'
	exit 1
fi

# if we're linked to MySQL and thus have credentials already, let's use them
: ${PANDORA_DB_USER:=${MYSQL_ENV_MYSQL_USER:-root}}
if [ "$PANDORA_DB_USER" = 'root' ]; then
	: ${PANDORA_DB_PASSWORD:=$MYSQL_ENV_MYSQL_ROOT_PASSWORD}
fi
: ${PANDORA_DB_PASSWORD:=$MYSQL_ENV_MYSQL_PASSWORD}
if [ -z "$PANDORA_DB_NAME" ]; then
	: ${PANDORA_DB_NAME:=${MYSQL_ENV_MYSQL_DATABASE:-pandora}}
fi

if [ -z "$PANDORA_DB_PASSWORD" ]; then
	echo >&2 'error: missing required PANDORA_DB_PASSWORD environment variable'
	echo >&2 '  Did you forget to -e PANDORA_DB_PASSWORD=... ?'
	echo >&2
	echo >&2 '  (Also of interest might be PANDORA_DB_USER and PANDORA_DB_NAME.)'
	exit 1
fi


#Configure the Pandora FMS Server to connect to the database
/generate_conf_from_env.sh

# Wait MySQL startup
/wait-for-it.sh -h ${MYSQL_PORT_3306_TCP_ADDR:-$PANDORA_DB_HOST} -p ${MYSQL_PORT_3306_TCP_PORT:-3306} -t 300

#Rock n' roll!
/etc/init.d/tentacle_serverd start &
/usr/bin/pandora_server /etc/pandora/pandora_server.conf
