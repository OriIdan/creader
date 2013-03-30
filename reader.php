<?PHP
/*
 | Read contents of reader.html in given path
 */
$basename = isset($_GET['name']) ? $_GET['name'] : '';

$reader = "data/$basename/reader.html";

print file_get_contents($reader);

?>

