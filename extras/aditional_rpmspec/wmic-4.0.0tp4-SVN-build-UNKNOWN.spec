#WMIC
#
%define name        wmic
%define version	    4.0.0SVN
Summary:            Linux binary to do WMI querys
Name:               %{name}
Version:            %{version}
Release:            0
License:            Other License(s), see package
Group:		    System/Management
Source0:            %{name}-%{version}.tar.bz2
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
AutoReq:            1
Provides:           %{name}-%{version}

%description
Linux binary to do WMI querys

%prep
rm -rf $RPM_BUILD_ROOT

%setup -c

%install 
mkdir -p $RPM_BUILD_ROOT/usr/bin/
cp -p wmic $RPM_BUILD_ROOT/usr/bin/

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
/usr/bin/wmic
