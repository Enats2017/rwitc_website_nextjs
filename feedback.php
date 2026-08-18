<?php 
session_start();
include_once('bootstrap.php');
//include_once('design.php');

if (isset($_REQUEST['submit'])) {
    $msg = "";
    $secCode  = getParameterString('secCode','');
    if ($secCode == $_SESSION['security_code']) {
       $name = getParameterString('name','');
       $email = getParameterString('email',''); 
       $feedback = getParameterString('feedback','');
       
           $msgBody = "<b>Name: </b>$name<br /><b>Email: </b>$email<br /><b>Feedback: </b><br />$feedback";
           $from = "web@rwitc.com";
           $to = "niranjanbhati@gmail.com";
           $toName = "Niranjan";
           $cc = "hardik2403@gmail.com";
           $subject = "New Site Feedback";
           if (mailer($from,"Site Feedback",$to,$toName,$subject,$msgBody,$cc)) {
               $msg = "Your feedback is valuable to us. Someone from RWITC will contact you shortly. Thank You";
           } else {
               $msg = "Your feedback could not be saved. Please try again after sometime";
           }
    } else {
        $msg = 'Invalid Security Code';
    }   
   
}
  $pageTitle ='Feedback';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  ?>
  <script type="text/javascript">
    function validate() {
        name = document.getElementById("name").value;
        email = document.getElementById("email").value;
        feedback = document.getElementById("feedback").value;
        secCode = document.getElementById("secCode").value;
        
        //alert("Name: "+name);
        if (name == "") {
            alert("Please enter a valid Name");
            return false;
        }   
        if (email == "") {
            alert("Please enter a valid Email");
            return false;
        }
        if (feedback == "") {
            alert("Please enter a valid feedback");
            return false;
        }
        if (secCode == "") {
            alert("Please enter a valid security code");
            return false;
        }
        if (!testEmail(email))  {
           return false; 
        }
        return true;  
    }
    function testEmail(emailID) {
       var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
       //var address = document.forms[form_id].elements[email].value;
       if(reg.test(emailID) == false) {
          alert('Invalid Email Address');
          return false;
       }
       return true
    }
  </script>
    <?php if ($msg !== "") { ?>
        <table class="contentTable">
            <tr>
                <th class="thwhite"><?php echo $msg; ?></th>
            </tr>
        </table>
        
    <?php } ?>
    <h2>Feedback</h2>
   <br />
   <form name="feedbackFrm" method="post" action="/feedback.php" onsubmit="return validate();">
   <Table class="contentTable">
        <tr>
            <th>Name</th>
            <td class="alignLeft"><input type="text" name='name' id="name" /></td>
        </tr>
        <tr>
            <th>Email</th>
            <td class="alignLeft"><input type="text" name='email' id="email" /></td>
        </tr>
        <tr>
            <th>Feedback</th>
            <td class="alignLeft"><textarea name='feedback' id="feedback" rows="5" cols="50"></textarea></td>
        </tr>
        <tr>
            <th><img src="captcha.php" /></th>
            <td class="alignLeft"><input type="text" name='secCode' id="secCode" /></td>
        </tr>
        <tr>
            <td class="thwhite">&nbsp;</td>
            <td class="thwhite alignLeft">
                <input type="submit" name="submit" value="Submit" />&nbsp;
                <input type="reset" name="reset" value="Clear" />
            </td>
        </tr>
   </table> 
   </form>
<?php                   
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object