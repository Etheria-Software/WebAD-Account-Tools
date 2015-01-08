<?php
// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
// Additional headers
$headers .= 'From: WebADTools <noreply@webadtools.systemsmanagment.company.com>' . "\r\n";

$message = "<br />";

$message = $message."<title>"."Test"."</title>";
$message = $message."<H1><center><u>".Test."</u></center></H1>";
$subject = "Test message"; 
$mailto = "user@company.com";
mail($mailto, $subject, $message, $headers);
?>
