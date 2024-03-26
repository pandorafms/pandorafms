#
# Pandora FMS Server 
#
%global __os_install_post %{nil}
%global _missing_build_ids_terminate_build 0
%define __strip /bin/true
%define debug_package %{nil}
%define name        pandorafms_server
%define version     7.0NG.776
%define release     240326

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
BuildArchitectures: x86_64 
AutoReq:            0
Provides:           %{name}-%{version}
Requires(pre):      shadow-utils
Requires(post,preun): /sbin/chkconfig /sbin/service
Requires:           coreutils
Requires:           perl(DBI) perl(DBD::mysql)
Requires:           perl(HTTP::Request::Common) perl(LWP::Simple) perl(LWP::UserAgent)
Requires:           perl(XML::Simple) perl(XML::Twig) net-snmp-utils
Requires:           perl(NetAddr::IP) net-snmp net-tools
Requires:           perl(IO::Socket::INET6) perl(IO::Socket::SSL) perl(Net::Telnet)
Requires:           fping nmap sudo perl(JSON)
Requires:           perl(Time::HiRes) perl(Encode::Locale)
Requires:           perl perl(Sys::Syslog) perl(HTML::Entities) perl(Geo::IP) expect

%description
Pandora FMS is a monitoring system for big IT environments. It uses remote tests, or local agents to grab information. Pandora supports all standard OS (Linux, AIX, HP-UX, Solaris and Windows XP,2000/2003), and support multiple setups in HA enviroments.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server

%build

%install

rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{_bindir}/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/pandora/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/pandora/conf.d
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/tentacle/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/conf
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/md5
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/collections
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/netflow
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/sflow
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/trans
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/commands
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf.d/
mkdir -p $RPM_BUILD_ROOT%{_mandir}/man1/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.hourly/
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/lib/pandora/.ssh
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/

# Copy open discovery plugins to data_in
if [ -d "$RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery" ]; then
        echo ">Installing the open discovery scripts to $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery..."
        for disco_folder in $(ls "discovery/"); do
                if [ -d "discovery/"$disco_folder ]; then
                        if [ -d "$RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery/$disco_folder" ]; then
                                rm -Rf "$RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery/$disco_folder"
                        fi
                        cp -Rf "discovery/"$disco_folder "$RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery/$disco_folder"
                        chmod -R 770 "$RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery/$disco_folder"
                fi
        done

else
        echo ">ERROR: Failed to copy open discovery scripts to $RPM_BUILD_ROOT%{_localstatedir}/spool/pandora/data_in/discovery - Folder not found"
fi

# All binaries go to %{_bindir}
cp -aRf bin/pandora_server $RPM_BUILD_ROOT%{_bindir}/
cp -aRf bin/pandora_exec $RPM_BUILD_ROOT%{_bindir}/
install -m 0755 bin/tentacle_server $RPM_BUILD_ROOT%{_bindir}/

cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
cp -aRf util/pandora_ha.pl $RPM_BUILD_ROOT/usr/bin/pandora_ha
cp -aRf lib/* $RPM_BUILD_ROOT/usr/lib/perl5/

install -m 0755 util/pandora_server $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
install -m 0755 util/tentacle_serverd $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/

install -m 0444 man/man1/pandora_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/
install -m 0444 man/man1/tentacle_server.1.gz $RPM_BUILD_ROOT%{_mandir}/man1/

rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/PandoraFMS
rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/recon_scripts/PandoraFMS

if [ ! -f $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_server ] ; then
   install -m 0644 util/pandora_server_logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_server
fi
install -m 0640 conf/pandora_server.conf.new $RPM_BUILD_ROOT%{_sysconfdir}/pandora/pandora_server.conf.new
install -m 0640 conf/pandora_server_sec.conf.template $RPM_BUILD_ROOT%{_sysconfdir}/pandora/conf.d/pandora_server_sec.conf.template
install -m 0640 conf/tentacle_server.conf.new $RPM_BUILD_ROOT%{_sysconfdir}/tentacle/tentacle_server.conf.new

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d
chmod 0750 $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d
cat <<EOF > $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/pandora
Defaults:root !requiretty
EOF
chmod 0440 $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/pandora

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

current_ver=$(perl -le 'eval "require $ARGV[0]" and print $ARGV[0]->VERSION' Thread::Semaphore 2> /dev/null | cut -d '.' -f 2)
if [ $((current_ver)) -lt 13 ] ; then
        echo "perl Thread::Semaphore version >= 2.13 should be installed. Current version installed ver:  $(perl -le 'eval "require $ARGV[0]" and print $ARGV[0]->VERSION' Thread::Semaphore 2> /dev/null)"
        exit 1
fi

exit 0

%post
# Initial installation
# Run when not uninstalling
if [ "$1" -ge 1 ]
then
        if [ `command -v systemctl` ]
        then
                echo "Copying new version for tentacle_serverd service"
                cp -f /usr/share/pandora_server/util/tentacle_serverd.service /usr/lib/systemd/system/
                chmod -x /usr/lib/systemd/system/tentacle_serverd.service
        # Enable the services on SystemD
                systemctl enable tentacle_serverd.service     
        else
                /sbin/chkconfig --add tentacle_serverd
                /sbin/chkconfig tentacle_serverd on 
        fi

        /sbin/chkconfig --add pandora_server
        /sbin/chkconfig pandora_server on 

        systemctl enable pandora_server.service

        echo "Pandora FMS Server configuration is %{_sysconfdir}/pandora/pandora_server.conf"
        echo "Pandora FMS Server main directory is %{prefix}/pandora_server/"
        echo "The manual can be reached at: man pandora or man pandora_server"
        echo "Pandora FMS Documentation is in: http://pandorafms.org"
        echo " "
fi

# This will avoid config files overwritting on UPGRADES.
# Main configuration file
if [ ! -e "/etc/pandora/pandora_server.conf" ]
then
        echo "Creating a new version of Pandora FMS Server config file at /etc/pandora/pandora_server.conf"
        cat /etc/pandora/pandora_server.conf.new > /etc/pandora/pandora_server.conf
else
        # Do a copy of current .conf, just in case.
        echo "An existing version of pandora_server.conf is found."
        cat /etc/pandora/pandora_server.conf > /etc/pandora/pandora_server.conf.old
fi
# Tentacle server
if [ ! -e "/etc/tentacle/tentacle_server.conf" ]
then
        echo "Creating a new version of Tentacle Server config file at /etc/tentacle/tentacle_server.conf"
        cat /etc/tentacle/tentacle_server.conf.new > /etc/tentacle/tentacle_server.conf
fi

echo "Don't forget to start Tentacle Server daemon if you want to receive"
echo "data using tentacle"

if [ "$1" -gt 1 ]
then

      echo "If Tentacle Server daemon was running with init.d script,"
      echo "please stop it manually and start the service with systemctl"

fi

%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

current_ver=$(perl -le 'eval "require $ARGV[0]" and print $ARGV[0]->VERSION' Thread::Semaphore 2> /dev/null | cut -d '.' -f 2)
if [ $((current_ver)) -lt 13 ] ; then
        echo "perl Thread::Semaphore version >= 2.13 should be installed. Current version installed ver:  $(perl -le 'eval "require $ARGV[0]" and print $ARGV[0]->VERSION' Thread::Semaphore 2> /dev/null)"
        exit 1
fi

/sbin/service pandora_server stop >/dev/null 2>&1 || :
/sbin/service tentacle_serverd stop >/dev/null 2>&1 || :
/sbin/chkconfig --del pandora_server
/sbin/chkconfig --del tentacle_serverd

exit 0

%files
%defattr(750,root,root)
%doc AUTHORS COPYING README
%{_sysconfdir}/rc.d/init.d/pandora_server
%{_sysconfdir}/rc.d/init.d/tentacle_serverd
%{_sysconfdir}/cron.hourly/pandora_db
%config(noreplace) %{_sysconfdir}/sudoers.d/pandora
%config(noreplace) %{_sysconfdir}/logrotate.d/pandora_server

%defattr(755,pandora,root)
%{prefix}/pandora_server
/usr/lib/perl5/PandoraFMS

%{_mandir}/man1/pandora_server.1.gz
%{_mandir}/man1/tentacle_server.1.gz

%defattr(750,pandora,root)
%{_bindir}/pandora_exec
%{_bindir}/pandora_server
%{_bindir}/tentacle_server
%{_bindir}/pandora_ha

%dir %{_sysconfdir}/pandora
%dir %{_localstatedir}/spool/pandora

%defattr(-,pandora,root, 754)
%dir %{_localstatedir}/log/pandora

%defattr(600,root,root)
/etc/pandora/pandora_server.conf.new

%defattr(600,root,root)
/etc/pandora/conf.d/pandora_server_sec.conf.template

%defattr(664,root,root)
/etc/tentacle/tentacle_server.conf.new

%defattr(-,pandora,apache,2770)
%{_localstatedir}/spool/pandora
%{_localstatedir}/spool/pandora/data_in
%{_localstatedir}/spool/pandora/data_in/md5
%{_localstatedir}/spool/pandora/data_in/collections
%{_localstatedir}/spool/pandora/data_in/conf
%{_localstatedir}/spool/pandora/data_in/netflow
%{_localstatedir}/spool/pandora/data_in/sflow
%{_localstatedir}/spool/pandora/data_in/trans
%{_localstatedir}/spool/pandora/data_in/commands
