<?php 

include_once('bootstrap.php');

//include_once('design.php');



  

  $pageTitle ='Dividends';        

  $design = new Design();

  // Live paths .............

//   $design->js ="
// <script type='text/javascript' src='/js/jquery-ui-custom.js'></script>
// <script type='text/javascript' src='/js/lib/moment.min.js'></script>
// <script type='text/javascript' src='/js/fullcalendar.min.js'></script>";

// ... Local paths .............

$design->js ="
<script type='text/javascript' src='{$http_base}assets/js/jquery-ui-custom.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/moment.min.js'></script>
<script type='text/javascript' src='{$http_base}assets/js/fullcalendar.min.js'></script>";


//.....Live paths .............

  // $design->css = "
  //   <link rel='stylesheet' type='text/css' href='/css/fullcalendar.css' />


// ... Local paths .............

$design->css = "
    <link rel='stylesheet' type='text/css' href='{$http_base}assets/css/fullcalendar.css' />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>


    <style type='text/css'>

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

    .dividends-title {
        font-size: 24px;
        color: #2b332f;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dividends-title i {
        color: #0f5c33;
    }

    .dividends-card {
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        position: relative;
    }

    #loading {

        position: absolute;

        top: 5px;

        right: 5px;

        font-size: 13px;
        color: #0f5c33;
        font-weight: 600;

        }



    #calendar {

        width: 100%;

        margin: 0 auto;

        }

    .fc-event, .fc-agenda .fc-event-time, .fc-event a {
    font-size: 13px;
}

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    .fc-button {
        background: #fff;
        border: 1px solid #e2e6e4;
        color: #0f5c33;
    }
    .fc-state-active, .fc-state-hover {
        background: #e6f4ec;
    }

    @media (max-width: 700px) {
        #leftArea.col-lg-9 { padding: 0 16px; }
        .dividends-card { padding: 16px; }
    }

    

</style>

  "; 

  $design->jqueryJs = "

    $('#calendar').fullCalendar({

        

            editable: false,

            disableDragging : true,

            //height: 300,

            aspectRatio : 1.2,
         
          

            //events: '/lib/fetchDividends.php',
            events: '{$http_base}lib/fetchDividends.php',
            

            loading: function(bool) {

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

<h1 class="dividends-title"><i class="fas fa-coins"></i> Dividends</h1>

<div class="dividends-card">
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
</div>

<?php                   

  $design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object