<?php

  include_once('../bootstrap.php');

  require_once("../lib/users.class.php");

  require_once("../lib/userchecks.php");

  require_once("../lib/pagination.class.php");

  $pageno = getParameterNumber('pageno',1);

//   $user = "rwitc_erp";
  $user = 'root';
    // $pass = "S4Y@3tAZ@GvLJ1";
    $pass = '';
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
    $result2Full=array();
    if(mysqli_num_rows($res)){
        while ($res1=mysqli_fetch_array($res)) {
            $result2Full[]=$res1;
        }
    }
    $totalFeedback = count($result2Full);
    $paging = new Pagination($pageno,15,$totalFeedback);
    $result2 = array_slice($result2Full,($pageno-1)*10,10);

  //$uid = $_COOKIE['uid'];             

  $userObj = new Users($db);



  if (!isAdminlogin()) { // check login    

    $secmsg = "You do not have access to this page.";

  }

  $pageTitle ='Feedback';        

  // create a template object

  $design = new Design();

  

  

  $design->js='
<script type="text/javascript" src="https://code.jquery.com/ui/1.10.2/jquery-ui.js"></script>
';

  $design->css ='
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" type="text/css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style type="text/css">
.ui-helper-hidden-accessible { display: none !important; }

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

.feedback-title { font-size: 24px; color: #2b332f; margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; }
.feedback-title i { color: #0f5c33; }

.feedback-filter-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.feedback-filter-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
.feedback-filter-field { flex: 1 1 200px; min-width: 180px; }
.feedback-filter-field label { display: block; font-size: 13.5px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
.feedback-filter-field input { width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 9px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit; }
.feedback-filter-field input:focus { outline: none; border-color: #1a7a45; }
#button-filter { background: #0f5c33; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
#button-filter:hover { background: #0c4a29; }

.feedback-table-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.feedback-table { width: 100%; border-collapse: collapse; }
.feedback-table thead th { background: #f5f4ee; color: #2b332f; text-align: left; padding: 12px 16px; font-size: 13.5px; font-weight: 700; border-bottom: 1px solid #e2e6e4; }
.feedback-table tbody td { padding: 12px 16px; font-size: 14px; color: #2b332f; border-bottom: 1px solid #eef0ee; vertical-align: top; }
.feedback-table tbody tr:last-child td { border-bottom: none; }
.feedback-table tbody tr:hover { background: #f9faf9; }
.feedback-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }
.feedback-action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
.feedback-action-btn.delete { background: #c0392b; color: #fff; }
.feedback-action-btn.delete:hover { background: #a5301f; }
.feedback-action-btn.edit { background: #0f5c33; color: #fff; }
.feedback-action-btn.edit:hover { background: #0c4a29; }


html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

@media (max-width: 700px) {
  #leftArea.col-lg-9 { padding: 0 16px; }
}
</style>
';

  $design->jqueryJs = ""; 

  $design->startPage("$pageTitle");  

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea",'col-lg-9');

?>
<?php if (!empty($secmsg)) {?>
    <div class="message">
        <?php echo $secmsg; ?>
    </div>
<?php } ?>

<h1 class="feedback-title"><i class="fas fa-comment-dots"></i> Feedback</h1>

<div class="feedback-filter-card">
    <div class="feedback-filter-row">
        <div class="feedback-filter-field">
            <label for="input-name">Name</label>
            <input type="text" name="filter_name" value="<?php ?>" placeholder="Name" id="input-name" />
        </div>
        <div class="feedback-filter-field">
            <label for="input-model">Email</label>
            <input type="text" name="filter_email" value="<?php  ?>" placeholder="Email" id="input-model" />
        </div>
        <div class="feedback-filter-field">
            <label for="input-price">Date</label>
            <input type="date" name="filter_date" value="<?php ?>" placeholder="Date" id="input-price" />
        </div>
        <div class="feedback-filter-field" style="flex: 0 0 auto;">
            <button type="button" id="button-filter"><i class="fas fa-search"></i> Filter</button>
        </div>
    </div>
</div>

<div class="feedback-table-card">
<table class="feedback-table">
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
    <?php if(empty($result2)){ ?>
        <tr><td colspan="6" class="feedback-empty">No feedback found.</td></tr>
    <?php } ?>
    <?php if($result2 != ""){?>
     <?php foreach ($result2 as $res3){ ?>
    <tr>
      <td><?php echo $res3['name'];  ?></td>
      <td><?php echo $res3['email'];  ?></td>
      <td><?php echo $res3['date'];  ?></td>
      <td><?php echo $res3['text'];  ?></td>
      <!-- <td><button onclick="delete_feedback(<?php echo $res3['id'];  ?>)">Delete</button></td> -->
      <td><a href="email_to_chairman_list.php?type=delete&id=<?php echo $res3['id'];  ?>"><button class="feedback-action-btn delete"><i class="fas fa-trash-alt"></i> Delete</button></a></td>
   
      <td><a href="email_to_chairman_form.php?type1=edit&id=<?php echo $res3['id'];?>"><button class="feedback-action-btn edit"><i class="fas fa-edit"></i> Edit</button></a></td>
    </tr>
   <?php }} ?>
  </tbody>
</table>
</div>
<?php $paging->writePagination(); ?>

<?php                   

  $design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

    //$design->pageClose();

$design = NULL; // release object
?>
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