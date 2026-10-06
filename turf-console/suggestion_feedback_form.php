<?php

  include_once('../bootstrap.php');

  require_once("../lib/users.class.php");

  require_once("../lib/userchecks.php");

//   $user = "rwitc_erp";
  $user = "app_user";
    // $pass = "S4Y@3tAZ@GvLJ1";
    $pass = 'ho{HslC)jWaky${L';
    $schema = 'rwitc_website';
    $conn = mysqli_connect('localhost',$user,$pass,$schema);
    // Check connection
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }
        
        

  session_start(); 
$current_date = date('m/d/y');  



if(isset($_GET['type1']) && $_GET['type1'] == 'edit'){
    $id=$_GET['id'];
    $sql = "SELECT * from `suggestion_feedback` where id ='$id'";
    $result11 = mysqli_query($conn,$sql);
    if(mysqli_num_rows($result11) > 0){
       $row = mysqli_fetch_array($result11);
    }

  /*echo "<pre>";print_r($row);exit;*/
}    

if(isset($_GET['submit'])){
    $id=$_GET['id'];  
    $name = $_GET['name'];
    $email = $_GET['email'];
    $date = $_GET['date'];
    $message = $_GET['message'];


    $sql = " UPDATE `suggestion_feedback` SET name = '$name', email= '$email' , date='$date', message='$message' WHERE id = '$id'";
    $res = mysqli_query($conn,$sql);
      
}      

  //$uid = $_COOKIE['uid'];             

  $userObj = new Users($db);



  if (!isAdminlogin()) { // check login    

    $secmsg = "You do not have access to this page.";

  }

  $pageTitle ='Dashboard';        

  // create a template object

  $design = new Design();

  

  

  $design->js='';

  $design->css ='
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
#infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
.form-control { width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit; }
.form-control:focus { outline: none; border-color: #1a7a45; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
}

.feedback-title { font-size: 24px; color: #2b332f; margin: 0 0 20px 0; }

.feedback-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    max-width: 800px;
    box-sizing: border-box;
}

@media (max-width: 520px) {
    .feedback-form-wrap { padding: 16px; }
    .form-control { font-size: 13.5px; }
}

</style>
  ';

  $design->jqueryJs = "";

  $design->startPage("$pageTitle");  

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea","col-lg-9");

?>

<body>
<h1 class="feedback-title">Suggestion Feedback</h1>
<div class="feedback-form-wrap">
                                <form action="" id="reused_form" method="_GET">
                                    <div class="form-group">
                                        <label><i class="fa fa-user" aria-hidden="true"></i> Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Enter Name" value="<?php echo $row['name']?>">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fa fa-envelope" aria-hidden="true"></i> Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="Enter Email" value="<?php echo $row['email']?>">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fa fa-calendar" aria-hidden="true"></i> Date</label>
                                        <input type="text" name="date" class="form-control" value="<?php echo $row['date']?>" >
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fa fa-comment" aria-hidden="true"></i> Message</label>
                                        <textarea rows="4"  name="message" class="form-control" placeholder="Type Your Message" ><?php echo $row['text']?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="submit" formmethod="get" class="btn btn-raised btn-block btn-success" style="display: none;">Submit</button>
                                    </div>
                                </form>
</div>
</body>


<?php                   

  $design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

  $design->closeDiv();

    //$design->pageClose();

$design = NULL; // release object
?>
