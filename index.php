<?PHP
$epubname = isset($_GET['epub']) ? $_GET['epub'] : '';

if($epubname != '') {
	$fname = escapeshellcmd("data/$epubname");
	system("perl readepub.pl $fname");
	$pathparts = pathinfo("data/$epubname");
//	print_r($pathparts);
	$dir = $pathparts['dirname'];
	$basename = $pathparts['filename'];
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    />
    <title>Helicon books online reader</title>
    <script type="text/javascript" src="jquery.js"></script>
	<style>
	.col1 {
		width:49%;
		float:left;
	}
	.col2 {
		width:48%;
		float:left;
		margin-left:10px;
	}
	</style>
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
	</script>
  </head>
  <body>
	<div id="reader" class="col1">
<?PHP
	print "<iframe src=\"reader.php?name=$basename\" height=\"100%\" width=\"100%\" frameborder=\"1\"></iframe>\n";
?>
	</div>
	<div id="meta" class="col2">
<?PHP
	print file_get_contents("data/$basename/metadata.html");
?>
	</div>

  </body>

</html>
