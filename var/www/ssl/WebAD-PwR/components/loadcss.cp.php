<?php

//Version 2.0
//V 1.0 - original
//V 2.0 - converted to dynamic css loader (folder based restriction of css files)


//path to directory to scan
$directory = $csspath;
 
//get all css files with a .css extension.
$csss = glob($directory . "*.css");
 
//print each file name
foreach($csss as $css)
{
print '<link rel="stylesheet" type="text/css" href="'.$css.'" media="screen" /> ';
}

?>
