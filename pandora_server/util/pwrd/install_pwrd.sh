#!/bin/bash

if [ "$1" == "" ] || [ "$1" != "--install" ]; then
	cat<<_HELP
**********************
 PWR Server installer
**********************

To install the Pandora web robot daemon (pwrd)
Please launch this script as root:

$0 --install [directory]


_HELP
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


if [ "$2" != "" ] && [ -d "$2" ]; then
	GLOBAL_INST_DIR=$2
fi

chmod +x pwrd

PWR_SERVER_DEST=$GLOBAL_INST_DIR/usr/lib/pwr
PWR_SERVER_RSC=$GLOBAL_INST_DIR/etc/pwr/tmp
PWR_SERVER_LOG=$GLOBAL_INST_DIR/var/log/pwr
PWR_FIREFOX_INSTALLDIR=$GLOBAL_INST_DIR/opt

mkdir -p $PWR_SERVER_DEST
mkdir -p $PWR_SERVER_LOG
mkdir -p $PWR_SERVER_RSC
mkdir -p $PWR_FIREFOX_INSTALLDIR


tar xvf firefox-47.0.1.tar >/dev/null
mv firefox $PWR_FIREFOX_INSTALLDIR/

tar xvzf firefox_profile.tar.gz >/dev/null
chown -R `whoami`. firefox_profile
mv firefox_profile $PWR_FIREFOX_INSTALLDIR

mkdir -P $PWR_FIREFOX_INSTALLDIR/selenium
mv config.json $PWR_FIREFOX_INSTALLDIR/selenium/

ln -s $PWR_FIREFOX_INSTALLDIR/firefox/firefox /usr/bin/firefox

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

# Update pwrd daemon
if [ "$GLOBAL_INST_DIR" != "" ]; then
	sed -i "s/PWR_GLOBAL_DIR=\"\"/PWR_GLOBAL_DIR=\"\\$GLOBAL_INST_DIR\"/g" ./pwrd
fi
cp ./selenium-server-standalone-2.53.1.jar $PWR_SERVER_DEST/
cp ./pwrd /etc/init.d/pwrd
chmod +x /etc/init.d/pwrd

cat <<EOF
*********************
 PWR Server deployed
*********************

Succesfully installed!

Please start the service with:

/etc/init.d/pwrd start


EOF
