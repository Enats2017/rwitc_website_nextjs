<?php
$filepath = "/var/www/vhosts/rwitc.com/httpdocs/club/trustee.html";
$filename = "trustee.html";
$html = file_get_contents($filepath);
//echo $html;exit;
header('Content-type: text/html');
header('Content-Disposition: attachment; filename='.$filename);
echo $html;
?>
