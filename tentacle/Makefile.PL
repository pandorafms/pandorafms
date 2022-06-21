use 5.000;
use ExtUtils::MakeMaker;

my %ARGV = map { my @r = split /=/,$_; defined $r[1] or $r[1]=1; @r } @ARGV;
my @exe_files = qw(tentacle_server);

WriteMakefile(
	INSTALLSITELIB  => '/usr/lib/perl5',
	(($^O eq 'freebsd')
		? (INSTALLSITELIB => '') : ()
	),
	($^O eq 'netbsd') ? (
		INSTALLSITELIB => '',
		INSTALLSITESCRIPT => '/usr/local/bin',
	) :(),

	NAME            => 'PandoraFMS',
	AUTHOR 			=> 'Artica ST <info@artica.es>',
	EXE_FILES 		=> [ @exe_files ],
	PMLIBDIRS  		=> [ 'lib' ],
	'dist'		=> { 'TAR' => 'tar', 'TARFLAGS' => 'cvfz', 'SUFFIX' => '.gz', 'COMPRESS' => 'gzip'}
);
