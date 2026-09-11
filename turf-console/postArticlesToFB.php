<?php
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
require_once("../lib/users.class.php");
require_once("../lib/facebook/facebook.php");
$uid = $_COOKIE['uid'];             
$userObj = new Users($db);  

$facebook = new Facebook(array(
    'appId'  => FB_ARTICLES_APPID,
    'secret' => FB_ARTICLES_APPSECRET,
    'cookie' => true,
    ));
$articles = new Articles($db);
//$code = getParameterString('code','');

$fbcode =$_REQUEST['code'];

$articleID = getParameterNumber('articleID',0);
$articleDetails = $articles->getArticleByID($articleID);

$params = null;
$page_id = "rwitcmumbai";

$baseurl = "http://www.rwitc.com/admin/postArticlesToFB.php?articleID=".$articleID;

if (isset($fbcode)) {
    
    $token_url="https://graph.facebook.com/oauth/access_token?client_id="
      . FB_ARTICLES_APPID . "&redirect_uri=" . urlencode($baseurl) 
      . "&client_secret=" . FB_ARTICLES_APPSECRET 
      . "&code=" . $fbcode . "&display=popup";
    
    
    $response = file_get_contents($token_url);   
    
    parse_str($response, $params);
    $token = $params['access_token'];
    
    $pageAccessTokenUrl = "https://graph.facebook.com/me/accounts?access_token={$token}";
    $pageTokenResponse = file_get_contents($pageAccessTokenUrl); 
    //print_r($pageTokenResponse);
    $adminAppsArr =  json_decode($pageTokenResponse);
   
    foreach($adminAppsArr->data as $apps) {
        if ($apps->id == PAGEID) {
            $pageToken = $apps->access_token;
            $attachment = array
             (
                'access_token'=>$apps->access_token,
                'message' => '#RWITCArticles',
                'name' => $articleDetails['title'],
                'link' => 'http://www.rwitc.com/viewArticles.php?id='.$articleID,
                'picture' => 'http://www.rwitc.com/images/rwitc-logo.jpg'
                
             );
             
            $result = $facebook->api('/rwitcmumbai/feed/','POST',$attachment);
            if (isset($result['id']) && $result['id'] !== "") {
                echo "<script type='text/javascript'>";
                echo "alert('Posted To FB');";
                echo "self.close();";
                echo "</script>";
            }
        } 
    }
 }