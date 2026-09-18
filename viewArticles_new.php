<?php 
	 include"header.php";
    include 'config.php';

    if (!isset ($_GET['page']) ) {  
        $page = 1;  
    } else {  
        $page = $_GET['page'];  
    }

    $results_per_page = 20;  
    $page_first_result = ($page-1) * $results_per_page;

    $show_pagi_sql = "SELECT * FROM articles ORDER BY created DESC LIMIT 200";


    $num_page_data = $conn->query($show_pagi_sql);

    $number_of_result = $num_page_data->num_rows;
    //determine the total number of pages available  
    $number_of_page = ceil ($number_of_result / $results_per_page);  

    // echo "<pre>";print_r($number_of_page);exit;

	$sql="SELECT * FROM `articles` WHERE published='Y'";
    $sql .= " ORDER BY created DESC LIMIT ".$page_first_result .", ".$results_per_page ."";

    $arti_details_datas=mysqli_query($conn,$sql);
    $results2=array();

    if ($arti_details_datas->num_rows > 0) {
    while($arti_data = $arti_details_datas->fetch_assoc()){
        $all_assessment[] = array(
            'articles_id' => $arti_data['id'],
            'article_title' => $arti_data['title'],
            'date_added'  => $arti_data['created'],
        );
    }
}

?>
<style type="text/css">
    .font_f {
        font-family: 'consolas, "lucida console", "courier new", monospace';
        color: rgb(32, 33, 36);
        text-align: justify;
        background: white;
        border-radius: 10px;
    }
    .border-danger {
    border:1px solid #116614 !important;
    padding: 10px;
    border-radius: 10px;

    }
    .text-danger {
    color: #116614;
    }
    .btn-danger {
    color: #fff;
    background-color: #4caf50;
    border-color: #8bc34a;
    }
    .btn-danger:hover {
    color: #fff;
    background-color: #3fd145 !important;
    border-color: #8bc34a !important;
    }
    .btn-outline-danger {
    color: #4caf50 !important;
    border-color: #4caf50 !important;
    margin-top: 14px;
    margin-bottom: 14px;
    }
    .btn-outline-danger:hover {
    color: #fff;
    background-color: #4caf50 !important;
    border-color: #4caf50 !important;
    }
    
    .bg {
    color: #000 !important;
    background-color: #4caf50 !important;
    border-color: #4caf50 !important;
    }
    
    .hclass p{
        font-size: 60px;
    }

    h1{
        margin: unset !important;
        font-size: 26px!important;
    }

    @media (max-width: 481px){
        .hclass p{
            font-size: 36px;
        }

        .hclass {
            display: inline-block !important;
            font-size: 20px !important;
            padding: 8px !important;
        }
        h1{
            margin: unset !important;
            font-size: 18px !important;
        }
    }

    /*heading*/
    .hclass {
        text-align: center;
        display: inline-block;
        background-color: #11a14e;
        border-radius: 33px;
        padding: 11px;
        vertical-align: middle !important;
        color: white;
      	margin:25px;
    }

</style>
<section class="py-3">
    <div class="container-fluid">
        <div class="row">
            <div class="text-center">
                <div class="hclass">
                    <h1><b>Articles</b></h1>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-5 container">
        <div class="font_f p-5">
            <?php foreach ($all_assessment as $allvalue ) { ?>
                <div class="border border-danger p-3 mb-3 border_rad" style="margin-bottom: 10px;">
                    <h4 class="font-weight-bold text-danger">
                        <?php  echo stripcslashes($allvalue['article_title']);?>
                    </h4>
                    <h6>
                        <?php $formattedDate = date('d/m/Y', strtotime(stripcslashes($allvalue['date_added']))); ?>
                        <?php  echo $formattedDate;?>
                    </h6>
                    <h5 class="text-right">
                       <a href="viewArticles.php?id=<?php echo $allvalue['articles_id']; ?>" class="btn btn-danger">View More ...</a>
                    </h5>
                </div>
            <?php } ?>

            <span class="text-danger font-weight-bold pr-2" style="font-weight: 700 !important;">Page NO &nbsp;</span>
            <?php for($page_no = 1; $page_no<= $number_of_page; $page_no++) {  ?>
                <?php if($page_no == $page) { ?>
                    <a href="viewArticles_new.php?page=<?php echo $page_no; ?>" class="btn btn-outline-danger bg"><?php echo $page_no ?></a>
                <?php } else { ?>  
                    <a href="viewArticles_new.php?page=<?php echo $page_no; ?>" class="btn btn-outline-danger"><?php echo $page_no ?></a>
                <?php }  ?>   
            <?php } ?>
        </div> 
    </div>
</section>
<?php  include"footer.php"; ?>         
   

