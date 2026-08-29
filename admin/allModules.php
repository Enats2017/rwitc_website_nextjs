<?php
  include_once('../bootstrap.php');
  include_once('../lib/users.class.php');
  require_once("../lib/userchecks.php");
  session_start();
  if(isset($_COOKIE['uid'])){
    $uid = $_COOKIE['uid'];
  } else {
    $uid = 0;
  }
  $userObj = new Users($db);

  $msg = $secmsg = "";
  if (!isAdminlogin()) {
      $secmsg = "Please login to access this page";
  }

  $pageTitle ='All Modules';
  $design = new Design();
  $design->js='';
  $design->css ='
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
  $design->jqueryJs = "";
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
?>

<style type="text/css">
#infoWrapper.col-lg-12 {
    display: flex;
    flex-direction: row-reverse;
    align-items: flex-start;
    max-width: 1500px;
    margin: 30px auto;
    float: none;
}
#leftArea.col-lg-9 {
    flex: 1 1 auto;
    min-width: 0;
    max-width: none;
    margin: 0;
    padding: 0 30px;
    box-sizing: border-box;
    float: none;
    width: auto;
    display: block;
}
.message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }

.modules-header { margin-bottom: 20px; }
.modules-header h2 { margin: 0 0 6px 0; font-size: 22px; color: #2b332f; }
.modules-header p { margin: 0; font-size: 14px; color: #7a8c84; }

.rwQuickLinks {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 16px;
    width: 100%;
    margin-bottom: 24px;
}

.rwQuickLinkBtn {
    display: flex;
    align-items: center;
    min-height: 60px;
    padding: 12px 16px;
    border-radius: 10px;
    color: #ffffff !important;
    font-size: 13.5px;
    font-weight: 700;
    line-height: 1.3;
    text-decoration: none;
    letter-spacing: 0.1px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}

.rwQuickLinkBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.28);
    filter: brightness(1.08);
}

.rwqlColor1  { background: linear-gradient(135deg, #0b3d20, #15923c); }
.rwqlColor2  { background: linear-gradient(135deg, #0d4c28, #1c9e45); }
.rwqlColor3  { background: linear-gradient(135deg, #0f5a30, #22a94c); }
.rwqlColor4  { background: linear-gradient(135deg, #125f33, #2ab355); }
.rwqlColor5  { background: linear-gradient(135deg, #14683a, #33bb5c); }
.rwqlColor6  { background: linear-gradient(135deg, #0b3d20, #26ad50); }
.rwqlColor7  { background: linear-gradient(135deg, #0d4c28, #2fb85d); }
.rwqlColor8  { background: linear-gradient(135deg, #0f5a30, #3ac267); }
.rwqlColor9  { background: linear-gradient(135deg, #3d5c1f, #7a9c3f); }
.rwqlColor10 { background: linear-gradient(135deg, #43631f, #87ab45); }
.rwqlColor11 { background: linear-gradient(135deg, #0e4a3d, #1c8a6e); }
.rwqlColor12 { background: linear-gradient(135deg, #4a5c1f, #96af4a); }
.rwqlColor13 { background: linear-gradient(135deg, #123d2e, #1f6b52); }
.rwqlColor14 { background: linear-gradient(135deg, #3a5c22, #7ea850); }
.rwqlColor15 { background: linear-gradient(135deg, #0b3d20, #1c9e45); }
.rwqlColor16 { background: linear-gradient(135deg, #0f2e1c, #1c5638); }

@media (max-width: 700px) {
  #leftArea.col-lg-9 { padding: 0 16px; }
  .rwQuickLinks { grid-template-columns: 1fr; }
}

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

</style>

    <?php if (!empty($secmsg)) {?>
        <div class="message">
            <?php echo $secmsg; ?>
        </div>
    <?php } else { ?>

        <div class="modules-header">
            <h2>All Modules</h2>
            <p>One place to manage everything on the club's website.</p>
        </div>

        <div class="rwQuickLinks">

            <a href="availibilityCalendar.php" class="rwQuickLinkBtn rwqlColor1">
                Grounds available for Schools &amp; Colleges
            </a>

            <a href="calendar.php" class="rwQuickLinkBtn rwqlColor9">
                Racing Fixtures
            </a>

            <a href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb" target="_blank" class="rwQuickLinkBtn rwqlColor2">
                RWITC App on Google Play Store
            </a>

            <a href="horseRatings.php" class="rwQuickLinkBtn rwqlColor10">
                Ratings of all Horses
            </a>

            <a href="https://apps.apple.com/us/app/rwitc/id619375717?ls=1" target="_blank" class="rwQuickLinkBtn rwqlColor3">
                RWITC App on Apple Itunes
            </a>

            <a href="horsesInTraining.php" class="rwQuickLinkBtn rwqlColor11">
                Horses in Training
            </a>

            <a href="https://appworld.blackberry.com/webstore/content/26326879/" target="_blank" class="rwQuickLinkBtn rwqlColor4">
                RWITC App on Blackberry Appworld
            </a>

            <a href="dividends.php" class="rwQuickLinkBtn rwqlColor12">
                Tote Dividends
            </a>

            <a href="app-qr.php" class="rwQuickLinkBtn rwqlColor5">
                QR Code for RWITC App
            </a>

            <a href="https://www.indianstudbook.com/" class="rwQuickLinkBtn rwqlColor13">
                Indian Stud Book
            </a>

            <a href="performanceProfile.php" class="rwQuickLinkBtn rwqlColor6">
                Performance Profile of Horses
            </a>

            <a href="moneyLeaders.php" class="rwQuickLinkBtn rwqlColor14">
                Money Leaders
            </a>

            <a href="https://forsale.godaddy.com/forsale/horsein.com" target="_blank" class="rwQuickLinkBtn rwqlColor7">
                Webportal for Owners/Trainers
            </a>

            <a href="download/Prospectus.pdf" class="rwQuickLinkBtn rwqlColor15">
    Prospectus
</a>    

            <a href="https://www.rwitcraces.com/RaceArchives.aspx" class="rwQuickLinkBtn rwqlColor8">
                Video Archives
            </a>

            <a href="feedback.php" class="rwQuickLinkBtn rwqlColor16">
                Feedback
            </a>

        </div>

    <?php } ?>
<?php
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design->pageClose();
$design = NULL; // release object