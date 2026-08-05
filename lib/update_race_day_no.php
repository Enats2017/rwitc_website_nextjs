<?php 

header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);

ini_set("display_errors", 1);

$file = 'service_54.txt';

$handle = fopen($file, 'a+'); 

$data = file_get_contents('php://input');

$datas = json_decode($data,true);

$Itemapi = new Itemapi();



if(isset($_GET) && $_GET['type'] == 'runs_data') {

   	$value = $Itemapi->insertRuns($datas, $handle);



}



exit(json_encode($value));

class Itemapi {

	public $conn;

	public function __construct() {

	    // Create connection

	    $this->conn = new mysqli('localhost', 'rwitc_erp', 'S4Y@3tAZ@GvLJ1', 'rwitc_website');

	    // Check connection

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




	public function insertRuns($data = array(), $handle){	 		
            $horse  = $this->query("SELECT dbid,raceno FROM  `runs_data` WHERE  `cracedate` <= '2022-07-31' ORDER BY `cracedate` DESC LIMIT 200000,100000",$this->conn);
            // echo "<pre>";print_r("SELECT dbid,raceno FROM  `runs_data` WHERE  `cracedate` <= '2022-07-31' ORDER BY `cracedate` DESC LIMIT 20000,20000");exit;
            foreach ($horse->rows as $key => $value) {	
                $this->query("UPDATE `runs_data` SET  `day_race_no` = '".$this->escape($value['raceno'],$this->conn)."' WHERE dbid = '".$value['dbid']."'",$this->conn);
            }

	}

}

?>

