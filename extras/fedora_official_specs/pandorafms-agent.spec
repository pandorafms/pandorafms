Name: pandorafms-agent
Version: 5.0
Release: 140223.sp3%{?dist}
Summary: Host/service/network agent for Pandora FMS monitoring system
License: GPLv2
Vendor: Artica <http://www.artica.es>
Source: http://code.pandorafms.com/static_download/pandorafms_agent_unix-5.0SP3.tar.gz
#Source: %{name}-%{version}.tar.gz
#Source0: http://code.pandorafms.com/static_download/pandorafms_agent_unix-5.0SP3.tar.gz
URL: http://pandorafms.com
Group: Applications/System
#Prefix: /usr/share
#BuildRoot: %{_tmppath}/%{name}-%{version}-buildroot
BuildArch: noarch
Requires(pre): /bin/sed /bin/grep 
Requires(pre): shadow-utils
Requires: coreutils unzip
Requires(preun): initscripts, chkconfig
Requires(post): initscripts, chkconfig
Requires(postun): initscripts

%description
Pandora FMS agent for unix. Pandora FMS is a full-featured monitoring software.

%prep
%setup -q -n unix
%build

%install

rm -rf %{buildroot}
mkdir -p %{buildroot}/usr/bin/
mkdir -p %{buildroot}/etc/pandora/
mkdir -p %{buildroot}/etc/init.d/
mkdir -p %{buildroot}/var/log/pandora/
mkdir -p %{buildroot}/usr/share/man/man1/

install -m 0755 pandora_agent %{buildroot}%{_bindir}/pandora_agent
install -m 0755 pandora_agent_exec %{buildroot}%{_bindir}/pandora_agent_exec
install -m 0755 tentacle_client %{buildroot}%{_bindir}/tentacle_client
install -m 0755 pandora_agent_daemon %{buildroot}/etc/init.d/pandora_agent_daemon
install -m 0644 man/man1/pandora_agent.1.gz %{buildroot}/usr/share/man/man1/pandora_agent.1.gz
install -m 0644 man/man1/tentacle_client.1.gz %{buildroot}/usr/share/man/man1/tentacle_client.1.gz
install -m 0600 Linux/pandora_agent.conf %{buildroot}/etc/pandora/pandora_agent.conf
install -d -m 0755 %{buildroot}/etc/pandora/plugins
install -d -m 0755 %{buildroot}/etc/pandora/collections

# Copying all plugins inside plugin directory and set 755 perms on them
cp plugins/* %{buildroot}/etc/pandora/plugins
chmod 755 %{buildroot}/etc/pandora/plugins/*

%clean
rm -Rf %{buildroot}

%pre
getent passwd pandora >/dev/null || \
	/usr/sbin/useradd -d /etc/pandora -s /bin/false -M -g 0 pandora
exit 0

%post

if [ $1 -eq 1 ]; then
	# Initial installation. Needed to start pandora agent
	mkdir -p /var/spool/pandora/data_out
	/sbin/chkconfig --add pandora_agent_daemon
	/sbin/chkconfig pandora_agent_daemon on
fi

%preun

# package removal, not upgrade 
if [ $1 -eq 0  ]; then
	/sbin/chkconfig --del pandora_agent_daemon 
	/etc/init.d/pandora_agent_daemon stop
	/usr/sbin/userdel pandora
	rm -Rf /var/log/pandora/pandora_agent* 2> /dev/null
fi

%files
%defattr(750,pandora,root)
/usr/bin/pandora_agent
/usr/bin/pandora_agent_exec
/usr/bin/tentacle_client
/etc/init.d/pandora_agent_daemon

%defattr(-,pandora,root,770)
/var/log/pandora/

%defattr(600,pandora,root)
/etc/pandora/pandora_agent.conf
/etc/pandora/collections
/etc/pandora/plugins

%config /etc/pandora/pandora_agent.conf

%doc
/usr/share/man/man1/pandora_agent.1.gz
/usr/share/man/man1/tentacle_client.1.gz

%changelog
* Sun Feb 23 2014 Sancho Lerena <slerena at gmail.com> - 5.0
- Several changes on SPEC to comply with Fedora standards. This one pass rpmlint

* Sat Feb 01 2014 Sancho Lerena <slerena at gmail.com> - 5.0
- First version, after re-re-re-reading the fedora contributor guidelines :)
