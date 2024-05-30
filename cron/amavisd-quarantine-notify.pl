#!/usr/bin/perl -W
#
# (P) & (C) 2019-2024 by Peter Bieringer <pb@bieringer.de>
#
# This program supports "amavisd-release-web" by sending on regular basis (cron)
# notification e-mails to recipients, when entries in quarantine were found
# of type "virus", "spam", "badh", "banned"
#
# Filename prefixes currently hardwired and related to "amavisd" options:
#   $virus_quarantine_method              = 'local:virus-%m';
#   $banned_files_quarantine_method       = 'local:banned-%m';
#   $spam_quarantine_method               = 'local:spam-%m.gz';
#   $bad_header_quarantine_method         = 'local:badh-%m';
#   $unchecked_quarantine_method          = 'local:unchecked-%m'; (if enabled)
#   $clean_quarantine_method              = 'local:clean-%m';     (if enabled)
#   $archive_quarantine_method            = 'local:archive-%m.gz' (if enabled)
#
# Very useful in case 'amavisd' is running in before-queue mode
#
# License: MIT
#
# 20191201/PB: initial
# 20191208/PB: implement option handling and blacklist/whitelist filter option, add syslog
# 20191210/PB: extend info about type of quarantined e-mails, add warning in case of virus
# 20200129/PB: add X-Spam-Level: ***** (5)
# 20201113/PB: replace X-Spam-Level: ******* (7)
# 20201117/PB: display original X-Spam-Level in message
# 20220924/PB: add option -L/-U for Spam-Score limits
# 20221011/PB: add recipient on release URL for "banned"
# 20240107/PB: fix for catching only "spam" on limit checks
# 20240530/PB: add support for multiple recipients in X-Envelope-To

use strict;
use warnings;
use IO::Zlib;
use Encode qw(encode decode);
use MIME::Lite;
use POSIX qw(strftime);
use Getopt::Std;
use Data::Dumper;
use Digest::MD5 qw(md5 md5_hex md5_base64);
use Net::Domain qw(hostname hostfqdn hostdomain domainname);
use Sys::Syslog;
use File::Basename;

my $progname = basename($0);
my $progname_full = $0;
$progname_full = "/path/to" . substr($progname_full, 1) if $progname_full =~ /^\.\//o;

# from config file
my %domains_whitelist;
my %domains_blacklist;
my %recipients_whitelist;
my %recipients_blacklist;

my %quarantine;
my %recipients;
my $now = time;

# defaults
my %opts;
$opts{'n'} = hostfqdn;
$opts{'u'} = "/amavisd-release-web";
$opts{'H'} = "5";
$opts{'Q'} = "/var/spool/amavisd/quarantine";
$opts{'F'} = "spam.police\@" . hostdomain;
$opts{'R'} = "/etc/amavisd/amavisd-release-web-recipients";


# option handling
getopts("R:F:H:U:L:u:n:Q:r:ctdh?", \%opts);

if (defined $opts{'h'} || defined $opts{'?'}) {
        print qq|$progname - (P) & (C) 2019 by Peter Bieringer <pb\@bieringer.de>

run through spam\|virus-* files in quarantine directory and
 send list of entries to found recipients via local e-mail
 uses syslog in case not running in trace mode

Options:
	-H <hours>	hours back in history (default: $opts{'H'})
	-Q <quarantine>	amavis quarantine directory (default: $opts{'Q'})
	-R <recipfile>	file to list of recipients (default: $opts{'R'})
	-F <fromaddr>	sender of e-mail (default: $opts{'F'})
	-U <upperscore>	upper limit of score (excluded)
	-L <lowerscore>	lower limit of score (included)
	-n <hostname>	hostname for URL (default: $opts{'n'})
	-u <URI>	URI to amavisd-release-web (default: $opts{'u'})
	-r <recipient>	filter only given recipient (mosty for test/debug purposes) / ignores -R
	-t		trace, print to stdout
	-c		collect summary only (in trace mode)
	-d		debug
	-h\|?		this online help

Example for content of recipient file (-R $opts{'R'})
  # whitelisted user
  user\@want-to-be-informed.domain.example
  # whitelisted domain
  \@want-to-be-informed.domain.example
  # blacklisted user
  !user\@dont-care-about-rejected-spams.domain.example
  # blacklisted domain
  !\@dont-care-about-rejected-spams.domain.example

This program should be regulary triggered by cron, example for crontab file /etc/cron.d/amavis:

Simple example, using defaults (-H $opts{'H'})
|;

	printf "%d */%d * * * amavis %s -H %s\n", rand(60), $opts{'H'} - 1, $progname_full, $opts{'H'};

        print qq|
2-staged example, using Spam-Score limits to reduce frequency (< 20: every 4 hours, >= 20: every day)
|;
	printf "%d */4 * * * amavis %s -H 5  -U 20\n", rand(60), $progname_full;
	printf "%d %d   * * * amavis %s -H 25 -L 20\n", rand(60), rand(24), $progname_full;
	exit 0;
};

my $HOURS = $opts{'H'};
my $QUARANTINE = $opts{'Q'};
my $URL_BASE = "https://" . $opts{'n'} . $opts{'u'} . "/?ID=";
my $FROM = $opts{'F'};
my $RECIPFILE = $opts{'R'};

my $SCORE_UPPER = $opts{'U'} || undef;
my $SCORE_LOWER = $opts{'L'} || undef;

if (defined $opts{'r'}) {
	undef $RECIPFILE; # ignore file
	if ($opts{'r'} =~ /^@(\S+)/o) {
		$domains_whitelist{$1} = 1;
		warn("add recipient domain to whitelist: " . $opts{'r'}) if defined $opts{'d'};
	} else {
		$recipients_whitelist{$opts{'r'}} = 1;
		warn("add recipient to whitelist: " . $opts{'r'}) if defined $opts{'d'};
	};
};

# open syslog
openlog($progname, "pid", "mail") unless (defined $opts{'t'});

# read recipients
if (defined $RECIPFILE && -f $RECIPFILE) {
	open (my $fh, $RECIPFILE) || die "Can't open file $RECIPFILE: $!";
	while (<$fh>) {
		chomp($_);
		$_ =~ s/\s+//o; # remove leading spaces
		$_ =~ s/\s+$//o; # remove trailing spaces
		next if $_ =~ /^#/o; # skip comments
		next if $_ !~ /^!?(\S+)?@\S+$/o; # skip what is not looking like e-mail address
		next if $_ =~ /^$/o; # emty lines

		$domains_blacklist{$1} = 1 if ($_ =~ /^!@(\S+)$/o);
		$domains_whitelist{$1} = 1 if ($_ =~ /^@(\S+)$/o);
		$recipients_blacklist{$1} = 1 if ($_ =~ /^!(\S+@\S+)$/o);
		$recipients_whitelist{$1} = 1 if ($_ =~ /^([^!]\S+@\S+)$/o);
	};
};


# read quarantine
opendir (my $dh, $QUARANTINE) || die "Can't opendir $QUARANTINE: $!";
while (readdir $dh) {
	my $entry = $_;
	next if $entry =~ /^\./o; # skip directories

	next if $entry !~ /^(spam|virus|badh|banned|archive|unchecked|clean)-/o; # skip not expected files

	my $type = $1;

	my $file = $QUARANTINE . "/" . $entry;
	next if (! -f $file);

	my $mtime = (stat($file))[9];
	next if (($now - $mtime) > $HOURS * 60 * 60);
	$quarantine{$_}->{'mtime'} = $mtime;

	# get contents
	my $fh;

	if ($file =~ /\.gz$/o) {
		$fh = new IO::Zlib;
		$fh->open($file, "rb") || die "Can't open gz file $file: $!";
	} else {
		open ($fh, $file) || die "Can't open file $file: $!";
	};

	while (<$fh>) {
		last if $_ eq ""; # header/content separator
		if ($_ =~ /^(To|From|Date|Subject|X-Spam-Score|X-Spam-Level|X-Envelope-To|X-Envelope-From): (.+)/o) {
			my $key = $1;
			my $value = $2;
			if ($key =~ /^(X-Envelope-To|X-Envelope-From)$/o) {
				$value =~ s/<//og;
				$value =~ s/>//og;
				$value =~ s/,//og;
			} elsif ($key eq "X-Spam-Score" && $type eq "virus") {
				$value .= "  ***** VIRUS FOUND *****";
			};
			$quarantine{$entry}->{$key} = decode("MIME-Header", $value);
			$quarantine{$entry}->{$key} =~ s/[^\x00-\x7f]//og;
			$quarantine{$entry}->{'type'} = $type;
			if ($key eq "X-Envelope-To") {
				foreach my $recipient (split(" ", $value)) {
					$recipients{$recipient}++;
				};
			};
		};
	};
	close $fh;
};
closedir $dh;

my $num_recipients_blacklist = scalar(keys %recipients_blacklist);
my $num_recipients_whitelist = scalar(keys %recipients_whitelist);
my $num_domains_blacklist = scalar(keys %domains_blacklist);
my $num_domains_whitelist = scalar(keys %domains_whitelist);

foreach my $recipient (sort keys %recipients) {
	# extract domain
	$recipient =~ /^\S+@(\S+)$/o;
	my $recipient_domain = $1;

	warn("recipient=$recipient domain=$recipient_domain") if defined $opts{'d'};

	my $skip_default = 0;
	my $skip_recipient = 0;

	my $action = "pass";

	if ($num_recipients_blacklist + $num_recipients_whitelist + $num_domains_blacklist + $num_domains_whitelist > 0) {
		# blacklist/whitelist file given and has entries
	
		if (defined $recipients_blacklist{$recipient}) {
			# recipient is on blacklist -> skip
			warn("skipped (recipient blacklist): " . $recipient) if defined $opts{'d'};
			next;
		};

		unless (defined $recipients_whitelist{$recipient}) {
			# recipient is not on whitelist
			if (defined $domains_blacklist{$recipient_domain}) {
				# domain is on blacklist -> skip
				warn("skipped (domain blacklist): " . $recipient) if defined $opts{'d'};
				next;
			};

			if ($num_recipients_whitelist + $num_domains_whitelist == 0) {
				# no whitelist recipients/domains at all
				#  blacklist recipient/domain checked aready above -> pass
			} elsif ($num_domains_whitelist > 0) {
				unless (defined $domains_whitelist{$recipient_domain}) {
					# domain not on whitelist -> skip
					warn("skipped (domain not on whitelist): " . $recipient) if defined $opts{'d'};
					next;
				};
			} elsif ($num_recipients_whitelist > 0) {
				warn("skipped (recipient not on whitelist): " . $recipient) if defined $opts{'d'};
				next;
			};
		};
	};

	my @output;
	my %spam_list;

	my @keys = ("X-Envelope-From", "X-Envelope-To", "From" , "To", "Date", "Subject", "X-Spam-Score", "X-Spam-Level");

	foreach my $entry (sort { $quarantine{$b}->{'mtime'} <=> $quarantine{$a}->{'mtime'} } keys %quarantine) {
		# print Dumper($quarantine{$entry});
		#
		# check recipient (against list of recipients in X-Envelope-To)
		next unless grep { /^$recipient$/ } split(" ", $quarantine{$entry}->{'X-Envelope-To'});

		if (defined $opts{'L'} || defined $opts{'U'}) {
			if (defined $quarantine{$entry}->{'X-Spam-Score'} && $quarantine{$entry}->{'type'} eq "spam") {
				# check for spam score limits
				next if (defined $opts{'L'} && $quarantine{$entry}->{'X-Spam-Score'} <  $opts{'L'});
				next if (defined $opts{'U'} && $quarantine{$entry}->{'X-Spam-Score'} >= $opts{'U'});
			};
		};

		my $ctx = Digest::MD5->new;
		my %spam;
		for my $key (@keys) {
			my $value = $quarantine{$entry}->{$key};
		
			unless ($key eq "X-Spam-Score") {
				# do not honor spam score	
				$ctx->add($key);
				$ctx->add($value) if defined ($value);
			};

			$value = "" if (! defined $value);
			$value = substr($value, 0, 57) . "..." if (length($value) > 60);
			$value =~ s/@/(at)/og;
			$value =~ s/(mailto):/$1_/og;

			$spam{$key} = $value;
		};
		my $digest = $ctx->b64digest;

		if (! defined $spam_list{$digest}) {
			$spam_list{$digest}->{'content'} = \%spam;
			$spam_list{$digest}->{'entry'}->{$quarantine{$entry}->{'mtime'}} = $entry;
		} else {
			$spam_list{$digest}->{'entry'}->{$quarantine{$entry}->{'mtime'}} = $entry;
		};
	};

	my %statistics;

	foreach my $digest (keys %spam_list) {
		warn($digest) if defined $opts{'d'};
		for my $key (@keys) {
			warn($digest . ":" . $key) if defined $opts{'d'};
			next if $key eq "X-Envelope-To";
			my $key_display = $key;
			$key_display =~ s/^X-//o;
			push @output, sprintf "%-13s: %s", $key_display, $spam_list{$digest}->{'content'}->{$key};
		};

		for my $mtime (sort { $b <=> $a } keys %{$spam_list{$digest}->{'entry'}}) {
			# only print last entry
			push @output, strftime "Received     : %Y-%m-%d %H:%M:%S %Z", localtime($mtime);
			my $token = $spam_list{$digest}->{'entry'}->{$mtime};
			if ($spam_list{$digest}->{'entry'}->{$mtime} =~ /^banned-/o) {
				$token .= "&R=" . $recipient;
			};
			push @output, sprintf "%-13s: %s", "Release-URL", $URL_BASE . $token;
			$spam_list{$digest}->{'entry'}->{$mtime} =~ /^([^-]+)-.*$/o;
			$statistics{$1}++;
			last;
		};
		push @output, "\n";
	};
	# print @output;

	next if (scalar(@output) == 0);

	my $timestamp = strftime "%Y-%m-%d %H:%M:%S %Z", localtime(time);

	my @topic;
	push @topic, "=" x 75;
	push @topic, "E-Mail Quarantine Information " . $timestamp;
	push @topic, " Following spams were rejected but still in quarantine";
	my $info = "";
	for my $stat (keys %statistics) {
		$info .= " " if (length($info) > 0);
		$info .= $stat . "=" . $statistics{$stat};
	};
	push @topic, " Range: last " . $HOURS . " hours, found: " . $info;
	push @topic, " Spam-Score limit: >= " . $opts{'L'} if (defined $opts{'L'});
	push @topic, " Spam-Score limit: <  " . $opts{'U'} if (defined $opts{'U'});
	push @topic, " Recipient: " . $recipient;
	push @topic, "-" x 75;
	
	if (defined $opts{'t'}) {
		print join("\n", @topic) . "\n";
		if (defined $opts{'c'}) {
			print "(list of e-mails suppressed by option '-c')\n\n";
		} else {
			print "\n" . join("\n", @output);
		};
	} else {
		my $message = MIME::Lite->new(
			From    => $FROM,
			To      => $recipient,
			Subject => 'E-Mail Quarantine Information ' . $timestamp,
			'X-Priority' => '5',
			'X-Spam-Level' => '*******',
			Data	=> join("\n", @topic) . "\n\n" . join("\n", @output)
		);

		$message->send;

		syslog("info", "send quarantine information to $recipient ($info)") unless (defined $opts{'t'});
	};
};

# close syslog
closelog() unless (defined $opts{'t'});
