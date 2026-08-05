<?php 
     $user = "rwitc_erp";
    $pass = "S4Y@3tAZ@GvLJ1";
    $schema = 'rwitc_website';
    $conn = mysqli_connect('localhost',$user,$pass,$schema);
    // Check connection
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }

     if(isset($_GET['method']) && $_GET['method'] == 'feedback'){

        if(isset($_GET['type']) && $_GET['type'] == 'filter_name'){
            $sql = "SELECT * FROM `suggestion_feedback` WHERE name LIKE '%". $_GET['filter_name']."%'";
            $res=mysqli_query($conn,$sql);
            $result2=array();
            if(mysqli_num_rows($res)){
                while ($res1=mysqli_fetch_array($res)) {
                    $result2[]=array(
                        'id' => $res1['id'],
                        'name' => $res1['name'],
                    );
                }
            }

           echo  json_encode($result2);
        }

        if(isset($_GET['type']) && $_GET['type'] == 'filter_email'){
            $sql = "SELECT * FROM `suggestion_feedback` WHERE email LIKE '%". $_GET['filter_email']."%'";
            $res=mysqli_query($conn,$sql);
            $result2=array();
            if(mysqli_num_rows($res)){
                while ($res1=mysqli_fetch_array($res)) {
                    $result2[]=array(
                        'id' => $res1['id'],
                        'email' => $res1['email'],
                    );
                }
            }

           echo  json_encode($result2);
        }
    }

    if(isset($_GET['method']) && $_GET['method'] == 'feedback1'){

        if(isset($_GET['type']) && $_GET['type'] == 'filter_name'){
            $sql = "SELECT * FROM `email_to_chairman` WHERE name LIKE '%". $_GET['filter_name']."%'";
            $res=mysqli_query($conn,$sql);
            $result2=array();
            if(mysqli_num_rows($res)){
                while ($res1=mysqli_fetch_array($res)) {
                    $result2[]=array(
                        'id' => $res1['id'],
                        'name' => $res1['name'],
                    );
                }
            }

           echo  json_encode($result2);
        }

        if(isset($_GET['type']) && $_GET['type'] == 'filter_email'){
            $sql = "SELECT * FROM `email_to_chairman` WHERE email LIKE '%". $_GET['filter_email']."%'";
            $res=mysqli_query($conn,$sql);
            $result2=array();
            if(mysqli_num_rows($res)){
                while ($res1=mysqli_fetch_array($res)) {
                    $result2[]=array(
                        'id' => $res1['id'],
                        'email' => $res1['email'],
                    );
                }
            }

           echo  json_encode($result2);
        }
    }


?>
