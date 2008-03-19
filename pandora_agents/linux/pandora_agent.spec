#
# Pandora Agents
#
%define name        pandora_agents
%define version	    1.3.1
Summary:            Pandora Agents
Name:               %{name}
Version:            %{version}
Release:            Beta1
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://pandora.sf.net
Group:              System/Monitoring
Packager:           Manuel Arostegui <marostegui@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArchitectures: noarch
Requires:	    coreutils
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora agents are based on native languages in every platform: scripts that can be written in any language. It’s possible to reproduce any agent in any programming language and can be extended without difﬁculty the existing ones in order to cover aspects not taken into account up to the moment.
These scripts are formed by modules that each one gathers a "chunk" of information. Thus, every agent gathers several "chunks" of information; this one is organized in a data set and stored in a single ﬁle, called data ﬁle.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n linux

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_agent/
mkdir -p $RPM_BUILD_ROOT/usr/
mkdir -p $RPM_BUILD_ROOT/usr/share/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_agent
mkdir -p $RPM_BUILD_ROOT/usr/
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/etc/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/etc/init.d
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_out
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
cp -aRf * $RPM_BUILD_ROOT%{prefix}/pandora_agent/
mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent_daemon $RPM_BUILD_ROOT/etc/init.d/pandora_agent_daemon
#cp pandora.1 $RPM_BUILD_ROOT/usr/share/man/man1/
#cp pandora_agents.1 $RPM_BUILD_ROOT/usr/share/man/man1/
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT
%post
echo "Pandora Agent has been placed under /usr/share/"
echo "Pandora Agent configuration file is /etc/pandora/pandora_agent.conf"
echo "Pandora Agent Daemon has been placed in /etc/init.d/pandora_agent_daemon"
mkdir -p /etc/pandora
ln -s /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf
%if "%{_vendor}" == "suse"
ln -s /etc/init.d/pandora_agent_daemon /etc/rc.d/rc3.d/S99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc.d/rc2.d/S99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc.d/rc6.d/K99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc.d/rc0.d/K99pandora_agent_daemon
%else
ln -s /etc/init.d/pandora_agent_daemon /etc/rc0.d/K99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc6.d/K99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc3.d/S99pandora_agent_daemon
ln -s /etc/init.d/pandora_agent_daemon /etc/rc5.d/S99pandora_agent_daemon
%endif
%files
%defattr(700,pandora,pandora)
/usr/bin/pandora_agent
%defattr(600,pandora,pandora)
/var/log/pandora/
/var/spool/pandora/
%defattr(755,pandora,pandora)
/etc/init.d/pandora_agent_daemon
%docdir %{prefix}/pandora_agents/docs
%{prefix}/pandora_agent
#%{_mandir}/man1/pandora.1.gz
#%{_mandir}/man1/pandora_agents.1.gz
