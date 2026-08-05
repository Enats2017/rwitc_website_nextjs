<?php

header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set("display_errors", 1);
$data = file_get_contents('php://input');
$datas = json_decode($data,true);
$Itemapi = new Itemapi();
if(isset($_GET) && $_GET['type'] == 'rating_change') {
    $value = $Itemapi->racerating($datas);
}else{
    echo "Not Valid!";
}
// echo 'done';exit;
exit(json_encode($value));

class Itemapi {

	public $conn;

	public function __construct() {

	    $this->conn = new mysqli('localhost', 'rwitc_erp', 'S4Y@3tAZ@GvLJ1', 'rwitc_website');

	    if ($this->conn->connect_error) {

	    	die("Connection failed: " . $this->conn->connect_error);

	    }

	}



	public function escape($value, $conn) {

	    return $conn->real_escape_string($value);

	}



	public function getLastId($conn){

		return $conn->insert_id;

	}



	public function query($sql, $conn) {

	    $query = $conn->query($sql);

	    if (!$conn->errno){

	    	if (isset($query->num_rows)) {

	    		$data = array();

	    		while ($row = $query->fetch_assoc()) {

	    			$data[] = $row;

	    		}

    			$result = new stdClass();

    			$result->num_rows = $query->num_rows;

    			$result->row = isset($data[0]) ? $data[0] : array();

    			$result->rows = $data;

    			unset($data);

    			$query->close();

    			return $result;

	    	} else{

	    		return true;

	    	}

	    } else {

	    	throw new ErrorException('Error: ' . $conn->error . '<br />Error No: ' . $conn->errno . '<br />' . $sql);

	    	exit();

	    }

	}



	public function racerating($data){
		// $inputJSON = file_get_contents('php://input'); // Get the raw POST data
		// $inputData = json_decode($inputJSON, true);   // Decode JSON into an associative array
		if (!$data) {
			echo "No Data !";
			exit;
		}

		$type = isset($_GET['type']) ? $_GET['type'] : '';
		$race_date = isset($data['race_date']) ? $data['race_date'] : '';
		$horse_id = isset($data['horse_id']) ? $data['horse_id'] : '';
		$day_race_no = isset($data['day_race_no']) ? $data['day_race_no'] : '';
		$new_rating = isset($data['new_rating']) ? $data['new_rating'] : '';

        if($race_date != '' && $horse_id != '' && $type === 'rating_change'){

            // echo "SELECT * FROM runs_data WHERE horseseq = '$horse_id' AND cracedate = '$race_date' AND day_race_no = '$day_race_no'";
            $sql =  $this->query("SELECT * FROM runs_data WHERE horseseq = '".$horse_id."' AND cracedate = '".$race_date."' AND day_race_no = '".$day_race_no."'",$this->conn);
            if ($sql->num_rows > 0) {
	            // Update the record with the new rating
	            $updateQuery = $this->query("UPDATE runs_data SET aft = '$new_rating' WHERE horseseq = '".$horse_id."' AND cracedate = '".$race_date."' AND day_race_no = '".$day_race_no."'", $this->conn);
				$json['status'] = $updateQuery;
	            return $json;         
           } else {
           		$json['msg'] = 'No record found for the provided details.';
	            $json['status'] = 0;
	            return $json;
           }

            // $update_query = $this->query("UPDATE runs_data SET aft = '$new_rating' WHERE horseseq = '".$horse_id."' AND cracedate = '".$race_date."' AND day_race_no = '".$day_race_no."'",$this->conn);
            // $json['msg'] = 'Record sent successfully!';
            // $json['status'] = 1;		
            // return $json;

        } else {
			echo "Didn't Update.";
		}

        // exit;
    }

}
?>