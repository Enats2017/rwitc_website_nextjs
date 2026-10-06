<?php

function islogin(){

    // fix this check
    if (count($_SESSION) == 0 || count($_COOKIE) == 0) {
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
    if (session_status() === PHP_SESSION_NONE) { session_start();}
    if ( !isset($_SESSION['username']) || $_SESSION['username'] === "") {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        header( "Location: index.php?uri=" . urlencode($currentUrl));
        exit;
    }
    return true;
}


function checkLiveAccess($db, $userID){

    include_once('subscriptions.class.php');

    $subs = new Subscriptions($db);

    // get latest active subscription for a userID
    $subInfo = $subs->getLatestActiveSubscription($userID);

    $startDate = strtotime($subInfo['start_date']);
    $endDate = strtotime($subInfo['end_date']);
    $now = strtotime(date("Y-m-d"));

    /**
     * return code list and explanation
     *
     * Case 1: all good
     * Case 2: today is before start date
     * Case 0: subscription expired
     */

    if ($now >= $startDate && $now <= $endDate) {

        // all fine
        return "1";

    } elseif ($now < $startDate) {

        // today is before start date
        return "2";

    } else {

        // subscription expired
        return "0";
    }
}


/*
|--------------------------------------------------------------------------
| SUPER ADMIN CHECK
|--------------------------------------------------------------------------
|
| Admin table ID 1 = Super Admin
|
| Super Admin is NOT identified by user_group_id.
|
*/

function isSuperAdmin()
{
    return (
        isset($_SESSION['uid']) &&
        isset($_SESSION['role']) &&
        (int)$_SESSION['uid'] === 19 &&
        strtoupper((string)$_SESSION['role']) === 'ADMIN'
    );
}


/*
|--------------------------------------------------------------------------
| PERMISSION CHECK
|--------------------------------------------------------------------------
|
| Super Admin = admins.id 1
|
| Other admins:
| user_group_id ke according permission milegi.
|
*/

function hasPermission($permissionKey)
{
    /*
     * Super Admin
     */
    if (isSuperAdmin()) {
        return true;
    }


    /*
     * Normal admin: DB based 
     */
    if (function_exists('hasModuleAccess')) {
        return hasModuleAccess($permissionKey);
    }

    if (
        !isset($_SESSION['permissions']) ||
        !isset($_SESSION['permissions']['access']) ||
        !is_array($_SESSION['permissions']['access'])
    ) {
        return false;
    }


    return in_array(
        $permissionKey,
        $_SESSION['permissions']['access'],
        true
    );
}


/*
|--------------------------------------------------------------------------
| PAGE ACCESS CHECK
|--------------------------------------------------------------------------
*/

function checkPermission($permissionKey)
{
    if (!hasPermission($permissionKey)) {

        echo '
            <h2
                style="
                    color:red;
                    text-align:center;
                    margin-top:50px;
                "
            >
                Access Denied
            </h2>
        ';

        exit;
    }
}