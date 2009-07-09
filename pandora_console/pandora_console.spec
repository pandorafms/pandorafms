#
# Pandora FMS Console
#
%define name        PandoraFMS_Console
%define version     3.0.0


%define httpd_name      httpd
# User and Group under which Apache is running
# Red Hat: apache:apache
%define httpd_user      apache
%define httpd_group     apache
# OpenSUSE: wwwrun:www
%if "%{_vendor}" == "suse"
    %define httpd_name  apache2
    %define httpd_user  wwwrun
    %define httpd_group www
%endif

# Red Hat
# Apache server is packaged under the name of:
# - apache: up to Red Hat 9.0, FC6 and Red Hat Enterprise 2.1
# - httpd: after these releases above
%define is_rh7      0
%define is_el2      0
%define is_centos2  0
%define is_fedora   0
%if %(test -f "/etc/redhat-release" && echo 1 || echo 0)
    %define is_rh7 %(test -n "`cat /etc/redhat-release | grep '(Valhalla)'`" && echo 1 || echo 0)
    %define is_el2 %(test -n "`cat /etc/redhat-release | grep '(Pensacola)'`" && echo 1 || echo 0)
    %define is_centos2 %(test -n "`cat /etc/redhat-release | grep 'CentOS release 2'`" && echo 1 || echo 0)
    %define is_fedora %(test -n "`cat /etc/redhat-release | grep 'Fedora'`" && echo 1 || echo 0)
%endif
%define is_apache   0
%if %{is_rh7}
%define is_apache   1
%endif
%if %{is_el2}
%define is_apache   1
%endif
%if %{is_centos2}
%define is_apache   1
%if %{is_fedora}
%define is_apache   1
%endif
%endif
# Evaluate PHP version
%define phpver_lt_430 %(out=`rpm -q --queryformat='%{VERSION}' php` 2>&1 >/dev/null || out=0 ; out=`echo $out | tr . : | sed s/://g` ; if [ $out -lt 430 ] ; then out=1 ; else out=0; fi ; echo $out)

Summary:            Web Console for Pandora FMS
Name:               %{name}
Version:            %{version}
Release:            1 
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              Productivity/Networking/Web/Utilities
Packager:           Manuel Arostegui <marostegui@artica.es>

%if "%{_vendor}" == "suse"
Prefix:             /srv/www/htdocs
%else
Prefix:             /var/www/html
%endif
BuildRoot:          %{_tmppath}/%{name}
BuildArchitectures: noarch

AutoReq:            0
%if "%{_vendor}" == "suse"
Requires:           apache2
Requires:           php >= 4.3.0
Requires:           php-gd, php-snmp, php-pear
Requires:           mysql, php-mysql
Requires:           graphviz

%else
%if %{is_apache}
Requires:           apache
%else
Requires:           httpd
%endif
Requires:           httpd mysql-server php-pear php-mysql php-pear-DB php-gd php-snmp php-ldap php-mbstring net-snmp-perl net-snmp-utils graphviz-php 
%endif

Provides:           %{name}-%{version}

%description
The Web Console is a web application that allows to see graphical reports, state of every agent, also to
access to the information sent by the agent, to see every monitored parameter and to see its evolution
throughout the time, to form the different nodes, groups and users of the system. It is the part that
interacts with the ﬁnal user, and that will allows you to administer the system.


%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_console

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_console
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_console
if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console.spec ] ; then
   rm $RPM_BUILD_ROOT%{prefix}/pandora_console/pandora_console.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT
%post
echo "Please, now, point your broswer to http://localhost/pandora_console/install.php and follow all the steps described on it."

#
# Has an install already been done, if so we only want to update the files
# push install.php aside so that the console works immediately using existing
# configuration.
#
if [ -f %{prefix}/pandora_console/include/config.php ] ; then
   mv %{prefix}/pandora_console/install.php %{prefix}/pandora_console/install.done
else
   pear install DB
   echo "Please, now, point your broswer to http://localhost/pandora_console/install.php and follow all the steps described on it."
fi
%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/pandora_console/docs
%{prefix}/pandora_console
