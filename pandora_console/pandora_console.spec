#
# Pandora FMS Console
#
%define name        pandorafms_console
%define version     7.0NG.776
%define release     240320
%define httpd_name      httpd
# User and Group under which Apache is running
%define httpd_name  apache2
%define httpd_user  wwwrun
%define httpd_group www

# Evaluate PHP version
%define phpver_lt_430 %(out=`rpm -q --queryformat='%{VERSION}' php` 2>&1 >/dev/null || out=0 ; out=`echo $out | tr . : | sed s/://g` ; if [ $out -lt 430 ] ; then out=1 ; else out=0; fi ; echo $out)

Summary:            Pandora FMS Console
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Pandora FMS <info@pandorafms.com>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.org
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:              /srv/www/htdocs
BuildRoot:          %{_tmppath}/%{name}
BuildArch:          noarch
AutoReq:            0
Requires:           apache2
Requires:           apache2-mod_php7
Requires:           php >= 8.0
Requires:           php-gd, php-snmp, php-json, php-gettext
Requires:           php-mysqlnd, php-ldap, php-mbstring, php 
Requires:           graphviz, xorg-x11-fonts-core, graphviz-gd
Requires:           php-zip, php-zlib, php-curl
Provides:           %{name}-%{version}

%description
Pandora FMS Console is a web application to manage Pandora FMS. Console allows to see graphical reports, state of every agent, also to access to the information sent by the agent, to see every monitored parameter and to see its evolution throughout the time, to form the different nodes, groups and users of the system. It is the part that interacts with the final user, and that will allows you to administer the system.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_console

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_console
cp -aRf . $RPM_BUILD_ROOT%{prefix}/pandora_console
if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console.spec ] ; then
   rm $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT

%post

# Install pandora_websocket_engine service.
cp -pf %{prefix}/pandora_console/pandora_websocket_engine /etc/init.d/
chmod +x /etc/init.d/pandora_websocket_engine

echo "You can now start the Pandora FMS Websocket service by executing"
echo "   /etc/init.d/pandora_websocket_engine start"

# Has an install already been done, if so we only want to update the files
# push install.php aside so that the console works immediately using existing
# configuration.
#
if [ -f %{prefix}/pandora_console/include/config.php ] ; then
   mv %{prefix}/pandora_console/install.php %{prefix}/pandora_console/install.done
   
   # Upgrading MR.
	echo "Updating the database schema."
	/usr/bin/php %{prefix}/pandora_console/godmode/um_client/updateMR.php 2>/dev/null

else
   echo "Please, now, point your browser to http://your_IP_address/pandora_console/install.php and follow all the steps described on it."
fi
cp -aRf %{prefix}/pandora_console/pandora_console_logrotate_suse /etc/logrotate.d/pandora_console

%preun

# Upgrading
if [ "$1" -eq "1" ]; then
        exit 0
fi

rm -Rf %{prefix}/pandora_console

%postun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

rm -Rf /etc/logrotate.d/pandora_console

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/pandora_console/docs
%{prefix}/pandora_console
