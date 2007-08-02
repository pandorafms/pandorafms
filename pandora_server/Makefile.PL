use 5.000;
use ExtUtils::MakeMaker;

WriteMakefile(
	NAME            => 'PandoraFMS',
	VERSION_FROM    => 'bin/PandoraFMS/Config.pm',
	AUTHOR 		=> 'Sancho Lerena <slerena@gmail.com>',
	PREREQ_PM 	=> {
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
		SNMP => 0
    },
    EXE_FILES 		=>
[ 'bin/pandora_server.pl', 'bin/pandora_network.pl', 'bin/pandora_recon.pl', 'bin/pandora_snmpconsole.pl'],
    PMLIBDIRS  		=> [ 'lib' ],
    'dist'		=> { 'TAR' => 'tar', 'TARFLAGS' => 'cvfz', 'SUFFIX'
=> '.gz', 'COMPRESS' => 'gzip'}
);

