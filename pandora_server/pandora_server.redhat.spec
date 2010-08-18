#
# Pandora FMS Server 
#
%define name        pandorafms_server
%define version     3.1
%define release     2

Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
Patch0:             %{name}-3.1-init.patch
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             %{_datadir}
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: noarch 
Prereq:             /sbin/chkconfig, /sbin/service
Prereq:             %{_sbindir}/useradd
AutoReq:            0
Provides:           %{name}-%{version}
Requires:           coreutils
Requires:           perl-Mail-Sendmail perl-DBI perl-DBD-mysql perl-Time-Format 
Requires:           perl-XML-Simple perl-XML-SAX
Requires:           perl-NetAddr-IP net-snmp net-tools
Requires:           nmap wmic sudo xprobe2

%description
Pandora FMS is a monitoring system for big IT environments. It uses remote tests, or local agents to grab information. Pandora supports all standard OS (Linux, AIX, HP-UX, Solaris and Windows XP,2000/2003), and support multiple setups in HA enviroments.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server
%patch0 -p2 -b .init

%build
#$%{__perl} Makefile.PL INSTALLDIRS=vendor
%{__perl} Makefile.PL
make

%install

rm -rf $RPM_BUILD_ROOT
make install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT
find $RPM_BUILD_ROOT -type f -name .packlist -delete
find $RPM_BUILD_ROOT -type d -depth -delete 2>/dev/null
#mkdir -p $RPM_BUILD_ROOT%{_bindir}/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/pandora/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/conf
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/md5
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
#mkdir -p $RPM_BUILD_ROOT%{perl_sitelib}/
mkdir -p $RPM_BUILD_ROOT%{_mandir}/man1/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/lib/pandora/.ssh

# All binaries go to %{_bindir}
#cp -aRf bin/pandora_server $RPM_BUILD_ROOT%{_bindir}/
#cp -aRf bin/pandora_exec $RPM_BUILD_ROOT%{_bindir}/
install -m 0755 bin/tentacle_server $RPM_BUILD_ROOT%{_bindir}/

cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
#cp -aRf lib/* $RPM_BUILD_ROOT%{perl_sitelib}/

install -m 0755 util/pandora_server $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
install -m 0755 util/tentacle_serverd $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/

install -m 0444 man/man1/pandora_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/
install -m 0444 man/man1/tentacle_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/

rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/PandoraFMS

install -m 0644 util/pandora_logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_server
install -m 0640 conf/pandora_server.conf $RPM_BUILD_ROOT%{_sysconfdir}/pandora/

cat <<EOF > $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/pandora_db
#!/bin/bash
%__perl %{prefix}/pandora_server/util/pandora_db.pl %{_sysconfdir}/pandora/pandora_server.conf
EOF
chmod 0755 $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/pandora_db

%clean
rm -fr $RPM_BUILD_ROOT

%pre
/usr/sbin/useradd -d %{prefix}/pandora_server -s /bin/false -M -g 0 pandora
if [ -e "/etc/pandora/pandora_server.conf" ]
then
	cat /etc/pandora/pandora_server.conf > /etc/pandora/pandora_server.conf.old
fi

id pandora >/dev/null 2>&1 || \
/usr/sbin/useradd -d /var/spool/pandora -s /sbin/nologin -m -g 0 pandora 2> /dev/null
exit 0

%post
# Initial installation
if [ "$1" = 1 ]; then
   /sbin/chkconfig --add pandora_server
   /sbin/chkconfig --add tentacle_serverd
   /sbin/chkconfig pandora_server on 
   /sbin/chkconfig tentacle_serverd on 

   echo "Pandora FMS Server configuration is %{_sysconfdir}/pandora/pandora_server.conf"
   echo "Pandora FMS Server main directory is %{prefix}/pandora_server/"
   echo "The manual can be reached at: man pandora or man pandora_server"
   echo "Pandora FMS Documentation is in: http://pandorafms.org"
   echo " "
fi

echo "Don't forget to start Tentacle Server daemon if you want to receive"
echo "data using tentacle"

%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

/sbin/service pandora_server stop &>/dev/null
/sbin/service tentacle_serverd stop &>/dev/null
/sbin/chkconfig --del pandora_server
/sbin/chkconfig --del tentacle_serverd
userdel pandora

exit 0

%files
%defattr(-,root,root)
%doc AUTHORS COPYING ChangeLog README
%{_sysconfdir}/rc.d/init.d/pandora_server
%{_sysconfdir}/rc.d/init.d/tentacle_serverd
%{_sysconfdir}/cron.daily/pandora_db
%config(noreplace) %{_sysconfdir}/logrotate.d/pandora_server
%{perl_sitelib}/PandoraFMS/
%{prefix}/pandora_server
%{_mandir}/man1/pandora_server.1.gz
%{_mandir}/man1/tentacle_server.1.gz
%{_mandir}/man3/PandoraFMS::Core.3pm.gz
%{_mandir}/man3/PandoraFMS::GIS.3pm.gz
%{_mandir}/man3/PandoraFMS::GeoIP.3pm.gz

%defattr(-,pandora,root)
%{_bindir}/pandora_exec
%{_bindir}/pandora_server
%{_bindir}/tentacle_server
%dir %{_localstatedir}/log/pandora
%dir %{_sysconfdir}/pandora
%config(noreplace) %{_sysconfdir}/pandora/pandora_server.conf
%dir %{_localstatedir}/spool/pandora

%defattr(770,pandora,apache)
%{_localstatedir}/spool/pandora/data_in

