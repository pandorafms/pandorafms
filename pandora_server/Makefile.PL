use 5.000;
use ExtUtils::MakeMaker;

WriteMakefile(
	INSTALLSITELIB  => '/usr/lib/perl5',
	(($^O eq 'freebsd')
		? (INSTALLSITELIB => '') : ()
	),
	NAME            => 'PandoraFMS',
	VERSION_FROM    => 'lib/PandoraFMS/Config.pm',
	AUTHOR 			=> 'Artica ST <info@artica.es>',
	PREREQ_PM 	=> {
		NetAddr::IP => 0,
		DBI => 0,
		threads::shared => 0,
		IO::Socket => 0,
		Socket6 => 0,
		Time::Local => 0,
		XML::Simple => 0,
		XML::Twig => 0,
		Time::HiRes => 0,
		HTML::Entities => 0,
		IO::Socket::INET6 => 0,
    },
    EXE_FILES 		=> [ 'bin/pandora_server', 'bin/pandora_exec'],
    PMLIBDIRS  		=> [ 'lib' ],
    'dist'		=> { 'TAR' => 'tar', 'TARFLAGS' => 'cvfz', 'SUFFIX'
=> '.gz', 'COMPRESS' => 'gzip'}
);

