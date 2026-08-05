<?php
header("Content-Type: text/html; charset=ISO-8859-1");
require_once("lib/dbTools.php");
require_once("lib/utils.php");
require_once("lib/design-white.class.php");
$db = new dbTool();
date_default_timezone_set("Asia/Kolkata");
define('CLUB_NAME', 'ROYAL WESTERN INDIA TURF CLUB.');
define('CURRENT_SEASON', '');
//print_r($_SERVER);
//$base = 'C:/xampp/htdocs/rwitc_website/';//$_SERVER['DOCUMENT_ROOT'];
// $base = '/var/www/html/rwitc_website/';//$_SERVER['DOCUMENT_ROOT'];


// $base = 'C:/xampp/htdocs/rwitc-website/';
$base = '/var/www/html/customers/rwitc-website/';


//$http_base = 'http://localhost:8012/rwitc_website/';//$_SERVER['DOCUMENT_ROOT'];
//$http_base = 'https://rw1.space2let.com/~rwitc/';//$_SERVER['DOCUMENT_ROOT'];

// $http_base = 'https://rwitc.com/';
$http_base = 'http://91.99.229.154/rwitc-website/';
//define('BASE_HREF',"http://localhost:8012/rwitc_website/"); // rwitcmumbai page ID
//define('BASE_HREF',"https://rw1.space2let.com/~rwitc/"); // rwitcmumbai page ID
// define('BASE_HREF',"https://rwitc.com/");
define('BASE_HREF', "http://91.99.229.154/rwitc-website/");
//define('DIR_BASE',"C:/xampp/htdocs/rwitc_website/"); // rwitcmumbai page ID
//define('DIR_BASE',"https://rw1.space2let.com/~rwitc/"); // rwitcmumbai page ID
// define('DIR_BASE',"/var/www/html/rwitc_website/");


// define('DIR_BASE', "C:/xampp/htdocs/rwitc-website/");
define('DIR_BASE', "/var/www/html/customers/rwitc-website/");

define('SWEEPSTAKES_BASE', 'staticpages/sweepstakes');

define('DIVIDENDS_BASE', 'staticpages/dividends');

define('RACEREPORTS_BASE', 'staticpages/racedayreports');

define('RATINGSCHANGE_BASE', 'staticpages/ratingschange');

define('STEWARDS_REPORT_BASE', 'staticpages/stewards_report');

define('GALLERY_BASE', 'images/gallery');

define('SPONSOR_GALLERY_BASE', 'rwitc_upload/sponsor_gallery');

// no base needed as the path is relative of SEVER TO ROOT

define('DOWNLOADFILE_BASE', 'rwitc_upload/static/print');

define('DIR_UPLOAD', 'images');

define('HTTP_BANNER_UPLOAD', $http_base . 'images');
define('DIR_BANNER_UPLOAD', $base . 'images');

define('DIR_SPONSOR_UPLOAD', $base . 'images/sponsors');
define('HTTP_SPONSOR_UPLOAD', $http_base . 'images/sponsors');


define('DIR_HOMEPOPUP_UPLOAD', $base . 'images/homepopup');
define('HTTP_HOMEPOPUP_UPLOAD', $http_base . 'images/homepopup');

define('DIR_ATTACHMENT_UPLOAD', $base . 'images/mail_attachment');
define('HTTP_ATTACHMENT_UPLOAD', $http_base . 'images/mail_attachment');

define('HTTP_ATTACHMENT_DBF', $http_base . 'rwitc_upload/static/print');

define('XMLFILE_BASE', $base . 'xml');

include_once('liveconfig.inc');

class RWITC_exception extends Exception {}

//session_save_path($base.'session');
//ini_set('session.gc_probability', 1);
/**
 * Pagination Settings* 
 */
define("ARTICLES_PER_PAGE", 20);
define("TICKERS_PER_PAGE", 20);
define('FB_ARTICLES_APPID', '141884759273419');
define('FB_ARTICLES_APPSECRET', 'c5fa179eac3427194f261be594928a6e');
define('PAGEID', "165292783507507"); // rwitcmumbai page ID
/*
list($mobile,$device) = detectDevice();
if ($mobile) {    
    header('Location: http://www.rwitc.com/phone/landing.html');
}*/
