#!/bin/bash

if [ "$1" == "" ] || [ "$1" != "--install" ]; then
	cat<<_HELP
**********************
 PWR Server installer
**********************

To install the Pandora web robot daemon (pwrd)
Please launch this script as root:

$0 --install


_HELP
	exit 0
fi

if [ "`rpm -qa | grep xorg-x11-server-Xvfb | wc -l`" == "0" ]; then 
	echo "Package xorg-x11-server-Xvfb is required"
	exit 0
fi

chmod +x pwrd

PWR_SERVER_DEST=/usr/lib/pwr
PWR_SERVER_RSC=/etc/pwr/tmp
PWR_SERVER_LOG=/var/log/pwr
PWR_FIREFOX_INSTALLDIR=/opt

mkdir -p $PWR_SERVER_DEST
mkdir -p $PWR_SERVER_LOG
mkdir -p $PWR_SERVER_RSC
mkdir -p $PWR_FIREFOX_INSTALLDIR


tar xvf firefox-43.0.tar >/dev/null
mv firefox $PWR_FIREFOX_INSTALLDIR/

tar xvzf firefox_profile.tar.gz >/dev/null
chown -R `whoami`. firefox_profile
mv firefox_profile $PWR_FIREFOX_INSTALLDIR

ln -s $PWR_FIREFOX_INSTALLDIR/firefox/firefox /usr/bin/firefox

# Generate logrotate configuration

cat > /etc/logrotate.d/pwrd <<EO_LROTATE
/var/log/pwr/pwr_std.log
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

