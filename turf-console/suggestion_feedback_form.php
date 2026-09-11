<?php

  include_once('../bootstrap.php');

  require_once("../lib/users.class.php");

  require_once("../lib/userchecks.php");

  $user = "rwitc_erp";
    $pass = "S4Y@3tAZ@GvLJ1";
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

  $design->css ='';

  $design->jqueryJs = ""; 

  $design->startPage("$pageTitle");  

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper");

  $design->openDiv("leftArea");

?>

<body>
     <div class="container-fluid">
        <div class="hclass">
            <h1 class="h1class" style="text-align:center;"><b>Suggestion Feedback</b></h1>

        </div>
    </div>
    <div class="row" style="width:95%;margin: auto;margin-top: 3%;">
        <div class="container form-top">
                <div class="row">   
                    <div class="col-md-12  col-sm-12 col-xs-12">
                        <div class="panel panel-danger">
                            <div class="panel-body">
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
                        </div>
                    </div>
                </div>
            </div>
    </div>
</body>


<?php                   

  $design->closeDiv();

  //$design->rightArea();  

  //$design->closeDiv();

  $design->closeDiv();

    //$design->pageClose();

$design = NULL; // release object
?>
