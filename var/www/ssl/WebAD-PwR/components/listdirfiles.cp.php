<?php
//version 1.0
  function getDirectoryList ($directory)
  {

    // create an array to hold directory list
    $results = array();

    // create a handler for the directory
    $handler = opendir($directory);

    // open directory and walk through the filenames
    while ($file = readdir($handler)) {

      // if file isn't this directory or its parent or on the band files list, add it to the results
      if ($file != "." && $file != ".." && $file != "index.php") {
        $results[] = $file;
      }

    }

    // tidy up: close the handler
    closedir($handler);

    // done!
    return $results;

  }

?>

