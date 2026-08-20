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
@media (max-width: 700px) { .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } .dashboard-title { font-size: 20px; } }
@media (max-width: 560px) { .cards-grid { grid-template-columns: 1fr; } .dashboard-header { flex-direction: column; align-items: flex-start; } .card-item { padding: 14px 12px; } .card-title { font-size: 14px; } }
@media (max-width: 360px) { .card-icon { width: 32px; height: 32px; font-size: 13px; } }
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
                href="admin/dashboard.php"
            >
                Dashboard
            </a>

            <a
                style="float:left; margin-left:5px;"
                href="admin/adminlogin.php?q=logout"
            >
                Logout
            </a>

        </div>

    </div>


        <div class="main-content">


            <div class="dashboard-header">

                <div>

                    <h1 class="dashboard-title">
                        ADMIN DASHBOARD
                    </h1>

                    <p class="dashboard-subtitle">
                        Manage all club activities
                    </p>

                </div>

            </div>


            <div class="cards-grid">


                <!-- ================================================= -->
                <!-- ARTICLES -->
                <!-- ================================================= -->

                <?php if (hasModuleAccess('articles')) { ?>

                    <a
                        class="card-item"
                        href="admin/articlesManager.php"
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
                        href="admin/csrArticlesManager.php"
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
                        href="admin/raceHistoryManager.php"
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
                        href="admin/sendMailers.php"
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
                        href="admin/ratingsChangeManager.php"
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
                        href="admin/galleryManager.php"
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
                        href="admin/manageVideos.php"
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
                        href="admin/dividendsManager.php"
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
                        href="admin/stewardsReportManager.php"
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


                <!-- RACE DAY REPORT -->

                <?php if (hasModuleAccess('race_day_report')) { ?>

                    <a
                        class="card-item"
                        href="admin/racedayReportsManager.php"
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


                <!-- CALENDAR -->

                <?php if (hasModuleAccess('calendar')) { ?>

                    <a
                        class="card-item"
                        href="admin/calendarManager.php"
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
                        href="admin/availibilityManager.php"
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
                        href="admin/pgArticlesManager.php"
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
                        href="admin/ssArticlesManager.php"
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
                        href="admin/managePolls.php"
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
                        href="admin/manageAdmin.php"
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
                        href="admin/workingManager.php"
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
                        href="admin/bannerManager.php"
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
                        href="admin/tickerManager.php"
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
                        href="admin/sponsorManager.php"
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
                        href="admin/sponsorofthedayManager.php"
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
                        href="admin/configManager.php"
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
                        href="admin/horseweightManager.php"
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
                        href="admin/racedataManager.php"
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
                        href="admin/mailManager.php"
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
                        href="admin/homepopup.php"
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
                        href="admin/erp_prerace.php"
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
                        href="admin/erp_postrace.php"
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
                        href="admin/trackworkManager.php"
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
                        href="admin/suggestion_feedback_list.php"
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
                        href="admin/youtube_videos_upload.php"
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
                        href="admin/email_to_chairman_list.php"
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
                        href="admin/image_upload.php"
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


            </div>

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