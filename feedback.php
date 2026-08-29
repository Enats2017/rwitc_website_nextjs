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
.rw-feedback-card {
    background: #ffffff;
    border: 1px solid #e2e6e4;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 8px 30px rgba(11, 61, 36, 0.06);
    margin-bottom: 30px;
}

.rw-feedback-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-bottom: 20px;
    margin-bottom: 26px;
    border-bottom: 1px solid #eef1ef;
}

.rw-feedback-icon {
    width: 48px;
    height: 48px;
    background: #e6f4ec;
    color: #0f5c33;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.rw-feedback-title-wrap h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #1c2520;
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
    gap: 20px;
    margin-bottom: 24px;
}

.rw-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
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
    gap: 8px;
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
    left: 14px;
    color: #8c9e94;
    font-size: 15px;
    pointer-events: none;
}

.rw-form-control {
    width: 100%;
    padding: 12px 14px 12px 40px;
    border: 1px solid #d4ded8;
    border-radius: 10px;
    font-size: 14.5px;
    font-family: inherit;
    color: #1c2520;
    background: #fcfdfe;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

textarea.rw-form-control {
    padding: 12px 14px;
    min-height: 130px;
    resize: vertical;
}

.rw-form-control:focus {
    outline: none;
    border-color: #0f5c33;
    box-shadow: 0 0 0 3.5px rgba(15, 92, 51, 0.12);
    background: #ffffff;
}

.rw-captcha-container {
    background: #f6f9f7;
    border: 1px solid #dcdedc;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.rw-captcha-img-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rw-captcha-img-box img {
    height: 42px;
    border-radius: 8px;
    border: 1px solid #c8d4cd;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.rw-btn-refresh {
    background: #e6f4ec;
    color: #0f5c33;
    border: 1px solid #b7ddc5;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.rw-btn-refresh:hover {
    background: #1a7a45;
    color: #ffffff;
    border-color: #1a7a45;
}

.rw-form-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-top: 10px;
}

.rw-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #0f5c33;
    color: #ffffff;
    border: none;
    padding: 13px 28px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(15, 92, 51, 0.25);
    transition: all 0.2s ease;
}

.rw-btn-submit:hover {
    background: #1a7a45;
    transform: translateY(-1.5px);
    box-shadow: 0 6px 18px rgba(26, 122, 69, 0.3);
}

.rw-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    color: #687970;
    border: 1px solid #d4ded8;
    padding: 13px 22px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.rw-btn-reset:hover {
    background: #f5f7f6;
    color: #1c2520;
    border-color: #b0c2b7;
}

.rw-msg-box {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
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

@media (max-width: 768px) {
    .rw-form-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .rw-form-group.full-width {
        grid-column: span 1;
    }
    .rw-feedback-card {
        padding: 22px 18px;
    }
    .rw-captcha-container {
        flex-direction: column;
        align-items: stretch;
    }
    .rw-btn-submit, .rw-btn-reset {
        width: 100%;
        justify-content: center;
    }
    .rw-form-actions {
        flex-direction: column;
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
            <h2>Send Us Your Feedback</h2>
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
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design = NULL;
?>