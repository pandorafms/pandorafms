#!/bin/bash

HELP=`cat<<_HELP
**********************
 PWR Server installer
**********************

To install the Pandora web robot daemon (pwrd)
Please launch this script as root:

$0 --install [[user] [directory]]


_HELP
`

if [ "$1" == "" ] || [ "$1" != "--install" ]; then
	echo "$HELP"
	exit 0
fi

if [ "`which rpm`" ]; then
	if [ "`rpm -qa | grep xorg-x11-server-Xvfb | wc -l`" == "0" ]; then 
		echo "Package xorg-x11-server-Xvfb is required"
		exit 0
	fi
else
	echo "Xvfb is required, please confirm is installed in your system"
fi

# default user is running user
GLOBAL_INST_USER=`whoami`

if [ "$2" != "" ]; then
	if [ `cat /etc/passwd | cut -f1 -d':' | grep -w "$2" | wc -l` -gt 0 ]; then
		GLOBAL_INST_USER=$2
	elif [ -d "$2" ]; then
		GLOBAL_INST_DIR=$2
	else
		echo "Cannot use \"$2\" as user nor directory"
		echo "$HELP"
		exit 0
	fi
fi

if [ "$3" != "" ]; then
	if [ -d "$3" ]; then
		GLOBAL_INST_DIR=$3
	elif [ `cat /etc/passwd | cut -f1 -d':' | grep -w "$3" | wc -l` -gt 0 ]; then
		GLOBAL_INST_USER=$3
	else
		echo "Cannot use \"$3\" as directory nor user"
		echo "$HELP"
		exit 0
	fi
fi

chmod +x pwrd

PWR_SERVER_DEST=$GLOBAL_INST_DIR/usr/lib/pwr
PWR_SERVER_RSC=$GLOBAL_INST_DIR/etc/pwr/tmp
PWR_SERVER_LOG=$GLOBAL_INST_DIR/var/log/pwr
PWR_FIREFOX_INSTALLDIR=$GLOBAL_INST_DIR/opt

PWR_FIREFOX_INSTALLDIR_ESCAPED=`echo $PWR_FIREFOX_INSTALLDIR | sed 's/\\//\\\\\//g'`
GLOBAL_INST_DIR_ESCAPED=`echo $GLOBAL_INST_DIR | sed 's/\\//\\\\\//g'`

[ -d $PWR_SERVER_DEST ] || mkdir -p $PWR_SERVER_DEST
[ -d $PWR_SERVER_LOG ] || mkdir -p $PWR_SERVER_LOG
[ -d $PWR_SERVER_RSC ] || mkdir -p $PWR_SERVER_RSC
[ -d $PWR_FIREFOX_INSTALLDIR ] || mkdir -p $PWR_FIREFOX_INSTALLDIR
[ -d $GLOBAL_INST_DIR/etc/init.d ] || mkdir -p $GLOBAL_INST_DIR/etc/init.d

tar xvf firefox-47.0.1.tar >/dev/null
mv firefox $PWR_FIREFOX_INSTALLDIR/firefox-47
ln -s $PWR_FIREFOX_INSTALLDIR/firefox-47/firefox $PWR_FIREFOX_INSTALLDIR/firefox

tar xvzf firefox_profile.tar.gz >/dev/null
if [ $? -ne 0 ]; then
	echo "Failed to deploy firefox profile, please retry installation"
	exit 1
fi
chown -R "$GLOBAL_INST_USER". firefox_profile
[ ! -d "$PWR_FIREFOX_INSTALLDIR/firefox_profile" ] && mv firefox_profile $PWR_FIREFOX_INSTALLDIR

[ -d "$PWR_FIREFOX_INSTALLDIR/selenium" ] || mkdir -p $PWR_FIREFOX_INSTALLDIR/selenium

cp config.json $PWR_FIREFOX_INSTALLDIR/selenium/

ln -s $PWR_FIREFOX_INSTALLDIR/firefox /usr/bin/firefox

# Generate logrotate configuration
echo <<EO_LROTATE > /etc/logrotate.d/pwrd
/var/log/pwr/pwr_std.log
/var/log/pwr/xvfb.log
/var/log/pwr/pwr_error.log {
	weekly
	missingok
	size 300000
	rotate 3
	maxage 90
	compress
	notifempty
	copytruncate
}

EO_LROTATE

cp ./extras/restore_firefox.sh $PWR_FIREFOX_INSTALLDIR/
cp ./extras/pwrd_maintenance.sh $PWR_FIREFOX_INSTALLDIR/
cp ./selenium-server-standalone-2.53.1.jar $PWR_SERVER_DEST/
cp ./pwrd /etc/init.d/pwrd
cp ./pwrd $GLOBAL_INST_DIR/etc/init.d/pwrd
chmod +x /etc/init.d/pwrd

# Update pwrd daemon
if [ "$GLOBAL_INST_DIR" != "" ]; then
	echo "Adjusting pwrd global directory to: $GLOBAL_INST_DIR"
	sed -i "s/PWR_GLOBAL_DIR=\"\"/PWR_GLOBAL_DIR=\"$GLOBAL_INST_DIR_ESCAPED\"/g" /etc/init.d/pwrd
	sed -i "s/PWR_GLOBAL_DIR=\"\"/PWR_GLOBAL_DIR=\"$GLOBAL_INST_DIR_ESCAPED\"/g" $GLOBAL_INST_DIR/etc/init.d/pwrd
	echo "Adjusting pwrd_maintenance global directory to: $PWR_FIREFOX_INSTALLDIR"
	sed -i "s/PWR_FIREFOX_INSTALLDIR=\"\/opt\"/PWR_FIREFOX_INSTALLDIR=\"$PWR_FIREFOX_INSTALLDIR_ESCAPED\"/g" $PWR_FIREFOX_INSTALLDIR/pwrd_maintenance.sh
	echo "Adjusting restore_firefox global directory to: $PWR_FIREFOX_INSTALLDIR"
	sed -i "s/PWR_FIREFOX_INSTALLDIR=\"\/opt\"/PWR_FIREFOX_INSTALLDIR=\"$PWR_FIREFOX_INSTALLDIR_ESCAPED\"/g" $PWR_FIREFOX_INSTALLDIR/restore_firefox.sh
fi

if [ "$GLOBAL_INST_USER" != "" ]; then
	echo "Adjusting pwrd global user to: $GLOBAL_INST_USER"
	sed -i "s/USER=\"root\"/USER=\"$GLOBAL_INST_USER\"/g" /etc/init.d/pwrd
	sed -i "s/USER=\"root\"/USER=\"$GLOBAL_INST_USER\"/g" $GLOBAL_INST_DIR/etc/init.d/pwrd
fi


[ "$GLOBAL_INST_USER" != "" ] && chown -R "$GLOBAL_INST_USER". $PWR_SERVER_DEST
[ "$GLOBAL_INST_USER" != "" ] && chown -R "$GLOBAL_INST_USER". $PWR_SERVER_LOG
[ "$GLOBAL_INST_USER" != "" ] && chown -R "$GLOBAL_INST_USER". $PWR_SERVER_RSC

cat <<EOF
*********************
 PWR Server deployed
*********************

Succesfully installed!

Now you can start the service with:

/etc/init.d/pwrd start


EOF
