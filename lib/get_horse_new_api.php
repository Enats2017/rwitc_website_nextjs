<?php 
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set("display_errors", 1);
$file = 'service56.txt';
$handle = fopen($file, 'a+'); 
$data = file_get_contents('php://input');
$datas = json_decode($data,true);
$Itemapi = new Itemapi();
$value = '';
if(isset($_GET['type']) && $_GET['type'] == 'declarationapp') {
	$value = $Itemapi->insertDeclarationapp($datas, $handle);
}
exit(json_encode($value));
class Itemapi {
	public $conn;
	public function __construct() {
		// Create connection
		$this->conn = new mysqli('localhost', 'app_user', 'ho{HslC)jWaky${L', 'rwitc_website');
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
			} else {
				return true;
			}
		} else {
			throw new ErrorException('Error: ' . $conn->error . '<br />Error No: ' . $conn->errno . '<br />' . $sql);
			exit();
		}
	}

	public function insertDeclarationapp($datas = array(), $handle){    
		$insert_query = '';
		if(isset($datas)){
			$data = serialize($datas);
			$race_date = date("Y-m-d", strtotime($datas['race_date']));
			$this->query("DELETE FROM erp_declaration_app WHERE `race_date` = '".$this->escape($race_date,$this->conn)."' ",$this->conn);
			$insert_query = $this->query("INSERT INTO `erp_declaration_app` SET `race_date` = '".$this->escape($race_date,$this->conn)."', `data` = '".$this->escape($data,$this->conn)."', `declaration_date` = '".date('Y-m-d')."' ", $this->conn);
		}       
		$json['status'] = $insert_query;
		return $json;   
	}
}
?>