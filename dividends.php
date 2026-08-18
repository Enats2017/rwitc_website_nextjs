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

<div id='loading' style='display:none'>loading...</div>

<div id='calendar'></div>

<?php                   

  $design->closeDiv();

  $design->rightArea();

  $design->closeDiv();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object

