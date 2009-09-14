#!/bin/bash

# Pandora FMS Command line Backup Tool
# (c) Sancho Lerena <slerena@gmail.com>, Artica Soluciones Tecnologicas 2009

function help {
	echo ""
	echo -e "Pandora FMS Command line backup tool. http://www.pandorafms.org" 
	echo -e "(c) 2009 Sancho Lerena <slerena@gmail.com>, Artica Soluciones Tecnologicas"
	echo ""
	echo -e "Syntax:" 
	echo -e "\t\t-c Path to Pandora FMS console, p.e: /srv/www/htdocs/pandora_console"
	echo -e "\t\t-d Destination path for backup file. p.e: /tmp"
	echo -e "\t\t-s Source filename for backup restore. p.e: /tmp/pandorafms"
	echo -e "\t\t-f Restore also files"
	echo -e "\t\t-q Quiet. No output message (used for scripts/cron)"
	echo -e "\n\nPlease BE SURE TO USE RESTORE (-s) option. This will OVERWRITE ALL your"
	echo -e "PandoraFMS install, including files, configuration and data. Consided to"
	echo -e "make a backup first."
	echo ""
	exit 1
}

if [ $# -eq 0 ]
then
	help
fi

SOURCEBACKUP="thisnotexist"
QUIET=0
RESTOREFILES=0
TIMESTAMP=`date +"%Y-%m-%d-%H-%M-%S"`

# Main parsing code

while getopts "fhqc:d:s:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "c")
	        PANDORAPATH=$OPTARG
        ;;
      "f")
	        RESTOREFILES=1
        ;;
      "d")
		BACKUPDIR=$OPTARG
        ;;
      "s")
		SOURCEBACKUP=$OPTARG
        ;;
      "q")
		QUIET=1
        ;;
      ?)
		help
	;;
      default) 
		help
	;;
    esac
done

# Execution

if [ ! -e "$PANDORAPATH/include/config.php" ]
then
	echo "Cannot read config file at $PANDORAPATH/include/config.php. Aborting"
	exit 1
fi


DBUSER=`cat $PANDORAPATH/include/config.php | grep dbuser | grep -v "^\/" | grep -o "\=\"[a-zA-Z0-9]*\"" | grep -o "[A-Za-z0-9]*"`
DBPASS=`cat $PANDORAPATH/include/config.php | grep dbpass | grep -v "^\/" | grep -o "\=\"[a-zA-Z0-9]*\"" | grep -o "[A-Za-z0-9]*"`
DBHOST=`cat $PANDORAPATH/include/config.php | grep dbhost | grep -v "^\/" | grep -o "\=\"[a-zA-Z0-9]*\"" | grep -o "[A-Za-z0-9]*"`
DBNAME=`cat $PANDORAPATH/include/config.php | grep dbname | grep -v "^\/" | grep -o "\=\"[a-zA-Z0-9]*\"" | grep -o "[A-Za-z0-9]*"`


cd /tmp
mkdir $TIMESTAMP
cd $TIMESTAMP

# Make the backup

if [ ! -e "$SOURCEBACKUP" ]
then

	rm -Rf $BACKUPDIR/pandorafms_backup_$TIMESTAMP.tar.gz 2> /dev/null

	mysqldump -u $DBUSER -p$DBPASS -h $DBHOST $DBNAME > pandorafms_backup_$TIMESTAMP.sql
	tar cvzf pandorafms_backup_$TIMESTAMP.tar.gz pandorafms_backup_$TIMESTAMP.sql $PANDORAPATH /etc/pandora /var/spool/pandora/data_in --exclude .data 2> /dev/null > /dev/null

	mv /tmp/$TIMESTAMP/pandorafms_backup_$TIMESTAMP.tar.gz $BACKUPDIR
	cd /tmp
	rm -Rf /tmp/$TIMESTAMP
	if [ $QUIET == 0 ]
	then
		echo "Backup completed and placed in $BACKUPDIR/pandorafms_backup_$TIMESTAMP.tar.gz"
	fi

else 

# Make the backup restore process

	echo "Detected Pandora FMS backup at $SOURCEBACKUP, please wait..."
	tar xvzf $SOURCEBACKUP > /dev/null 2> /dev/null

	echo "Dropping current database"
	echo "drop database $DBNAME;" | mysql -u $DBUSER -p$DBPASS -h $DBHOST

	echo "Restoring backup database"
	echo "create database $DBNAME;" | mysql -u $DBUSER -p$DBPASS -h $DBHOST
	cat *.sql | mysql -u $DBUSER -p$DBPASS -h $DBHOST -D $DBNAME

	if [ $RESTOREFILES == 1 ]
	then
		echo "Restoring files and configuration"
#Need testing, not finished!
		echo "mv var/spool/pandora/* /var/spool/pandora"
		echo "mv etc/pandora/* /etc/pandora"
		echo "BACKUPBASEPATH=`echo $PANDORAPATH | cut -c2-`"
		echo "mv $BACKUPBASEPATH $PANDORAPATH"
	fi
	
	cd /tmp
	rm -Rf /tmp/$TIMESTAMP

	echo "Done. Backup in $SOURCEBACKUP restored"
fi


exit 0
