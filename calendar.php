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
<script type='text/javascript' src='{$http_base}assets/js/fullcalendar.min.js'></script>";


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

</head>
<body>
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
<a href="javascript:download_fun()">Export</a>
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
<?php                   
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
?>
