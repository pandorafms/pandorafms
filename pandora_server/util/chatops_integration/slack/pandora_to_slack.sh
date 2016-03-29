#!/bin/bash
# Integration script with Mattermost and Slack for sending messages from Pandora FMS
# (c) 2016 Axel Amigo <axl@artica.es>


# SET HERE YOUR DATA
USERNAME=pandorafmsbot
ICON="http://cdn9.staztic.com/app/a/3528/3528476/pandora-fms-1-l-78x78.png"
URL="$2"

# Do not touch from there:

MSG="'payload={\"username\": \"$USERNAME\", \"text\": \"$1\", \"icon_url\": \"$ICON\"}'"
COMMAND="curl -k -X POST --data-urlencode $MSG $URL"

eval $COMMAND
