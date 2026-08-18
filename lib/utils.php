<?php

function getParameterString($param,$default="",$db=null,$convertQuotes=true)
{
	if(isset($_REQUEST[$param]))
		if($db!=null)
			if($convertQuotes)
				return $db->escape(convertSpecialQuotes($_REQUEST[$param]));
			else	
				return $db->escape($_REQUEST[$param]);
		else
			return $_REQUEST[$param];
	else
		if($db!=null)
			return $db->escape($default);
		else
			return $default;
}

function getParameterString_custom($param,$default="",$db=null,$convertQuotes=true) {
    if($db != null){
        if($convertQuotes){
            return $db->escape(convertSpecialQuotes($param));
        } else {    
            return $db->escape($param);
        }
    } else {
        return $param;
    }
}

function getParameterNumber($param,$default=0)
{
	
	if(isset($_REQUEST[$param]))
	{
		$tmp = trim($_REQUEST[$param]);
		if(is_numeric($tmp))
			return $tmp;
	}

	return $default;
}

function getPostParameterStringArray($db=null)
{
	$cleanPostParams = array();

	if($db!=null)
	{
		foreach($_POST as $name => $value) // $_POST variables cleaned using $db->escape()
		{
			$cleanPostParams [$db->escape($name)] = $db->escape($value);
		}
	}

 	return $cleanPostParams;
}

function checkParameter($param,$value=null)
{
	if($value==null) {
		if(isset($_REQUEST[$param]) && trim($_REQUEST[$param])!='')
			return true;
	}else {
		if(isset($_REQUEST[$param]) && trim($_REQUEST[$param])==$value)
			return true;		
	}
	
	return false;
}

// this function converts the special quotes - left and right versions - to normal ones. 
// These left and right versions often appear when cutting and pasting text from Word.
function convertSpecialQuotes($str)
{
	// single left - 145
	// single right - 146
	// double left - 147
	// double right - 148
	
	for($i=0;$i<strlen($str);$i++)
	{
	//	error_log("position $i: [".$str{$i}."] ".ord($str{$i}));
      
      //Deperaction warnning to this code
      
		//if(ord($str{$i})==145 || ord($str{$i})==146)
		//	$str{$i} ="'";
		//else if(ord($str{$i})==147 || ord($str{$i})==148)
		//	$str{$i} ='"';
      
      //Deperaction warnning to this code end
      
      
      //deperaction solution 
      
      if(ord($str[$i])==145 || ord($str[$i])==146)
			$str[$i] ="'";
		else if(ord($str[$i])==147 || ord($str[$i])==148)
			$str[$i] ='"';
       //deperaction solution end
	}
	return $str;
	
}
function drawOptionsFromArray($array, $selected)
{
	foreach($array as $value)
    {   echo $value;
    	if($selected == $value)
        	$sel = 'selected="selected"';
        else
        	$sel = "";
           
    	echo "<option value=\"$value\" $sel>$value</option>\n";
	}
}

function drawOptionsFromHashtable($hash, $selected)
{
	if($hash!=null)
	{
		foreach($hash as $key => $value)
	    {
	    	if($selected == $key)
	        	$sel = 'selected="selected"';
	        else
	        	$sel = "";
	           
	    	echo "<option value=\"$key\" $sel>$value</option>\n";
		}
	}
}
function drawOptionsFromHashtableMultipleSelected($hash, $options_selected)
{
	foreach($hash as $key => $value) {
	    foreach($options_selected as $selected) {	
	    	if($selected == $key) {
	        	$sel = 'selected="selected"';
	        	break;
	        }else {
	        	$sel = "";
	        }
       }    
    	echo "<option value=\"$key\" $sel>$value</option>\n";
	}
}

function msort($array, $id) {
    $temp_array = array();
    while(count($array)>0) {
        $lowest_id = 0;
        $index=0;
        foreach ($array as $item) {
            if (isset($item[$id]) && $array[$lowest_id][$id]) {
                if ($item[$id] < $array[$lowest_id][$id]) {
                    $lowest_id = $index;
                }
            }
            $index++;
        }
        $temp_array[] = $array[$lowest_id];
        $array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
    }
    return $temp_array;
}

// From yyyy-mm-dd to dd-mm-yyyy or dd-mm-yyyy to yyyy-mm-dd
function reverseDateFormat($date) {
	$date_arr = explode("-", $date);
	return $date_arr[2]."-".$date_arr[1]."-".$date_arr[0];
}

// From dd-mm-yyyy hh:mm to yyyy-mm-dd hh:mm:ss
function convertDateTimeFormat($dateTime) {
	list($date, $time) = split(' ', $dateTime);
	$date_converted = reverseDateFormat($date);
	$time_converted = $time.":00";
	return $date_converted." ".$time_converted;			
}		
	
// Test if two dates in dd-mm-yyyy format span months
function ifSpanMonth($dateStart,$dateEnd) {
	$dateStart_arr = explode("-", $dateStart);
	$dateEnd_arr = explode("-", $dateEnd);
	if( ($dateStart_arr[2] < $dateEnd_arr[2]) || 
			( ($dateStart_arr[2] == $dateEnd_arr[2]) && ($dateStart_arr[1] < $dateEnd_arr[1]) ) ) {
		return true;
	}
}

if (!function_exists('cal_days_in_month')){
  function cal_days_in_month($a_null, $a_month, $a_year) {
     return date('t', mktime(0, 0, 0, $a_month+1, 0, $a_year));
  }
}

// convert from #xxxxxx to 0xyyyyyy
function colorHtmlToHex($htmlCode)
{
   $red_value = substr($htmlCode, 1, 2);
   $green_value = substr($htmlCode, 3, 2);
   $blue_value = substr($htmlCode, 5, 2);
   
   $hex_value = "0x".$blue_value.$green_value.$red_value;
   return $hex_value;    
}

// convert from yyyyyy to #xxxxxx
function colorHexToHtml($hexValue)
{
	 $hexValueLength = strlen($hexValue);
	 
	 if($hexValueLength == 5) {
	 	 $hexValue = "0".$hexValue;
	 }else if($hexValueLength == 4) {
	 	 $hexValue = "00".$hexValue;
	 }else if($hexValueLength == 3) {
	 	 $hexValue = "000".$hexValue;
	 }else if($hexValueLength == 2) {
	 	 $hexValue = "0000".$hexValue;
	 }else if($hexValueLength == 1) {
	 	 $hexValue = "00000".$hexValue;
	 }
	
   $hexValue_0 = substr($hexValue, 0, 2);
   $hexValue_1 = substr($hexValue, 2, 2);
   $hexValue_2 = substr($hexValue, 4, 2);
   
   $html_value = "#".$hexValue_2.$hexValue_1.$hexValue_0;
   return $html_value;    
}

function byteConvert($bytes)
{
    $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
    $e = floor(log($bytes)/log(1024));
    
    if($bytes > 0) {
    	return sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
    }else {
    	return 0;	
    }
}
/**
* Returns the file extension from the FileName (without the dot)
*
* 
* @param mixed $fileName
*/
function getFileExtension($fileName){
    $tempArray = split('\.',$fileName);
    $noOfElements = sizeof($tempArray);
    return strtolower($tempArray[$noOfElements-1]);    // return last array index in lowercase
}

/**
* Convert date to array
* @param $dateVal - datetime value
* @returns an array
* [1]=>year
* [2]=>month
* [3]=>date
* [4]=>hours
* [5]=>mintues
* [6]=>seconds
* 
*/
function dateToArray($dateVal) {    
    preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s+(\d\d):(\d\d):(\d\d)/',$dateVal,$matches);
    return $matches;
}

function createMonthSelectTag($fieldName,$selected="01"){
    $monthArray = array("00"=>"Present","01"=>"Jan","02"=>"Feb","03"=>"Mar","04"=>"Apr","05"=>"May","06"=>"Jun",
                        "07"=>"Jul","08"=>"Aug","09"=>"Sep","10"=>"Oct","11"=>"Nov","12"=>"Dec");
    $selectTag = "<select name='$fieldName'>\n";
    foreach($monthArray as $key => $value) {
        if($selected == $key) {
            $sel = 'selected="selected"';
        }else {
            $sel = "";
        }
        $selectTag .= "<option value=\"$key\" $sel>$value</option>\n";
    }
    $selectTag .= "</select>\n";
    return $selectTag;
}

function createYearSelectTag($fieldName,$startYear,$endYear,$selected="0000"){
    $yearArray = array();
    for($i=$endYear;$i>=$startYear;$i--) {
        array_push ($yearArray,"$i");
    }
    
    $selectTag = "<select name='$fieldName'>\n";
     if ($selected == "0000") {
           $sel = 'selected="selected"';     
           $selectTag .= "<option value=\"0000\" $sel>Present</option>\n";
    }
    else {
        $selectTag .= "<option value=\"0000\">Present</option>\n";
    }
    foreach($yearArray as $value)
    {  
        if($selected == $value)
            $sel = 'selected="selected"';
        else
            $sel = "";
           
        $selectTag .= "<option value=\"$value\" $sel>$value</option>\n";
    }
    $selectTag .= "</select>\n";
    return $selectTag;
}

/**
* Function to create an HTML select tag based from 
* start to end and all values in between
* It can also assign the default selected value in the dropdown
* 
* @param html field name $fieldName - will be used in the name attribute of the select tag
* @param integer $start - first value in the drop down
* @param integer $end   - last value in the drop down
* @param integer $selected - default selected value in the drop down
*/
function createNumberSelectTag($fieldName,$start,$end,$selected=1){
    $numberArray = array();
    for($i=$start;$i<=$end;$i++) {
        array_push ($numberArray,"$i");
    }
    
    $selectTag = "<select name='$fieldName'>\n";
    
    foreach($numberArray as $value)
    {  
        if($selected == $value)
            $sel = 'selected="selected"';
        else
            $sel = "";
           
        $selectTag .= "<option value=\"$value\" $sel>$value</option>\n";
    }
    $selectTag .= "</select>\n";
    return $selectTag;
}



function convertDecimalToFractionString($decNo) {  
    preg_match('/(\d*)\.(\d*)/',$decNo,$matchedNos);
    $fraction = '';
    if ($matchedNos[1]) {
        $fraction = $matchedNos[1] ." ";
    }
    if ($matchedNos[2]==25) {
        $fraction .= "1/4";
    }
    if ($matchedNos[2]==50) {
        $fraction .= "1/2";
    }
    if ($matchedNos[2]==75) {
        $fraction .= "3/4";
    }
    //echo $fraction ."<br />";
    return $fraction;
    //print_r($matchedNos);
}


/**
* A mailing function which sens mail
* 
* @param string $from
* @param string $to
* @param string $subject
* @param string $body
* @param string $cc
* @param string $bcc
* @returns successful or unsuccessful message
* 
*/
// function mailer($from,$fromName='',$to,$toName='',$subject,$body,$cc='',$bcc='',$attachmentStub='') {
function mailer($from,$fromName='',$to='',$toName='',$subject='',$body='',$cc='',$bcc='',$attachmentStub='') {
    if (empty($fromName)) {
      $fromName = $from;
    }
    if (empty($toName)) {
      $toName = $to;
    }
    
    include_once('phpmailer/class.phpmailer.php'); 
    $mail             = new PHPMailer(true);
    //$mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
                                               // 1 = errors and messages
                                               // 2 = messages only
    $mail->SMTPAuth   = true;                  // enable SMTP authentication
    //$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
    $mail->Host       = "smtp.rwitc.com";      // sets GMAIL as the SMTP server
    $mail->Port       = 26;                   // set the SMTP port for the GMAIL server
    $mail->Username   = "web@rwitc.com";  // GMAIL username
    $mail->Password   = ")web050";
    $mail->FromName = $fromName;
    $mail->SetFrom($from,$fromName);
    $mail->AddReplyTo($from,$fromName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
   // $mail->AltBody    = $body; //Text Body
   // $mail->WordWrap   = 70; // set word wrap
    //$address = "$to";   
    $toArr = explode(",",$to);   
    foreach ($toArr as $toAddr)  {        
        $mail->AddAddress($toAddr);
    }
    if ($cc != '') {
        $mail->AddCC($cc,$cc);
    }
    if ($bcc != '') {
        $mail->AddBCC($bcc,$bcc);
    }
    if ($attachmentStub !== "") {
        $attachment = $_SERVER['DOCUMENT_ROOT'] . $attachmentStub;     
        $mail->AddAttachment($attachment);
    }
    if(!$mail->Send()) {
        return false;
    } else {
        return true;
    }    
}
function detectDevice() {
        $device = "";
        $mobile=false;
        $userAgent = $_SERVER['HTTP_USER_AGENT'];         
            switch (true) {
                case (preg_match('/Android/i',$userAgent));
                    if (preg_match('/Mobile/i',$userAgent)) {       
                        $mobile = true;
                       $device = "Android";     
                    }  else {
                        $mobile = false;
                        $device = "Android/Tablet";
                    }   
                break;
                case (preg_match('/iphone/i',$userAgent));
                    $mobile = true;
                    $device = "Iphone";
                break;
                case (preg_match('/ipad/i',$userAgent));
                    $mobile = true;
                    $device = "Ipad";
                break;
                case (preg_match('/blackberry*/i',$userAgent));
                    $mobile = true;
                    $device = "BlackBerry";
                break;
                case (preg_match('/wap*/i',$userAgent));
                    $mobile = true;
                    $device = "WAP Based";
                break;
                case (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i',$userAgent));
                    $mobile = true;
                    $device = "Palm";
                break;    
                case (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i',$userAgent));
                    $mobile = true;
                    $device = "Windows";
                break;    
                case (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i',$userAgent)); 
                    $mobile = true;
                    $device = "WAP Based Phone";
                break;
                case (isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE']));
                  $mobile = true; 
                  $device = 'Mobile identified by Headers';
                break;
                default: 
                    $mobile = false;
                    $device = "Desktop";
            }
        return array($mobile,$device);
}

// function mailer1($from,$fromName='',$to,$toName='',$subject,$body,$cc='',$bcc='',$attachmentStub='') {
function mailer1($from,$fromName='',$to='',$toName='',$subject='',$body='',$cc='',$bcc='',$attachmentStub='') {
    if (empty($fromName)) {
      $fromName = $from;
    }
    if (empty($toName)) {
      $toName = $to;
    }
    
    if($attachmentStub != ''){
        $http_upload_path = HTTP_ATTACHMENT_DBF.'/';
        $destFile_http = $http_upload_path . $attachmentStub;
        $attachment_array[] = $destFile_http;
    } else {
        $attachment_array = array();
    }
    
    require('Mailin.php');
    $mailin = new Mailin("https://api.sendinblue.com/v2.0","N12OPU6qLfTsxnK7");
    $data = array("to" => array($to => $toName),
                "from" => array($from, $fromName),
                "subject" => $subject,
                "html" => html_entity_decode(trim($body)),
                "attachment" => $attachment_array
            );
    // echo '<pre>';
    // print_r($data);
    // exit;
    $response = $mailin->send_email($data);
    if($response['code'] == 'success'){
        //$msg = "Mail Sent";
        return true;
    } else {
        return false;
        //$msg = "Please try again later";
    }
}