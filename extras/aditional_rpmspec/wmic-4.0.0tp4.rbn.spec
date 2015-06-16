%define name		wmic
%define version		4.0.0tp4.rbn
Summary:		Linux WMI client to do WMI querys using DCOM http://samba.org
Name:			%{name}
Version:		%{version}
Release:		1
License:		GPL2
Group:			System/Management
Packager:		Robert B. Nelson <robertn@the-nelsons.org>
Source:			http://www.openvas.org/download/wmi/wmi-1.3.14.tar.bz2
Patch1:			http://www.openvas.org/download/wmi/openvas-wmi-1.3.14.patch
Patch2:			http://www.openvas.org/download/wmi/openvas-wmi-1.3.14.patch2
Patch3:			http://www.openvas.org/download/wmi/openvas-wmi-1.3.14.patch3v2
Patch4:			http://www.openvas.org/download/wmi/openvas-wmi-1.3.14.patch4
Patch5:			http://www.openvas.org/download/wmi/openvas-wmi-1.3.14.patch5
BuildRoot:		%{_tmppath}/%{name}-%{version}-build
AutoReq:		1
Provides:		%{name}-%{version}

%description
Linux WMI client to do WMI querys. More information at SAMBA4 project at http://www.samba.org/

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n wmi-1.3.14

%patch1 -p1
%patch2 -p1
%patch3 -p1
%patch4 -p1
%patch5 -p1

%build
cd Samba/source
./autogen.sh
./configure --without-readline --enable-debug
make "CPP=gcc -E -ffreestanding" proto bin/wmic

%install 
mkdir -p $RPM_BUILD_ROOT/usr/bin/
install Samba/source/bin/wmic $RPM_BUILD_ROOT%{_bindir}

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
%{_bindir}/wmic
