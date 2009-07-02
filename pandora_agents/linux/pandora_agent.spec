#Pandora Linux Agent
#
%define name        PandoraFMS_Agent
%define version	    3.0.0
Summary:            Pandora Agents
Name:               %{name}
Version:            %{version}
Release:            1
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
mv $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.conf $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.conf.rpmnew
if [ -f $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/pandora_agent/pandora_agent.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT

%pre
%if "%{_vendor}" == "redhat"
   /usr/sbin/useradd -d %{prefix}/pandora -s /sbin/nologin -M -r pandora 2>/dev/null
%endif
exit 0

%post
mkdir -p /etc/pandora
if [ ! -f /usr/share/pandora_agent/pandora_agent.conf ] ; then
   mv /usr/share/pandora_agent/pandora_agent.conf.rpmnew /usr/share/pandora_agent/pandora_agent.conf
   ln -s /usr/share/pandora_agent/pandora_agent.conf /etc/pandora/pandora_agent.conf
else
   echo "Pandora Agent configuration file installed as /usr/share/pandora_agent/pandora_agent.conf.rpmnew"
fi
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

%preun
if [ "$1" = 0 ]; then
   # stop pandora_agent silently, but only if it's running
   %if "%{_vendor}" == "redhat"
      /usr/sbin/service pandora_agent_daemon stop &>/dev/null
      /sbin/chkconfig --del pandora_agent_daemon
      /usr/sbin/userdel pandora
   %endif
fi
exit 0

%files
%defattr(700,pandora,pandora)
%if "%{_vendor}" == "redhat"
/usr/bin/pandora_agent
%else
/usr/bin/pandora_agent
%endif
%defattr(700,pandora,pandora)
/var/log/pandora/
/var/spool/pandora/
%defattr(755,pandora,pandora)
/usr/bin/tentacle_client
/etc/init.d/pandora_agent_daemon
%docdir %{prefix}/pandora_agents/docs
%{prefix}/pandora_agent
#%{_mandir}/man1/pandora.1.gz
#%{_mandir}/man1/pandora_agents.1.gz


