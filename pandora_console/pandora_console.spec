#
# Pandora Console
#
%define name        PandoraFMS_Console
%define version     1.2.0
%define release     1


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

Summary:            Web Console for PandoraFMS
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.net
Group:              Productivity/Networking/Web/Utilities
Packager:           Manuel Arostegui <marostegui@artica.es>

%if "%{_vendor}" == "suse"
Prefix:             /srv/www
%else
Prefix:             /var/www/html
%endif
BuildRoot:          %{_tmppath}/%{name}
BuildArchitectures: noarch

AutoReq:            0
%if "%{_vendor}" == "suse"
Requires:           apache2

Requires:           mysql, php4-mysql
%else
%if %{is_apache}
Requires:           apache
%else
Requires:           httpd
%endif
Requires:           php >= 4.3.0
Requires:           php-gd
Requires:           mysql, mysql-server, php-mysql jpgraph 
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
mkdir -p $RPM_BUILD_ROOT%{prefix}/%{name}
mkdir -p $RPM_BUILD_ROOT/usr/share/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}
cp pandora.1 $RPM_BUILD_ROOT/usr/share/man/man1/
cp pandora_console.1 $RPM_BUILD_ROOT/usr/share/man/man1/
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}/%{name}.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT
%post
echo "Please you HAVE to take a look at the INSTALL file placed on your DocumentRoot in order to follow the instructions to create the database for Pandora"
%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/%{name}/docs
%{prefix}/%{name}
%{_mandir}/man1/pandora.1.gz
%{_mandir}/man1/pandora_console.1.gz
