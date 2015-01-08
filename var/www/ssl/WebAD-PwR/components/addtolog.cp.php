<?php
//version 1.0
function addtolog($logfile,$stufftolog)
{ 
$todaynow = date("Y/M/D d -- H:i:s");
$file = fopen($logfile, "a");
fputs($file, "$stufftolog		($todaynow)\r\n");
fclose($file);
}
?> 
