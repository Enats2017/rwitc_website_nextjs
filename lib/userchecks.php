<?php
function islogin(){
    // fix this check
    if (count ($_SESSION) == 0|| count($_COOKIE)==0) {        
        return false;
    }
    if ($_SESSION['email'] == $_COOKIE['email']) { 
       if ($_SESSION['uid'] == $_COOKIE['uid']) {           
           return true;
       } else {
        return false;
       }
    } else {
        return false;
    }
}

function isAdminlogin(){
    // fix this check
    if (count ($_SESSION) == 0) {        
        return false;
    }
    if ($_SESSION['username'] !== "") { 
           
       return true;
    } else {
         return false;
    }
}


function checkLiveAccess($db,$userID){
    include_once('subscriptions.class.php');
    $subs = new Subscriptions($db);
    // get latest active subscription for a userID
    $subInfo = $subs->getLatestActiveSubscription($userID);
    
    $startDate = strtotime($subInfo['start_date']);
    $endDate = strtotime($subInfo['end_date']);
    $now =  strtotime(date("Y-m-d"));
    /**
    * return code list and explanation
    * Case 1: // all good    * 
    * Case 2: (if today is before start date of subscription)
    * Case 0: (subscription expired)
    * 
    */
    if ($now >= $startDate && $now <= $endDate) { // all fine
        return "1";
    } elseif ($now < $startDate) { // if today is before start date of subscription
       return "2";     
    } else {
        return "0"; // subscription expired
    }
}
