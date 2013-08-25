#
# Pandora FMS Server 
#
%define name        pandorafms_server
%define version     4.1
%define release     130826

Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             %{_datadir}
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: noarch 
Prereq:             /sbin/chkconfig, /sbin/service
AutoReq:            0
Provides:           %{name}-%{version}
Requires(pre):      shadow-utils
Requires:           coreutils
Requires:           perl-DBI perl-DBD-mysql perl-libwww-perl
Requires:           perl-XML-Simple perl-XML-Twig net-snmp-utils
Requires:           perl-NetAddr-IP net-snmp net-tools
Requires:           perl-IO-Socket-INET6 perl-Socket6
Requires:           nmap wmic sudo perl-JSON

%description
Pandora FMS is a monitoring system for big IT environments. It uses remote tests, or local agents to get information. Pandora FMS supports all standard OS (Linux, AIX, HP-UX, BSD, Solaris and Windows), and support multiple setups in HA enviroments. Pandora FMS server is the core component to process all information and requires a database to work.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server

%build

%install

rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{_bindir}/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/pandora/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/conf
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/md5
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/collections
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT%{_mandir}/man1/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.hourly/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/lib/pandora/.ssh
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/

# All binaries go to %{_bindir}
cp -aRf bin/pandora_server $RPM_BUILD_ROOT%{_bindir}/
cp -aRf bin/pandora_exec $RPM_BUILD_ROOT%{_bindir}/
install -m 0755 bin/tentacle_server $RPM_BUILD_ROOT%{_bindir}/

cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
cp -aRf lib/* $RPM_BUILD_ROOT/usr/lib/perl5/

install -m 0755 util/pandora_server $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
install -m 0755 util/tentacle_serverd $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/

install -m 0444 man/man1/pandora_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/
install -m 0444 man/man1/tentacle_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/

rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/PandoraFMS
rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/recon_scripts/PandoraFMS

install -m 0644 util/pandora_logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_server
install -m 0640 conf/pandora_server.conf.new $RPM_BUILD_ROOT%{_sysconfdir}/pandora/pandora_server.conf.new

cat <<EOF > $RPM_BUILD_ROOT%{_sysconfdir}/cron.hourly/pandora_db
#!/bin/bash
%__perl %{prefix}/pandora_server/util/pandora_db.pl %{_sysconfdir}/pandora/pandora_server.conf
EOF
chmod 0755 $RPM_BUILD_ROOT%{_sysconfdir}/cron.hourly/pandora_db

%clean
rm -fr $RPM_BUILD_ROOT

%pre
getent passwd pandora >/dev/null || \
    /usr/sbin/useradd -d %{prefix}/pandora_server -s /sbin/nologin -M -g 0 pandora

exit 0

%post
# Initial installation
if [ "$1" = 1 ]; then
   /sbin/chkconfig --add pandora_server
   /sbin/chkconfig --add tentacle_serverd
   /sbin/chkconfig pandora_server on 
   /sbin/chkconfig tentacle_serverd on 

   echo "Pandora FMS Server main directory is %{prefix}/pandora_server/"
   echo "The manual can be reached at: man pandora or man pandora_server"
   echo "Pandora FMS Documentation is in: http://pandorafms.com"
   echo " "
fi

# This will avoid pandora_server.conf overwritting on UPGRADES.

if [ ! -e "/etc/pandora/pandora_server.conf" ]
then
        echo "Creating a new version of Pandora FMS Server config file at /etc/pandora/pandora_server.conf"
        cat /etc/pandora/pandora_server.conf.new > /etc/pandora/pandora_server.conf
else
        # Do a copy of current .conf, just in case.
        echo "An existing version of pandora_server.conf is found."
        cat /etc/pandora/pandora_server.conf > /etc/pandora/pandora_server.conf.old
fi

echo "Don't forget to start Tentacle Server daemon if you want to receive data using tentacle"

%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

/sbin/service pandora_server stop &>/dev/null
/sbin/service tentacle_serverd stop &>/dev/null
/sbin/chkconfig --del pandora_server
/sbin/chkconfig --del tentacle_serverd

exit 0

%files
%defattr(-,root,root)
%doc AUTHORS COPYING ChangeLog README
%{_sysconfdir}/rc.d/init.d/pandora_server
%{_sysconfdir}/rc.d/init.d/tentacle_serverd
%{_sysconfdir}/cron.hourly/pandora_db
%config(noreplace) %{_sysconfdir}/logrotate.d/pandora_server

%defattr(755,pandora,root)
%{prefix}/pandora_server
/usr/lib/perl5/PandoraFMS

%{_mandir}/man1/pandora_server.1.gz
%{_mandir}/man1/tentacle_server.1.gz

%defattr(-,pandora,root)
%{_bindir}/pandora_exec
%{_bindir}/pandora_server
%{_bindir}/tentacle_server
%dir %{_localstatedir}/log/pandora
%dir %{_sysconfdir}/pandora
%dir %{_localstatedir}/spool/pandora

%defattr(600,root,root)
/etc/pandora/pandora_server.conf.new

%defattr(-,pandora,apache,770)
%{_localstatedir}/spool/pandora
%{_localstatedir}/spool/pandora/data_in
%{_localstatedir}/spool/pandora/data_in/md5
%{_localstatedir}/spool/pandora/data_in/collections
%{_localstatedir}/spool/pandora/data_in/conf

