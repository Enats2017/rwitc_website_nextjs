<?php

error_reporting(1);
ini_set('display_errors', 'On');
date_default_timezone_set('Asia/Kolkata');
require_once("../bootstrap.php");
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

$siteuser = new Users($db);


/*
|--------------------------------------------------------------------------
| Requested URL
|--------------------------------------------------------------------------
*/

$uri = $_SERVER['REQUEST_URI'];

if (preg_match('/uri=(.+)/is', $uri, $matches)) {

    $uri = $matches[1];
} else {

    $uri = getParameterString("uri", "dashboard.php");
}


/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

session_start();

$q = getParameterString('q', '', $db, true);

$msg = "";

/* Password-change popup (login page par hi dikhta hai) */
$showPwModal = false;
$pwModalUser = '';
$pwModalRedirect = '';


/*
|--------------------------------------------------------------------------
| Logout / Existing Session Cleanup
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['login_id']) || $q == "logout") {

    $_SESSION = array();

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    if ($q == "logout") {

        $msg = "You have been successfully logged out";
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($q == "login-user") {

    try {

        $username = getParameterString('username', '', $db, true);
        $password = getParameterString('password', '', $db, true);


        /* Basic Validation */

        if ($username == '' || $password == '') {

            throw new Exception("Username and password are required.");
        }


        /* 1. FIRST CHECK ADMINS TABLE */

        $userDetails = $siteuser->checkAdminUser($username, $password);

        $isSiteUser = false;


        /* 2. IF NOT ADMIN, CHECK USERS TABLE */

        if (empty($userDetails)) {

            $userDetails = $siteuser->checkSiteUser($username, $password);

            if (!empty($userDetails)) {

                $isSiteUser = true;
            }
        }


        /* LOGIN FAILED */

        if (empty($userDetails)) {

            throw new Exception("Login failed. Incorrect username or password");
        }


        /* ACCOUNT STATUS */

        if (isset($userDetails['active']) && $userDetails['active'] == 'N') {

            throw new RWITC_exception(
                "Your account has been deactivated. Kindly contact the web admin"
            );
        }


        /* COOKIES */

        if (isset($userDetails['email'])) {

            setcookie("username", $userDetails['email'], time() + 60 * 60 * 24, "/");
        }

        if (isset($userDetails['id'])) {

            setcookie("uid", $userDetails['id'], time() + 60 * 60 * 24, "/");
        }


        /* COMMON SESSION */

        $_SESSION['uid'] = isset($userDetails['id']) ? $userDetails['id'] : 0;


        /* USERNAME */

        if (isset($userDetails['username'])) {

            $_SESSION['username'] = $userDetails['username'];
        } elseif (isset($userDetails['firstname']) || isset($userDetails['lastname'])) {

            $_SESSION['username'] = trim(
                (isset($userDetails['firstname']) ? $userDetails['firstname'] : '') .
                    ' ' .
                    (isset($userDetails['lastname']) ? $userDetails['lastname'] : '')
            );
        } else {

            $_SESSION['username'] = $username;
        }


        /* ROLE */

        $_SESSION['role'] = isset($userDetails['role']) ? $userDetails['role'] : 'SITE-USER';


        /* OLD ADMIN PERMISSIONS (existing admin pages still use these) */

        $_SESSION['articles']               = isset($userDetails['articles']) ? $userDetails['articles'] : 'N';
        $_SESSION['race_history']           = isset($userDetails['race_history']) ? $userDetails['race_history'] : 'N';
        $_SESSION['send_mailer']            = isset($userDetails['send_mailer']) ? $userDetails['send_mailer'] : 'N';
        $_SESSION['rating_change']          = isset($userDetails['rating_change']) ? $userDetails['rating_change'] : 'N';
        $_SESSION['gallery']                = isset($userDetails['gallery']) ? $userDetails['gallery'] : 'N';
        $_SESSION['video']                  = isset($userDetails['video']) ? $userDetails['video'] : 'N';
        $_SESSION['dividends']              = isset($userDetails['dividends']) ? $userDetails['dividends'] : 'N';
        $_SESSION['stewards_report']        = isset($userDetails['stewards_report']) ? $userDetails['stewards_report'] : 'N';
        $_SESSION['race_day_report']        = isset($userDetails['race_day_report']) ? $userDetails['race_day_report'] : 'N';
        $_SESSION['calendar']               = isset($userDetails['calendar']) ? $userDetails['calendar'] : 'N';
        $_SESSION['prakash_gosavi']         = isset($userDetails['prakash_gosavi']) ? $userDetails['prakash_gosavi'] : 'N';
        $_SESSION['shiven_surendranath']    = isset($userDetails['shiven_surendranath']) ? $userDetails['shiven_surendranath'] : 'N';
        $_SESSION['polls']                  = isset($userDetails['polls']) ? $userDetails['polls'] : 'N';
        $_SESSION['adminusers']             = isset($userDetails['adminusers']) ? $userDetails['adminusers'] : 'N';
        $_SESSION['workingManager']         = isset($userDetails['workingManager']) ? $userDetails['workingManager'] : 'N';
        $_SESSION['bannerManager']          = isset($userDetails['bannerManager']) ? $userDetails['bannerManager'] : 'N';
        $_SESSION['tickerManager']          = isset($userDetails['tickerManager']) ? $userDetails['tickerManager'] : 'N';
        $_SESSION['sponsorManager']         = isset($userDetails['sponsorManager']) ? $userDetails['sponsorManager'] : 'N';
        $_SESSION['sponsorofthedayManager'] = isset($userDetails['sponsorofthedayManager']) ? $userDetails['sponsorofthedayManager'] : 'N';
        $_SESSION['horseweightManager']     = isset($userDetails['horseweightManager']) ? $userDetails['horseweightManager'] : 'N';
        $_SESSION['racedataManager']        = isset($userDetails['racedataManager']) ? $userDetails['racedataManager'] : 'N';
        $_SESSION['configManager']          = isset($userDetails['configManager']) ? $userDetails['configManager'] : 'N';
        $_SESSION['mailManager']            = isset($userDetails['mailManager']) ? $userDetails['mailManager'] : 'N';
        $_SESSION['homepopup']              = isset($userDetails['homepopup']) ? $userDetails['homepopup'] : 'N';


        /* USER GROUP PERMISSIONS */

        if ($isSiteUser) {

            loadUserPermissions($db, (int)$userDetails['id']);
        } else {

            loadAdminPermissions($db, (int)$userDetails['id']);
        }


        /*
        |--------------------------------------------------------------------------
        | FORCE PASSWORD CHANGE (temporary password / expired password)
        |--------------------------------------------------------------------------
        | Agar super admin ka diya hua password temporary hai (must_change_password = 1)
        | ya password ki expiry ho gayi hai, to session me flag lagta hai.
        | Design::startPage() is flag ko dekhkar popup dikhata hai.
        */

        $_SESSION['force_pw_change'] = 0;

        if (!$isSiteUser) {

            $pwRow = $db->getSingleRowAssoc(
                "SELECT must_change_password,
                        password_expiry_days,
                        COALESCE(password_changed_at, created) AS pw_since
                 FROM admins
                 WHERE id = " . (int)$userDetails['id']
            );

            if ($pwRow) {

                $forceChange = ((int)$pwRow['must_change_password'] === 1);
                $expiryDays  = (int)$pwRow['password_expiry_days'];

                if (
                    !$forceChange &&
                    $expiryDays > 0 &&
                    !empty($pwRow['pw_since']) &&
                    (strtotime($pwRow['pw_since']) + ($expiryDays * 86400)) < time()
                ) {
                    $forceChange = true;
                }

                if ($forceChange) {
                    $_SESSION['force_pw_change'] = 1;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN (uid 19): login ke baad sidebar ka pehla group selected dikhe
        |--------------------------------------------------------------------------
        */

        if (
            !$isSiteUser &&
            (int)$_SESSION['uid'] === 19 &&
            strtoupper((string)$_SESSION['role']) === 'ADMIN' &&
            basename((string)parse_url($uri, PHP_URL_PATH)) === 'dashboard.php' &&
            strpos($uri, 'grp=') === false
        ) {

            $firstGroupId = 0;

            $allGroups = $db->getMultiDimensionalArray(
                "SELECT user_group_id, permission FROM user_group ORDER BY name ASC"
            );

            if (is_array($allGroups)) {

                // Design class load na ho to bhi code na phate
                $catalog = class_exists('Design') ? Design::moduleCatalog() : array();

                foreach ($allGroups as $g) {

                    $p = @unserialize($g['permission']);

                    if (!is_array($p) || empty($p['access']) || !is_array($p['access'])) {
                        continue;
                    }

                    foreach ($p['access'] as $k) {

                        if (empty($catalog) || isset($catalog[$k])) {
                            $firstGroupId = (int)$g['user_group_id'];
                            break 2;
                        }
                    }
                }
            }

            if ($firstGroupId > 0) {

                $uri = "dashboard.php?grp=" . $firstGroupId;
            }
        }

        /* LAST LOGIN: har successful login pe date + time save karo */

        if (!$isSiteUser) {

            try {

                $db->update(
                    "UPDATE admins
                     SET last_login = '" . date('Y-m-d H:i:s') . "'
                     WHERE id = " . (int)$userDetails['id']
                );
            } catch (Exception $e) {
                // last login save na ho to bhi login chalta rahe
            }
        }
        /* LOGIN SUCCESS */

        if (!empty($_SESSION['force_pw_change'])) {

            /*
            | Temporary / expired password:
            | dashboard par redirect NAHI karna. Isi login page par popup dikhana hai
            | (piche ka page blur rahega).
            */

            $showPwModal     = true;
            $pwModalUser     = $_SESSION['username'];
            $pwModalRedirect = $uri;
        } else {

            header("refresh:2;url=" . $uri);

            $safeUri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');

            $msg =
                "Login Successful.. Redirecting " .
                "<a href='" . $safeUri . "'>Here</a> in 2 secs";
        }
    } catch (RWITC_exception $rwitc_err) {

        session_destroy();

        $msg = $rwitc_err->getMessage();
    } catch (Exception $err) {

        session_destroy();

        $msg = $err->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

/* Agar popup wali login ke baad page reload ho gaya, to popup phir dikhao */

if (
    !$showPwModal &&
    $q != 'logout' &&
    $q != 'login-user' &&
    !empty($_SESSION['uid']) &&
    !empty($_SESSION['force_pw_change'])
) {

    $showPwModal     = true;
    $pwModalUser     = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $pwModalRedirect = $uri;
}

$pageTitle = "RWITC | " . CURRENT_SEASON . " - Admin Login";

$isSuccess = (strpos($msg, "Login Successful") !== false);

$isLogoutMsg = (strpos($msg, "successfully logged out") !== false);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $pageTitle; ?>
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --rw-green-dark: #04160c;
            --rw-green-mid: #0b3d20;
            --rw-green: #0b6d2a;
            --rw-green-bright: #15923c;
            --rw-lime: #c7e46a;
            --rw-cream: #f5f4ee;
            --rw-ink: #17251c;
            --rw-muted: #667066;
            --rw-line: #e2e1d8;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            color: var(--rw-ink);
        }

        .rwAdminAuth {
            min-height: 100vh;
            display: flex;
        }

        /* ---------- LEFT PANEL ---------- */

        .rwAuthLeft {
            flex: 1 1 50%;
            position: relative;
            padding: 56px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #eef4ee;
            background:
                radial-gradient(circle at 15% 15%,
                    rgba(199, 228, 106, 0.10),
                    transparent 45%),
                radial-gradient(circle at 80% 70%,
                    rgba(21, 146, 60, 0.25),
                    transparent 55%),
                linear-gradient(160deg,
                    var(--rw-green-dark) 0%,
                    var(--rw-green-mid) 55%,
                    var(--rw-green-dark) 100%);
            overflow: hidden;
        }

        .rwAuthLeftInner {
            position: relative;
            z-index: 2;
            max-width: 560px;
            width: 100%;
        }

        .rwBrandBadge {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 15px;
        }

        .rwBrandMark {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--rw-lime);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .rwBrandMark i {
            font-size: 22px;
            color: #14210f;
            transform: scaleX(-1);
        }

        .rwBrandEyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: var(--rw-lime);
        }

        .rwAuthLeft h1 {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 44px;
            line-height: 1.08;
            margin: 0 0 16px;
            color: #ffffff;
        }

        .rwAuthLeft>.rwAuthLeftInner>p {
            margin: 0 0 40px;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(238, 244, 238, 0.78);
            max-width: 380px;
        }

        .rwQuickLinks {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 14px;
            width: 100%;
        }

        .rwQuickLinkBtn {
            display: flex;
            align-items: center;
            min-height: 54px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 700;
            line-height: 1.3;
            text-decoration: none;
            letter-spacing: 0.1px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                filter .2s ease;
        }

        .rwQuickLinkBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.28);
            filter: brightness(1.08);
        }

        .rwqlColor1 {
            background: linear-gradient(135deg, #0b3d20, #15923c);
        }

        .rwqlColor2 {
            background: linear-gradient(135deg, #0d4c28, #1c9e45);
        }

        .rwqlColor3 {
            background: linear-gradient(135deg, #0f5a30, #22a94c);
        }

        .rwqlColor4 {
            background: linear-gradient(135deg, #125f33, #2ab355);
        }

        .rwqlColor5 {
            background: linear-gradient(135deg, #14683a, #33bb5c);
        }

        .rwqlColor6 {
            background: linear-gradient(135deg, #0b3d20, #26ad50);
        }

        .rwqlColor7 {
            background: linear-gradient(135deg, #0d4c28, #2fb85d);
        }

        .rwqlColor8 {
            background: linear-gradient(135deg, #0f5a30, #3ac267);
        }

        .rwqlColor9 {
            background: linear-gradient(135deg, #3d5c1f, #7a9c3f);
        }

        .rwqlColor10 {
            background: linear-gradient(135deg, #43631f, #87ab45);
        }

        .rwqlColor11 {
            background: linear-gradient(135deg, #0e4a3d, #1c8a6e);
        }

        .rwqlColor12 {
            background: linear-gradient(135deg, #4a5c1f, #96af4a);
        }

        .rwqlColor13 {
            background: linear-gradient(135deg, #123d2e, #1f6b52);
        }

        .rwqlColor14 {
            background: linear-gradient(135deg, #3a5c22, #7ea850);
        }

        .rwqlColor15 {
            background: linear-gradient(135deg, #0b3d20, #1c9e45);
        }

        .rwqlColor16 {
            background: linear-gradient(135deg, #0f2e1c, #1c5638);
        }

        /* ---------- RIGHT PANEL ---------- */

        .rwAuthRight {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--rw-cream);
            padding: 40px;
        }

        .rwAuthCard {
            width: 100%;
            max-width: 380px;
        }

        .rwCardBrand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 26px;
        }

        .rwGetInTouch {
            margin-left: auto;
        }

        .rwGetInTouch img {
            height: 77px;
            width: 80px;
            margin: 0 50px 10px;
        }

        .rwGetInTouch img:hover {
            transform: scale(1.05);
            transition: transform .6s ease;
            height: 85px;
        }

        .rwCardBrandMark {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--rw-lime);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .rwCardBrandMark i {
            font-size: 17px;
            color: #14210f;
            transform: scaleX(-1);
        }

        .rwCardBrandText {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 16px;
            line-height: 1.2;
            color: var(--rw-ink);
        }

        .rwCardDivider {
            border: none;
            border-top: 1px solid var(--rw-line);
            margin: 0 0 26px;
        }

        .rwEyebrow {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: var(--rw-green);
        }

        .rwAuthCard h2 {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 30px;
            margin: 0 0 10px;
            color: var(--rw-ink);
        }

        .rwAuthCard>p.rwSubtext {
            margin: 0 0 28px;
            font-size: 14px;
            color: var(--rw-muted);
        }

        .rwAlert {
            margin: 0 0 22px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
        }

        .rwAlertSuccess {
            background: #eafaf0;
            color: #0b6d2a;
            border: 1px solid #bfe8cd;
        }

        .rwAlertSuccess a {
            color: #0b6d2a;
            font-weight: 700;
            text-decoration: underline;
        }

        .rwAlertError {
            background: #fdeceb;
            color: #b3261e;
            border: 1px solid #f6c8c4;
        }

        .rwAlertInfo {
            background: #eef3fb;
            color: #244b8a;
            border: 1px solid #cfdcf3;
        }

        .rwField {
            margin-bottom: 20px;
        }

        .rwField label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--rw-ink);
        }

        .rwField input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            border-radius: 10px;
            border: 1px solid var(--rw-line);
            background: #ffffff;
            font-family: inherit;
            font-size: 14.5px;
            color: var(--rw-ink);
            outline: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .rwField input:hover {
            border-color: rgba(11, 109, 42, 0.4);
        }

        .rwField input:focus {
            border-color: var(--rw-green);
            box-shadow: 0 0 0 4px rgba(11, 109, 42, 0.1);
        }

        .rwSubmitBtn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            background:
                linear-gradient(135deg,
                    var(--rw-green-mid) 0%,
                    var(--rw-green) 100%);
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
            margin-top: 6px;
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                opacity .2s ease;
            box-shadow: 0 14px 26px rgba(11, 109, 42, 0.22);
        }

        .rwSubmitBtn:hover {
            background: #f5f4ee;
            border-color: rgba(11, 109, 42, 0.3);
            color: var(--rw-green);
        }

        .rwSubmitBtn:active {
            transform: translateY(0);
        }

        .rwResetBtn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            background:
                linear-gradient(135deg,
                    var(--rw-green-mid) 0%,
                    var(--rw-green) 100%);
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
            margin-top: 6px;
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                opacity .2s ease;
            box-shadow: 0 14px 26px rgba(11, 109, 42, 0.22);
        }

        .rwResetBtn:hover {
            background: #f5f4ee;
            border-color: rgba(11, 109, 42, 0.3);
            color: var(--rw-green);
        }

        .rwFinePrint {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: var(--rw-muted);
        }

        .rwSocialRow {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 18px;
        }

        .rwSocialIcon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--rw-green);
            color: #ffffff;
            font-size: 14px;
            text-decoration: none;
            transition:
                transform .2s ease,
                background .2s ease;
        }

        .rwSocialIcon:hover {
            transform: translateY(-2px);
            background: var(--rw-green-bright);
        }


        /* ---------- RESPONSIVE ---------- */

        @media (max-width: 900px) {

            .rwAdminAuth {
                flex-direction: column;
            }

            .rwAuthLeft {
                padding: 40px 32px;
            }

            .rwAuthLeft h1 {
                font-size: 32px;
            }

            .rwQuickLinks {
                margin-top: 28px;
            }

            .rwAuthRight {
                padding: 32px 24px 56px;
            }

            .rwCardBrand {
                flex-wrap: wrap;
            }

            .rwGetInTouch {
                margin-left: 0;
            }

            .rwGetInTouch img {
                height: 60px;
                width: 62px;
                margin: 0;
            }
        }

        @media (max-width: 600px) {

            .rwAuthLeft {
                padding: 32px 22px;
            }

            .rwBrandBadge {
                margin-bottom: 28px;
            }

            .rwAuthLeft h1 {
                font-size: 26px;
            }

            .rwAuthLeft>.rwAuthLeftInner>p {
                font-size: 13.5px;
            }

            .rwQuickLinks {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .rwQuickLinkBtn {
                font-size: 12px;
                min-height: 46px;
            }

            .rwAuthRight {
                padding: 28px 18px 44px;
            }

            .rwAuthCard {
                max-width: 100%;
            }

            .rwCardBrandText {
                font-size: 14.5px;
            }

            .rwAuthCard h2 {
                font-size: 25px;
            }

            .rwField input {
                height: 44px;
                font-size: 14px;
            }

            .rwSubmitBtn,
            .rwResetBtn {
                height: 46px;
                font-size: 14px;
            }

            .rwGetInTouch img {
                height: 48px;
                width: 50px;
            }
        }

        @media (max-width: 380px) {

            .rwBrandMark {
                width: 44px;
                height: 44px;
            }

            .rwBrandMark i {
                font-size: 18px;
            }

            .rwBrandEyebrow {
                font-size: 10px;
            }

            .rwAuthLeft h1 {
                font-size: 22px;
            }

            .rwSocialRow {
                gap: 8px;
            }

            .rwSocialIcon {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
        }

        @media (min-width: 1600px) {

            .rwAuthLeftInner {
                max-width: 640px;
            }

            .rwAuthLeft h1 {
                font-size: 52px;
            }

            .rwAuthCard {
                max-width: 440px;
            }

            .rwQuickLinkBtn {
                font-size: 13.5px;
                min-height: 60px;
            }
        }

        @media (max-height: 620px) and (orientation: landscape) {

            .rwAdminAuth {
                min-height: auto;
            }

            .rwAuthLeft,
            .rwAuthRight {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .rwBrandBadge {
                margin-bottom: 20px;
            }

            .rwAuthLeft h1 {
                font-size: 24px;
                margin-bottom: 8px;
            }

            .rwAuthLeft>.rwAuthLeftInner>p {
                margin-bottom: 20px;
            }

            .rwField {
                margin-bottom: 14px;
            }

            .rwCardDivider {
                margin-bottom: 16px;
            }
        }
        /* ---------- LOGIN PASSWORD EYE ICON ---------- */

        .rwField .rwPwField {
            position: relative;
        }

        .rwField .rwPwField input {
            padding-right: 50px;
        }

        .rwEyeLogin {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: 0;
            background: transparent;
            color: var(--rw-muted);
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .rwEyeLogin:hover {
            color: var(--rw-green);
            background: #f1f3f1;
        }
        /* ---------- FORCE PASSWORD CHANGE POPUP ---------- */

html.rwLock, html.rwLock body { overflow: hidden !important; height: 100%; }
#rwPwOverlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(4,22,12,.55); z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box; font-family: 'Inter','Segoe UI',Arial,sans-serif; }
#rwPwBox { background: #fff; border-radius: 16px; width: 100%; max-width: 420px; padding: 22px 24px 16px; box-shadow: 0 20px 60px rgba(0,0,0,.35); box-sizing: border-box; max-height: calc(100vh - 24px); max-height: calc(100dvh - 24px); overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none; }
#rwPwBox::-webkit-scrollbar { display: none; }
#rwPwBox h3 { margin: 0 0 4px; font-family: 'Source Serif 4', Georgia, serif; font-size: 22px; line-height: 1.2; color: #0b3d20; font-weight: 600; }
#rwPwBox .rwPwSub { margin: 0 0 6px; font-size: 12.5px; line-height: 1.4; color: #667066; }
#rwPwBox label { display: block; font-size: 12.5px; font-weight: 600; margin: 10px 0 5px; color: #17251c; }
#rwPwBox .rwPwWrap { position: relative; }
#rwPwBox input[type=text], #rwPwBox input[type=password] { width: 100%; height: 40px; padding: 0 44px 0 12px; border: 1px solid #e2e1d8; border-radius: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; outline: none; background: #fff; color: #17251c; }
#rwPwBox input[type=password]:focus, #rwPwBox input[type=text]:focus { border-color: #0b6d2a; box-shadow: 0 0 0 3px rgba(11,109,42,.1); }
#rwPwBox input[readonly] { background: #f1f3f1; color: #667066; padding-right: 12px; }
#rwPwBox .rwEye { position: absolute; top: 50%; right: 4px; transform: translateY(-50%); width: 34px; height: 34px; border: 0; background: transparent; color: #667066; font-size: 15px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; justify-content: center; padding: 0; }
#rwPwBox .rwEye:hover { color: #0b6d2a; background: #f1f3f1; }
#rwPwRules { list-style: none; margin: 12px 0 2px; padding: 0; font-size: 12.5px; display: grid; grid-template-columns: 1fr 1fr; gap: 3px 12px; }
#rwPwRules li { color: #8a938d; line-height: 1.35; }
#rwPwRules li:before { content: "\25CB "; }
#rwPwRules li.ok { color: #0b6d2a; font-weight: 600; }
#rwPwRules li.ok:before { content: "\2714 "; }
#rwPwMsg { font-size: 12.5px; color: #b3261e; margin-top: 8px; min-height: 16px; }
#rwPwBtn { width: 100%; height: 42px; border: 0; border-radius: 999px; background: linear-gradient(135deg,#0b3d20 0%,#0b6d2a 100%); color: #fff; font-family: inherit; font-size: 14.5px; font-weight: 700; cursor: pointer; margin-top: 6px; }
#rwPwBtn:disabled { opacity: .45; cursor: not-allowed; }
#rwPwLogout { display: block; text-align: center; margin-top: 10px; font-size: 12.5px; color: #667066; text-decoration: underline; }
@media (max-width: 380px) {
  #rwPwBox { padding: 18px 16px 12px; }
  #rwPwBox h3 { font-size: 19px; }
  #rwPwRules { grid-template-columns: 1fr; }
}
@media (max-height: 620px) {
  #rwPwBox { padding-top: 14px; padding-bottom: 10px; }
  #rwPwBox .rwPwSub { display: none; }
  #rwPwBox label { margin: 6px 0 4px; }
  #rwPwBox input[type=text], #rwPwBox input[type=password] { height: 36px; }
  #rwPwRules { margin-top: 8px; }
  #rwPwBtn { height: 38px; }
}
    </style>

</head>

<body>

    <div class="rwAdminAuth<?php echo $showPwModal ? ' rwBlur' : ''; ?>" id="rwPageWrap">


        <!-- LEFT PANEL -->

        <div class="rwAuthLeft">

            <div class="rwAuthLeftInner">

                <div class="rwBrandBadge">

                    <span class="rwBrandMark">

                        <i class="fa-solid fa-horse-head"></i>

                    </span>

                    <p class="rwBrandEyebrow">
                        Royal Western India Turf Club
                    </p>

                </div>


                <h1>
                    Content Studio
                </h1>

                <p>
                    One place to manage everything on the club's website.
                </p>


                <div class="rwQuickLinks">

                    <a
                        href="../availibilityCalendar.php"
                        class="rwQuickLinkBtn rwqlColor1">
                        Grounds available for Schools &amp; Colleges
                    </a>

                    <a
                        href="../calendar.php"
                        class="rwQuickLinkBtn rwqlColor9">
                        Racing Fixtures
                    </a>


                    <a
                        href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb"
                        target="_blank"
                        class="rwQuickLinkBtn rwqlColor2">
                        RWITC App on Google Play Store
                    </a>

                    <a
                        href="../horseRatings.php"
                        class="rwQuickLinkBtn rwqlColor10">
                        Ratings of all Horses
                    </a>


                    <a
                        href="https://apps.apple.com/us/app/rwitc/id619375717?ls=1"
                        target="_blank"
                        class="rwQuickLinkBtn rwqlColor3">
                        RWITC App on Apple Itunes
                    </a>

                    <a
                        href="../horsesInTraining.php"
                        class="rwQuickLinkBtn rwqlColor11">
                        Horses in Training
                    </a>


                    <a
                        href="https://appworld.blackberry.com/webstore/content/26326879/"
                        target="_blank"
                        class="rwQuickLinkBtn rwqlColor4">
                        RWITC App on Blackberry Appworld
                    </a>

                    <a
                        href="../dividends.php"
                        class="rwQuickLinkBtn rwqlColor12">
                        Tote Dividends
                    </a>


                    <a
                        href="../app-qr.php"
                        class="rwQuickLinkBtn rwqlColor5">
                        QR Code for RWITC App
                    </a>

                    <a
                        href="https://www.indianstudbook.com/"
                        class="rwQuickLinkBtn rwqlColor13">
                        Indian Stud Book
                    </a>


                    <a
                        href="../performanceProfile.php"
                        class="rwQuickLinkBtn rwqlColor6">
                        Performance Profile of Horses
                    </a>

                    <a
                        href="../moneyLeaders.php"
                        class="rwQuickLinkBtn rwqlColor14">
                        Money Leaders
                    </a>


                    <a
                        href="https://forsale.godaddy.com/forsale/horsein.com"
                        target="_blank"
                        class="rwQuickLinkBtn rwqlColor7">
                        Webportal for Owners/Trainers
                    </a>

                    <a
                        href="../download/Prospectus.pdf"
                        class="rwQuickLinkBtn rwqlColor15">
                        Prospectus
                    </a>


                    <a
                        href="https://www.rwitcraces.com/RaceArchives.aspx"
                        class="rwQuickLinkBtn rwqlColor8">
                        Video Archives
                    </a>

                    <a
                        href="../feedback.php"
                        class="rwQuickLinkBtn rwqlColor16">
                        Feedback
                    </a>

                </div>

            </div>

        </div>


        <!-- RIGHT PANEL -->

        <div class="rwAuthRight">

            <div class="rwAuthCard">


                <div class="rwCardBrand">

                    <span class="rwCardBrandMark">

                        <i class="fa-solid fa-horse-head"></i>

                    </span>

                    <span class="rwCardBrandText">
                        Royal Western<br>
                        India Turf Club
                    </span>


                    <a
                        href="http://localhost:3000/officialcontact"
                        target="_blank"
                        class="rwGetInTouch">

                        <img
                            src="../images/rightlogo.png"
                            alt="Get in Touch" />

                    </a>

                </div>


                <hr class="rwCardDivider">


                <p class="rwEyebrow">
                    Admin Access
                </p>

                <h2>
                    Welcome back
                </h2>

                <p class="rwSubtext">
                    Sign in with your club admin account.
                </p>


                <?php if (!empty($msg)) { ?>

                    <div
                        class="rwAlert
                    <?php
                    echo $isSuccess
                        ? 'rwAlertSuccess'
                        : (
                            $isLogoutMsg
                            ? 'rwAlertInfo'
                            : 'rwAlertError'
                        );
                    ?>">

                        <?php echo $msg; ?>

                    </div>

                <?php } ?>


                <form
                    name="registerFrm"
                    method="post"
                    action="index.php">

                    <div class="rwField">

                        <label for="rw-username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="rw-username"
                            name="username"
                            autocomplete="username"
                            required>

                    </div>


                    <div class="rwField">

                        <label for="rw-password">
                            Password
                        </label>

                        <div class="rwPwField">

                            <input
                                type="password"
                                id="rw-password"
                                name="password"
                                autocomplete="current-password"
                                required>

                            <button
                                type="button"
                                class="rwEyeLogin"
                                id="rwEyeLogin"
                                title="Show / hide password"
                                aria-label="Show or hide password">
                                <i class="fa fa-eye"></i>
                            </button>

                        </div>

                    </div>


                    <button
                        type="submit"
                        name="submit"
                        class="rwSubmitBtn">
                        Login
                    </button>


                    <button
                        type="reset"
                        name="reset"
                        class="rwResetBtn">
                        Reset
                    </button>


                    <input
                        type="hidden"
                        name="uri"
                        value="<?php
                                echo htmlspecialchars(
                                    $uri,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>">

                    <input
                        type="hidden"
                        name="q"
                        value="login-user">

                </form>


                <p class="rwFinePrint">
                    Authorized personnel only. Access is logged.
                </p>


                <div class="rwSocialRow">

                    <a
                        href="https://www.facebook.com/rwitcmumbai/"
                        target="_blank"
                        class="rwSocialIcon">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>

                    <a
                        href="https://x.com/rwitcmumbai"
                        target="_blank"
                        class="rwSocialIcon">
                        <i class="fa-brands fa-twitter"></i>
                    </a>

                    <a
                        href="https://www.instagram.com/rwitcmumbai/"
                        target="_blank"
                        class="rwSocialIcon">
                        <i class="fa-brands fa-instagram"></i>
                    </a>

                </div>


            </div>

        </div>

    </div>
    <script>
        (function() {
            var btn = document.getElementById('rwEyeLogin');
            var input = document.getElementById('rw-password');

            if (!btn || !input) {
                return;
            }

            btn.addEventListener('click', function() {
                var icon = btn.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fa fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fa fa-eye';
                }
            });
        })();
    </script>
<?php if ($showPwModal) { ?>

<div id="rwPwOverlay">
  <div id="rwPwBox">
    <h3>Please change your password</h3>
    <p class="rwPwSub">Your current password is temporary or has expired. You must set a new password to continue.</p>

    <label>Username</label>
    <input type="text" value="<?php echo htmlspecialchars($pwModalUser, ENT_QUOTES, 'UTF-8'); ?>" readonly>

    <label>New Password</label>
    <div class="rwPwWrap">
      <input type="password" id="rwNewPw" autocomplete="new-password">
      <button type="button" class="rwEye" data-target="rwNewPw" title="Show / hide password"><i class="fa fa-eye"></i></button>
    </div>

    <label>Confirm New Password</label>
    <div class="rwPwWrap">
      <input type="password" id="rwConfPw" autocomplete="new-password">
      <button type="button" class="rwEye" data-target="rwConfPw" title="Show / hide password"><i class="fa fa-eye"></i></button>
    </div>

    <ul id="rwPwRules">
      <li id="rwr_len">At least 8 characters</li>
      <li id="rwr_up">1 uppercase letter (A-Z)</li>
      <li id="rwr_low">1 lowercase letter (a-z)</li>
      <li id="rwr_num">1 number (0-9)</li>
      <li id="rwr_sp">1 special character (@ # $ !)</li>
      <li id="rwr_match">Both passwords match</li>
    </ul>

    <div id="rwPwMsg"></div>
    <button type="button" id="rwPwBtn" disabled>Change Password</button>
    <a id="rwPwLogout" href="index.php?q=logout">Cancel &amp; Logout</a>
  </div>
</div>
<script>
(function () {
    var redirectTo = <?php echo json_encode($pwModalRedirect); ?>;
    document.documentElement.className += ' rwLock';

    var np = document.getElementById('rwNewPw');
    var cp = document.getElementById('rwConfPw');
    var btn = document.getElementById('rwPwBtn');
    var msg = document.getElementById('rwPwMsg');

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.preventDefault(); }
    });

    var eyes = document.querySelectorAll('#rwPwBox .rwEye');
    for (var i = 0; i < eyes.length; i++) {
        eyes[i].addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-target'));
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa fa-eye';
            }
        });
    }

    function check() {
        var v = np.value;
        var res = {
            len: v.length >= 8,
            up: /[A-Z]/.test(v),
            low: /[a-z]/.test(v),
            num: /[0-9]/.test(v),
            sp: /[^A-Za-z0-9]/.test(v),
            match: v !== '' && v === cp.value
        };
        var all = true;
        for (var k in res) {
            document.getElementById('rwr_' + k).className = res[k] ? 'ok' : '';
            if (!res[k]) { all = false; }
        }
        btn.disabled = !all;
    }

    np.addEventListener('input', check);
    cp.addEventListener('input', check);

    btn.addEventListener('click', function () {
        btn.disabled = true;
        msg.style.color = '#667066';
        msg.textContent = 'Saving...';

        var body = 'new_password=' + encodeURIComponent(np.value) +
                   '&confirm_password=' + encodeURIComponent(cp.value);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'changePassword.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) { return; }
            var r = null;
            try { r = JSON.parse(xhr.responseText); } catch (e) {}
            if (r && r.ok) {
                msg.style.color = '#0b6d2a';
                msg.textContent = r.message + ' Redirecting...';
                setTimeout(function () { window.location.href = redirectTo; }, 900);
            } else {
                msg.style.color = '#b3261e';
                msg.textContent = (r && r.message) ? r.message : 'Something went wrong. Try again.';
                check();
            }
        };
        xhr.send(body);
    });
})();
</script>
    <?php } ?>

</body>

</html>