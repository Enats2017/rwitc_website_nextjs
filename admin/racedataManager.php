<?php
include_once('../bootstrap.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/race.class.php");
$q = getParameterString('q','',$db);
session_start();                    
$uid = $_SESSION['uid'];             
$userObj = new Users($db);  
$msg = $secmsg = "";
$rObj = new Racedata($db);
if (isAdminlogin()) {
	if ($_SESSION['racedataManager'] == "Y") { // check login
		//if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		//}
		
		$json = array();
		$json['success'] = '';
		$json['error'] = '';
		// all actions POST form submissions go here
		// echo '<pre>';
		// print_r($_REQUEST);
		// exit;
		//echo $q;exit;
		

		if (isset($_REQUEST['Reset_Race_Data'])) {
            $race_date = getParameterString('race_date','',$db);
            $race_type = getParameterString('race_type','',$db);
			$race_type_1 = getParameterString('race_type_1','',$db);
			// echo $race_date;
   //          echo '<br />';
   //          echo $race_type;
   //          echo '<br />';
   //          echo $race_type_1;
   //          echo '<br />';
   //          exit;
            if($race_date == '' || $race_type == '' || $race_type_1 == ''){
                $json['error'] = 'Please Select All Filter for Reset';
            } else {
                if ($q=="save-data") {
                    try {
                        $rObj->clearracedata($race_date, $race_type, $race_type_1);
                    } catch (Exception $err) {
                        echo $err->getMessage();
                    }
                    $json['success'] = 'Race Data Cleared Successfully';      
                }    
            }
		}
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
$pageTitle ='Reset Race Data Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
<script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
<script type="text/javascript">
    function confirmDelete(calendarID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/availibilityManager.php?q=delete-calendar&id="+calendarID;
        }
    }
</script>
';
$design->css ='
<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

.racedata-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.racedata-header h2 { margin: 0; font-size: 20px; color: #2b332f; display: flex; align-items: center; gap: 10px; white-space: nowrap; }
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.racedata-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
.racedata-form-wrap .form-row { margin-bottom: 20px; }
.racedata-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
.racedata-form-wrap input[type="text"], .racedata-form-wrap select {
  width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
}
.racedata-form-wrap input[type="text"]:focus, .racedata-form-wrap select:focus { outline: none; border-color: #1a7a45; }
.racedata-form-wrap .form-actions { padding-top: 6px; }
.racedata-form-wrap input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
.racedata-form-wrap input[type="submit"]:hover { background: #0c4a29; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }


@media (max-width: 700px) {
  #leftArea.col-lg-9 { padding: 0 16px; }
  .racedata-header { flex-direction: column; align-items: flex-start; }
  .racedata-form-wrap { padding: 18px; }
}
</style>
';
$design->jqueryJs = "
    jQuery.browser = {};
    (function () {
        jQuery.browser.msie = false;
        jQuery.browser.version = 0;
        if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
            jQuery.browser.msie = true;
            jQuery.browser.version = RegExp.$1;
        }
    })();
    $('#race_date').datepicker({
            showOn: 'button',
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            dateFormat : 'yy-mm-dd'
        });
        $('#race_type').change(function() {
            console.log('ininin');
            type = $(this).val();
            $('#race_type_1').find('option').remove();
            if(type == 1){
                $('#race_type_1').append($('<option>', { 
                    value: 1,
                    text : 'Handicap' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 2,
                    text : 'Declaration' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 3,
                    text : 'Race Card' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 4,
                    text : 'Acceptance' 
                }))
            } else if(type == 2) {
                $('#race_type_1').append($('<option>', { 
                    value: 4,
                    text : 'Race Result' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 5,
                    text : 'Rating Change' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 6,
                    text : 'RaceDay Report' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 7,
                    text : 'Photos' 
                }))
                $('#race_type_1').append($('<option>', { 
                    value: 8,
                    text : 'Videos' 
                }))
            }
        });
"; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
?>
	<?php if (!empty($json['success'])) {?>
		<div class="message">
			<?php echo $json['success']; ?>
		</div>
	<?php } ?>
	<?php if (!empty($json['error'])) {?>
		<div class="message">
			<?php echo $json['error']; ?>
		</div>
	<?php } ?>    
	
	<div class="racedata-header">
	  	<h2><i class="fas fa-database"></i> Reset Race Data Manager</h2>
	  	<div class="header-links">
			<!-- <a href="admin/dashboard.php">Dashboard</a>
			<a href="admin/adminlogin.php?q=logout">Logout</a> -->
	   	</div>
	</div>
	<div class="racedata-form-wrap">
	<form enctype="multipart/form-data" name="racedataimageForm" method="post" action="admin/racedataManager.php">
		<div class="form-row">
                <label class="form-label" for="race_date">Race Date</label>
                	<input type="text" name="race_date" value="" id="race_date" />
        </div>
        <div class="form-row">
                <label class="form-label" for="race_type">Pre / Post Race Type</label>
                	<select name="race_type" id="race_type">
                		<option value="">Please Select</option>
                		<option value="1">Pre Race</option>
                		<option value="2">Post Race</option>
                	</select>
        </div>
        <div class="form-row">
                <label class="form-label" for="race_type_1">Race Type</label>
                	<select name="race_type_1" id="race_type_1">
                		<option value="">Please Select</option>
                		<!-- <option value="1">Handicap / Acceptance</option>
                		<option value="2">Declaration</option>
                        <option value="3">Race Card</option>
                		<option value="4">Race Result</option>
                		<option value="5">Rating Change</option>
                		<option value="6">RaceDay Report</option>
                		<option value="7">Photos</option>
                		<option value="8">Videos</option> -->
                	</select>
        </div>
		<div class="form-actions">
				<input type="submit" name="Reset Race Data" value="Reset Race Data" />
				<input type="hidden" name="q" value="save-data" />
		</div>
	</form>
	</div>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object