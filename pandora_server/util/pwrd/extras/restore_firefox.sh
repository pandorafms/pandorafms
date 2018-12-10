#!/bin/bash
#
# Script to restore firefox to tar
#
# **********************************************************************

# Customize PWR global installation directory
PWR_FIREFOX_INSTALLDIR="/opt"

cd $PWR_FIREFOX_INSTALLDIR

if [ -f firefox-47.0.1.tar ]; then
	rm -rf firefox-47 >/dev/null 2>&1
	mv firefox firefox_ >/dev/null 2>&1
	tar xvf firefox-47.0.1.tar >/dev/null 2>&1
	mv firefox firefox-47 >/dev/null 2>&1
	mv firefox_ firefox >/dev/null 2>&1
else
	echo "firefox-47.0.1.tar not found, please leave a copy at $PWR_FIREFOX_INSTALLDIR"
fi
