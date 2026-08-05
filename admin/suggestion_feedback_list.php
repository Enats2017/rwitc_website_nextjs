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

    if(isset($_GET['type']) && $_GET['type'] == 'delete'){
        $id=$_GET['id'];
        $q="DELETE FROM `suggestion_feedback` WHERE id='".$id."'";
        mysqli_query($conn,$q);
    }


    $sql = "SELECT * FROM `suggestion_feedback` WHERE 1=1";

    if(isset($_GET['filter_name']) && $_GET['filter_name'] != ''){
        $sql .= " AND  name LIKE '%". $_GET['filter_name']."%' ";
    }

    if(isset($_GET['filter_email']) && $_GET['filter_email'] != ''){
        $sql .= " AND  email LIKE '%". $_GET['filter_email']."%' ";
    }

    if(isset($_GET['filter_date']) && $_GET['filter_date'] != ''){
        $sql .= " AND  date = '". $_GET['filter_date']."' ";
    }


    $res=mysqli_query($conn,$sql);
    $result2=array();
    if(mysqli_num_rows($res)){
        while ($res1=mysqli_fetch_array($res)) {
            $result2[]=$res1;
        }
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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<style>
  span{
    
    display:none !important;
}
</style>
<body>
     <div class="container-fluid">
                <div class="hclass">
                    <h1 class="h1class" style="text-align:center;"><b>Feedback</b></h1>

                </div>
    </div>
    <div class="container-fluid">
        <div class="panel-body">
            <div class="well">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                             <label class="control-label" for="input-name">Name</label>
                            <input type="text" name="filter_name" value="<?php ?>" placeholder="Name" id="input-name" class="form-control" />
                        </div>
                    </div>    
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label class="control-label" for="input-model">Email</label>
                            <input type="text" name="filter_email" value="<?php  ?>" placeholder="Email" id="input-model" class="form-control" />
                        </div>
                    </div> 
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label class="control-label" for="input-price">Date</label>
                            <input type="date" name="filter_date" value="<?php ?>" placeholder="Date" id="input-price" class="form-control" />
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <button type="button" id="button-filter" class="btn btn-primary pull-right"><i class="fa fa-search"></i>Filter</button>
                         </div>   
                    </div>   
                </div>
            </div>
        </div>
     </div>   
    <div class="row" style="width:95%;margin: auto;margin-top: 3%;">
        <div class="col-sm-12 col-md-12 col-lg-12" style="margin-bottom: 240px" align="justify">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Email</th>
              <th scope="col">Date</th>
               <th scope="col">Text</th>
               <th scope="col">Delete</th>
               <th scope="col">Edit</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result2 != ""){?>
             <?php foreach ($result2 as $res3){ ?>
            <tr>
              <td><?php echo $res3['name'];  ?></td>
              <td><?php echo $res3['email'];  ?></td>
              <td><?php echo $res3['date'];  ?></td>
              <td><?php echo $res3['text'];  ?></td>
              <!-- <td><button onclick="delete_feedback(<?php echo $res3['id'];  ?>)">Delete</button></td> -->
              <td><a href="email_to_chairman_list.php?type=delete&id=<?php echo $res3['id'];  ?>"><button class="btn btn-danger">Delete</button></a></td>
           
              <td><a href="email_to_chairman_form.php?type1=edit&id=<?php echo $res3['id'];?>"><button class="btn btn-success">Edit</button></a></td>
            </tr>
           <?php }} ?>
          </tbody>
        </table>
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js" ></script>
<script type="text/javascript">
    function delete_feedback(id){ 
        console.log(id);
        $.ajax( {
        url : 'suggestion_feedback_list.php?type=delete&id='+id,
        type: 'GET',
        success: function (msg) {
           
        }
    });}
   
</script>
<script type="text/javascript"><!--
$('#button-filter').on('click', function() {
   // var url = 'index.php?route=catalog/product&token=<?php //  echo $token; ?>';
   var url = 'admin/suggestion_feedback_list.php?type=autocomplete';

    var filter_name = $('input[name=\'filter_name\']').val();
    if (filter_name) {
        url += '&filter_name=' + (filter_name);
    }

    var filter_email = $('input[name=\'filter_email\']').val();
    if (filter_email) {
        url += '&filter_email=' + (filter_email);
    }

    var filter_date = $('input[name=\'filter_date\']').val();
    if (filter_date) {
        url += '&filter_date=' + (filter_date);
    }

    location = url;
});
//--></script>
  <script type="text/javascript"><!--
$('input[name=\'filter_name\']').autocomplete({
    'source': function(request, response) {
        $.ajax({
            url: 'admin/feedback_query.php?type=filter_name&method=feedback&filter_name=' + (request.term),
            dataType: 'json',
            success: function(json) {
                response($.map(json, function(item) {
                console.log(item);

                    return {
                        label: item['name'],
                        value: item['name']
                    }
                }));
            }
        });
    },
    'select': function(item) {
        $('input[name=\'filter_name\']').val(item['label']);
    }
});

$('input[name=\'filter_email\']').autocomplete({
    'source': function(request, response) {
        $.ajax({
             url: 'admin/feedback_query.php?type=filter_email&method=feedback&filter_email=' + (request.term),
            dataType: 'json',
            success: function(json) {
                response($.map(json, function(item) {
                    return {
                        label: item['email'],
                        value: item['email']
                    }
                }));
            }
        });
    },
    'select': function(item) {
        $('input[name=\'filter_email\']').val(item['label']);
    }
});


//--></script>