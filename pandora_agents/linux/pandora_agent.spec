#
#Pandora FMS Linux Agent
#
%define name        pandorafms_agent
%define version	    3.0.0
Summary:            Pandora FMS Linux agent
Name:               %{name}
Version:            %{version}
Release:            1
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://pandorafms.org
Group:              System/Monitoring
Packager:           Manuel Arostegui <manuel@todo-linux.com>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArchitectures: noarch
Requires:           coreutils
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora FMS agents are based on native languages in every platform: scripts that can be written in any language. It’s possible to reproduce any agent in any programming language and can be extended without difﬁculty the existing ones in order to cover aspects not taken into account up to the moment.
These scripts are formed by modules that each one gathers a "chunk" of information. Thus, every agent gathers several "chunks" of information; this one is organized in a data set and stored in a single ﬁle, called data ﬁle.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n linux

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_agent/
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_out
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_agent/
cp -aRf  $RPM_BUILD_ROOT%{prefix}/pandora_agent/tentacle_client $RPM_BUILD_ROOT/usr/bin/
%if "%{_vendor}" == "redhat"
   mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent $RPM_BUILD_ROOT/usr/bin/
%else
   mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent $RPM_BUILD_ROOT/usr/bin/
%endif
mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_daemon $RPM_BUILD_ROOT/etc/init.d/pandora_agent_daemon

# Checking old config file (if exists)
if [ -f /etc/pandora/pandora_agent.conf ] ; then
	cp /etc/pandora/pandora_agent.conf /etc/pandora/pandora_agent.conf.backup
fi

cp $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.conf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.conf.rpmnew
if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec
fi

%clean
rm -Rf $RPM_BUILD_ROOT

%pre
/usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
exit 0



%post
if [ ! -d /etc/pandora ] ; then
	mkdir -p /etc/pandora
fi

if [ ! -f /usr/share/pandora_agent/pandora_agent.conf ] ; then
    cp /usr/share/pandora_agent/pandora_agent.conf.rpmnew /usr/share/pandora_agent/pandora_agent.conf
else
	cp /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf.backup
   	cp /usr/share/pandora_agent/pandora_agent.conf.rpmnew /usr/share/pandora_agent/pandora_agent.conf
fi

if [ -f /etc/pandora/pandora_agent.conf ] ; then
	rm -Rf /etc/pandora/pandora_agent.conf
fi

if [ ! -e /etc/pandora/plugins ]; then
	ln -s /usr/share/pandora_agent/plugins /etc/pandora
fi

if [ ! -e /etc/pandora/pandora_agent.conf ]; then
	ln -s /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf 
fi

chkconfig -s pandora_agent_daemon on

%preun

chkconfig -d pandora_agent_daemon 
/etc/init.d/pandora_agent_daemon stop
rm /etc/init.d/pandora_agent_daemon
/usr/sbin/userdel pandora
rm -Rf /etc/pandora/pandora_agent.conf
rm -Rf /var/log/pandora/pandora_agent* 2> /dev/null
exit 0

%files
%defattr(750,pandora,root)
/usr/bin/pandora_agent

%defattr(770,pandora,root)
/var/log/pandora/
/var/spool/pandora/data_out

%defattr(755,pandora,root)
/usr/bin/tentacle_client
/etc/init.d/pandora_agent_daemon
%docdir %{prefix}/pandora_agents/docs
%{prefix}/pandora_agent
#%{_mandir}/man1/pandora.1.gz
#%{_mandir}/man1/pandora_agents.1.gz


