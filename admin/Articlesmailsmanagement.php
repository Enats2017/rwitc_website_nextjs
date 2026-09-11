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

$pageTitle = 'Articles & Mails Management';


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
                        ARTICLES &amp; MAILS MANAGEMENT
                    </h1>

                    <p class="dashboard-subtitle">
                        Manage all articles, mailers &amp; correspondence
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