<?php
include_once('bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Racing Calendar';        
  $design = new Design();
//   $design->js ="
// <script type='text/javascript' src='/js/ics.js'></script>
// <script type='text/javascript' src='/js/ics.deps.min.js'></script>
// <script type='text/javascript' src='/js/jquery-ui-custom.js'></script>
// <script type='text/javascript' src='/js/lib/moment.min.js'></script>
// <script type='text/javascript' src='/js/fullcalendar.min.js'></script>";


// ... Local paths .............

 $design->js ="
<script type='text/javascript' src='{$http_base}assets/js/ics.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/ics.deps.min.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/jquery-ui-custom.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/moment.min.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/fullcalendar.min.js'></script>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>";


  // $design->css = "
  //   <link rel='stylesheet' type='text/css' href='/css/fullcalendar.css' />

  // ... Local paths .............

  $design->css = "
    <link rel='stylesheet' type='text/css' href='{$http_base}assets/css/fullcalendar.css' />



    <style type='text/css'>

    #loading {
        position: absolute;
        top: 5px;
        right: 5px;
        }

    #calendar {
        width: 100%;
        margin: 20px auto;
        }

    /* ===== layout: leftArea (content) + rightArea (sidebar) side by side, same pattern as other managers ===== */
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
    #infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

    /* ===== Export button (same style as articlesManager.php add-article-btn) ===== */
    .rc-export-wrap { margin-bottom: 16px; }
    .rc-export-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #1a7a45;
        color: #0f5c33;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        white-space: nowrap;
    }
    .rc-export-btn:hover { background: #e6f4ec; }

    @media (max-width: 900px) {
        #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
        #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
    }
    @media (max-width: 700px) {
        #leftArea.col-lg-9 { padding: 0 16px; }
        .rc-export-btn { width: 100%; justify-content: center; }
    }
    
</style>
  "; 
  $design->jqueryJs = "
    $('#calendar').fullCalendar({
        
            editable: false,
            disableDragging : true,
            //height: 300,
            aspectRatio : 1.2,
            //events: '/lib/fetchCalendar.php',
            events: '{$http_base}lib/fetchCalendar.php',           
            loading: function(bool) {
                console.log(bool);
                if (bool) $('#loading').show();

                else $('#loading').hide();

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

<script>
    //var cal = ics();
    //cal.addEvent('Data Event', 'This is an all day event', 'Nome, AK', '8/7/2013', '8/7/2013');
    //cal.addEvent('Data Event', 'This is an all day event', 'Nome, AK', '8/7/2016', '8/7/2016');
    //cal.addEvent('Data Event', 'This is an all day event', 'Nome, AK', '8/7/2016', '8/7/2016');
    //cal.addEvent('Data Event', 'This is an all day event', 'Nome, AK', '8/7/2016', '8/7/2016');
    //alert('aaaaa');
    function download_fun(){
      var monthNames = [
        "January", "February", "March",
        "April", "May", "June", "July",
        "August", "September", "October",
        "November", "December"
      ];

      var date = new Date();
      var day = date.getDate();
      var monthIndex = date.getMonth();
      var year = date.getFullYear();
      start_date = year + '-' + monthIndex + '-01'
      //start_date = '2016-4-01';
      
      var CurrentDate = new Date();
      CurrentDate.setMonth(CurrentDate.getMonth() + 6);
      var day = CurrentDate.getDate();
      var monthIndex = CurrentDate.getMonth();
      var year = CurrentDate.getFullYear();
      end_date = year + '-' + monthIndex + '-' + day
      
      $.ajax({
         url: 'http://www.rwitc.com/lib/fetchCalendar.php?start='+start_date+'&end='+end_date,
         dataType: 'json',
         success: function(data) {
            var cal = ics();
            for(i=0; i<data.length; i++){
              cal.addEvent(data[i]['title'], data[i]['title'], data[i]['title'], data[i]['start']+' 0:00 pm', data[i]['start']+' 0:00 pm');
            }
            cal.download();
            //window.location.reload();
         }
      });
      /*
      var cal = ics();
      cal.addEvent('Data Event', 'This is an all day event', 'Nome, AK', '8/7/2016', '8/7/2016');
      cal.download();
      window.location.reload();
      */
    }
</script>
<div class="rc-export-wrap">
    <a href="javascript:download_fun()" class="rc-export-btn"><i class="fas fa-download"></i> Export</a>
</div>
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  // $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
?>