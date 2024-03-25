#
#Pandora FMS Linux Agent
#
%global __os_install_post %{nil}
%define name        pandorafms_agent_linux
%define version     7.0NG.776
%define release     240325

Summary:            Pandora FMS Linux agent, PERL version
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://pandorafms.org
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArch:          noarch
#PreReq:             %fillup_prereq %insserv_prereq /usr/bin/sed /usr/bin/grep /usr/sbin/useradd
Requires(pre,preun):/usr/bin/sed /usr/bin/grep /usr/sbin/useradd
Requires:           coreutils unzip perl perl(Sys::Syslog) perl(IO::Compress::Zip)
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
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_agent/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/tentacle_client $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_exec $RPM_BUILD_ROOT/usr/bin/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_daemon $RPM_BUILD_ROOT/etc/init.d/pandora_agent_daemon
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_daemon $RPM_BUILD_ROOT/etc/init.d/pandora_agent_daemon
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/man/man1/pandora_agent.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/man/man1/tentacle_client.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/

cp -aRf $RPM_BUILD_ROOT%{prefix}/pandora_agent/Linux/pandora_agent.conf $RPM_BUILD_ROOT/usr/share/pandora_agent/pandora_agent.conf.rpmnew

#if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec ] ; then
#	rm $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec
#fi

%clean
rm -Rf $RPM_BUILD_ROOT

%pre
if [ "`id pandora 2>/dev/null | grep uid | wc -l`" = 0 ]
then
		echo "User pandora does not exist. Creating it..."
        /usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
fi

%post
mkdir -p /var/log/pandora
chown pandora:root /var/log/pandora
if [ ! -d /etc/pandora ] ; then
	mkdir -p /etc/pandora
fi

if [ ! -f /usr/share/pandora_agent/pandora_agent.conf ] ; then
	cp /usr/share/pandora_agent/pandora_agent.conf.rpmnew /usr/share/pandora_agent/pandora_agent.conf
fi

if [ ! -f /etc/pandora/pandora_agent.conf ] ; then
	ln -s /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf
else
	ln -s /usr/share/pandora_agent/pandora_agent.conf.rpmnew /etc/pandora/pandora_agent.conf.rpmnew
fi

if [ ! -e /etc/pandora/plugins ]; then
	ln -s /usr/share/pandora_agent/plugins /etc/pandora
fi

if [ ! -e /etc/pandora/collections ]; then
	mkdir /etc/pandora/collections
fi

if [ ! -e /etc/pandora/ref ]; then
	mkdir /etc/pandora/ref
fi

if [ ! -e /etc/pandora/commands ]; then
	mkdir /etc/pandora/commands
fi

cp -aRf /usr/share/pandora_agent/pandora_agent_logrotate /etc/logrotate.d/pandora_agent

mkdir -p /var/spool/pandora/data_out

if [ `command -v systemctl` ];
then
    echo "Copying new version of pandora_agent_daemon service"
    cp -f /usr/share/pandora_agent/pandora_agent_daemon.service /usr/lib/systemd/system/
	chmod -x /usr/lib/systemd/system/pandora_agent_daemon.service
    # Enable the services on SystemD
    systemctl enable pandora_agent_daemon.service || chkconfig pandora_agent_daemon on
else 
    chkconfig pandora_agent_daemon on
fi

if [ "$?" -gt 0 ]
then
    echo "There was a problem configuring pandora_agent_daemon service to run on boot. Please enable it manually."
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

chkconfig --del pandora_agent_daemon 
/etc/init.d/pandora_agent_daemon stop
rm /etc/init.d/pandora_agent_daemon
/usr/sbin/userdel pandora
rm -Rf /etc/pandora/pandora_agent.conf
rm -Rf /var/log/pandora/pandora_agent* 2> /dev/null
rm -Rf /usr/share/pandora_agent
rm -Rf /usr/share/man/man1/pandora_agent.1.gz
rm -Rf /usr/share/man/man1/tentacle_client.1.gz
exit 0

%postun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

rm -Rf /etc/logrotate.d/pandora_agent

%files
%defattr(750,pandora,root)
/usr/bin/pandora_agent
/usr/bin/pandora_agent_exec

%defattr(755,pandora,root)
/usr/bin/tentacle_client
/etc/init.d/pandora_agent_daemon
%docdir %{prefix}/pandora_agents/docs
%{prefix}/pandora_agent

%defattr(644,pandora,root)
/usr/share/man/man1/pandora_agent.1.gz
/usr/share/man/man1/tentacle_client.1.gz

