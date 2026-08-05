<?php
$base = 'C:/xampp/htdocs/rwitc_website/';
if (!session_id()) {
  session_start();
}
session_save_path($base.'session');
ini_set('session.gc_probability', 1);
include("phptextClass.php");	
/*create class object*/
$phptextObj = new phptextClass();	
/*phptext function to genrate image with text*/
$phptextObj->phpcaptcha('#162453','#fff',120,40,10,25);	
?>