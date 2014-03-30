#
# Pandora FMS Server 
#
%define name        pandorafms_server
%define version     4.1.1
%define release     140330

Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArch:          noarch 
PreReq:             %fillup_prereq %insserv_prereq /usr/bin/sed /usr/bin/grep /usr/sbin/useradd
BuildRequires:      sysvinit cron rsyslog sysconfig
AutoReq:            0
Provides:           %{name}-%{version}
Requires:           perl-DBI perl-DBD-mysql perl-libwww-perl
Requires:           perl-NetAddr-IP net-snmp net-tools perl-XML-Twig
Requires:           nmap wmic sudo perl-HTML-Tree perl-XML-Simple
Requires:           perl-IO-Socket-INET6 perl-Socket6 snmp-mibs perl-JSON

%description

Pandora FMS is a monitoring Open Source software. It watches your systems and applications, and allows you to know the status of any element of those systems. 

Pandora FMS could detect a network interface down, a defacement in your website, a memory leak in one of your server application, or the movement of any value of the NASDAQ new technology market. Pandora FMS could send out SMS message when your systems fails... or when Google's value drop below US348.60? 

Pandora FMS runs on any operating system, with specific agents for each platform, gathering data and sending it to a server, it has specific agents for GNU/Linux, AIX, Solaris, HP-UX, BSD/IPSO, and Windows 2000, XP and 2003. 

Pandora FMS can also monitor any kind of TCP/IP service, without the need to install agents, and monitor network systems such as load balancers, routers, switches, operating systems, applications, or simply printers if you need. Pandora FMS also supports SNMP for collecting data and for receiving traps. 

A few examples of common resources monitored by Pandora FMS could be processor load, disk and memory usage, running processes, log files, environmental factors such as temperature, or application values like strings contained in web pages or any possible way to collect data in an automatic way.


%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server

%build

%install

rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/conf
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/md5
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/collections
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1/

# All binaries go to /usr/bin
cp -aRf bin/pandora_server $RPM_BUILD_ROOT/usr/bin/
cp -aRf bin/pandora_exec $RPM_BUILD_ROOT/usr/bin/
cp -aRf bin/tentacle_server $RPM_BUILD_ROOT/usr/bin/

cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf conf/pandora_server.conf.new $RPM_BUILD_ROOT/etc/pandora/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
cp -aRf lib/* $RPM_BUILD_ROOT/usr/lib/perl5/
cp -aRf AUTHORS COPYING ChangeLog README $RPM_BUILD_ROOT%{prefix}/pandora_server/

cp -aRf util/pandora_server $RPM_BUILD_ROOT/etc/init.d/
cp -aRf util/tentacle_serverd $RPM_BUILD_ROOT/etc/init.d/

cp -aRf man/man1/pandora_server.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf man/man1/tentacle_server.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/

rm -Rf $RPM_BUILD_ROOT%{prefix}/pandora_server/util/PandoraFMS
rm -Rf $RPM_BUILD_ROOT%{prefix}/pandora_server/util/recon_scripts/PandoraFMS

%clean
rm -fr $RPM_BUILD_ROOT

%pre
if [ "`id pandora | grep uid | wc -l`" = 0 ]
then
	/usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
fi
exit 0

%post
chkconfig pandora_server on 
chkconfig tentacle_serverd on 

echo "/usr/share/pandora_server/util/pandora_db.pl /etc/pandora/pandora_server.conf" > /etc/cron.hourly/pandora_db
chmod 750 /etc/cron.hourly/pandora_db
cp -aRf /usr/share/pandora_server/util/pandora_logrotate /etc/logrotate.d/pandora

if [ ! -d /etc/pandora ] ; then
   mkdir -p /etc/pandora
fi

if [ ! -e "/etc/pandora/pandora_server.conf" ]
then
        echo "Creating a new version of Pandora FMS Server config file at /etc/pandora/pandora_server.conf"
        cat /etc/pandora/pandora_server.conf.new > /etc/pandora/pandora_server.conf
else
        # Do a copy of current .conf, just in case.
        echo "An existing version of pandora_server.conf is found."
        cat /etc/pandora/pandora_server.conf > /etc/pandora/pandora_server.conf.old
fi

echo "Don't forget to start Tentacle Server daemon if you want to receive"
echo "data using tentacle"

exit 0

%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

/etc/init.d/pandora_server stop &>/dev/null
/etc/init.d/tentacle_serverd stop &>/dev/null
chkconfig --del pandora_server
chkconfig --del tentacle_serverd

%postun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

rm -Rf /etc/init.d/tentacle_serverd
rm -Rf /etc/init.d/pandora_server
rm -Rf %{prefix}pandora_server
rm -Rf /var/log/pandora
rm -Rf /usr/lib/perl5/PandoraFMS/
rm -Rf /etc/pandora/pandora_server.conf*
rm -Rf /var/spool/pandora
rm -Rf /etc/init.d/pandora_server /etc/init.d/tentacle_serverd 
rm -Rf /usr/bin/pandora_exec /usr/bin/pandora_server /usr/bin/tentacle_server
rm -Rf /etc/cron.hourly/pandora_db
rm -Rf /etc/logrotate.d/pandora
rm -Rf /usr/share/man/man1/pandora_server.1.gz
rm -Rf /usr/share/man/man1/tentacle_server.1.gz

%files

%defattr(750,pandora,root)
/etc/init.d/pandora_server
/etc/init.d/tentacle_serverd

%defattr(755,pandora,root)
/usr/bin/pandora_exec
/usr/bin/pandora_server
/usr/bin/tentacle_server

%defattr(755,pandora,root,755)
/usr/lib/perl5/PandoraFMS/
%{prefix}/pandora_server
/var/log/pandora

%defattr(-,pandora,www,770)
/var/spool/pandora
/var/spool/pandora/data_in
/var/spool/pandora/data_in/md5
/var/spool/pandora/data_in/collections
/var/spool/pandora/data_in/conf

%defattr(-,pandora,root,750)
/etc/pandora

%defattr(644,pandora,root)
/usr/share/man/man1/pandora_server.1.gz
/usr/share/man/man1/tentacle_server.1.gz

