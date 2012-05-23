# Pandora FMS AgentWin32 monitoring library.
package PandoraWin32;
use strict;
use warnings;
use PandoraWMI;
use PandoraNT;

########################################################################################
# PandoraWin32 class constructor.
########################################################################################
sub new ($$) {
    my ($class, $lib) = @_;
	
    my $self = { '_lib_' => undef };
	
	if ($lib =~ /NT/i) {
		$self->{'_lib_'} = PandoraNT->new ();
	} else {
		$self->{'_lib_'} = PandoraWMI->new ();
	}
	
    bless $self, $class;
    return $self;
}

# Get CPU load percentage
sub get_cpu_usage ($) {
	my $self = shift;
	
	return $self->{'_lib_'}->get_cpu_usage ();
}

# Get memory usage in MBytes
sub get_free_memory ($) {
	my $self = shift;
	
	return $self->{'_lib_'}->get_free_memory ();
}

# Get memory usage percentage
sub get_free_memory_percentage ($) {
	my $self = shift;
	
	return $self->{'_lib_'}->get_free_memory_percentage ();
}

# Get process status (1 running, 0 not running)
sub get_process_status ($$) {
	my ($self, $process_name) = @_;
	
	return $self->{'_lib_'}->get_process_status ($process_name);
}

# Get service status (1 running, 0 not running)
sub get_service_status ($$) {
	my ($self, $service_name) = @_;
	
	return $self->{'_lib_'}->get_service_status ($service_name);
}

# Get free disk space in MBytes
sub get_free_disk_space ($$) {
	my ($self, $device_id) = @_;
	
	return $self->{'_lib_'}->get_free_disk_space ($device_id);
}

# Get free disk space percentage
sub get_free_disk_space_percentage($$) {
	my ($self, $device_id) = @_;
	
	return $self->{'_lib_'}->get_free_disk_space_percentage ($device_id);
}

1;
__END__
