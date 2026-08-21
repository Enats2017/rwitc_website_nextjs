<?php
include_once('../bootstrap.php');
//include_once('design.php');
$pageTitle = 'Jockey Weights';
$design = new Design();
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
$design->writeContentPageStyles();

include_once('../rwitc_upload/static/RIDINGWEIGHT.HTM');

$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL; // release object

