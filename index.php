<?PHP
$epubname = isset($_GET['epub']) ? $_GET['epub'] : '';

if($epubname != '') {
	$fname = escapeshellcmd("data/$epubname");
	system("perl readepub.pl $fname > /dev/null");
	$pathparts = pathinfo("data/$epubname");
//	print_r($pathparts);
	$dir = $pathparts['dirname'];
	$basename = $pathparts['filename'];
}
include("data/$basename/i18n.inc.php");
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Helicon books online reader</title>
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="ajaxsubmit.js"></script>
    <link rel="stylesheet" type="text/css" href="bootstrap.css" />
    <style>
    .top {
		background-color:#313131;
		background-image: linear-gradient(to bottom, #313131 0%, #232323 100%);
		color:white;
		height:60px;
	}
	.top h1 {
		margin-top:25px;
		margin-right:30px;
		margin-left:30px;
	}
    </style>
<?PHP
	print "<link rel=\"stylesheet\" type=\"text/css\" href=\"data/$basename/main.css\" />\n";
?>
	<script type="text/javascript">
	$(window).resize(function() {
		var h = $(window).height() - 20;
		var w = $(document).width();
		$('#reader').height(h);
		if(w < 700) {
			$('#meta').hide();
			$('#reader').width('100%');
		}
		else {
			$('#reader').width('50%');
			$('#meta').show();
		}
	});
	$(document).ready(function() {
		var h = $(window).height() - 20;
		var w = $(document).width();
		$('#reader').height(h);
		if(w < 700) {
			$('#meta').hide();
			$('#reader').width('100%');
		}
		else {
			$('#reader').width('50%');
			$('#meta').show();
		}
	});

	function ajaxLoad(url, mydiv) {
		var xmlhttp;
	
		document.getElementById(mydiv).innerHTML = '<div style="text-align:center"><img src="images/ajax-loader.gif" /></div>';
	
		if(window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		}
		else {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	
		xmlhttp.onreadystatechange=function() {
			if((xmlhttp.readyState == 4) && (xmlhttp.status == 200)) {
				document.getElementById(mydiv).innerHTML = xmlhttp.responseText;
			}
		}
		xmlhttp.open("GET",url,true);
		xmlhttp.send();
	}
	
	function setCookie(c_name,value,exdays) {
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
		document.cookie=c_name + "=" + c_value;
	}
	</script>
  </head>
  <body>
	<div id="reader" class="col1">
	
<?PHP
	print "<iframe src=\"reader.php?name=$basename\" height=\"100%\" width=\"100%\" frameborder=\"1\"></iframe>\n";
?>
	
	</div>
	<div id="meta" class="col2">
	<div class="top">
	<a href="http://www.heliconbooks.com"><img src="images/logo.png" align="left" height="60"></a>
	&nbsp;&nbsp;
<?PHP
	$l = _("Helicon Books cloud based reader demo");
	print "<h2>$l</h2>\n";
?>
	</div>
<?PHP
	print file_get_contents("data/$basename/metadata.html");
?>
	</div>
	<div id="debug" style="clear:both">&nbsp;</div>
	<div id="comments" class="col1">
	
	</div>

  </body>

</html>
