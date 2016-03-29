#!/bin/bash
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /tmp/pandorafms/pandora_console/pandoradb.sql
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /tmp/pandorafms/pandora_console/pandoradb_data.sql
