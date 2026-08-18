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

    // fix this check
    if (count($_SESSION) == 0) {
        return false;
    }

    if (
        isset($_SESSION['username']) &&
        $_SESSION['username'] !== ""
    ) {

        return true;

    } else {

        return false;
    }
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
| User Group ID 1 = Super Admin
|
*/

function isSuperAdmin()
{
    return (
        isset($_SESSION['user_group_id']) &&
        (int)$_SESSION['user_group_id'] === 1
    );
}


/*
|--------------------------------------------------------------------------
| PERMISSION CHECK
|--------------------------------------------------------------------------
|
| Group ID 1 = Full access
|
| Other groups:
| access permission ke andar module hona chahiye.
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
     * Normal group permission
     */
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