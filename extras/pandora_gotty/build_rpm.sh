#!/bin/bash
#RPM
VERSION=$(grep '%define version' pandora_gotty.spec | awk '{print $3}')
mkdir -p pandora_gotty-${VERSION} 
cp src/* pandora_gotty-${VERSION}/
tar -cvzf pandora_gotty-${VERSION}.tar.gz pandora_gotty-${VERSION}/*
mv pandora_gotty-${VERSION}.tar.gz ${HOME}/rpmbuild/SOURCES/
rm -rf ${HOME}/rpmbuild/RPMS/x86_64/pandora_gotty*
rpmbuild -ba pandora_gotty.spec 
rm -rf pandora_gotty-${VERSION}
mv ${HOME}/rpmbuild/RPMS/x86_64/pandora_gotty* .

#DEB