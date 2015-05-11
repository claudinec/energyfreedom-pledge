#!/usr/bin/env perl
# Summarise login details for people with custom field info:
# facebook/twitter/password/nopassword.

use strict;
use warnings;

use Carp;
use Config::JSON;
use Data::Dumper;
use File::HomeDir;
use FindBin qw($Bin);
use Getopt::Args;
use Mozilla::CA;
use Template::Liquid;
use WebService::NationBuilder;

# Identify the nation.
arg nation => (
    isa      => 'Str',
    required => 1,
    comment  => 'Our nation\'s slug.',
);
arg email => (
    isa      => 'Str',
    required => 1,
    comment  => 'Email address to query.',
);

my $ref = optargs;
my $slug = $ref->{nation};
my $email = $ref->{email};

# Authenticate to NationBuilder.
my $home = File::HomeDir->my_home;
my $conf_file = $home . "/.nationbuilder/config.json";
my $config = Config::JSON->new($conf_file)
    or croak "Config file $conf_file not found.\n";
my $access_token = $config->get("$slug/access_token")
    or croak "Access token for $slug not set.\n";

my $nb = WebService::NationBuilder->new(
    access_token => $access_token,
    subdomain    => $slug,
    retries      => 5
);

# Query the site for a person with matching email address.
my $person = $nb->match_person({
    email        => $email
}) or croak "No match found for $email in $slug.\n";
# Volunteer?
print "### Volunteer ###\n";
print Dumper($person->{is_volunteer});
# Facebook?
# print Dumper($person->{has_facebook});
print "### Facebook  ###\n";
print Dumper($person->{facebook_username});
# Twitter?
print "### Twitter   ###\n";
print Dumper($person->{twitter_login});

1;

__END__

=pod

=head1 CONFIGURATION

    Configuration file C<~/.nationbuilder/config.json>
