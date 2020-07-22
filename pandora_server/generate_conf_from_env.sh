#!/bin/bash
set -e

#Configure the Pandora FMS Server to connect to the database
sed -i "s/dbname pandora/dbname $PANDORA_DB_NAME/g" /etc/pandora/pandora_server.conf
sed -i "s/dbpass pandora/dbpass $PANDORA_DB_PASSWORD/g" /etc/pandora/pandora_server.conf
sed -i "s/dbuser pandora/dbuser $PANDORA_DB_USER/g" /etc/pandora/pandora_server.conf
sed -i "s/dbhost 127.0.0.1/dbhost $PANDORA_DB_HOST/g" /etc/pandora/pandora_server.conf
#Configure the Pandora FMS Server to connect to SMTP host
sed -i "s/#mta_address localhost/mta_address ${PANDORA_SMTP_HOST:-localhost}/g" /etc/pandora/pandora_server.conf
sed -i "s/#mta_port 25/mta_port ${PANDORA_SMTP_PORT:-25}/g" /etc/pandora/pandora_server.conf
sed -i "s/#mta_from Pandora FMS <pandora@mydomain.com>/mta_from Pandora FMS <${PANDORA_SMTP_FROM:-pandora@localhost}>/g" /etc/pandora/pandora_server.conf
