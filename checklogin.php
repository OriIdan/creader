<?PHP
/*
 | checklogin.php
 | This file should be included from comments.php, it has no meaning by itself
 */

$parser = xml_parser_create();
global $usermail, $userpasswd;

function loginstart($parser, $element, $attrs) {
	global $state;
	global $userpasswd, $usermail;

	switch($state) {
		case 0:		// Waiting for root tag (comments)
			if($element == 'COMMENTS')
				$state = 1;
			break;
		case 1:
			if($element == 'USERINFO')
				$state = 100;
			break;
		case 100:	// Waiting for user info
			if($element == 'USERMAIL') {
				$state = 101;
				$usermail = '';
			}
			if($element == 'USERPASSWD') {
				$state = 102;
				$userpasswd = '';
			}
			break;
	}
}

/*
 | The real work is done here, since here we already have the data
 */
function loginstop($parser, $element) {
	global $state;
	global $userpasswd, $usermail;

	switch($state) {
		case 1:
			if($element == 'COMMENTS') {
				$state = 0;
			}
			break;
		case 100:
			if($element == 'USERINFO') {
				$state = 1;
			}
			break;
		case 101:
			if($element == 'USERMAIL') {
				$state = 100;
			}
		case 102:
			if($element == 'USERPASSWD') {
				$state = 100;
			}
			break;
	}
}

function loginchar($parser, $data) {
	global $state, $usermail, $userpasswd;

	switch($state) {
		case 101:
			$usermail .= $data;
			break;
		case 102:
			$userpasswd .= $data;
			break;
	}
}

xml_set_element_handler($parser, "loginstart", "loginstop");
xml_set_character_data_handler($parser, "loginchar");

$fp = fopen($filename, "r");

while($data = fread($fp, 4096)) {	// Read 4096 chars at a time
	xml_parse($parser, $data, feof($fp));
}

xml_parser_free($parser);

fclose($fp);

