Name:           nfdump
Version:        1.6.8p1
Release:        1
Summary:        Tool to collect and process netflow data on the command line
License:        BSD License
Group:     	Network/Tools
URL:            http://nfdump.sourceforge.net/
Packager:       Mario Pulido <mario.pulido@artica.es>
Source:         http://sourceforge.net/projects/nfdump/files/stable/nfdump-1.6.8p1/nfdump-1.6.8p1.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}
Provides:       %{name}-%{version}
BuildRequires:  flex,bison,byacc,rrdtool-devel


%description
nfdump is a set of tools to collect and process netflow data. It's fast and has
a powerful filter pcap like syntax. It supports netflow versions v1, v5, v7 and
v9 as well as a limited set of sflow and is IPv6 compatible. IPFIX is supported
in beta state.  For CISCO ASA devices, which export Netflow Security Event
Loging (NSEL) records.

%prep
%setup -q

%build
%configure --prefix=/usr --enable-nfprofile --enable-nftrack --enable-sflow

%install
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=$RPM_BUILD_ROOT

%clean
rm -Rf $RPM_BUILD_ROOT

%pre

%post


%preun

%files
%{_bindir}/*
%{_mandir}/*
%defattr(-,root,root,-)
%doc
