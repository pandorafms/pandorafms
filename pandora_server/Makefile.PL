use 5.000;
use ExtUtils::MakeMaker;

WriteMakefile(
	NAME            => 'PandoraFMS',
	VERSION_FROM    => 'bin/PandoraFMS/Config.pm',
	AUTHOR 		=> 'Sancho Lerena <slerena@gmail.com>',
	PREREQ_PM 	=> {
		NetAddr::IP => 0,
		DBI => 0,
                threads::shared => 0,
                IO::Socket => 0,
		Time::Format => 0,
                Time::Local => 0,
                Date::Manip => 0,
		XML::Simple => 0,
		Net::Ping => 0,
		Time::HiRes => 0,
		IO::Socket => 0,
		Mail::Sendmail => 0,
		Net::Traceroute::PurePerl => 0,
		HTML::Entities => 0,
		SNMP => 0
    },
    EXE_FILES 		=> [ 'bin/pandora_server', 'bin/pandora_network', 'bin/pandora_wmi', 'bin/pandora_recon', 'bin/pandora_snmpconsole' , 'bin/pandora_plugin', 'bin/pandora_prediction', 'util/pandora_exec'],
    PMLIBDIRS  		=> [ 'lib' ],
    'dist'		=> { 'TAR' => 'tar', 'TARFLAGS' => 'cvfz', 'SUFFIX'
=> '.gz', 'COMPRESS' => 'gzip'}
);

