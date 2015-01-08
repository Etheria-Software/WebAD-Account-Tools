
<?php
//version 2.0

$tempFile = tempnam(sys_get_temp_dir(), 'image');
$imageString=$userpicstring;
file_put_contents($tempFile, $imageString);
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = explode(';', $finfo->file($tempFile));
echo '<img width='.$userimgsizeWidth.' src="data:' . $mime[0] . ';base64,' . base64_encode($imageString) . '"/>';
?>

