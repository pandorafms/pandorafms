Name:           smstools
Version:        3.1.3
Release:        4.1
Summary:        Tools to send and receive short messages through GSM modems or mobile phones

License:        GPLv2+
Group:          Applications/Communications
URL:            http://smstools3.kekekasvi.com
Source0:        http://smstools3.kekekasvi.com/packages/smstools3-%{version}.tar.gz
Source1 :       smsd.init
Source2:        smsd.logrotate
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Requires(post): /sbin/chkconfig
Requires(preun): /sbin/chkconfig
Requires(preun): /sbin/service
Requires(postun): /sbin/service

%description
The SMS Server Tools are made to send and receive short messages through
GSM modems. It supports easy file interfaces and it can run external
programs for automatic actions. 

%prep
%setup -q -n smstools3
mv doc manual
mv examples/.procmailrc examples/procmailrc
mv examples/.qmailrc examples/qmailrc
find scripts/ examples/ manual/ -type f -print0 |xargs -0 chmod 644

%build
make -C src 'CFLAGS=%{optflags} -DNOSTATS' %{_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
install -Dm 755 %{SOURCE1} $RPM_BUILD_ROOT%{_initrddir}/smsd
install -Dm 664 %{SOURCE2} $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/smstools
install -Dm 600 examples/smsd.conf.easy $RPM_BUILD_ROOT%{_sysconfdir}/smsd.conf
install -Dm 755 src/smsd $RPM_BUILD_ROOT%{_sbindir}/smsd
install -Dm 755 scripts/sendsms $RPM_BUILD_ROOT%{_bindir}/smssend
install -Dm 755 scripts/sms2html $RPM_BUILD_ROOT%{_bindir}/sms2html
install -Dm 755 scripts/sms2unicode $RPM_BUILD_ROOT%{_bindir}/sms2unicode
install -Dm 755 scripts/sms2xml $RPM_BUILD_ROOT%{_bindir}/sms2xml
install -Dm 755 scripts/unicode2sms $RPM_BUILD_ROOT%{_bindir}/unicode2sms
install -dm 750 $RPM_BUILD_ROOT%{_localstatedir}/spool/sms/checked
install -dm 750 $RPM_BUILD_ROOT%{_localstatedir}/spool/sms/failed
install -dm 750 $RPM_BUILD_ROOT%{_localstatedir}/spool/sms/incoming
install -dm 750 $RPM_BUILD_ROOT%{_localstatedir}/spool/sms/outgoing
install -dm 750 $RPM_BUILD_ROOT%{_localstatedir}/spool/sms/sent

%clean
rm -rf $RPM_BUILD_ROOT

%post
if [ $1 -eq 0 ]; then
        /sbin/chkconfig --add smsd
fi

%preun
if [ $1 -eq 0 ]; then
        /sbin/service smsd stop >/dev/null 2>&1
        /sbin/chkconfig --del smsd
fi

%postun
if [ $1 -ge 1 ]; then
        /sbin/service smsd condrestart >/dev/null 2>&1
fi

%files
%defattr(-,root,root,-)
%doc LICENSE manual/ examples/ scripts/checkhandler-utf-8 scripts/email2sms scripts/eventhandler-utf-8
%doc scripts/mysmsd scripts/regular_run scripts/smsevent scripts/smsresend scripts/sql_demo
%{_sbindir}/*
%{_bindir}/*
%{_initrddir}/smsd
%config(noreplace) %{_sysconfdir}/logrotate.d/smstools
%config(noreplace) %{_sysconfdir}/smsd.conf
%dir %{_localstatedir}/spool/sms/
%dir %{_localstatedir}/spool/sms/checked
%dir %{_localstatedir}/spool/sms/failed
%dir %{_localstatedir}/spool/sms/incoming
%dir %{_localstatedir}/spool/sms/outgoing
%dir %{_localstatedir}/spool/sms/sent


%changelog
* Sat Nov 10 2007 Marek Mahut <mmahut@fedoraproject.org> 3.0.10-1
- Rewrite of spec file.
- Updated to version 3.0.10

* Sat Apr 07 2007 Andreas Thienemann <andreas@bawue.net> 3.0.6-1
- Updated to version 3.0.6
- Reverted daemonize patch as it is not needed anymore

* Wed Nov 30 2005 Andreas Thienemann <andreas@bawue.net> 1.15.7-3
- Fixed logrotate script

* Sun Sep 13 2005 Andreas Thienemann <andreas@bawue.net> 1.15.7-2
- Now with statistics support

* Sat Sep 12 2005 Andreas Thienemann <andreas@bawue.net> 1.15.7-1
- Initial spec.

