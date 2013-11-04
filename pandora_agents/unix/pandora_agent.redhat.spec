#
#Pandora FMS Linux Agent
#
%define name        pandorafms_agent_unix
%define version     4.1
%define release     131104

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
PreReq:             /bin/sed /bin/grep /usr/sbin/useradd
Requires:           coreutils unzip
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

if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec ] ; then
	rm $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec
fi

%clean
rm -Rf $RPM_BUILD_ROOT

%pre
getent passwd pandora >/dev/null || \
	/usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
exit 0

%post
if [ ! -d /etc/pandora ] ; then
	mkdir -p /etc/pandora
fi

# Checking old config file (if exists)
if [ -e /etc/pandora/pandora_agent.conf ] ; then
	echo "Current configuration file exist."
else
	cp /usr/share/pandora_agent/pandora_agent.conf.rpmnew /etc/pandora/pandora_agent.conf
fi

if [ ! -e /etc/pandora/plugins ]; then
	ln -s /usr/share/pandora_agent/plugins /etc/pandora
fi

if [ ! -e /etc/pandora/collections ]; then
	ln -s /usr/share/pandora_agent/collections /etc/pandora
fi

mkdir -p /var/spool/pandora/data_out
/sbin/chkconfig --add pandora_agent_daemon
/sbin/chkconfig pandora_agent_daemon on

%preun

# Upgrading
if [ "$1" = "1" ]; then
	exit 0
fi

/sbin/chkconfig --del pandora_agent_daemon 
/etc/init.d/pandora_agent_daemon stop
rm /etc/init.d/pandora_agent_daemon
/usr/sbin/userdel pandora
rm -Rf /etc/pandora/pandora_agent.conf
rm -Rf /var/log/pandora/pandora_agent* 2> /dev/null
rm -Rf /usr/share/pandora_agent
rm -Rf /usr/share/man/man1/pandora_agent.1.gz
rm -Rf /usr/share/man/man1/tentacle_client.1.gz
exit 0

%files
%defattr(750,pandora,root)
/usr/bin/pandora_agent
/usr/bin/pandora_agent_exec

%defattr(770,pandora,root)
/var/log/pandora/

%defattr(755,pandora,root)
/usr/bin/tentacle_client
/etc/init.d/pandora_agent_daemon
%docdir %{prefix}/pandora_agents/docs
%{prefix}/pandora_agent

%defattr(644,pandora,root)
/usr/share/man/man1/pandora_agent.1.gz
/usr/share/man/man1/tentacle_client.1.gz

