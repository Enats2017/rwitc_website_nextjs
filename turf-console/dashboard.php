<?php

include_once('../bootstrap.php');

require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

$userObj = new Users($db);


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isAdminlogin()) {

    $secmsg = "You do not have access to this page.";

}


/*
|--------------------------------------------------------------------------
| SELECTED USER GROUP (sidebar se ?grp=ID aaye toh)
|--------------------------------------------------------------------------
*/

$groupCards = array();
$groupName  = '';
$isGroupView = false;

if (empty($secmsg) && isset($_GET['grp']) && (int)$_GET['grp'] > 0) {

    $grpId    = (int)$_GET['grp'];
    $adminUid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

    $grpRow = $db->getSingleRowAssoc(
        "SELECT name, permission FROM user_group WHERE user_group_id = $grpId"
    );

    if ($grpRow) {

        $gCatalog = Design::moduleCatalog();
        $gAccess  = array();

        if ($adminUid === 19) {
            // Super admin: all modules of the group
            $gPerm   = @unserialize($grpRow['permission']);
            $gAccess = (is_array($gPerm) && !empty($gPerm['access']) && is_array($gPerm['access'])) ? $gPerm['access'] : array();
        } else {
            // Normal admin: only the modules ticked for this admin in this group
            $gAccess = Design::getAdminGroupModules($db, $adminUid, $grpId);
        }

        foreach ($gAccess as $k) {
            if (isset($gCatalog[$k])) {
                $groupCards[] = $gCatalog[$k];
            }
        }

        if (!empty($groupCards)) {
            $isGroupView = true;
            $groupName   = $grpRow['name'];
        } else {
            $secmsg = "You do not have access to this group.";
        }

    } else {
        $secmsg = "Invalid group.";
    }
}


/*
|--------------------------------------------------------------------------
| HARDCODED MENU (?menu=KEY )
|--------------------------------------------------------------------------
*/

if (empty($secmsg) && !$isGroupView && isset($_GET['menu'])) {

    $menuKey  = $_GET['menu'];
    $fixedMap = Design::fixedMenuModules();

    $menuTitles = array(
        'photo'   => 'Photo Manager - Banners',
        'notice'  => 'Notice & Annual Report Manager',
        'stories' => 'Stories & News Article Manager',
        'prerace'  => 'Pre Race Manager',
        'postrace'  => 'Post Race Manager',
        'trackwork' => 'Track Work Manager',
        'liverace'  => 'Live Race Manager - Updates',
        'sponsor'   => 'Sponsor Manager',
        'mailer'    => 'Mailer Manager',
        'calendar'  => 'Calender Manager',
        'dividends' => 'Dividends Manager',
        'others'    => 'Others/Miscellaneous',

    );

    if (isset($fixedMap[$menuKey])) {

        $mCatalog = Design::moduleCatalog();

        foreach ($fixedMap[$menuKey] as $mk) {
            if (isset($mCatalog[$mk]) && hasModuleAccess($mk)) {
                $groupCards[] = $mCatalog[$mk];
            }
        }

        $isGroupView = true;
        $groupName   = isset($menuTitles[$menuKey]) ? $menuTitles[$menuKey] : $menuKey;

    } else {
        $secmsg = "Invalid menu.";
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';


/*
|--------------------------------------------------------------------------
| DESIGN OBJECT
|--------------------------------------------------------------------------
*/

$design = new Design();

$design->js = '';
$design->css = '';
$design->jqueryJs = "";

$design->startPage("$pageTitle");

$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");

$design->openDiv("infoWrapper","col-lg-12");

$design->openDiv("leftArea",'col-lg-9');

$design->writeContentPageStyles();

?>

<style type="text/css">
.message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }
.submenu { display: none; }
.dashboard-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.dashboard-header > div { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; }
.dashboard-title { font-size: 24px; font-weight: 700; color: #2b332f; margin-top: 0; }
.dashboard-subtitle { font-size: 14px; color: #7a8c84; margin: 0; }
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.card-item { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 16px 16px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: #2b332f; box-shadow: 0 1px 2px rgba(0,0,0,0.03); transition: box-shadow .15s ease, transform .15s ease; }
.card-item:not(.card-static):hover { box-shadow: 0 4px 10px rgba(0,0,0,0.08); transform: translateY(-1px); border-color: #1a7a45; }
.card-static { cursor: default; color: #7a8c84; }
.card-icon { width: 36px; height: 36px; border-radius: 9px; background: #0f5c33; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.card-static .card-icon { background: #7a8c84; }
.card-title { font-size: 15px; font-weight: 500; line-height: 1.3; flex: 1; }
.card-arrow { color: #7a8c84; font-size: 13px; }
@media (min-width: 1920px) { .cards-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px; } .dashboard-title { font-size: 30px; } }
@media (max-width: 1200px) { .cards-grid { grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); } }
@media (max-width: 900px) { .cards-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); } }
@media (max-width: 700px) { .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } .dashboard-title { font-size: 20px; } }
@media (max-width: 560px) {
    .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .dashboard-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 4px; }
    .dashboard-subtitle { font-size: 13px; }
    .card-item { padding: 11px 10px; gap: 8px; border-radius: 10px; }
    .card-icon { width: 32px; height: 32px; font-size: 13px; border-radius: 8px; }
    .card-title { font-size: 12.5px; line-height: 1.25; font-weight: 500; }
    .card-arrow { font-size: 11px; }
}
@media (max-width: 360px) {
    .cards-grid { grid-template-columns: 1fr; }
    .card-icon { width: 30px; height: 30px; font-size: 12px; }
}
html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

</style>


<?php if (!empty($msg)) { ?>

    <div class="message">
        <?php echo $msg; ?>
    </div>

<?php } ?>


<?php if (!empty($secmsg)) { ?>

    <div class="message">
        <?php echo $secmsg; ?>
    </div>

<?php } ?>


<?php if (empty($secmsg)) { ?>


    <div class="submenu">

        <div style="float:right;">

            <a
                style="float:left;"
                href="dashboard.php"
            >
                Dashboard
            </a>

            <a
                style="float:left; margin-left:5px;"
                href="index.php?q=logout"
            >
                Logout
            </a>

        </div>

    </div>


        <div class="main-content">


            <div class="dashboard-header">

                <div>

                    <h1 class="dashboard-title">
                        <?php echo $isGroupView ? htmlspecialchars(strtoupper($groupName)) : 'ADMIN DASHBOARD'; ?>
                    </h1>

                    <p class="dashboard-subtitle">
                        <?php echo $isGroupView ? 'Modules in this group' : 'Manage all club activities'; ?>
                    </p>

                </div>

            </div>


            <?php if ($isGroupView) { ?>

            <div class="cards-grid">

                <?php foreach ($groupCards as $c) { ?>
                    <a class="card-item" href="<?php echo htmlspecialchars($c[1]); ?>">
                        <span class="card-icon"><i class="<?php echo htmlspecialchars($c[2]); ?>"></i></span>
                        <span class="card-title"><?php echo htmlspecialchars($c[0]); ?></span>
                        <i class="fas fa-chevron-right card-arrow"></i>
                    </a>
                <?php } ?>

            </div>

            <?php } else { ?>

            <div class="cards-grid">


                <!-- ================================================= -->
                <!-- ARTICLES -->
                <!-- ================================================= -->

                <?php if (hasModuleAccess('articles')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/articlesManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </span>

                        <span class="card-title">
                            Articles Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- CSR ARTICLES -->

                <?php if (hasModuleAccess('articles')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/csrArticlesManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-heart"></i>
                        </span>

                        <span class="card-title">
                            CSR Articles Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- RACE HISTORY -->

                <?php if (hasModuleAccess('race_history')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/raceHistoryManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-horse-head"></i>
                        </span>

                        <span class="card-title">
                            Race History Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- SEND MAILERS -->

                <?php if (hasModuleAccess('send_mailer')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/sendMailers.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-envelope"></i>
                        </span>

                        <span class="card-title">
                            Send Mailers
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- RATINGS -->

                <?php if (hasModuleAccess('rating_change')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/ratingsChangeManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </span>

                        <span class="card-title">
                            Ratings Change Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- GALLERY -->

                <?php if (hasModuleAccess('gallery')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/galleryManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-images"></i>
                        </span>

                        <span class="card-title">
                            Gallery Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- VIDEO -->

                <?php if (hasModuleAccess('video')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/manageVideos.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-video"></i>
                        </span>

                        <span class="card-title">
                            Videos Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- DIVIDENDS -->

                <?php if (hasModuleAccess('dividends')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/dividendsManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </span>

                        <span class="card-title">
                            Dividends Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- STEWARDS REPORT -->

                <?php if (hasModuleAccess('stewards_report')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/stewardsReportManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </span>

                        <span class="card-title">
                            Stewards Report Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>

                <!-- SWEEPSTAKES -->

                <?php if (hasModuleAccess('sweepstakes')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/sweepstakesManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </span>

                        <span class="card-title">
                            Sweepstakes Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- RACE DAY REPORT -->

                <?php if (hasModuleAccess('race_day_report')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/racedayReportsManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </span>

                        <span class="card-title">
                            Race Day Reports Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- RACE RESULTS -->

                <?php if (hasModuleAccess('race_results')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/raceResultsManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </span>

                        <span class="card-title">
                            Media Tips & updates Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- CALENDAR -->

                <?php if (hasModuleAccess('calendar')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/calendarManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </span>

                        <span class="card-title">
                            Calendar Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>


                    <a
                        class="card-item"
                        href="turf-console/availibilityManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </span>

                        <span class="card-title">
                            Racecource Availibility Calendar Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- PRAKASH GOSAVI -->

                <?php if (hasModuleAccess('prakash_gosavi')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/pgArticlesManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-pen-nib"></i>
                        </span>

                        <span class="card-title">
                            Prakash Gosavi Articles Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- SHIVEN SURENDRANATH -->

                <?php if (hasModuleAccess('shiven_surendranath')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/ssArticlesManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-feather-alt"></i>
                        </span>

                        <span class="card-title">
                            Shiven Surendranath Articles Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- POLLS -->

                <?php if (hasModuleAccess('polls')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/managePolls.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-poll"></i>
                        </span>

                        <span class="card-title">
                            Manage Polls
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- ADMIN USERS -->

                <?php if (hasModuleAccess('adminusers')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/manageUser.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-users-cog"></i>
                        </span>

                        <span class="card-title">
                            Manage Admins
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- WORKING MANAGER -->

                <?php if (hasModuleAccess('workingManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/workingManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </span>

                        <span class="card-title">
                            Working Group Upload
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- BANNER -->

                <?php if (hasModuleAccess('bannerManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/bannerManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-image"></i>
                        </span>

                        <span class="card-title">
                            Banner Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- TICKER -->

                <?php if (hasModuleAccess('tickerManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/tickerManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-stream"></i>
                        </span>

                        <span class="card-title">
                            Ticker Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- SPONSOR -->

                <?php if (hasModuleAccess('sponsorManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/sponsorManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-handshake"></i>
                        </span>

                        <span class="card-title">
                            Sponsor Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- SPONSOR OF THE DAY -->

                <?php if (hasModuleAccess('sponsorofthedayManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/sponsorofthedayManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-star"></i>
                        </span>

                        <span class="card-title">
                            Sponsor Of the Day Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- CONFIG -->

                <?php if (hasModuleAccess('configManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/configManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-cogs"></i>
                        </span>

                        <span class="card-title">
                            Config Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- HORSE WEIGHT -->

                <?php if (hasModuleAccess('horseweightManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/horseweightManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-weight"></i>
                        </span>

                        <span class="card-title">
                            Reset Horse Weight Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- RACE DATA -->

                <?php if (hasModuleAccess('racedataManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/racedataManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-database"></i>
                        </span>

                        <span class="card-title">
                            Reset Race Data Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- MAIL -->

                <?php if (hasModuleAccess('mailManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/mailManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-envelope-open-text"></i>
                        </span>

                        <span class="card-title">
                            Draft Mail Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- HOME POPUP -->

                <?php if (hasModuleAccess('homepopup')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/homepopup.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-window-restore"></i>
                        </span>

                        <span class="card-title">
                            Home Popup
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- PRE RACE -->

                <?php if (hasModuleAccess('erp_prerace')) { ?>

                    <div class="card-item card-static">

                        <span class="card-icon">
                            <i class="fas fa-calendar-day"></i>
                        </span>

                        <span class="card-title">
                            Race Date
                        </span>

                    </div>


                    <a
                        class="card-item"
                        href="turf-console/erp_prerace.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </span>

                        <span class="card-title">
                            Pre Race Date
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- POST RACE -->

                <?php if (hasModuleAccess('erp_postrace')) { ?>

                    <div class="card-item card-static">

                        <span class="card-icon">
                            <i class="fas fa-calendar-day"></i>
                        </span>

                        <span class="card-title">
                            Race Date
                        </span>

                    </div>


                    <a
                        class="card-item"
                        href="turf-console/erp_postrace.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </span>

                        <span class="card-title">
                            Post Race Date
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- TRACKWORK -->

                <?php if (hasModuleAccess('trackworkManager')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/trackworkManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-running"></i>
                        </span>

                        <span class="card-title">
                            Trackwork Manager
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- SUGGESTION STATIC -->

                <?php if (hasModuleAccess('suggestion_feedback')) { ?>

                    <div class="card-item card-static">

                        <span class="card-icon">
                            <i class="fas fa-lightbulb"></i>
                        </span>

                        <span class="card-title">
                            Suggestion List
                        </span>

                    </div>


                    <a
                        class="card-item"
                        href="turf-console/suggestion_feedback_list.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-comments"></i>
                        </span>

                        <span class="card-title">
                            Suggestion Feedback
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- YOUTUBE -->

                <?php if (hasModuleAccess('youtube_upload')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/youtube_videos_upload.php"
                    >

                        <span class="card-icon">
                            <i class="fab fa-youtube"></i>
                        </span>

                        <span class="card-title">
                            YouTube Upload
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- CHAIRMAN -->

                <?php if (hasModuleAccess('chairman_email')) { ?>

                    <div class="card-item card-static">

                        <span class="card-icon">
                            <i class="fas fa-user-tie"></i>
                        </span>

                        <span class="card-title">
                            Chairman List
                        </span>

                    </div>


                    <a
                        class="card-item"
                        href="turf-console/email_to_chairman_list.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-envelope"></i>
                        </span>

                        <span class="card-title">
                            Chairman Email list
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- IMAGE UPLOAD -->

                <?php if (hasModuleAccess('image_upload')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/image_upload.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </span>

                        <span class="card-title">
                            Image Upload
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- NOTICE FOR THE AGM -->

                <?php if (hasModuleAccess('notice_agm')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/noticeAgmManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-bullhorn"></i>
                        </span>

                        <span class="card-title">
                            Notice for the AGM
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- ANNUAL REPORT -->

                <?php if (hasModuleAccess('annual_report')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/annualReportManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-file-invoice"></i>
                        </span>

                        <span class="card-title">
                            Annual Report
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- ABOUT RWITC -->

                <?php if (hasModuleAccess('about_rwitc')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/aboutRwitcManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-landmark"></i>
                        </span>

                        <span class="card-title">
                            About RWITC
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- VISION & MISSION -->

                <?php if (hasModuleAccess('vision_mission')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/visionMissionManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-bullseye"></i>
                        </span>

                        <span class="card-title">
                            Vision &amp; Mission
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- ORGANISATION & MANAGEMENT -->

                <?php if (hasModuleAccess('organisation_management')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/organisationManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-sitemap"></i>
                        </span>

                        <span class="card-title">
                            Organisation &amp; Management
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


                <!-- HISTORY -->

                <?php if (hasModuleAccess('history_timeline')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/historyTimelineManager.php"
                    >

                        <span class="card-icon">
                            <i class="fas fa-history"></i>
                        </span>

                        <span class="card-title">
                            History
                        </span>

                        <i class="fas fa-chevron-right card-arrow"></i>

                    </a>

                <?php } ?>


            </div>

            <?php } /* end else (normal dashboard) */ ?>

        </div>


<?php } ?>


<?php

$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();

$design = NULL;

?>