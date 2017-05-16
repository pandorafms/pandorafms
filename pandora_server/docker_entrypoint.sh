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

#Create the pandora user to run the anyterd, mainly
/usr/sbin/useradd -d /home/pandora -s /bin/false -M -g 0 pandora

cd /tmp/pandorafms/pandora_server && ./pandora_server_installer --install

#Configure the Pandora FMS Server to connect to the database
sed -i "s/dbname pandora/dbname $PANDORA_DB_NAME/g" /etc/pandora/pandora_server.conf
sed -i "s/dbpass pandora/dbpass $PANDORA_DB_PASSWORD/g" /etc/pandora/pandora_server.conf
sed -i "s/dbuser pandora/dbuser $PANDORA_DB_USER/g" /etc/pandora/pandora_server.conf
sed -i "s/dbhost 127.0.0.1/dbhost $PANDORA_DB_HOST/g" /etc/pandora/pandora_server.conf

#Rock n' roll!
/etc/init.d/crond start &
/etc/init.d/ntpd start &
/etc/init.d/anytermd start &
/etc/init.d/postfix start &
/etc/init.d/tentacle_serverd start &
/usr/bin/pandora_server /etc/pandora/pandora_server.conf
