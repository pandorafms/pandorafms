#
# Pandora FMS Server 
#
%define name        pandora_server
%define version	    2.0.0
Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            1
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Manuel Arostegui <marostegui@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: noarch
%if "%{_vendor}" == "suse"
Requires:           perl-XML-Simple perl-DateManip perl-Net-SNMP
%else
Requires:	    perl-XML-Simple net-snmp-perl perl-DateManip
%endif
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora watchs your systems and applications, and allows to know the status of any element of that systems. Pandora could detect a network interface down, a defacementin your website, memory leak in one of your server app, or the movement of any value of the NASDAQ new technology market. If you want, Pandora could sent a SMS messagewhen your systems fails... or when Google value low below US$ 33

%prep
rm -rf $RPM_BUILD_ROOT

#Evaluate perl version:
export perl_version=`rpm -q --queryformat='%{VERSION}' perl`

%setup -q -n pandora_server

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
mkdir -p $RPM_BUILD_ROOT/usr/
mkdir -p $RPM_BUILD_ROOT/usr/share/
mkdir -p $RPM_BUILD_ROOT/usr/local/
mkdir -p $RPM_BUILD_ROOT/usr/local/bin
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/etc/
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
mkdir -p $RPM_BUILD_ROOT/usr/lib/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/Time
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr/IP
mkdir -p $RPM_BUILD_ROOT/var
mkdir -p $RPM_BUILD_ROOT/var/run/
mkdir -p $RPM_BUILD_ROOT/var/run/pandora
#mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/`rpm -q --queryformat='%{VERSION}' perl`
cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}
#mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server $RPM_BUILD_ROOT/usr/bin/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server $RPM_BUILD_ROOT/usr/bin/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network $RPM_BUILD_ROOT/usr/bin/pandora_network
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_recon $RPM_BUILD_ROOT/usr/bin/pandora_recon
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_snmpconsole $RPM_BUILD_ROOT/usr/bin/pandora_snmpconsole
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server $RPM_BUILD_ROOT/usr/local/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network $RPM_BUILD_ROOT/usr/local/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_recon $RPM_BUILD_ROOT/usr/local/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_snmpconsole $RPM_BUILD_ROOT/usr/local/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_recon $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_snmpconsole $RPM_BUILD_ROOT/usr/share/pandora_server/pandora_snmpconsole
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/Time/Format.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/Time/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/NetAddr/IP.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/NetAddr/IP/Lite.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr/IP/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/NetAddr/IP/Util_IS.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr/IP/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/NetAddr/IP/Util.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr/IP/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/NetAddr/IP/UtilPP.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/NetAddr/IP/
cp -r $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/util/ $RPM_BUILD_ROOT/usr/share/pandora_server/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}/bin/pandora_config.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}/bin/pandora_db.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}/bin/pandora_tools.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/DB.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/Tools.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/Config.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_config.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_db.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_tools.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_server $RPM_BUILD_ROOT/etc/init.d/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_network $RPM_BUILD_ROOT/etc/init.d/pandora_network
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_recon $RPM_BUILD_ROOT/etc/init.d/pandora_recon
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_recon $RPM_BUILD_ROOT/etc/init.d/pandora_snmpconsole
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/pandora_server $RPM_BUILD_ROOT/etc/init.d/pandora_server
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/pandora_recon $RPM_BUILD_ROOT/etc/init.d/pandora_network
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/bin/pandora_recon $RPM_BUILD_ROOT/usr/bin/pandora_recon
rm -fr $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec
fi

%clean
rm -fr $RPM_BUILD_ROOT
rm -fr $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
%post
if [ "$1" = "0" ]; then
       /usr/sbin/userdel pandora
       /usr/sbin/groupdel pandora
fi
mkdir -p /etc/pandora
ln -s /usr/share/pandora_server/conf/pandora_server.conf /etc/pandora/
%if "%{_vendor}" == "suse"
ln -s /etc/init.d/pandora_server /etc/rc.d/rc3.d/S99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc.d/rc3.d/S99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc.d/rc3.d/S99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc.d/rc3.d/S99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc.d/rc2.d/S99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc.d/rc2.d/S99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc.d/rc2.d/S99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc.d/rc2.d/S99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc.d/rc0.d/K99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc.d/rc0.d/K99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc.d/rc0.d/K99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc.d/rc0.d/K99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc.d/rc6.d/K99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc.d/rc6.d/K99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc.d/rc6.d/K99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc.d/rc6.d/K99pandora_snmpconsole
%else
ln -s /etc/init.d/pandora_server /etc/rc3.d/S99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc3.d/S99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc3.d/S99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc3.d/S99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc2.d/S99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc2.d/S99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc2.d/S99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc2.d/S99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc0.d/K99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc0.d/K99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc0.d/K99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc0.d/K99pandora_snmpconsole
ln -s /etc/init.d/pandora_server /etc/rc6.d/K99pandora_server
ln -s /etc/init.d/pandora_network /etc/rc6.d/K99pandora_network
ln -s /etc/init.d/pandora_recon /etc/rc6.d/K99pandora_recon
ln -s /etc/init.d/pandora_snmpconsole /etc/rc6.d/K99pandora_snmpconsole
%endif
mkdir -p /usr/share/pandora_server/
rm -fr /usr/share/pandora_server-1.3.1-1/
echo "Pandora Server configuration is /etc/pandora/pandora_server.conf"
echo "Pandora Server data has been placed under /var/spool/pandora/data_in/"
echo "Pandora Server logs has benn placed under /var/log/"
echo "Pandora Server main directory is /usr/share/pandora_server/"
echo "To start Pandora Server: /etc/init.d/pandora_server start"
echo "To start Pandora Network Server: /etc/init.d/pandora_network start"
echo "To start Pandora Recon Server: /etc/init.d/pandora_recon start"
echo "The manual can be reached at: man pandora or man pandora_server"
echo "Pandora Documentation is in: http://openideas.info/wiki/index.php?title=Pandora_2.0:Documentation"
%files
%defattr(700,pandora,pandora)
/usr/bin/pandora_server
/usr/bin/pandora_network
/usr/bin/pandora_recon
/usr/bin/pandora_snmpconsole
/var/spool/pandora/
/etc/init.d/pandora_recon
/etc/init.d/pandora_server
/etc/init.d/pandora_network
/etc/init.d/pandora_snmpconsole
%defattr(755,pandora,pandora)

#/etc/pandora/conf/pandora_server.conf
/usr/share/pandora_server/util/pandora_db
/usr/share/pandora_server/util/pandora_dbstress.pl
/usr/share/pandora_server/bin/pandora_network
/usr/share/pandora_server/bin/pandora_recon
/usr/share/pandora_server/bin/pandora_server
#/usr/share/pandora_server/util/pandora_config.pm
#/usr/share/pandora_server/util/pandora_db.pm
#/usr/share/pandora_server/util/pandora_tools.pm
#/usr/share/pandora_server/util/pandora_db.pl
#/usr/share/pandora_server/util/pandora_dbstress.pl
#/usr/share/pandora_server/util/snmptrapd
#/usr/lib/perl5/site_perl/5.8.5/pandora_config.pm
#/usr/lib/perl5/site_perl/5.8.5/pandora_db.pm
#/usr/lib/perl5/site_perl/5.8.5/pandora_tools.pm
#/usr/share/pandora_server/bin/PandoraFMS/Config.pm
#/usr/share/pandora_server/bin/PandoraFMS/DB.pm
#/usr/share/pandora_server/bin/PandoraFMS/PingExternal.pm
#/usr/share/pandora_server/bin/PandoraFMS/Tools.pm
/usr/lib/perl5/site_perl/PandoraFMS/DB.pm
/usr/lib/perl5/site_perl/PandoraFMS/Tools.pm
/usr/lib/perl5/site_perl/PandoraFMS/Config.pm
/usr/lib/perl5/site_perl/Time/Format.pm
/usr/lib/perl5/site_perl/NetAddr/IP.pm
/usr/lib/perl5/site_perl/NetAddr/IP/Lite.pm
/usr/lib/perl5/site_perl/NetAddr/IP/Util_IS.pm
/usr/lib/perl5/site_perl/NetAddr/IP/Util.pm
/usr/lib/perl5/site_perl/NetAddr/IP/UtilPP.pm
/usr/share/pandora_server/conf/pandora_server.conf
/usr/share/pandora_server/util/pandora_DBI_test.pl
#/usr/share/pandora_server/util/pandora_SNMP_test.pl
/usr/share/pandora_server/util/pandora_checkdep.pl
/usr/share/pandora_server/util/pandora_dbstress.README
/usr/share/pandora_server/util/pandora_snmp.README
/usr/share/pandora_server/util/n2p.README
/usr/share/pandora_server/util/n2p.pl
/var/log/pandora
/usr/share/pandora_server/AUTHORS
/usr/share/pandora_server/COPYING
/usr/share/pandora_server/ChangeLog
/usr/share/pandora_server/NetAddr/IP.pm
/usr/share/pandora_server/NetAddr/IP/Lite.pm
/usr/share/pandora_server/NetAddr/IP/Util.pm
/usr/share/pandora_server/NetAddr/IP/UtilPP.pm
/usr/share/pandora_server/NetAddr/IP/Util_IS.pm
/usr/share/pandora_server/README
/usr/share/pandora_server/Time/Format.pm
/usr/share/pandora_server/bin/pandora_snmpconsole
/usr/share/pandora_server/lib/PandoraFMS/Config.pm
/usr/share/pandora_server/lib/PandoraFMS/DB.pm
/usr/share/pandora_server/lib/PandoraFMS/Tools.pm
/usr/share/pandora_server/pandora_network
/usr/share/pandora_server/pandora_recon
/usr/share/pandora_server/pandora_server
/usr/share/pandora_server/pandora_server_installer
/usr/share/pandora_server/pandora_snmpconsole
/usr/share/pandora_server/specs/fedoracore5/pandora_server.spec
/usr/share/pandora_server/specs/fedoracore5/perl-Pandora-1.2-beta3.spec
/usr/share/pandora_server/specs/fedoracore5/perl-Pandora-1.2.spec
/usr/share/pandora_server/specs/rhel/perl-Pandora-1.2-beta3.spec
/usr/share/pandora_server/specs/rhel/perl-Pandora-1.2.spec
/usr/local/bin/pandora_server
/usr/local/bin/pandora_network
/usr/local/bin/pandora_recon
/usr/local/bin/pandora_snmpconsole
/usr/share/pandora_server/bin/PandoraFMS
/usr/share/pandora_server/util/PandoraFMS

#%docdir %{prefix}/%{name}-%{version}-%{release}/docs
#%{prefix}/%{name}-%{version}-%{release}
#%{_mandir}/man1/pandora.1.gz
