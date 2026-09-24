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

$pageTitle = 'Reports Management';


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
                href="turf-console/dashboard.php"
            >
                Dashboard
            </a>

            <a
                style="float:left; margin-left:5px;"
                href="turf-console/index.php?q=logout"
            >
                Logout
            </a>

        </div>

    </div>


        <div class="main-content">


            <div class="dashboard-header">

                <div>

                    <h1 class="dashboard-title">
                        REPORTS MANAGEMENT
                    </h1>

                    <p class="dashboard-subtitle">
                        Manage all reports, admins &amp; system related activities
                    </p>

                </div>

            </div>


            <div class="cards-grid">


                <!-- ================================================= -->
                <!-- ADMIN USERS -->
                <!-- ================================================= -->

                <?php if (hasModuleAccess('adminusers')) { ?>

                    <a
                        class="card-item"
                        href="turf-console/manageAdmin.php"
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