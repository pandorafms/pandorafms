#!/usr/bin/perl

use strict;
use warnings;

use Net::SMPP;

use lib '/usr/lib/perl5';
use PandoraFMS::PluginTools qw(read_configuration);

my $HELP =<<EO_H;


#######################
Pandora FMS SMPP client
#######################

Usage:

$0 -server <smsc_server:port> -user <user_id> -password <user_password> -source <source_number> -destination <destination_numbers> -message <short_message> [OPTIONS]

- <destination_numbers>            Comma separated list of destination numbers (123456789,234567891,...)

OPTIONS:

-service_type <value>              Default: ''
-source_addr_ton <value>           Default: 0x00
-source_addr_npi <value>           Default: 0x00
-dest_addr_ton <value>             Default: 0x00
-dest_addr_npi <value>             Default: 0x00
-esm_class <value>                 Default: 0x00
-protocol_id <value>               Default: 0x00
-priority_flag <value>             Default: 0x00
-schedule_delivery_time <value>    Default: ''
-validity_period <value>           Default: ''
-registered_delivery <value>       Default: 0x00
-replace_if_present_flag <value>   Default: 0x00
-data_coding <value>               Default: 0x00
-sm_default_msg_id <value>         Default: 0x00
-system_type <value>               Default: ''
-interface_version <value>         Default: 0x34
-addr_ton <value>                  Default: 0x00
-addr_npi <value>                  Default: 0x00
-address_range <value>             Default: ''

Example:

$0 -server 192.168.1.50:2775 -user myuser -password mypassword -source 123456789 -destination 234567891 -message "Content of SMS message"

EO_H

my $config;
$config = read_configuration($config);

if (!$config->{'server'}){
        print "Parameter -server is mandatory.";
        print $HELP;
        exit;
}
if (!$config->{'user'}){
        print "Parameter -user is mandatory.";
        print $HELP;
        exit;
}
if (!$config->{'password'}){
        print "Parameter -password is mandatory.";
        print $HELP;
        exit;
}
if (!$config->{'source'}){
        print "Parameter -source is mandatory.";
        print $HELP;
        exit;
}
if (!$config->{'destination'}){
        print "Parameter -destination is mandatory.";
        print $HELP;
        exit;
}
if (!$config->{'message'}){
        print "Parameter -message is mandatory.";
        print $HELP;
        exit;
}

my ($smsc_server, $smsc_port) = split /:/, $config->{'server'};

my @destination_numbers = split /,/, $config->{'destination'};

if (!$smsc_port){
	$smsc_port = 2775;
}

$config->{'service_type'}              = ''     if (!$config->{'service_type'});
$config->{'source_addr_ton'}           = '0x00' if (!$config->{'source_addr_ton'});
$config->{'source_addr_npi'}           = '0x00' if (!$config->{'source_addr_npi'});
$config->{'dest_addr_ton'}             = '0x00' if (!$config->{'dest_addr_ton'});
$config->{'dest_addr_npi'}             = '0x00' if (!$config->{'dest_addr_npi'});
$config->{'esm_class'}                 = '0x00' if (!$config->{'esm_class'});
$config->{'protocol_id'}               = '0x00' if (!$config->{'protocol_id'});
$config->{'priority_flag'}             = '0x00' if (!$config->{'priority_flag'});
$config->{'schedule_delivery_time'}    = ''     if (!$config->{'schedule_delivery_time'});
$config->{'validity_period'}           = ''     if (!$config->{'validity_period'});
$config->{'registered_delivery'}       = '0x00' if (!$config->{'registered_delivery'});
$config->{'replace_if_present_flag'}   = '0x00' if (!$config->{'replace_if_present_flag'});
$config->{'data_coding'}               = '0x00' if (!$config->{'data_coding'});
$config->{'sm_default_msg_id'}         = '0x00' if (!$config->{'sm_default_msg_id'});
$config->{'system_type'}               = ''     if (!$config->{'system_type'});
$config->{'interface_version'}         = '0x34' if (!$config->{'interface_version'});
$config->{'addr_ton'}                  = '0x00' if (!$config->{'addr_ton'});
$config->{'addr_npi'}                  = '0x00' if (!$config->{'addr_npi'});
$config->{'address_range'}             = ''     if (!$config->{'address_range'});

my $smpp = Net::SMPP->new_transmitter(
        $smsc_server,
        port              => $smsc_port,
        system_id         => $config->{'user'},
        password          => $config->{'password'},
        system_type       => $config->{'system_type'},
        interface_version => $config->{'interface_version'},
        addr_ton          => $config->{'addr_ton'},
        addr_npi          => $config->{'addr_npi'},
        address_range     => $config->{'address_range'}
) or die "Unable to connect to [$smsc_server] on port [$smsc_port] with user [" . $config->{'user'} . "]\n";

foreach my $destination_number (@destination_numbers){
        my $resp_pdu = $smpp->submit_sm(
                source_addr             => $config->{'source'},
                destination_addr        => $destination_number,
                short_message           => $config->{'message'},
                service_type            => $config->{'service_type'},
                source_addr_ton         => $config->{'source_addr_ton'},
                source_addr_npi         => $config->{'source_addr_npi'},
                dest_addr_ton           => $config->{'dest_addr_ton'},
                dest_addr_npi           => $config->{'dest_addr_npi'},
                esm_class               => $config->{'esm_class'},
                protocol_id             => $config->{'protocol_id'},
                priority_flag           => $config->{'priority_flag'},
                schedule_delivery_time  => $config->{'schedule_delivery_time'},
                validity_period         => $config->{'validity_period'},
                registered_delivery     => $config->{'registered_delivery'},
                replace_if_present_flag => $config->{'replace_if_present_flag'},
                data_coding             => $config->{'data_coding'},
                sm_default_msg_id       => $config->{'sm_default_msg_id'}
        );

        if ($resp_pdu->{message_id}){
                print "SUCCESS: Message sent to [$destination_number]\n";
        }else{
                print "ERROR: Unable to send message to [$destination_number] - Response error: " . $resp_pdu->explain_status() . "\n";
        }
}