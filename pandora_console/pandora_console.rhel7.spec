#
# Pandora FMS Console
#
%global _missing_build_ids_terminate_build 0
%define __strip /bin/true
%define debug_package %{nil}
%define name        pandorafms_console
%define version     7.0NG.776
%define release     240326

# User and Group under which Apache is running
%define httpd_name  httpd
%define httpd_user  apache
%define httpd_group apache

Summary:            Pandora FMS Console
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Pandora FMS <info@pandorafms.com>
#Source0:            %{name}-%{version}-%{revision}.tar.gz
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              Productivity/Networking/Web/Utilities
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /opt/rh/httpd24/root/var/www/html
BuildRoot:          %{_tmppath}/%{name}
BuildArch:          x86_64
AutoReq:            0
Requires:           httpd24-httpd
Requires:           httpd24-mod_php >= 7.2
Requires:           rh-php72-php-gd, rh-php72-php-ldap, rh-php72-php-snmp, rh-php72-php-session, rh-php72-php-gettext
Requires:           rh-php72-php-mysqlnd, rh-php72-php-mbstring, rh-php72-php-zip, rh-php72-php-zlib, rh-php72-php-curl
Requires:           xorg-x11-fonts-75dpi, xorg-x11-fonts-misc, php-pecl-zip
Requires:           graphviz
Provides:           %{name}-%{version}


%description
The Web Console is a web application that allows to see graphical reports, state of every agent, also to access to the information sent by the agent, to see every monitored parameter and to see its evolution throughout the time, to form the different nodes, groups and users of the system. It is the part that interacts with the final user, and that will allows you to administer the system.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_console

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_console
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -aRf . $RPM_BUILD_ROOT%{prefix}/pandora_console
rm $RPM_BUILD_ROOT%{prefix}/pandora_console/*.spec
rm $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console_install
install -m 0644 pandora_console_logrotate_centos $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_console

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

%preun

# Upgrading
if [ "$1" -eq "1" ]; then
        exit 0
fi

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/pandora_console/docs
%{prefix}/pandora_console
%config(noreplace) %{_sysconfdir}/logrotate.d/pandora_console
%attr(0644, root, root) %{_sysconfdir}/logrotate.d/pandora_console
%defattr(0744,%{httpd_user},%{httpd_group},0755)
%{prefix}/pandora_console/attachment/discovery
