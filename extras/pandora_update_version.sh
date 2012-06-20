#!/bin/bash
# Automatically update Pandora FMS version and build where necessary.

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
TEMP_FILE="/tmp/pandora_update_version.tmp"
CODE_HOME=~/code/pandora/branches/pandora_4.0
CODE_HOME_ENT=~/code/artica/code
SPEC_FILES="$CODE_HOME/pandora_console/pandora_console.spec \
$CODE_HOME/pandora_agents/unix/pandora_agent.spec \
$CODE_HOME/pandora_server/pandora_server.spec \
$CODE_HOME_ENT/pandora/branches/4.0/pandora_console/enterprise/pandora_console_enterprise.spec \
$CODE_HOME_ENT/pandora/branches/4.0/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec"
DEBIAN_FILES="$CODE_HOME/pandora_console/DEBIAN \
$CODE_HOME/pandora_server/DEBIAN \
$CODE_HOME/pandora_agents/unix/DEBIAN \
$CODE_HOME_ENT/pandora/branches/4.0/pandora_console/DEBIAN \
$CODE_HOME_ENT/pandora/branches/4.0/pandora_server/PandoraFMS-Enterprise/DEBIAN"
SERVER_FILE="$CODE_HOME/pandora_server/lib/PandoraFMS/Config.pm"
SERVER_DB_FILE="$CODE_HOME/pandora_server/util/pandora_db.pl"
SERVER_CLI_FILE="$CODE_HOME/pandora_server/util/pandora_manage.pl"
SERVER_CONF_FILE="$CODE_HOME/pandora_server/conf/pandora_server.conf"
CONSOLE_DB_FILE="$CODE_HOME/pandora_console/pandoradb_data.sql"
CONSOLE_FILE="$CODE_HOME/pandora_console/include/config_process.php"
CONSOLE_INSTALL_FILE="$CODE_HOME/pandora_console/install.php"
AGENT_BASE_DIR="$CODE_HOME/pandora_agents/"
AGENT_UNIX_FILE="$CODE_HOME/pandora_agents/unix/pandora_agent"
AGENT_WIN_FILE="$CODE_HOME/pandora_agents/win32/pandora.cc"
AGENT_WIN_MPI_FILE="$CODE_HOME/pandora_agents/win32/installer/pandora.mpi"
AGENT_WIN_RC_FILE="$CODE_HOME/pandora_agents/win32/versioninfo.rc"

# Update version in spec files
function update_spec_version {
	FILE=$1

	if [ $NB == 1 ]; then
		sed -e "s/^\s*%define\s\s*release\s\s*.*/%define release     $BUILD/" "$FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$FILE"
	else
		sed -e "s/^\s*%define\s\s*release\s\s*.*/%define release     1/" "$FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$FILE"
	fi
	sed -e "s/^\s*%define\s\s*version\s\s*.*/%define version     $VERSION/" "$FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$FILE"
}

# Update version in debian dirs
function update_deb_version {
	DEBIAN_DIR=$1
	
	if [ $NB == 1 ]; then
		LOCAL_VERSION="$VERSION-$BUILD"
	else
		LOCAL_VERSION="$VERSION"
	fi

	sed -e "s/^pandora_version\s*=.*/pandora_version=\"$LOCAL_VERSION\"/" "$DEBIAN_DIR/make_deb_package.sh" > "$TEMP_FILE" && mv "$TEMP_FILE" "$DEBIAN_DIR/make_deb_package.sh" && sed -e "s/^Version:\s*.*/Version: $LOCAL_VERSION/" "$DEBIAN_DIR/control" > "$TEMP_FILE" && mv "$TEMP_FILE" "$DEBIAN_DIR/control"
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

# Pandora Server
echo "Updating Pandora Server version..."
sed -e "s/my\s\s*\$pandora_version\s*=.*/my \$pandora_version = \"$VERSION\";/" "$SERVER_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$SERVER_FILE"
sed -e "s/my\s\s*\$pandora_build\s*=.*/my \$pandora_build = \"$BUILD\";/" "$SERVER_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$SERVER_FILE"
echo "Updating DB maintenance script version..."
sed -e "s/my\s\s*\$version\s*=.*/my \$version = \"$VERSION PS$BUILD\";/" "$SERVER_DB_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$SERVER_DB_FILE"
echo "Updating CLI script version..."
sed -e "s/my\s\s*\$version\s*=.*/my \$version = \"$VERSION PS$BUILD\";/" "$SERVER_CLI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$SERVER_CLI_FILE"
sed -e "s/\s*\#\s*\Version.*/\# Version $VERSION/" "$SERVER_CONF_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$SERVER_CONF_FILE"

# Pandora Console
echo "Updating Pandora Console DB version..."
sed -e "s/\s*[(]\s*'db_scheme_version'\s*\,.*/('db_scheme_version'\,'$VERSION'),/" "$CONSOLE_DB_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_DB_FILE"
sed -e "s/\s*[(]\s*'db_scheme_build'\s*\,.*/('db_scheme_build'\,'PD$BUILD'),/" "$CONSOLE_DB_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_DB_FILE"
echo "Updating Pandora Console version..."
sed -e "s/\s*\$pandora_version\s*=.*/\$pandora_version = 'v$VERSION';/" "$CONSOLE_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_FILE"
sed -e "s/\s*\$build_version\s*=.*/\$build_version = 'PC$BUILD';/" "$CONSOLE_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_FILE"
echo "Updating Pandora Console installer version..."
sed -e "s/\s*\$version\s*=.*/\$version = '$VERSION';/" "$CONSOLE_INSTALL_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_INSTALL_FILE"
sed -e "s/\s*\$build\s*=.*/\$build = '$BUILD';/" "$CONSOLE_INSTALL_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_INSTALL_FILE"
echo "Setting develop_bypass to 0..."
sed -e "s/\s*if\s*(\s*[!]\s*isset\s*(\s*$develop_bypass\s*)\s*)\s*$develop_bypass\s*=.*/if ([!]isset($develop_bypass)) $develop_bypass = 0;/" "$CONSOLE_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$CONSOLE_FILE"

# Pandora Agents
echo "Updating Pandora Unix Agent version..."
sed -e "s/\s*use\s*constant\s*AGENT_VERSION =>.*/use constant AGENT_VERSION => '$VERSION';/" "$AGENT_UNIX_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_UNIX_FILE"
sed -e "s/\s*use\s*constant\s*AGENT_BUILD =>.*/use constant AGENT_BUILD => '$BUILD';/" "$AGENT_UNIX_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_UNIX_FILE"
echo "Updating Pandora Windows Agent version..."
sed -e "s/\s*#define\s*PANDORA_VERSION\s*.*/#define PANDORA_VERSION (\"$VERSION(Build $BUILD)\")/" "$AGENT_WIN_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_FILE"
sed -e "s/{Pandora FMS Windows Agent v.*}/{Pandora FMS Windows Agent v$VERSION}/" "$AGENT_WIN_MPI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_MPI_FILE"
NUMERIC_VERSION=$(echo $VERSION | sed -e "s/\([0-9]*\.[0-9]*\).*/\1/")
sed -n "1h;1!H;\${;g;s/[\r\n]InstallVersion[\r\n]{\S*}/\nInstallVersion\n{$NUMERIC_VERSION.0.0}/g;p;}" "$AGENT_WIN_MPI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_MPI_FILE"
sed -n "1h;1!H;\${;g;s/[\r\n]Version[\r\n]{[^\n\r]*}/\nVersion\n{$BUILD}/g;p;}" "$AGENT_WIN_MPI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_MPI_FILE"
if [ $NB == 1 ]; then
	sed -n "1h;1!H;\${;g;s/[\r\n]Windows\,Executable[\r\n]{[^\n\r]*}/\nWindows\,Executable\n{\<\%AppName\%\>\-\<\%Version\%\>\-Setup\<\%Ext\%\>}/g;p;}" "$AGENT_WIN_MPI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_MPI_FILE"
else
	sed -n "1h;1!H;\${;g;s/[\r\n]Windows\,Executable[\r\n]{[^\n\r]*}/\nWindows\,Executable\n{\<\%AppName\%\>\-Setup\<\%Ext\%\>}/g;p;}" "$AGENT_WIN_MPI_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_MPI_FILE"
fi
sed -e "s/\s*VALUE \"ProductVersion\".*/      VALUE \"ProductVersion\", \"($VERSION(Build $BUILD))\"/" "$AGENT_WIN_RC_FILE" > "$TEMP_FILE" && mv "$TEMP_FILE" "$AGENT_WIN_RC_FILE"
echo "Updating Agent configuration files..."
for conf in `find $AGENT_BASE_DIR -name pandora_agent.conf`; do
	sed -e "s/#\s*[Vv]ersion\s*[^\,]*/# Version $VERSION/" "$conf" > "$TEMP_FILE" && mv "$TEMP_FILE" "$conf"
done

rm -f "$TEMP_FILE"

