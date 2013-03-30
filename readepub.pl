#/usr/bin/perl
# Unpack an EPUB file and create objects for monocole reader
use XML::Parser;
use File::Basename;
binmode STDOUT, ":utf8";

# Global variables
# $state holds the state of the parser when parsing opf files
use constant {
	NONE => 0,
	META => 100,
	MANIFEST => 200,
	SPINE => 300,
	DCTERMS => 101,
	NAV => 400,
	NAVUL => 401,
	NAVLI => 402,
	NAVLIA => 403,
};

$state = NONE;
%metadata = {};

$epubname = $ARGV[0];
if(!$epubname) {
	print "usage: readepub.pl <epubfile>\n";
}

($base, $dir, $suffix) = fileparse($epubname, qr/\.EPUB$/i);

print "base: $base, dir: $dir, suffix: $suffix\n";

$parsedir = "$dir$base";
mkdir($parsedir);
system("unzip -u $epubname -d $parsedir > /dev/null");


$container = "$parsedir/META-INF/container.xml";
if(! -e $container) {
	print "Error: $parsedir/META-INF/container.xml file does not exist\n";
	exit;
}

$cparse = new XML::Parser(Handlers => {Start => \&hdl_start });

$cparse->parsefile($container);

sub hdl_start {
	my ($p, $elt, %atts) = @_;
	if($elt eq 'rootfile') {
		$rootfile = $atts{'full-path'};
		print "#Root file: $parsedir/$rootfile\n";
		handleOPF();
	}
}

sub handleOPF {
	$parseopf = new XML::Parser(Handlers => {'Start' => \&opf_start, 'End' => \&opf_end, 'Char' => \&opf_char});
	print "#handleOPF $rootfile\n";
	($n, $oepbsdir, $ext) = fileparse($rootfile);
	$parseopf->parsefile("$parsedir/$rootfile");
}

sub opf_start {
	my ($p, $elt, %atts) = @_;
	
	if($elt eq 'metadata') {
		$state = META;	
	}
	elsif($elt eq 'manifest') {
		$state = MANIFEST;
	}
	elsif($elt eq 'spine') {
		$state = SPINE;
		$pageprogression = $atts{'page-progression-direction'};
		@spine = ();
	}
	if($state == SPINE) {
		if($elt eq 'itemref') {
			$id = $atts{'idref'};
			$linear = $atts{'linear'};
			if(!$linear || ($linear ne 'no')) {
				$linear = 'yes';
			}
			$href = $refhash{$id};
			$type = $mimehash{$id};
			ProcessFile($href);
			if($linear eq 'yes') {
				$ref = $refhash{$id};
				push(@spine, $ref);
			}
		}
	}
	if($state == MANIFEST) {
		if($elt eq 'item') {
			if($atts{'properties'} =~ /nav/) {
				$tocfile = $atts{'href'};
			}
			elsif($atts{'properties'} =~ /cover-image/) {
				$metadata{'coverid'} = $atts{'id'};
			}
			else {
				$id = $atts{'id'};
				$refhash{$id} = $atts{'href'};
				$mimehash{$id} = $atts{'media-type'};
			}
		}
	}
	elsif($state == META) {
		if($elt eq 'meta') {
			# print $atts{'property'};
			$elt = $atts{'property'};
		}
		if($elt =~ /dc:(.*)/) {
			$metaelement = $1;
			if($metaelement ne 'language') {
				$metadata{$metaelement} = '';
			}
			$state = DCTERMS;
		}
		elsif($elt =~ /dcterms:(.*)/) {
			$metaelement = $1;
			if($metaelement ne 'language') {
				$metadata{$metaelement} = '';
			}
			$state = DCTERMS;
		}
		if($atts{'name'} eq 'cover') {
			$metadata{'coverid'} = $atts{'content'};
		}
	}
}

sub opf_end {
	my ($p, $elt, %atts) = @_;

	if($elt eq 'metadata') {
		$state = NONE;
	}
	elsif($elt eq 'manifest') {
		$state = NONE;
		ProcessMetaData();
	}
	elsif($elt eq 'spine') {
		$state = NONE;
		ProcessSpine();
	}
	if($state == DCTERMS) {
		$state = META;
	}
}

sub opf_char {
	my ($p, $str) = @_;
	
	if($state == DCTERMS) {
		if($metaelement eq 'language') {
			$metadata{$metaelement} .= "$str, ";
		}
		elsif($metaelement eq 'modified') {
			$str =~ /(.*)T(.*)Z/;
			$mod = "$1 $2";
			$metadata{'modified'} .= $mod;
		}
		else {
			$metadata{$metaelement} .= $str;
		}
	}
}

sub ProcessMetaData {
	open(OUT, ">$parsedir/metadata.html");
	binmode OUT, ":utf8";
	if(($metadata{'language'} =~ 'he') || ($pageprogression eq 'rtl')) {
		print OUT "<div dir=\"rtl\" style=\"text-align:right\">\n";
		$imgstyle = "style=\"width:50%;margin-left:10px\"";
		$imgalign = "right";
	}
	else {
		print OUT "<div dir=\"ltr\" style=\"text-align:left\">\n";
		$imgstyle = "style=\"width:50%;margin-right:10px\"";
		$imgalign = "left";
	}
	print OUT "<h2>$metadata{'creator'}</h2>\n";
	print OUT "<h1>$metadata{'title'}</h1>\n";
	$coverid = $metadata{'coverid'};
	$coverimg = $refhash{$coverid};
	print OUT "<img src=\"$parsedir/$oepbsdir$coverimg\" $imgstyle align=\"$imgalign\" />\n";
	$desc = $metadata{'description'};
	$desc =~ s/\n/<br>\n/g;
	print OUT $desc;
	print OUT "\n</div>\n";
	close(OUT);
}

sub ProcessFile {
	my ($fname) = @_;
	
	$mathml = 0;
	open(IN, "$parsedir/$oepbsdir$fname");
	# Search for MathML
	while(<IN>) {
		if(/<math/i) {
			$mathml = 1;
		}
	}
	close(IN);

	open(IN, '<:encoding(UTF-8)', "$parsedir/$oepbsdir$fname");
	binmode OUT, ":utf8";
	open(OUT, ">$parsedir/$fname");
	binmode OUT, ":utf8";
	while(<IN>) {
		if(/<head/i) {
			print OUT $_;
			print OUT "<base href=\"$oepbsdir\" />\n";
		}
		elsif(/<\/head/i) {
			if($mathml) {
				print OUT "<script type=\"text/javascript\" src=\"http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML\"></script>\n";
			}
			print OUT $_;
		}
		elsif(/<body(.*)>/i) {
			print OUT "<body>\n<div $1>\n";
		}
		elsif(/<\/body/i) {
			print OUT "</div>\n$_";
		}
		elsif(/<meta.*viewport/) {
			next;
		}
		else {
			print OUT $_;
		}
	}
	close(OUT);
	close(IN);
}

sub ProcessSpine {
	print "Opening: $parsedir/$oepbsdir$tocfile\n";
	$tocpath = "$parsedir/$oepbsdir$tocfile";
	
	print "Out file: $parsedir/data.js\n";
	open(OUT, ">$parsedir/data.js");
	binmode OUT, ":utf8";
	print OUT "// data.js\n";
	print OUT "// Note: This file is created automatially and will be overwritten\n";
	if($pageprogression eq 'rtl') {
		print OUT "var readerOptions = {flipper: Monocle.Flippers.rtlSlider};\n";
		$readercss = "readerrtl.css";
	}
	else {
		print OUT "var readerOptions = {flipper: Monocle.Flippers.Slider};\n";
		$readercss = "reader.css";
	}
	print OUT "var bookData = Monocle.bookData({\n";
	print OUT "\tcomponents: [\n";
	$i = 0;
	foreach $s (@spine) {
		if($i) { print OUT ",\n"; }
		$i++;
		print OUT "\t\t'$parsedir/$s'";
	}
	print OUT "\t],\n";
	print OUT "chapters: [\n";

	$chapters = '';
	$parsetoc = new XML::Parser(Handlers => {'Start' => \&toc_start, 'End' => \&toc_end, 'Char' => \&toc_char});
	$parsetoc->parsefile($tocpath);
	
	print OUT "$chapters\n";
	print OUT "],\n";
	print OUT "\tmetadata: {\n";
	print OUT "\t\ttitle: \"$metadata{'title'}\",\n";
	print OUT "\t\tcreator: \"$metadata{'creator'}\"\n";
	print OUT "\t}\n";
	print OUT "});\n";
	close(OUT);
	open(IN, "reader.html");
	open(OUT, ">$parsedir/reader.html");
#	print "Readercss: $readercss\n";
	while(<IN>) {
		s/~readercss~/$readercss/;
		s/~datapath~/$parsedir/;
		print OUT $_;
	}
	close(OUT);
	close(IN);
}

sub toc_start {
	my ($p, $elt, %atts) = @_;
	
	if($elt eq 'nav') {
		$state = NAV;
		print "found nav element\n";
	}
	if($state == NAV) {
		if($elt eq 'ol') {
			$state = NAVUL;
			print "found nav ul\n";
		}
	}
	if($state == NAVUL) {
		if($elt eq 'li') {
			$state = NAVLI;
		}
	}
	if($state == NAVLI) {
		if($elt eq 'a') {
			$state = NAVLIA;
			$href = $atts{'href'};
			$t = '';
		}
	}
}

sub toc_end {
	my ($p, $elt) = @_;

	if($state == NAVLIA) {
		if($elt eq 'a') {
			if($chapters ne '') {
				$chapters .= ",\n";
			}
			$chapters .= "\t  {\n\t\ttitle: \"$t\",\n\t\tsrc: \"$parsedir/$href\"\n\t}";
			$state = NAVLI;
		}
	}
	if($state == NAVLI) {
		if($elt eq 'li') {
			$state = NAVUL;
		}
	}
	if($state == NAVUL) {
		if($elt eq 'ol') {
			$state = NAV;
		}
	}
	if($state == NAV) {
		if($elt eq 'nav') {
			$state = NONE;
		}
	}
}

sub toc_char {
	my ($p, $str) = @_;
	
	if($state == NAVLIA) {
		$t .= $str;
	}
}

