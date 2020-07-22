#!/bin/bash
set -e

#Configure the Pandora FMS Server to connect to the database
/generate_conf_from_env.sh

# Run tasks
/etc/cron.hourly/pandora_db
