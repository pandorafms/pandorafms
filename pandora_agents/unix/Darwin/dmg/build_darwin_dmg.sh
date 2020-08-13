#!/usr/bin/env bash

# Restore the original working directory and exit.
function error {
	popd >/dev/null 2>&1
	exit 1
}

# Keeping this for future CICD integration
if [ "$CI_PROJECT_DIR" != "" ]; then
	LOCALINST="$CODEHOME/pandora_agents/unix/Darwin/dmg"
else
	LOCALINST="/root/code/pandorafms/pandora_agents/unix/Darwin/dmg"
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
	VERSION="7.0NG.748"
fi

# Path for the generated DMG file
if [ "$#" -ge 3 ]; then
	BUILD_PATH="$3"
else
	BUILD_PATH="/root/code/pandorafms/pandora_agents/unix/Darwin/dmg"
fi

BUILD_DMG="$BUILD_PATH/build"
BUILD_TMP="$BUILD_PATH/buildtmp"

FULLNAME="$DMGNAME-$VERSION.dmg"
echo "VERSION-"$VERSION" NAME-"$DMGNAME
pushd .
cd $LOCALINST

# Copy necessary files to installer
cp ../com.pandorafms.pandorafms.plist files/pandorafms/
cp ../../../../pandora_agents/unix/pandora* files/pandorafms/
cp ../../../../pandora_agents/unix/tentacle* files/pandorafms/
cp -R ../../../../pandora_agents/unix/plugins files/pandorafms/
cp -R ../../../../pandora_agents/unix/man files/pandorafms/
cp -R ../../../../pandora_agents/unix/Darwin/pandora_agent.conf files/pandorafms/
mkdir $BUILD_DMG
mkdir $BUILD_TMP

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
	$BUILD_TMP/pandorafms_agent.pkg || error

# Clean and prepare dmg creation
rm $BUILD_TMP/pandorafms_src.pdk
rm $BUILD_TMP/pandorafms_uninstall.pdk

#Create dmg file
hdiutil create -volname "Pandora FMS agent installer" \
	-srcfolder "$BUILD_TMP" \
	-ov -format UDZO \
	"$BUILD_DMG/$FULLNAME" || error

#Change the icon to dmg
sips -i extras/pandora_installer.png || error
DeRez -only icns extras/pandora_installer.png > tmpicns.rsrc || error
Rez -append tmpicns.rsrc -o "$BUILD_DMG/$FULLNAME" || error
SetFile -a C "$BUILD_DMG/$FULLNAME" || error


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