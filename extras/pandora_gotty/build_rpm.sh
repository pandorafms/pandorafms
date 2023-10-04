#!/bin/bash
#RPM
VERSION=$(grep '%define version' pandora_gotty.spec | awk '{print $3}')
mkdir -p pandora_gotty-${VERSION} 
cp src/pandora_gotty pandora_gotty-${VERSION}/
cp src/pandora_gotty.conf pandora_gotty-${VERSION}/
curl -SsL --output pandora_gotty-${VERSION}/pandora_gotty_exec http://192.168.50.31/installers/installers/Linux/x86_64/pandora_gotty_exec
chmod +x pandora_gotty-${VERSION}/pandora_gotty_exec
tar -cvzf pandora_gotty-${VERSION}.tar.gz pandora_gotty-${VERSION}/*
mv pandora_gotty-${VERSION}.tar.gz ${HOME}/rpmbuild/SOURCES/
rm -rf ${HOME}/rpmbuild/RPMS/x86_64/pandora_gotty*
rpmbuild -ba pandora_gotty.spec 
rm -rf pandora_gotty-${VERSION}
mv ${HOME}/rpmbuild/RPMS/x86_64/pandora_gotty* .
chmod 777 *.rpm