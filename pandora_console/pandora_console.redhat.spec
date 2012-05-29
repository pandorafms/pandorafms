#
# Pandora FMS Console
#
%define name        pandorafms_console
%define version     4.0.2
%define release     1

%define httpd_name      httpd
# User and Group under which Apache is running
%define httpd_name  httpd
%define httpd_user  apache
%define httpd_group apache

# Evaluate PHP version
%define phpver_lt_430 %(out=`rpm -q --queryformat='%{VERSION}' php` 2>&1 >/dev/null || out=0 ; out=`echo $out | tr . : | sed s/://g` ; if [ $out -lt 430 ] ; then out=1 ; else out=0; fi ; echo $out)

Summary:            Pandora FMS Console
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Artica ST <info@artica.es>
#Source0:            %{name}-%{version}-%{revision}.tar.gz
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              Productivity/Networking/Web/Utilities
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:              /var/www/html
BuildRoot:          %{_tmppath}/%{name}
BuildArchitectures: noarch
AutoReq:            0
Requires:           httpd >= 2.0.0
Requires:           php >= 5.2.0
Requires:           php-gd, php-snmp
Requires:           php-mysql, php-ldap, php-mbstring, php, php-common
Requires:           xorg-x11-fonts-75dpi, xorg-x11-fonts-misc
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
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_console
rm $RPM_BUILD_ROOT%{prefix}/pandora_console/*.spec
rm $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console_install

%clean
rm -rf $RPM_BUILD_ROOT

%post
# Has an install already been done, if so we only want to update the files
# push install.php aside so that the console works immediately using existing
# configuration.
#
if [ -f %{prefix}/pandora_console/include/config.php ] ; then
   mv %{prefix}/pandora_console/install.php %{prefix}/pandora_console/install.done
else
   echo "Please, now, point your browser to http://your_IP_address/pandora_console/install.php and follow all the steps described on it."
fi


%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/pandora_console/docs
%{prefix}/pandora_console


%defattr(770,pandora,%{httpd_group})
/var/spool/pandora/data_in
