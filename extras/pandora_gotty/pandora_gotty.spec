%define name        pandora_gotty
%define version     1.1
%define release     1%{?dist}
Summary:            pandora_gptty for Pandora FMS
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             PandoraFMS
Source0:            %{name}-%{version}.tar.gz
URL:                https://pandorafms.com
Group:              System/Monitoring
Packager:           PandoraFMS
BuildArch:          x86_64
Provides:           %{name}-%{version}

%description
pandora_gotty for Pandora FMS.

%prep
%setup -q

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/%{_bindir}
mkdir -p %{buildroot}/etc/pandora_gotty/
cp %{name} $RPM_BUILD_ROOT/%{_bindir}
cp pandora_gotty_exec $RPM_BUILD_ROOT/%{_bindir}
cp pandora_gotty.conf %{buildroot}/etc/pandora_gotty/
%clean
rm -Rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%config(noreplace) /etc/pandora_gotty/pandora_gotty.conf
%{_bindir}/%{name}
%{_bindir}/pandora_gotty_exec

%changelog
* Mon Sep 18 2023  PandoraFMS  - 1.0-1
- Initial RPM release
