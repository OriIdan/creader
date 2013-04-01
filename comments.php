<?PHP
/*
 | Add comments to book in Helicon Books cloud reader.
 | Book comments are stored in comments.xml
 | an ad-hok XML file in the directory specified by 'book' under data.
 | file structure is:
 | <comments>
 |    <userinfo>
 |      <usermail>Root user email</usermail>
 |      <userpasswd>Root user password</userpasswd>
 |    </userinfo>
 |    <comment>
 |      <num>Comment num</num>
 |      <chapter>Chapter name</chapter>
 |      <name>Commenter name</name>
 |      <email>Commenter email</email>
 |      <flags><!-- 1 = receive mail on response, 2 = hidden (used to initialize mails) --></flags>
 |      <date>d-m-Y</date>
 |      <text>Comment text</text>
 |    </comment>
 | </comments>
 */
session_start();

$book = isset($_POST['book']) ? htmlspecialchars($_POST['book'], ENT_QUOTES) : '';
$book = isset($_GET['book']) ? htmlspecialchars($_GET['book'], ENT_QUOTES) : $book;
$cchapter = isset($_POST['chapter']) ? htmlspecialchars($_POST['chapter'], ENT_QUOTES) : '';
$cchapter = isset($_GET['chapter']) ? htmlspecialchars($_GET['chapter'], ENT_QUOTES) : $cchapter;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$num = isset($_GET['num']) ? $_GET['num'] : 0;

$logedin = isset($_SESSION['logedin']) ? $_SESSION['logedin'] : 0;
if($logedin) {
	$lfp = fopen("data/$book/logedin.txt", "r");
	$s = fgets($lfp);
	if($logedin == $s)
		$logedin = 1;
	else
		$logedin = 0;
}

if($book == '')
	return;		/* We have no idea what book this is so can't find  filename */

include("data/$book/i18n.inc.php");

$debug = 1;

$filename = "data/$book/comments.xml";
$lastnum = 0;
$state = 0;
$cname = '';
$flags = 0;
$email = '';
$text = '';
$sentmails = array();

$userpasswd = '';
$usermail = '';

/*
 | There are 3 functions:
 |   0 - display comments
 |   1 - Add comment (data is available in $_POST
 |   2 - Delete comment with specified number
 */
$function = 0;
if($action == 'delcomment') {
	$function = 2;
}
if($action == 'addcomment') {
	$function = 1;
}
if($function >= 1) {
	/* set tmp file name */
	$tmpfilename = "data/$book/tmp.xml";
	$tmpfp = fopen($tmpfilename, "w");
	fwrite($tmpfp, "<comments>\n");
}

function DebugPrint($str) {
	global $debug;
	
	if($debug)
		print $str;
}

if($action == 'forgot') {
	global $usermail, $userpasswd;
	
	require_once('checklogin.php');

	print "$usermail, $userpasswd<br />\n";

	$subject = "=?utf-8?B?" . base64_encode(_("Password reminder for Helicon Books cloud reader")) . "?=";
	$body = _("You have requested a password reminder from Helicon Books cloud reader");
	$body .= "\n". _("Note: this password is only needed for deleting comments");
	$body .= "\n\n" . _("The password is");
	$body .= ": $userpasswd\n";
	$headers = "Content-type: text/plain; charset=UTF-8\r\n";
	$headers .= "From: no-replay@heliconbooks.com\r\n";
	mail($usermail, $subject, $body, $headers);
	
	$l = _("Password reminder sent");
	print "<div class=\"alert alert-success\" >$l</div>\n";
	$action = 'login';
}

if($action == 'dologin') {
	$email = isset($_POST['email']) ? $_POST['email'] : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	require_once('checklogin.php');

	if(($email == $usermail) && ($userpasswd == $password)) {
		print "<div class=\"alert alert-sucess\">\n";
		$l = _("You have successfully logged in");
		print "$l</div>\n";
		/* Write a random hash so we know we are logged in and it will be harder to bypass it */
		$i = rand(1, 99999);
		$str = md5($i);
		$lfp = fopen("data/$book/logedin.txt", "w");
		fwrite($lfp, "$str");
		fclose($lfp);
		$_SESSION['logedin'] = $str;
		$logedin = 1;
	}
	else {
		print "<div class=\"alert alert-error\">\n";
		$l = _("Invalid email or password");
		print "$l\n";
		print "</div>\n";
		$logedin = 0;
		$action = 'login';
	}
}
if($action == 'login') {	/* Display login screen */
	$l = _("Login to system");
	print "<h3>$l: </h3>\n";
	print "<form name=\"loginform\" method=\"post\" onsubmit=\"xmlhttpPost('comments.php?book=$book&chapter=$cchapter&action=dologin', 'loginform', 'comments'); return false;\">\n";
	$l = _("Email");
	print "<input type=\"text\" placeholder=\"$l\" name=\"email\" value=\"$email\" /><br />\n";
	$l = _("Password");
	print "<input type=\"password\" placeholder=\"$l\" name=\"password\" /><br />\n";
	$l = _("Submit");
	print "<input type=\"submit\" value=\"$l\" class=\"btn btn-primary\" />\n";
	print "</form>\n";
	
	$l = _("Forgot password");
	$url = "comments.php?book=$book&chapter=$cchapter&action=forgot";
	print "<div style=\"cursor:pointer;margin-bottom:15px;\" onClick=\"ajaxLoad('$url', 'comments')\">$l</div>\n";
	
}

function SendUpdate($email) {
	global $sentmails;
	global $book, $cchapter;
	
	if(in_array($email, $sentmails))
		return;	// No need to send, we have already sent
	$sentmails[] = $email;
	$subject = "=?utf-8?B?" . base64_encode(_("New comment in Helicon Books cloud reader")) . "?=";
	$url = "http://creader.heliconbooks.com/index.php?book=$book&chapter=$cchapter";
	$body = "<div>";
	$body = _("New comment was received to a chapter you are following");
	$body .= "<br />\n" . _("Click the following link to see it");
	$body .= ": <a href=\"$url\">$url</a><br />\n</div>";
	$headers = "Content-type: text/html; charset=UTF-8\r\n";
	$headers .= "From: no-replay@heliconbooks.com\r\n";
	mail($email, $subject, $body, $headers);
}

function isValidEmail($email) {
	global $errstr;

	if($email == '') {
		$errstr .= "<br />" . _("Invalid email address");
		return 1;
	}
	if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email)) {
		list($username,$domain)=split('@',$email);
		return 0;
	}
	$errstr .= "<br />" . _("Invalid email address");
	return 1;
}

if($function == 1) {
	$cflags = isset($_POST['flags']) ? $_POST['flags'] : 0;
	$_cname = isset($_POST['cname']) ? htmlspecialchars($_POST['cname'], ENT_QUOTES) : '';
	$cemail = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : '';
	$ctext = isset($_POST['text']) ? $_POST['text'] : '';
	$errstr = '';
	$errnum = 0;
	/* Check input values */
	if($_cname == '') {
		$errstr .= _("Name field is required");
		$errnum++;
	}
	if(isValidEmail($cemail)) {
		$errnum++;
	}
	if($ctext == '') {
		$errstr .= "<br />" . _("Comment is empty");
		$errnum++;
	}
	if($errnum > 0) {
		print "<div class=\"alert alert-error\">$errstr</div>\n";
	}
}
else {
	$_cname = '';
	$cemail = '';
	$ctext = '';
	$cflags = '';
}

$text = isset($_POST['text']) ? $_POST['text'] : '';

print "<script src=\"ajaxsubmit.js\"></script>\n";
$l = _("Add comment");
print "<div id=\"_addcomment\" dir=\"ltr\">\n";
print "<img src=\"images/comment_add.png\" alt=\"\">&nbsp;$l<br />\n";
print "</div>\n";
print "<form name=\"commentform\" method=\"post\" onsubmit=\"xmlhttpPost('comments.php?action=addcomment', 'commentform', 'comments'); return false;\">\n";
print "<section id=\"_info\">\n";
print "<input type=\"hidden\" name=\"book\" value=\"$book\" />\n";
print "<input type=\"hidden\" name=\"chapter\" value=\"$cchapter\" />\n";
print "</section>\n";
print "<textarea name=\"text\" id=\"text\" style=\"width:80%;height:4em\">$ctext</textarea>\n";
$l = _("Name");
print "<br />\n";
print "<input type=\"text\" id=\"cname\" placeholder=\"$l\" name=\"cname\" value=\"$_cname\" />\n";
print "<br />\n";
$l = _("Email (will not be displayed)");
print "<input type=\"text\" id=\"email\" placeholder=\"$l\" name=\"email\" value=\"$cemail\" />\n";
print "<br />\n";
$ch = ($cflags & 1) ? "checked" : '';
print "<input type=\"checkbox\" id=\"flags\" name=\"flags\" value=\"1\" $ch>\n";
$l = _("Send me email on comments");
print "$l<br />\n";
$l = _("Sumbit");
print "<div style=\"width:100%;text-align:center\"><input type=\"submit\" id=\"submit\" class=\"btn btn-primary\" value=\"$l\" /></div>\n";
print "</form>\n";

if(!$logedin) {
	$url = "comments.php?book=$book&chapter=$cchapter&action=login";
	$l = _("Login");
	print "<div style=\"cursor:pointer;margin-bottom:15px;\" onClick=\"ajaxLoad('$url', 'comments')\">$l</div>\n";
}
	
$parser = xml_parser_create();

function start($parser, $element, $attrs) {
	global $state;
	global $chapter, $cchapter, $lastnum, $cname, $email, $flags, $date, $text;

	switch($state) {
		case 0:		// Waiting for root tag (comments)
			if($element == 'COMMENTS')
				$state = 1;
			break;
		case 1:		// Waiting for comment tag
			if($element == 'COMMENT')
				$state = 2;
			if($element == 'USERINFO')
				$state = 100;
			break;
		case 2:		// Inside comment tag
			if($element == 'NUM') {
				$state = 3;		/* Get comment number */
				$lastnum = '';
			}
			else if($element == 'NAME') {
				$state = 4;		/* Get commenter name */
				$cname = '';
			}
			else if($element == 'EMAIL') {
				$state = 5;		/* Get commenter email */
				$email = '';
			}
			else if($element == 'DATE') {
				$state = 6;		/* Get comment date */
				$date = '';
			}
			else if($element == 'FLAGS') {
				$state = 7;		/* Get flags */
				$flags = '';
			}
			else if($element == 'TEXT') {
				$state = 8;		/* Get comment text */
				$text = '';
			}
			else if($element == 'CHAPTER') {
				$state = 9;		/* Get chapter id */
				$chapter = '';
			}
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
function stop($parser, $element) {
	global $state, $function, $tmpfp, $num, $book;
	global $chapter, $cchapter, $lastnum, $cname, $email, $flags, $date, $text, $userpasswd, $usermail;
	global $logedin;

	switch($state) {
		case 1:
			if($element == 'COMMENTS') {
				$state = 0;
			}
			break;
		case 2:
			if($element == 'COMMENT') {
				$state = 1;
				/* The comment is done, see what we have to do with it */
				if($function < 1) {
					if($chapter == $cchapter) {
						print "<div class=\"cdiv\">\n";
						print "<table><tr><td>\n";
						$gurl = 'http://www.gravatar.com/avatar/';
					    $gurl .= md5( strtolower( trim( $email ) ) );
					    $gurl .= "?s=24&d=identicon&r=g";
						print "<img src=\"$gurl\" height=\"24\" alt=\"avatar\" > &nbsp;";
						print "</td><td class=\"cname\">$cname<br />$date</td>\n";
						print "</tr></table>\n";
						print "$text\n";
						if($logedin) {
							$url = "comments.php?book=$book&chapter=$cchapter&action=delcomment&num=$lastnum";
							$l = _("Delete comment");
							print "<div style=\"cursor:pointer\" onClick=\"ajaxLoad('$url', 'comments')\">$l</div>\n";
						}
						print "</div>\n";
					}
				}
				else {
					/* Check if we need to send mail */
					if(($function != 2) && ($flags & 1))	// We need to send mail to this person
						SendUpdate($email);
					
					if(($function != 2) || ($lastnum != $num)) {
						fwrite($tmpfp, "  <comment>\n");
						fwrite($tmpfp, "    <num>$lastnum</num>\n");
						fwrite($tmpfp, "    <chapter>$chapter</chapter>\n");
						fwrite($tmpfp, "    <name>$cname</name>\n");
						fwrite($tmpfp, "    <email>$email</email>\n");
						fwrite($tmpfp, "    <flags>$flags</flags>\n");
						fwrite($tmpfp, "    <date>$date</date>\n");
						fwrite($tmpfp, "    <text>$text</text>\n");
						fwrite($tmpfp, "  </comment>\n");
					}
				}
			}
			break;
		case 3:
			if($element == 'NUM')
				$state = 2;
			break;
		case 4:
			if($element == 'NAME')
				$state = 2;
			break;
		case 5:
			if($element == 'EMAIL')
				$state = 2;
			break;
		case 6:
			if($element == 'DATE')
				$state = 2;
			break;
		case 7:
			if($element == 'FLAGS')
				$state = 2;
			break;
		case 8:
			if($element == 'TEXT')
				$state = 2;
			break;
		case 9:
			if($element == 'CHAPTER')
				$state = 2;
			break;
		case 100:
			if($element == 'USERINFO') {
				$state = 1;
				if($function >= 1) {
					/* Write user info to tmp file */
					fwrite($tmpfp, "<userinfo>\n");
					fwrite($tmpfp, "  <usermail>$usermail</usermail>\n");
					fwrite($tmpfp, "  <userpasswd>$userpasswd</userpasswd>\n");
					fwrite($tmpfp, "</userinfo>\n");
				}
			}
			break;
		case 101:
			if($element == 'USERMAIL') {
				$state = 100;
			}
			break;
		case 102:
			if($element == 'USERPASSWD') {
				$state = 100;
			}
			break;
	}
}

function char($parser, $data) {
	global $state, $lastnum, $cname, $email, $date, $flags, $text, $chapter, $usermail, $userpasswd;

	switch($state) {
		case 3:	/* Get number */
			$lastnum .= $data;
			break;
		case 4:
			$cname .= $data;
			break;
		case 5:
			$email .= $data;
			break;
		case 6:
			$date .= $data;
			break;
		case 7:
			$flags .= $data;
			break;
		case 8:
			$text .= $data;
			break;
		case 9:
			$chapter .= $data;
			break;
		case 101:
			$usermail .= $data;
			break;
		case 102:
			$userpasswd .= $data;
			break;
	}
}

xml_set_element_handler($parser, "start", "stop");
xml_set_character_data_handler($parser, "char");

$fp = fopen($filename, "r");

while($data = fread($fp, 4096)) {	// Read 4096 chars at a time
	xml_parse($parser, $data, feof($fp));
}

xml_parser_free($parser);

fclose($fp);

if($function == 1) {	/* We are adding a new comment */
	$lastnum++;
	$date = date('d-m-Y');
	if($errnum == 0) {
		$text = nl2br(htmlspecialchars($text, ENT_QUOTES));
		fwrite($tmpfp, "  <comment>\n");
		fwrite($tmpfp, "    <num>$lastnum</num>\n");
		fwrite($tmpfp, "    <chapter>$cchapter</chapter>\n");
		fwrite($tmpfp, "    <name>$_cname</name>\n");
		fwrite($tmpfp, "    <email>$cemail</email>\n");
		fwrite($tmpfp, "    <flags>$cflags</flags>\n");
		fwrite($tmpfp, "    <date>$date</date>\n");
		fwrite($tmpfp, "    <text>$ctext</text>\n");
		fwrite($tmpfp, "  </comment>\n");
	}
}
if($function >= 1) {
	fwrite($tmpfp, "</comments>\n");
	fclose($tmpfp);
	copy($tmpfilename, $filename);
}
if($function == 0) {
	exit;
}

$parser = xml_parser_create();

function start1($parser, $element, $attrs) {
	global $state;
	global $chapter, $cchapter, $lastnum, $cname, $email, $flags, $date, $text;

	switch($state) {
		case 0:		// Waiting for root tag (comments)
			if($element == 'COMMENTS')
				$state = 1;
			break;
		case 1:		// Waiting for comment tag
			if($element == 'COMMENT')
				$state = 2;
			break;
		case 2:		// Inside comment tag
			if($element == 'NUM') {
				$state = 3;		/* Get comment number */
				$lastnum = '';
			}
			else if($element == 'NAME') {
				$state = 4;		/* Get commenter name */
				$cname = '';
			}
			else if($element == 'EMAIL') {
				$state = 5;		/* Get commenter email */
				$email = '';
			}
			else if($element == 'DATE') {
				$state = 6;		/* Get comment date */
				$date = '';
			}
			else if($element == 'FLAGS') {
				$state = 7;		/* Get flags */
				$date = '';
			}
			else if($element == 'TEXT') {
				$state = 8;		/* Get comment text */
				$text = '';
			}
			else if($element == 'CHAPTER') {
				$state = 9;		/* Get chapter id */
				$chapter = '';
			}
			break;
	}
}

/*
 | The real work is done here, since here we already have the data
 */
function stop1($parser, $element) {
	global $state;
	global $chapter, $cchapter, $lastnum, $cname, $email, $flags, $date, $text;
	global $logedin;

	switch($state) {
		case 1:
			if($element == 'COMMENTS') {
				$state = 0;
			}
			break;
		case 2:
			if($element == 'COMMENT') {
				$state = 1;
				/* The comment is done, see what we have to do with it */
				if($chapter == $cchapter) {
					print "<div class=\"cdiv\">\n";
					print "<table><tr><td>\n";
					$gurl = 'http://www.gravatar.com/avatar/';
				    $gurl .= md5( strtolower( trim( $email ) ) );
				    $gurl .= "?s=24&d=identicon&r=g";
					print "<img src=\"$gurl\" height=\"24\" alt=\"avatar\" > &nbsp;";
					print "</td><td class=\"cname\">$cname<br />$date</td>\n";
					print "</tr></table>\n";
					print "$text\n";
					if($logedin) {
						$url = "comments.php?book=$book&chapter=$cchapter&action=delcomment&num=$lastnum";
						$l = _("Delete comment");
						print "<div style=\"cursor:pointer\" onClick=\"ajaxLoad('$url', 'comments')\">$l</div>\n";
					}
					print "</div>\n";
				}
			}
			break;
		case 3:
			if($element == 'NUM')
				$state = 2;
			break;
		case 4:
			if($element == 'NAME')
				$state = 2;
			break;
		case 5:
			if($element == 'EMAIL')
				$state = 2;
			break;
		case 6:
			if($element == 'DATE')
				$state = 2;
			break;
		case 7:
			if($element == 'FLAGS')
				$state = 2;
			break;
		case 8:
			if($element == 'TEXT')
				$state = 2;
			break;
		case 9:
			if($element == 'CHAPTER')
				$state = 2;
			break;
	}
}

function char1($parser, $data) {
	global $state, $lastnum, $cname, $email, $date, $flags, $text, $chapter, $useremail, $userpasswd;

	switch($state) {
		case 3:	/* Get number */
			$lastnum .= $data;
			break;
		case 4:
			$cname .= $data;
			break;
		case 5:
			$email .= $data;
			break;
		case 6:
			$date .= $data;
			break;
		case 7:
			$flags .= $data;
			break;
		case 8:
			$text .= $data;
			break;
		case 9:
			$chapter .= $data;
			break;
	}
}

xml_set_element_handler($parser, "start1", "stop1");
xml_set_character_data_handler($parser, "char1");

$fp = fopen($filename, "r");

while($data = fread($fp, 4096)) {	// Read 4096 chars at a time
	xml_parse($parser, $data, feof($fp));
}

xml_parser_free($parser);

fclose($fp);

?>

