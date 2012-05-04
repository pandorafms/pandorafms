Name:           anytermd
Version:        1.1.29
Release:        1.1.29
Summary:        Pandora FMS Remote connection gateway
License:        GPLv2
Group:          Applications/Communications
Vendor:         ArticaST <http://www.artica.es>
Source0:        %{name}-%{version}.tar.gz
Packager:       Sancho Lerena <slerena@artica.es>
BuildRoot:      %{_tmppath}/%{name}-%{version}-buildroot
URL:            http://pandorafms.org
Requires(post): /sbin/chkconfig
Requires(preun): /sbin/chkconfig
Requires(preun): /sbin/service
Requires(postun): /sbin/service
Requires:	telnet openssh
%description
Pandora FMS uses a tool called "anytermd" to create a "proxy" between user browser and remote destination. This tool launches as a daemon, listeting in a port, and executing a command, forwarding all output to the user browser. That means all the connections are done FROM the pandora server and it has to be installed the telnet and ssh client

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n anytermd

%build
make 

%install
install -Dm 755 contrib/anytermd $RPM_BUILD_ROOT%{_initrddir}/anytermd
install -Dm 755 anytermd $RPM_BUILD_ROOT%{_bindir}/anytermd

%clean
rm -rf $RPM_BUILD_ROOT

%post
if [ $1 -eq 0 ]; then
        /sbin/chkconfig --add anytermd
fi

%preun
if [ $1 -eq 0 ]; then
        /sbin/chkconfig --del anytermd
fi

%postun
if [ $1 -ge 1 ]; then
        /sbin/service anytermd stop >/dev/null 2>&1
fi

%files
%defattr(-,root,root,-)
%{_bindir}/*
%{_initrddir}/anytermd


