<?PHP
/*
 | I18N initialization
 | Written by: Ori Idan
 */

~lang~

// print "lang: $lang<br />\n";
if($lang == 'he') {
	$dir = "rtl";
	setlocale(LC_ALL, "he_IL.UTF-8");
	putenv('LC_ALL=he_IL');
	$alignment = "right";
}
else {
	$dir = "ltr";
	$alignment = "left";
	setlocale(LC_ALL, "en_US");
}

textdomain("messages");
bindtextdomain("messages", "./locales");
bind_textdomain_codeset("messages", 'UTF-8');

?>
