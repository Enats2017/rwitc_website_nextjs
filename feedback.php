<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('bootstrap.php');

$msg = "";
$msgType = "success";

if (isset($_REQUEST['submit'])) {
    $secCode  = getParameterString('secCode','');
    $sessSecCode = isset($_SESSION['security_code']) ? $_SESSION['security_code'] : '';
    
    if (!empty($sessSecCode) && strtolower($secCode) == strtolower($sessSecCode)) {
       $name = getParameterString('name','');
       $email = getParameterString('email',''); 
       $feedback = getParameterString('feedback','');
       
       $msgBody = "<b>Name: </b>".htmlspecialchars($name)."<br /><b>Email: </b>".htmlspecialchars($email)."<br /><b>Feedback: </b><br />".nl2br(htmlspecialchars($feedback));
       $from = "web@rwitc.com";
       $to = "niranjanbhati@gmail.com";
       $toName = "Niranjan";
       $cc = "hardik2403@gmail.com";
       $subject = "New Site Feedback";
       if (mailer($from,"Site Feedback",$to,$toName,$subject,$msgBody,$cc)) {
           $msg = "Your feedback is valuable to us. Someone from RWITC will contact you shortly. Thank You!";
           $msgType = "success";
       } else {
           $msg = "Your feedback could not be saved. Please try again after sometime.";
           $msgType = "error";
       }
    } else {
        $msg = 'Invalid Security Code. Please try again.';
        $msgType = "error";
    }   
}

$pageTitle = 'Feedback';        
$design = new Design();

$design->css = '
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

.rw-feedback-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.rw-feedback-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0;
    margin: 0 0 20px 0;
    border: 0;
}

.rw-feedback-icon {
    width: auto;
    height: auto;
    background: transparent;
    color: #0f5c33;
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.rw-feedback-title-wrap h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 400;
    color: #2b332f;
    font-family: inherit;
}

.rw-feedback-title-wrap p {
    margin: 4px 0 0 0;
    font-size: 13.5px;
    color: #687970;
}

.rw-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px 20px;
    margin-bottom: 20px;
}

.rw-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.rw-form-group.full-width {
    grid-column: span 2;
}

.rw-form-label {
    font-size: 14px;
    font-weight: 600;
    color: #2b332f;
    display: flex;
    align-items: center;
    gap: 7px;
}

.rw-form-label i {
    color: #0f5c33;
    font-size: 14px;
}

.rw-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.rw-input-icon {
    position: absolute;
    left: 13px;
    color: #8c9e94;
    font-size: 14px;
    pointer-events: none;
}

.rw-form-control {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1px solid #d4ded8;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #2b332f;
    background: #fff;
    box-sizing: border-box;
}

textarea.rw-form-control {
    padding: 11px 12px;
    min-height: 120px;
    resize: vertical;
}

.rw-form-control:focus {
    outline: none;
    border-color: #0f5c33;
    box-shadow: 0 0 0 3px rgba(15,92,51,0.10);
}

.rw-captcha-container {
    background: #f6f9f7;
    border: 1px solid #e2e6e4;
    border-radius: 8px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.rw-captcha-img-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rw-captcha-img-box img {
    height: 40px;
    border-radius: 6px;
    border: 1px solid #c8d4cd;
}

.rw-btn-refresh {
    background: #e6f4ec;
    color: #0f5c33;
    border: 1px solid #b7ddc5;
    padding: 7px 11px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rw-btn-refresh:hover {
    background: #0f5c33;
    color: #fff;
    border-color: #0f5c33;
}

.rw-form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 4px;
}

.rw-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #0f5c33;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.rw-btn-submit:hover {
    background: #0c4a29;
}

.rw-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #fff;
    color: #687970;
    border: 1px solid #d4ded8;
    padding: 10px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.rw-btn-reset:hover {
    background: #f5f7f6;
    color: #2b332f;
}

.rw-msg-box {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rw-msg-box.success {
    background: #e6f4ec;
    border: 1px solid #b7ddc5;
    color: #0f5c33;
}

.rw-msg-box.error {
    background: #fdecea;
    border: 1px solid #f3b8b2;
    color: #b3261e;
}

html, body {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

html::-webkit-scrollbar, body::-webkit-scrollbar {
    display: none;
}

@media (max-width: 700px) {
    #leftArea.col-lg-9 {
        padding: 0 16px;
    }

    .rw-feedback-card {
        padding: 16px;
    }

    .rw-form-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .rw-form-group.full-width {
        grid-column: span 1;
    }

    .rw-captcha-container {
        flex-direction: column;
        align-items: stretch;
    }

    .rw-form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .rw-btn-submit,
    .rw-btn-reset {
        width: 100%;
        justify-content: center;
    }
}

</style>';

$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
?>

<script type="text/javascript">
  function validate() {
      var name = document.getElementById("name").value.trim();
      var email = document.getElementById("email").value.trim();
      var feedback = document.getElementById("feedback").value.trim();
      var secCode = document.getElementById("secCode").value.trim();
      
      if (name == "") {
          alert("Please enter your name");
          document.getElementById("name").focus();
          return false;
      }   
      if (email == "") {
          alert("Please enter your email address");
          document.getElementById("email").focus();
          return false;
      }
      if (!testEmail(email)) {
          alert("Please enter a valid email address");
          document.getElementById("email").focus();
          return false; 
      }
      if (feedback == "") {
          alert("Please enter your feedback message");
          document.getElementById("feedback").focus();
          return false;
      }
      if (secCode == "") {
          alert("Please enter the security code");
          document.getElementById("secCode").focus();
          return false;
      }
      return true;  
  }
  
  function testEmail(emailID) {
     var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
     return reg.test(emailID);
  }
</script>

<?php if (!empty($msg)) { ?>
    <div class="rw-msg-box <?php echo $msgType; ?>">
        <i class="fas <?php echo ($msgType == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo $msg; ?></span>
    </div>
<?php } ?>

<div class="rw-feedback-card">
    <div class="rw-feedback-header">
        <div class="rw-feedback-icon">
            <i class="fas fa-comments"></i>
        </div>
        <div class="rw-feedback-title-wrap">
            <h2>Feedback</h2>
            <p>We value your thoughts and suggestions to help us improve the RWITC experience.</p>
        </div>
    </div>

    <form name="feedbackFrm" method="post" action="feedback.php" onsubmit="return validate();">
        <div class="rw-form-grid">
            <!-- Name Input -->
            <div class="rw-form-group">
                <label class="rw-form-label" for="name">
                    <i class="fas fa-user"></i> Full Name
                </label>
                <div class="rw-input-wrap">
                    <i class="fas fa-user rw-input-icon"></i>
                    <input type="text" name="name" id="name" class="rw-form-control" placeholder="Enter your full name" />
                </div>
            </div>

            <!-- Email Input -->
            <div class="rw-form-group">
                <label class="rw-form-label" for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <div class="rw-input-wrap">
                    <i class="fas fa-envelope rw-input-icon"></i>
                    <input type="text" name="email" id="email" class="rw-form-control" placeholder="name@example.com" />
                </div>
            </div>

            <!-- Feedback Textarea -->
            <div class="rw-form-group full-width">
                <label class="rw-form-label" for="feedback">
                    <i class="fas fa-comment-dots"></i> Feedback / Suggestion
                </label>
                <textarea name="feedback" id="feedback" class="rw-form-control" rows="5" placeholder="Share your feedback or comments here..."></textarea>
            </div>

            <!-- Security Verification -->
            <div class="rw-form-group full-width">
                <label class="rw-form-label" for="secCode">
                    <i class="fas fa-shield-alt"></i> Security Verification
                </label>
                <div class="rw-captcha-container">
                    <div class="rw-captcha-img-box">
                        <img src="captcha.php" id="captchaImg" alt="Captcha Code" />
                        <button type="button" class="rw-btn-refresh" onclick="document.getElementById('captchaImg').src='captcha.php?'+Math.random();">
                            <i class="fas fa-sync-alt"></i> Refresh Captcha
                        </button>
                    </div>
                    <div style="flex: 1; min-width: 180px;" class="rw-input-wrap">
                        <i class="fas fa-lock rw-input-icon"></i>
                        <input type="text" name="secCode" id="secCode" class="rw-form-control" placeholder="Enter Security Code" />
                    </div>
                </div>
            </div>
        </div>

        <div class="rw-form-actions">
            <button type="submit" name="submit" class="rw-btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Feedback
            </button>
            <button type="reset" name="reset" class="rw-btn-reset">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
    </form>
</div>

<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design = NULL;
?>