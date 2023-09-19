#!/bin/bash
#DEB
cd deb
VERSION=$(grep 'Version:' pandora_gotty/DEBIAN/control | awk '{print $2}')
mkdir -p pandora_gotty/usr/bin
mkdir -p pandora_gotty/etc/pandora_gotty
cp -a ../src/pandora_gotty pandora_gotty/usr/bin
cp -a ../src/pandora_gotty.conf pandora_gotty/etc/pandora_gotty
dpkg-deb --build pandora_gotty
mv pandora_gotty.deb ../
rm -rf pandora_gotty/usr/ 
rm -rf pandora_gotty/etc/
cd ..
mv pandora_gotty.deb pandora_gotty_${VERSION}.deb
chmod 777 pandora_gotty_${VERSION}.deb
