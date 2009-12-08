Name:   	xprobe2
Version:	0.3
Release:	1
Summary:	Tool to detect OS remotely by TCP/IP fingerprinting.
License:	GPLv2
Group:          	Network/Security
URL:            	http://xprobe.sourceforge.net/
Packager:	Sancho Lerena <slerena@gmail.com>
Source:		http://dl.sf.net/xprobe/xprobe2-%{version}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}
Provides:	xprobe2
Requires:	libpcap
BuildRequires: 	gcc-c++ libpcap-devel

%description
Xprobe is an alternative to some tools which are heavily dependent upon the
usage of the TCP protocol for remote active operating system fingerprinting.

Xprobe I combines various remote active operating system fingerprinting methods
using the ICMP protocol, which were discovered during the "ICMP Usage in
Scanning" research project, into a simple, fast, efficient and a powerful way
to detect an underlying operating system a targeted host is using.

Xprobe2 is an active operating system fingerprinting tool with a different
approach to operating system fingerprinting. Xprobe2 rely on fuzzy signature
matching, probabilistic guesses, multiple matches simultaneously, and a
signature database. 

%prep
%setup 

%build
./configure --with-libpcap-libraries=/usr/lib --with-libpcap-includes=/usr/include/pcap --mandir=%{_mandir} --disable-schemas-install
make 

%install
make DESTDIR=$RPM_BUILD_ROOT install

%clean
rm -rf $RPM_BUILD_ROOT

%post
ln -s /usr/local/bin/xprobe2 /usr/bin

%preun

%postun

%files
%defattr(-,root,root,0755)
%doc AUTHORS CHANGELOG COPYING CREDITS README TODO docs/*
%doc /usr/share/man/man1/xprobe2.1.gz
/usr/local/bin/xprobe2
/usr/local/etc/xprobe2
/usr/local/etc/xprobe2/xprobe2.conf



%changelog
* Tue Dec 08 2009 Sancho Lerena <slerena@gmail.com> 3.2-1
- First RPM Spec for SUSE Systems, based on CentOS Spec from Dag Wieers

