<?php 
include_once('bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Racecourse ground for school children and colleges';        
  $design = new Design();
  $design->js ="
<script type='text/javascript' src='/js/jquery-ui-custom.js'></script>
<script type='text/javascript' src='/js/fullcalendar.min.js'></script>";
  $design->css = "
    <link rel='stylesheet' type='text/css' href='/css/fullcalendar.css' />
    <style type='text/css'>

    #loading {
        position: absolute;
        top: 5px;
        right: 5px;
        }

    #calendar {
        width: 700px;
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

</head>
<body>

<h1>Racecourse ground for school children and colleges</h1>
<br><br>
<p>Continuing its drive to become more accessible to all Mumbaikars the Royal Western India Turf Club (RWITC) has decided to open up the Mahalaxmi Racecourse ground for school children and colleges.</p>
<br>
<p>The following calendar shows the available days.</p>
<br>
<p>For any enquires kindly contact the Secretariat on 20842550/1/2/3/4.</p>
<br>
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
<?php                   
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object