<?php

//print_r($_SERVER);

error_reporting(1);

ini_set('display_errors','On');

require_once("../bootstrap.php");

require_once("../lib/users.class.php");

$siteuser = new Users($db);

//$role = "SITE-USER";

require_once("../lib/userchecks.php");

$uri = $_SERVER['REQUEST_URI'];

if(preg_match('/uri=(.+)/is', $uri, $matches)) {

    $uri = $matches[1];              

} else {

    $uri = getParameterString("uri","dashboard.php");  

}

//echo $uri;exit;

session_start();

$q = getParameterString('q','', $db,true);

if (isset($_SESSION['login_id']) || $q == "logout" ) {    

    $_SESSION = array();

    // Note: This will destroy the session, and not just the session data!

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', time() - 42000,

            $params["path"], $params["domain"],

            $params["secure"], $params["httponly"]

        );            

    }

// Finally, destroy the session.

    session_destroy();

    if ($q == "logout") {

       $msg = "You have been successfully logged out";         

    }

}

$msg = "";

if ($q == "login-user") {

    try {

        

        $username = getParameterString('username','', $db,true);

        $password = getParameterString('password','', $db,true);

        

        $userDetails = $siteuser->checkAdminUser($username, $password);

         if(empty($userDetails)){
            $msg = "Login failed. Incorrect username or password";
            throw new Exception($msg); // Throw exception to handle empty userDetails
        }
		
        

        if ($userDetails['active']=='N') {

            throw new RWITC_exception("Your account has not been deactivated. Kindly contact the web admin");

        }

        setcookie("username",$userDetails['email'],time()+60*60*24);

        setcookie("uid",$userDetails['id'],time()+60*60*24);

        $_SESSION['username'] = $userDetails['username'];

        $_SESSION['role'] = $userDetails['role'];

        $_SESSION['articles'] = $userDetails['articles'];

        $_SESSION['race_history'] = $userDetails['race_history'];

        $_SESSION['send_mailer'] = $userDetails['send_mailer'];

        $_SESSION['rating_change'] = $userDetails['rating_change'];

        $_SESSION['gallery'] = $userDetails['gallery'];

        $_SESSION['video'] = $userDetails['video'];

        $_SESSION['dividends'] = $userDetails['dividends'];

        $_SESSION['stewards_report'] = $userDetails['stewards_report'];

        $_SESSION['race_day_report'] = $userDetails['race_day_report'];

        $_SESSION['calendar'] = $userDetails['calendar'];

        $_SESSION['prakash_gosavi'] = $userDetails['prakash_gosavi'];

        $_SESSION['shiven_surendranath'] = $userDetails['shiven_surendranath'];

        $_SESSION['polls'] = $userDetails['polls'];

        $_SESSION['adminusers'] = $userDetails['adminusers'];

        $_SESSION['workingManager'] = $userDetails['workingManager'];

        $_SESSION['bannerManager'] = $userDetails['bannerManager'];

        $_SESSION['tickerManager'] = $userDetails['tickerManager'];

        $_SESSION['sponsorManager'] = $userDetails['sponsorManager'];

        $_SESSION['sponsorofthedayManager'] = $userDetails['sponsorofthedayManager'];

        $_SESSION['horseweightManager'] = $userDetails['horseweightManager'];

        $_SESSION['racedataManager'] = $userDetails['racedataManager'];

        $_SESSION['configManager'] = $userDetails['configManager'];

        $_SESSION['mailManager'] = $userDetails['mailManager'];

	    $_SESSION['homepopup'] = $userDetails['homepopup'];

        $_SESSION['uid'] = $userDetails['id'];

        

        //

        //echo $_SERVER['REQUEST_URI'];exit;
     

        header("refresh:2;url=$uri");
      	// header("refresh:2;url=https://rwitc.com/admin/".$uri);
      //header("Location:$uri");

        $msg = "Login Successful.. Redirecting <a href='$uri'>Here</a> in 2 secs";

         //header('Location: mailers.php');

    } catch (RWITC_exception $rwitc_err) {

        session_destroy();

        $msg = $rwitc_err->getMessage();

    }  catch (Exception $err) {

        session_destroy();

        $msg = "Login failed. Incorrect username or password";

    }

}

/*

if ($q=="logout") {

    //session_start();

    $_SESSION = array();

    // Note: This will destroy the session, and not just the session data!

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', time() - 42000,

            $params["path"], $params["domain"],

            $params["secure"], $params["httponly"]

        );

    }                   

    $msg = "You have been successfully logged out";

}

*/

$pageTitle ="RWITC | ".CURRENT_SEASON ." - Admin Login";

$design = new Design();

$design->startPage("$pageTitle");

$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");

$design->openDiv("infoWrapper");

$design->openDiv("leftArea");

?>



<form name="registerFrm" method="post" action="admin/adminlogin.php">

<table class="contentTable">

    <?php if(!empty($msg)) { ?>

    <tr>

        <th colspan="2"><?php echo $msg; ?></th>

    </tr>

    <?php } ?>    <tr>

        <th colspan="2" class="thwhite"><h3>Login</h3></th>

    </tr>

    <tr>

        <th>Username</th>

        <td class="alignLeft"><input type="text" name="username" /></td>

    </tr>

    <tr>

        <th>Password</th>

        <td class="alignLeft"><input type="password" name="password" /></td>

    </tr>

    <tr>

        <td colspan="2">

            <input type="submit" name="submit" value="Login" />

            <input type="reset" name="reset" value="Reset" />

        </td>

    </tr>



</table>

<input type="hidden" name="uri" value="<?php echo $uri; ?>" />

<input type="hidden" name="q" value="login-user" />

</form>



<?php

$design->closeDiv();

$design->rightArea();

$design->closeDiv();

$design->closeDiv();

$design->endPage();

$design = NULL; // release object

