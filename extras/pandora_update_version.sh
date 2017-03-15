#!/bin/bash
# Automatically update Pandora FMS version and build where necessary.
source build_vars.sh

# Check command line arguments
if [ $# -lt 2 ] || [ $# -gt 3 ]; then
	echo "Usage: $0 <final|nightly> <version string> [build string]"
	exit 1
fi

# Set some global vars
if [ "$1" == "nightly" ]; then
	NB=1
else
	NB=0
fi
VERSION=$2
if [ $# == 2 ]; then
	BUILD=`date +%g%m%d`
else
	BUILD=$3
fi
SPEC_FILES="$CODEHOME/pandora_console/pandora_console.spec \
$CODEHOME/pandora_agents/unix/pandora_agent.spec \
$CODEHOME/pandora_server/pandora_server.spec \
$PANDHOME_ENT/pandora_console/enterprise/pandora_console_enterprise.spec \
$PANDHOME_ENT/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec \
$CODEHOME/pandora_console/pandora_console.redhat.spec \
$CODEHOME/pandora_agents/unix/pandora_agent.redhat.spec \
$CODEHOME/pandora_server/pandora_server.redhat.spec \
$PANDHOME_ENT/pandora_console/enterprise/pandora_console_enterprise.redhat.spec \
$PANDHOME_ENT/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.redhat.spec"
DEBIAN_FILES="$CODEHOME/pandora_console/DEBIAN \
$CODEHOME/pandora_server/DEBIAN \
$CODEHOME/pandora_agents/unix/DEBIAN \
$PANDHOME_ENT/pandora_console/DEBIAN \
$PANDHOME_ENT/pandora_server/PandoraFMS-Enterprise/DEBIAN"
INSTALLER_FILES="$CODEHOME/pandora_console/pandora_console_install \
$CODEHOME/pandora_server/pandora_server_installer \
$CODEHOME/pandora_agents/unix/pandora_agent_installer"
SERVER_FILE="$CODEHOME/pandora_server/lib/PandoraFMS/Config.pm"
SERVER_DB_FILE="$CODEHOME/pandora_server/util/pandora_db.pl"
SERVER_CLI_FILE="$CODEHOME/pandora_server/util/pandora_manage.pl"
SERVER_CONF_FILE="$CODEHOME/pandora_server/conf/pandora_server.conf.new"
SERVER_WIN_MPI_OPEN_FILE="$PANDHOME_ENT/pandora_server/extras/nsis_installer/pandorafms_open.nsi"
SERVER_WIN_MPI_ENT_FILE="$PANDHOME_ENT/pandora_server/extras/nsis_installer/pandorafms_ent.nsi"
CONSOLE_DB_FILE="$CODEHOME/pandora_console/pandoradb_data.sql"
CONSOLE_DB_FILE_ORACLE="$CODEHOME/pandora_console/pandoradb.data.oracle.sql"
CONSOLE_FILE="$CODEHOME/pandora_console/include/config_process.php"
CONSOLE_INSTALL_FILE="$CODEHOME/pandora_console/install.php"
AGENT_BASE_DIR="$CODEHOME/pandora_agents/"
AGENT_UNIX_FILE="$CODEHOME/pandora_agents/unix/pandora_agent"
AGENT_WIN_FILE="$CODEHOME/pandora_agents/win32/pandora.cc"
AGENT_WIN_MPI_FILE="$CODEHOME/pandora_agents/win32/installer/pandora.mpi"
AGENT_WIN_RC_FILE="$CODEHOME/pandora_agents/win32/versioninfo.rc"
SATELLITE_FILE="$PANDHOME_ENT/satellite_server/satellite_server.pl"

# Update version in spec files
function update_spec_version {
	FILE=$1

	if [ $NB == 1 ]; then
		sed -i -e "s/^\s*%define\s\s*release\s\s*.*/%define release     $BUILD/" "$FILE"
	else
		sed -i -e "s/^\s*%define\s\s*release\s\s*.*/%define release     1/" "$FILE"
	fi
	sed -i -e "s/^\s*%define\s\s*version\s\s*.*/%define version     $VERSION/" "$FILE"
}

# Update version in debian dirs
function update_deb_version {
	DEBIAN_DIR=$1

	if [ $NB == 1 ]; then
		LOCAL_VERSION="$VERSION-$BUILD"
	else
		LOCAL_VERSION="$VERSION"
	fi

	sed -i -e "s/^pandora_version\s*=.*/pandora_version=\"$LOCAL_VERSION\"/" "$DEBIAN_DIR/make_deb_package.sh" && sed -i -e "s/^Version:\s*.*/Version: $LOCAL_VERSION/" "$DEBIAN_DIR/control"
}

# Update version in installer
function update_installer_version {
	FILE=$1

	sed -i -e "/^PI_VERSION/s/=.*/=\"$VERSION\"/" -e "/^PI_BUILD/s/=.*/=\"$BUILD\"/" "$FILE"
}

# Spec files
for file in $SPEC_FILES; do
	echo "Updating spec file $file..."
	update_spec_version $file
done

# Debian dirs
for dir in $DEBIAN_FILES; do
	echo "Updating DEBIAN dir $dir..."
	update_deb_version $dir
done

# Installer files
for file in $INSTALLER_FILES; do
	echo "Updating installer file $file..."
	update_installer_version $file
done

# Pandora Server
echo "Updating Pandora Server version..."
sed -i -e "s/my\s\s*\$pandora_version\s*=.*/my \$pandora_version = \"$VERSION\";/" "$SERVER_FILE"
sed -i -e "s/my\s\s*\$pandora_build\s*=.*/my \$pandora_build = \"$BUILD\";/" "$SERVER_FILE"
echo "Updating DB maintenance script version..."
sed -i -e "s/my\s\s*\$version\s*=.*/my \$version = \"$VERSION PS$BUILD\";/" "$SERVER_DB_FILE"
echo "Updating CLI script version..."
sed -i -e "s/my\s\s*\$version\s*=.*/my \$version = \"$VERSION PS$BUILD\";/" "$SERVER_CLI_FILE"
sed -i -e "s/\s*\#\s*\Version.*/\# Version $VERSION/" "$SERVER_CONF_FILE"
sed -i -e "s/\s*\!define PRODUCT_VERSION.*/\!define PRODUCT_VERSION \"$VERSION\"/" "$SERVER_WIN_MPI_OPEN_FILE"
sed -i -e "s/\s*\!define PRODUCT_VERSION.*/\!define PRODUCT_VERSION \"$VERSION\"/" "$SERVER_WIN_MPI_ENT_FILE"

# Pandora Satellite Server
echo "Updating Pandora Satellite Server version..."
sed -i -e "s/\s*use constant SATELLITE_VERSION.*/use constant SATELLITE_VERSION \=\> \"$VERSION\";/" "$SATELLITE_FILE"
sed -i -e "s/\s*use constant SATELLITE_BUILD.*/use constant SATELLITE_BUILD \=\> \"$BUILD\";/" "$SATELLITE_FILE"

# Pandora Console
echo "Updating Pandora Console DB version..."
sed -i -e "s/\s*[(]\s*'db_scheme_version'\s*\,.*/('db_scheme_version'\,'$VERSION'),/" "$CONSOLE_DB_FILE_ORACLE"
sed -i -e "s/\s*[(]\s*'db_scheme_build'\s*\,.*/('db_scheme_build'\,'PD$BUILD'),/" "$CONSOLE_DB_FILE_ORACLE"

sed -i -e "s/\s*[(]\s*'db_scheme_version'\s*\,.*/('db_scheme_version'\,'$VERSION');/" "$CONSOLE_DB_FILE_ORACLE"
sed -i -e "s/\s*[(]\s*'db_scheme_build'\s*\,.*/('db_scheme_build'\,'PD$BUILD');/" "$CONSOLE_DB_FILE_ORACLE"

echo "Updating Pandora Console version..."
sed -i -e "s/\s*\$pandora_version\s*=.*/\$pandora_version = 'v$VERSION';/" "$CONSOLE_FILE"
sed -i -e "s/\s*\$build_version\s*=.*/\$build_version = 'PC$BUILD';/" "$CONSOLE_FILE"
echo "Updating Pandora Console installer version..."
sed -i -e "s/\s*\$version\s*=.*/\$version = '$VERSION';/" "$CONSOLE_INSTALL_FILE"
sed -i -e "s/\s*\$build\s*=.*/\$build = '$BUILD';/" "$CONSOLE_INSTALL_FILE"
echo "Setting develop_bypass to 0..."
sed -i -e "s/\s*if\s*(\s*[!]\s*isset\s*(\s*$develop_bypass\s*)\s*)\s*$develop_bypass\s*=.*/if ([!]isset($develop_bypass)) $develop_bypass = 0;/" "$CONSOLE_FILE"

# Pandora Agents
echo "Updating Pandora Unix Agent version..."
sed -i -e "s/\s*use\s*constant\s*AGENT_VERSION =>.*/use constant AGENT_VERSION => '$VERSION';/" "$AGENT_UNIX_FILE"
sed -i -e "s/\s*use\s*constant\s*AGENT_BUILD =>.*/use constant AGENT_BUILD => '$BUILD';/" "$AGENT_UNIX_FILE"
echo "Updating Pandora Windows Agent version..."
sed -i -e "s/\s*#define\s*PANDORA_VERSION\s*.*/#define PANDORA_VERSION (\"$VERSION(Build $BUILD)\")/" "$AGENT_WIN_FILE"
sed -i -e "s/{Pandora FMS Windows Agent v.*}/{Pandora FMS Windows Agent v$VERSION}/" "$AGENT_WIN_MPI_FILE"
NUMERIC_VERSION=$(echo $VERSION | sed -e "s/\([0-9]*\.[0-9]*\).*/\1/")
sed -i -n "1h;1!H;\${;g;s/[\r\n]InstallVersion[\r\n]{\S*}/\nInstallVersion\n{$NUMERIC_VERSION.0.0}/g;p;}" "$AGENT_WIN_MPI_FILE"
sed -i -n "1h;1!H;\${;g;s/[\r\n]Version[\r\n]{[^\n\r]*}/\nVersion\n{$BUILD}/g;p;}" "$AGENT_WIN_MPI_FILE"
if [ $NB == 1 ]; then
	sed --in-place -n "1h;1!H;\${;g;s/[\r\n]Windows\,Executable[\r\n]{[^\n\r]*}/\nWindows\,Executable\n{\<\%AppName\%\>\-\<\%Version\%\>\-Setup\<\%Ext\%\>}/g;p;}" "$AGENT_WIN_MPI_FILE"
else
	sed --in-place -n "1h;1!H;\${;g;s/[\r\n]Windows\,Executable[\r\n]{[^\n\r]*}/\nWindows\,Executable\n{\<\%AppName\%\>\-Setup\<\%Ext\%\>}/g;p;}" "$AGENT_WIN_MPI_FILE"
fi
sed -i -e "s/\s*VALUE \"ProductVersion\".*/      VALUE \"ProductVersion\", \"($VERSION(Build $BUILD))\"/" "$AGENT_WIN_RC_FILE"
echo "Updating Agent configuration files..."
for conf in `find $AGENT_BASE_DIR -name pandora_agent.conf`; do
	sed -i -e "s/#\s*[Vv]ersion\s*[^\,]*/# Version $VERSION/" "$conf"
done

