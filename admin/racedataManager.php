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
		if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		}
		
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
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
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
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
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
	
	<div class="submenu">
	  	<a href="admin/racedataManager.php">Reset Race Data Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	<br />
	<form enctype="multipart/form-data" name="racedataimageForm" method="post" action="admin/racedataManager.php">
	  	<table class="contentTable">
		  	<col width="20%"><col width="80%">
		  	<tr>
                <th>Race Date</th>
                <td class="alignLeft">
                	<input type="text" name="race_date" value="" id="race_date" />
                </td>
            </tr>
            <tr>
                <th>Pre / Post Race Type</th>
                <td class="alignLeft">
                	<select name="race_type" id="race_type">
                		<option value="">Please Select</option>
                		<option value="1">Pre Race</option>
                		<option value="2">Post Race</option>
                	</select>
                </td>
            </tr>
            <tr>
                <th>Race Type</th>
                <td class="alignLeft">
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
                </td>
            </tr>
		  	<tr>
			  	<td colspan="2">
				  	<input type="submit" name="Reset Race Data" value="Reset Race Data" />
				  	<input type="hidden" name="q" value="save-data" />
			  	</td>
		  	</tr>
	  	</table>
	</form>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object