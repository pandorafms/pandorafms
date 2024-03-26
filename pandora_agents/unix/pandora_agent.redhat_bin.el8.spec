#
#Pandora FMS Linux Agent
#
%global __os_install_post %{nil}
%define name        pandorafms_agent_linux_bin
%define source_name pandorafms_agent_linux
%define version     7.0NG.776
%define release     240326
%define debug_package %{nil}

Summary:            Pandora FMS Linux agent, binary version
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{source_name}-%{version}.tar.gz
URL:                http://pandorafms.org
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArch:          x86_64
Requires(pre):      shadow-utils
Requires(post):     chkconfig /bin/ln
Requires(preun):    chkconfig /bin/rm /usr/sbin/userdel
Requires:           coreutils unzip
Requires:           util-linux procps grep
Requires:           /sbin/ip /bin/awk
Requires:           perl-interpreter
Requires:           perl-IO-Compress
Requires:           libnsl
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora FMS agent for unix. Pandora FMS is an OpenSource full-featured monitoring software.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n unix

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_agent/
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/etc/rc.d/init.d/
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_agent/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/tentacle_client $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_exec $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_daemon $RPM_BUILD_ROOT/etc/rc.d/init.d/pandora_agent_daemon
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/man/man1/pandora_agent.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/man/man1/tentacle_client.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/

cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/Linux/pandora_agent.conf $RPM_BUILD_ROOT/usr/share/pandora_agent/pandora_agent.conf.rpmnew

install -m 0644 pandora_agent_logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/pandora_agent

if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec ] ; then
	rm $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec
fi

%clean
rm -Rf $RPM_BUILD_ROOT

%pre
getent passwd pandora >/dev/null || \
	/usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
exit 0
chown pandora:root /var/log/pandora

%post
if [ ! -d /etc/pandora ] ; then
	mkdir -p /etc/pandora
fi

if [ ! -f /usr/share/pandora_agent/pandora_agent.conf ] ; then
	cp /usr/share/pandora_agent/pandora_agent.conf.rpmnew /usr/share/pandora_agent/pandora_agent.conf
fi

if [ ! -f /etc/pandora/pandora_agent.conf ] ; then
	ln -s /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf
else
	[[ ! -f /etc/pandora/pandora_agent.conf.rpmnew ]] && ln -s /usr/share/pandora_agent/pandora_agent.conf.rpmnew /etc/pandora/pandora_agent.conf.rpmnew
fi

if [ ! -e /etc/pandora/plugins ]; then
	ln -s /usr/share/pandora_agent/plugins /etc/pandora
fi

if [ ! -e /etc/pandora/collections ]; then
	mkdir -p /usr/share/pandora_agent/collections
	ln -s /usr/share/pandora_agent/collections /etc/pandora
fi

if [ ! -e /etc/pandora/commands ]; then
	mkdir -p /usr/share/pandora_agent/commands
	ln -s /usr/share/pandora_agent/commands /etc/pandora
fi

mkdir -p /var/spool/pandora/data_out
if [ ! -d /var/log/pandora ]; then
	mkdir -p /var/log/pandora
fi

if [ `command -v systemctl` ];
then
    echo "Copying new version of pandora_agent_daemon service"
    cp -f /usr/share/pandora_agent/pandora_agent_daemon.service /usr/lib/systemd/system/
	chmod -x /usr/lib/systemd/system/pandora_agent_daemon.service
# Enable the services on SystemD
    systemctl enable pandora_agent_daemon.service
else
	/sbin/chkconfig --add pandora_agent_daemon
	/sbin/chkconfig pandora_agent_daemon on
fi

if [ "$1" -gt 1 ]
then

      echo "If Pandora Agent daemon was running with init.d script,"
      echo "please stop it manually and start the service with systemctl"

fi


%preun

# Upgrading
if [ "$1" = "1" ]; then
	exit 0
fi

/sbin/chkconfig --del pandora_agent_daemon 
/etc/rc.d/init.d/pandora_agent_daemon stop >/dev/null 2>&1 || :

# Remove symbolic links
pushd /etc/pandora
for f in pandora_agent.conf plugins collections
do
	[ -L $f ] && rm -f $f
done
exit 0

%files
%defattr(750,root,root)
/usr/bin/pandora_agent

%defattr(755,pandora,root)
%{prefix}/pandora_agent

%defattr(755,root,root)
/usr/bin/pandora_agent_exec
/usr/bin/tentacle_client
/etc/rc.d/init.d/pandora_agent_daemon

%defattr(644,root,root)
/usr/share/man/man1/pandora_agent.1.gz
/usr/share/man/man1/tentacle_client.1.gz
%config(noreplace) %{_sysconfdir}/logrotate.d/pandora_agent
