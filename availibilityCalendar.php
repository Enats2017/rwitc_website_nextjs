<?php 
include_once('bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Racecourse ground for school children and colleges';        
  $design = new Design();
  $design->js ="
<script type='text/javascript' src='/js/jquery-ui-custom.js'></script>
<script type='text/javascript' src='/js/fullcalendar.min.js'></script>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>";
  $design->css = "
    <link rel='stylesheet' type='text/css' href='/css/fullcalendar.css' />
    <link href='https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>
    <style type='text/css'>

    #loading {
        position: absolute;
        top: 5px;
        right: 5px;
        }

    #calendar {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
        }
    .fc-event, .fc-agenda .fc-event-time, .fc-event a { 
        background: #FF0000;
        border-color: #FF0000;
        color: #FFFFFF;
        font-size: 13px;
    }    
    .fc-event a {
        padding: 19px 0;
    }
    .fc-grid th.fc-leftmost, .fc-grid td.fc-leftmost {
        border-width: 2px 2px 2px 0;
    }

    /* ===== page layout: sidebar LEFT, content RIGHT — same pattern as image_upload.php ===== */
    :root {
        --rw-green-dark: #04160c; --rw-green-mid: #0b3d20; --rw-green: #0b6d2a;
        --rw-cream: #f5f4ee; --rw-ink: #17251c; --rw-muted: #667066; --rw-line: #e2e1d8;
    }
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
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 34px rgba(11,61,36,0.10);
        padding: 44px 48px;
        box-sizing: border-box;
        font-family: 'Inter','Segoe UI',Arial,sans-serif;
        float: none;
        width: auto;
        display: block;
    }
    #infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

    .ground-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
        color: var(--rw-green); background: #e6f4ec;
        padding: 6px 14px; border-radius: 20px; margin-bottom: 18px;
    }
    #leftArea.col-lg-9 h1 {
        font-family: 'Source Serif 4', serif; font-weight: 600;
        font-size: 32px; line-height: 1.25; color: var(--rw-ink);
        margin: 0 0 24px; text-align: center;
    }
    .ground-divider { height: 1px; background: var(--rw-line); border: none; margin: 0 0 26px; }
    #leftArea.col-lg-9 p {
        font-size: 15.5px; line-height: 1.85; color: #2b332f;
        margin: 0 0 20px; text-align: center;
    }
    .ground-contact {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #e6f4ec; color: var(--rw-green-mid);
        border-radius: 10px; padding: 14px 18px; margin: 8px 0 30px;
        font-size: 14.5px; font-weight: 600;
    }
    #loading {
        color: var(--rw-muted); font-size: 13px;
    }
    #calendar {
        background: #fff;
    }

    @media (max-width: 900px) {
        #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
        #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 34px 28px; }
        #leftArea.col-lg-9 h1 { font-size: 27px; }
    }
    @media (max-width: 600px) {
        #leftArea.col-lg-9 { padding: 24px 18px; border-radius: 14px; }
        #leftArea.col-lg-9 h1 { font-size: 22px; }
        #leftArea.col-lg-9 p { font-size: 14.5px; text-align: left; }
        .ground-contact { flex-direction: column; text-align: center; gap: 4px; }
    }
</style>
  "; 
  $design->jqueryJs = "
    HCTObj('#calendar').fullCalendar({
        
            editable: false,
            disableDragging : true,
            //height: 300,
            aspectRatio : 1.2,
            events: '/lib/fetchAvailibility.php',            
            loading: function(bool) {
                if (bool) HCTObj('#loading').show();
                else HCTObj('#loading').hide();
            }
        });
  ";
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  ?>


<script type='text/javascript'>
   /* $(document).ready(function(){
        $('#calendar').fullCalendar({
        
            editable: false,
            disableDragging : true,
            //height: 300,
            aspectRatio : 1.2,
            events: "/lib/fetchArchives.php",            
            loading: function(bool) {
                if (bool) $('#loading').show();
                else $('#loading').hide();
            }
        });
    });*/
</script>

<div class="ground-eyebrow"><i class="fas fa-school"></i> Community Access</div>
<h1>Racecourse ground for school children and colleges</h1>
<hr class="ground-divider" />
<p>Continuing its drive to become more accessible to all Mumbaikars the Royal Western India Turf Club (RWITC) has decided to open up the Mahalaxmi Racecourse ground for school children and colleges.</p>
<p>The following calendar shows the available days.</p>
<div class="ground-contact"><i class="fas fa-phone"></i> For any enquires kindly contact the Secretariat on 20842550/1/2/3/4.</div>
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  //$design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object