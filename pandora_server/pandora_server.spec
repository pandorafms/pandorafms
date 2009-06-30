#
# Pandora FMS Server 
#
%define name        PandoraFMS_Server
%define version	    3.0.0
%define release     1_

Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Manuel Arostegui <marostegui@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: i386 
%if "%{_vendor}" == "suse"
Requires:           perl-XML-Simple perl-DateManip perl-Net-SNMP
%endif
%if "%{_vendor}" == "redhat"
Requires:           perl-XML-Simple net-snmp-perl perl-DateManip perl-Mail-Sendmail xprobe2 net-snmp-utils
Requires(pre):      /usr/sbin/useradd
%endif
AutoReq:            0
Provides:           %{name}-%{version}
Requires:           mysql, mysql-server perl-ExtUtils-MakeMaker perl-NetAddr-IP

%description
Pandora watchs your systems and applications, and allows to know the status of any element of that systems. Pandora could detect a network interface down, a defacementin your website, memory leak in one of your server app, or the movement of any value of the NASDAQ new technology market. If you want, Pandora could sent a SMS messagewhen your systems fails... or when Google value low below US$ 33

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server

%build

%install
%define perl_version %(rpm -q --queryformat='%{VERSION}' perl)
export perl_version=`rpm -q --queryformat='%{VERSION}' perl`
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/usr/local
mkdir -p $RPM_BUILD_ROOT/usr/local/bin
%if "%{_vendor}" == "redhat"
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
%endif
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT/var/run/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/Net
%if "%{_vendor}" == "redhat"
cp -aRf bin/pandora_* $RPM_BUILD_ROOT/usr/local/bin/
cp -aRf util/pandora_exec $RPM_BUILD_ROOT/usr/local/bin/
cp -aRf bin/tentacle_server $RPM_BUILD_ROOT/usr/sbin/
cp -aRf bin/tentacle_server $RPM_BUILD_ROOT/usr/local/bin/
%else
cp -aRf bin/pandora_* $RPM_BUILD_ROOT/usr/local/bin/
cp -aRf util/pandora_exec $RPM_BUILD_ROOT/usr/local/bin/
cp -aRf util/tentacle_server $RPM_BUILD_ROOT/usr/local/bin/
cp -aRf bin/tentacle_server $RPM_BUILD_ROOT/usr/local/bin/
%endif
cp -aRf util/wmic $RPM_BUILD_ROOT/usr/bin/
cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
cp -aRf lib/* $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/
cp -aRf util/Time/ $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/
cp -aRf util/Traceroute/ $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/Net
cp -aRf util/Traceroute.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/$perl_version/Net
cp -aRf AUTHORS COPYING ChangeLog README $RPM_BUILD_ROOT%{prefix}/pandora_server/
#%if "%{_vendor}" == "suse"
#   cp -aRf util/SLES10/pandora_* $RPM_BUILD_ROOT/etc/init.d/
#%else
#%if "%{_vendor}" == "redhat"
#   cp -aRf util/RHEL/* $RPM_BUILD_ROOT/etc/init.d/
#%else
   cp -aRf pandora_* $RPM_BUILD_ROOT/etc/init.d/
   cp -aRf util/tentacle_serverd $RPM_BUILD_ROOT/etc/init.d/
   rm -fr $RPM_BUILD_ROOT/etc/init.d/*_installer $RPM_BUILD_ROOT/etc/init.d/*.spec
#%endif
#%endif

%clean
rm -fr $RPM_BUILD_ROOT
%pre
%if "%{_vendor}" == "redhat"
   /usr/sbin/useradd -d %{prefix}/pandora -s /sbin/nologin -M -r pandora 2>/dev/null
%endif
exit 0

%post
%if "%{_vendor}" == "suse"
   ln -s /etc/init.d/pandora_server /etc/rc.d/rc3.d/S99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc.d/rc2.d/S99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc.d/rc0.d/K99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc.d/rc6.d/K99pandora_server
   ln -s /etc/init.d/tentacle_serverd /etc/rc.d/rc3.d/S99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc.d/rc2.d/S99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc.d/rc0.d/K99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc.d/rc6.d/K99tentacle_serverd
%else
   ln -s /etc/init.d/pandora_server /etc/rc3.d/S99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc2.d/S99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc0.d/K99pandora_server
   ln -s /etc/init.d/pandora_server /etc/rc6.d/K99pandora_server
   ln -s /etc/init.d/tentacle_serverd /etc/rc3.d/S99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc2.d/S99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc0.d/K99tentacle_serverd
   ln -s /etc/init.d/tentacle_serverd /etc/rc6.d/K99tentacle_serverd
%endif

if [ ! -d /etc/pandora ] ; then
   mkdir -p /etc/pandora
fi
if [ ! -L /etc/pandora/pandora_server.conf ] ; then
   ln -s /usr/share/pandora_server/conf/pandora_server.conf /etc/pandora/
   echo "Pandora Server configuration is /etc/pandora/pandora_server.conf"
   echo "Pandora Server data has been placed under /var/spool/pandora/data_in/"
   echo "Pandora Server logs has been placed under /var/log/"
   echo "Pandora Server main directory is %{prefix}/pandora_server/"
   echo "To start all PandoraFMS servers: pandora_server start"
   echo "The manual can be reached at: man pandora or man pandora_server"
   echo "Pandora Documentation is in: http://openideas.info/wiki/index.php?title=Pandora_2.0:Documentation"
fi
/etc/init.d/tentacle_serverd start
   echo "Pandora Server configuration is /etc/pandora/pandora_server.conf"
   echo "Pandora Server data has been placed under /var/spool/pandora/data_in/"
   echo "Pandora Server logs has been placed under /var/log/"
   echo "Pandora Server main directory is %{prefix}/pandora_server/"
   echo "To start all PandoraFMS servers: /etc/init.d/pandora_server start"
   echo "Make sure you have the correct dbuser and dbpass in /etc/pandora/pandora_server.conf"
   echo "The manual can be reached at: man pandora or man pandora_server"
   echo "Pandora Documentation is in: http://openideas.info/wiki/index.php?title=Pandora_2.0:Documentation"


%preun
if [ "$1" = 0 ]; then
   # stop pandora silently, but only if it's running
   /usr/sbin/pandora_server stop &>/dev/null
   %if "%{_vendor}" == "redhat"
      /sbin/chkconfig --del pandora_server
   %endif
fi

%files

%defattr(700,pandora,pandora)
/etc/init.d/pandora_server
/etc/init.d/tentacle_serverd
/usr/bin/wmic
/usr/local/bin/pandora_exec
/usr/local/bin/pandora_server
/usr/sbin/tentacle_server
/usr/local/bin/tentacle_server


%defattr(755,pandora,pandora)
/usr/lib/perl5/site_perl/%{perl_version}/PandoraFMS/
/usr/lib/perl5/site_perl/%{perl_version}/Net/Traceroute/
/usr/lib/perl5/site_perl/%{perl_version}/Net/Traceroute.pm
/usr/lib/perl5/site_perl/%{perl_version}/Time/
%{prefix}/pandora_server
/var/log/pandora
/var/spool/pandora/
#/var/spool/pandora/data_in

