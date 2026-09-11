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

$pageTitle = 'Race Management';


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
                        RACE MANAGEMENT
                    </h1>

                    <p class="dashboard-subtitle">
                        Manage all race related activities
                    </p>

                </div>

            </div>


            <div class="cards-grid">


                <!-- ================================================= -->
                <!-- RACE HISTORY -->
                <!-- ================================================= -->

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