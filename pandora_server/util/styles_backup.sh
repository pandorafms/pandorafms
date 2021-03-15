#!/bin/bash

#DATE
DATE=$(date +%Y-%m-%d-%H%M%S)

#CREATE STYLES BACKUP FOLDER
mkdir "/var/www/html/pandora_console/styles_backup"

#BACKUP DIR
BACKUP_DIR="/var/www/html/pandora_console/styles_backup/"

#IMAGES BACKUP
echo "Creating backup of images..."
SOURCE="/var/www/html/pandora_console/images/"
tar -cvzpf $BACKUP_DIR/images-backup-$DATE.tar.gz $SOURCE
echo "Done."

#STYLES BACKUP
echo "Creating backup of styles..."
SOURCE="/var/www/html/pandora_console/include/styles"
tar -cvzpf  $BACKUP_DIR/styles-backup-$DATE.tar.gz $SOURCE
echo "Done."

exit 0
