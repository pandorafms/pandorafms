#!/usr/bin/env bash

# Restore the original working directory and exit.
function error {
	popd >/dev/null 2>&1
	exit 1
}

# Gets information about Apple notarization process
function get_notarization_info() {
	CALL=$(xcrun altool --notarization-info "$1" -u $APPLE_USER -p "$APPLE_PASS"| grep -Ei "Status:|Status Message")
	STATUS=`echo $CALL |grep -ic "in progress"`
	MESSAGE=`echo $CALL |grep -ic "package approved"`
}

# Keeping this for future CICD integration
if [ "$CI_PROJECT_DIR" != "" ]; then
	LOCALINST="$CODEHOME/pandora_agents/unix/Darwin/dmg"
else
	LOCALINST="/opt/code/pandorafms/pandora_agents/unix/Darwin/dmg"
fi

# DMG package name
if [ "$#" -ge 1 ]; then
	DMGNAME="$1"
else
	DMGNAME="Pandora FMS MacOS agent"
fi

# DMG package version
if [ "$#" -ge 2 ]; then
	VERSION="$2"
else
	VERSION="7.0NG.776"
fi

# Path for the generated DMG file
if [ "$#" -ge 3 ]; then
	BUILD_PATH="$3"
else
	BUILD_PATH="/opt/code/pandorafms/pandora_agents/unix/Darwin/dmg"
fi

BUILD_DMG="$BUILD_PATH/build"
BUILD_TMP="$BUILD_PATH/buildtmp"
APPLE_USER="kevin.rojas@pandorafms.com"
APPLE_PASS="@keychain:signing"
APPLE_DEVNAME="Developer ID Installer: Artica Soluciones Tecnologicas SL"
APPLE_DEVAPP="Developer ID Application: Artica Soluciones Tecnologicas SL"
APPLE_DEVID="Q35RP2Y7WU"

FULLNAME="$DMGNAME-$VERSION.dmg"
printf "VERSION-'$VERSION' NAME-'$DMGNAME'\n"
pushd .
cd $LOCALINST

# Copy necessary files to installer
cp ../com.pandorafms.pandorafms.plist files/pandorafms/
cp ../../../../pandora_agents/unix/pandora* files/pandorafms/
cp ../../../../pandora_agents/unix/tentacle* files/pandorafms/
cp -R ../../../../pandora_agents/unix/plugins files/pandorafms/
cp -R ../../../../pandora_agents/unix/man files/pandorafms/
cp -R ../../../../pandora_agents/unix/Darwin/pandora_agent.conf files/pandorafms/
mkdir -p $BUILD_DMG
mkdir -p $BUILD_TMP

# Make sure the scripts have execution privileges
chmod +x "./scripts/preinstall"
chmod +x "./scripts/postinstall"
chmod +x "./files/pandorafms/inst_utilities/get_group.scpt"
chmod +x "./files/pandorafms/inst_utilities/get_remotecfg.scpt"
chmod +x "./files/pandorafms/inst_utilities/get_serverip.scpt"
chmod +x "./files/pandorafms/inst_utilities/print_conf.pl"
chmod +x "./files/pandorafms_uninstall/PandoraFMS agent uninstaller.app/Contents/MacOS/uninstall.sh"
chmod +x "./files/pandorafms_uninstall/PandoraFMS agent uninstaller.app/Contents/Resources/ask_root"
chmod +x "./files/pandorafms_uninstall/PandoraFMS agent uninstaller.app/Contents/Resources/confirm_uninstall"
chmod +x "./files/pandorafms_uninstall/PandoraFMS agent uninstaller.app/Contents/Resources/uninstall"

# Build pandorafms agent component
pkgbuild --root files/pandorafms/ \
	--identifier com.pandorafms.pandorafms_src \
	--version $VERSION \
	--scripts scripts \
	--install-location /usr/local/share/pandora_agent/ \
	$BUILD_TMP/pandorafms_src.pdk || error

# Build pandorafms uninstaller app
pkgbuild --root files/pandorafms_uninstall/ \
	--component-plist extras/pandorafms_uninstall.plist \
	--install-location /Applications \
	$BUILD_TMP/pandorafms_uninstall.pdk || error

# Put it together into a single pkg
productbuild --distribution extras/distribution.xml \
	--package-path $BUILD_TMP \
	--resources resources \
	--scripts scripts \
	--version "$VERSION" \
	$BUILD_TMP/pfms_agent.pkg || error

# Sign the package
productsign --sign "$APPLE_DEVNAME ($APPLE_DEVID)" \
        $BUILD_TMP/pfms_agent.pkg \
        $BUILD_TMP/pandorafms_agent.pkg

# Notarize
NOTARIZE=$(xcrun altool --notarize-app \
	--primary-bundle-id "com.pandorafms.pandorafms" \
	--asc-provider "$APPLE_DEVID" \
	--username "$APPLE_USER" \
	--password "$APPLE_PASS" \
	--file "$BUILD_TMP/pandorafms_agent.pkg")

if [ $(echo $NOTARIZE |grep -c UUID) -ne 1 ]
then
	printf "Unable to send the package to notarization. Exiting..."
	error
fi

RUUID=$(echo $NOTARIZE | awk '{print $NF}')

printf "PKG sent for notarization (Request UUID=$RUUID). This may take a few minutes...\n"

# In order to staple the pkg, notarization must be approved!
STATUS=1
while [ $STATUS -eq 1 ]; do
	get_notarization_info "$RUUID"
	printf "PKG not yet notarized by Apple. Trying again in 60 seconds...\n"
	sleep 60
done

if [ $MESSAGE -eq 1 ]
then
	echo "Package notarized. Stapling PKG..."
	xcrun stapler staple "$BUILD_TMP/pandorafms_agent.pkg" || error
fi


# Clean and prepare dmg creation
rm $BUILD_TMP/pfms_agent.pkg
rm $BUILD_TMP/pandorafms_src.pdk
rm $BUILD_TMP/pandorafms_uninstall.pdk

#Create dmg file
printf "Creating DMG file...\n"
hdiutil create -volname "Pandora FMS agent installer" \
	-srcfolder "$BUILD_TMP" \
	-ov -format UDZO \
	"$BUILD_DMG/$FULLNAME" || error

#Change the icon to dmg
sips -i extras/pandora_installer.png || error
DeRez -only icns extras/pandora_installer.png > tmpicns.rsrc || error
Rez -append tmpicns.rsrc -o "$BUILD_DMG/$FULLNAME" || error
SetFile -a C "$BUILD_DMG/$FULLNAME" || error

# Sign DMG. Not needed, but does not harm
printf "Signing DMG file...\n"
codesign --timestamp --options=runtime --sign "$APPLE_DEVAPP ($APPLE_DEVID)" \
	"$BUILD_DMG/$FULLNAME" || error

# Copy and clean folder
rm -Rf $BUILD_TMP
rm -Rf files/pandorafms/*pandora*
rm -Rf files/pandorafms/*tentacle*
rm -Rf files/pandorafms/plugins
rm -Rf files/pandorafms/man
rm -f files/pandorafms/README
rm -f tmpicns.rsrc

popd

printf "\nSUCCESS: DMG file created at \"$BUILD_DMG/$FULLNAME\"\n"