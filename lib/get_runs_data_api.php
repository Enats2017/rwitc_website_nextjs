<?php 
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set("display_errors", 1);
$file = 'service_run54.txt';
$handle = fopen($file, 'a+'); 
$data = file_get_contents('php://input');
$datas = json_decode($data,true);
$Itemapi = new Itemapi();
$value = $Itemapi->insertRuns($datas, $handle);
exit(json_encode($value));

class Itemapi {
	public $conn;
	public function __construct() {
	    // Create connection
	    $this->conn = new mysqli('127.0.0.1', 'rwitc_erp', 'S4Y@3tAZ@GvLJ1', 'rwitc_website');
	    // $this->conn = new mysqli('localhost', 'root', '', 'db_rwitc_erp');
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
		$this->query("SET sql_mode = ''", $this->conn);
		$this->query("SET SQL_SAFE_UPDATES = 0", $this->conn);
		// echo "<pre>";print_r($data);exit;
    	$sql_multiple_query = '';
      	 //fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('im in', true)  . "\n");
		// fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");	
        if(isset($data)){
            $this->query("DELETE FROM runs_data WHERE `cracedate` = '".$data[0]['race_date']."' ",$this->conn);
            foreach ($data as $key => $value) {	
              	 fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START TRAINER', true)  . "\n");
				// fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($value, true)  . "\n");
				$placing = $value['placing'];
				if($placing == 'NDS'){
					$placing = '55';
				} elseif($placing == 'NS'){
					$placing = '56';
				} elseif($placing == 'NPR'){
					$placing = '57';
				} elseif($placing == 'WD'){
					$placing = '58';
				} elseif($placing == 'BO'){
					$placing = '59';
				} elseif($placing == 'DQ'){
					$placing = '60';
				} elseif($placing == 'DNC'){
					$placing = '61';
				} elseif($placing == 'NPR'){
					$placing = '62';
				} elseif($placing == 'WDRN'){
					$placing = '63';
				} elseif($placing == 'DNF'){
					$placing = '64';
				} elseif($placing == '-'){
					$placing = '91';
				}

              	$insert_query = ("INSERT INTO `runs_data` SET  
              		`id` = '".$this->escape($value['id'],$this->conn)."',
					`horseseq` = '".$this->escape($value['horse_id'],$this->conn)."',
					`horsenm` = '".$this->escape($value['horsenm'],$this->conn)."',
					`age` = '0',
					`sex` = '',
					`color` = '',
					`foal_date` = '0000-00-00',
					`venue` = '".$this->escape($value['venue'],$this->conn)."',
					`racedate` = '".$this->escape($value['racedate'],$this->conn)."',
					`cracedate` = '".$this->escape($value['race_date'],$this->conn)."',
					`raceseason` = '',
					`raceno` = '".$this->escape($value['season_race_no'],$this->conn)."',
					`racecat` = '".$this->escape($value['racecat'],$this->conn)."',
					`distance` = '".$this->escape($value['distance'],$this->conn)."',
					`weighthd` = '0.00',
					`weightcd` = '".$this->escape($value['weight'],$this->conn)."',
					`placing` = '".$this->escape($placing,$this->conn)."',
					`timingmts` = '".$this->escape($value['timingmts'],$this->conn)."',
					`timingsec` = '".$this->escape($value['timingsec'],$this->conn)."',
					`timingsecd` = '".$this->escape($value['timingsecd'],$this->conn)."',
					`stakes` = '".$this->escape($value['stakes'],$this->conn)."',
					`wingross` = '".$this->escape($value['wingross'],$this->conn)."',
					`plastakes` = '".$this->escape($value['plastakes'],$this->conn)."',
					`trainer` = '',
					`jockey` = '',
					`trainernm` = '".$this->escape($value['trainernm'],$this->conn)."',
					`jockeynm` = '".$this->escape($value['jockeynm'],$this->conn)."',
					`shoe` = '',
					`shoedet` = '',
					`bitsdet` = '',
					`eqpt` = '',
					`startstall` = '0',
					`cardno` = '0',
					`opodds1` = '0.00',
					`opodds2` = '0.00',
					`miodds1` = '0.00',
					`miodds2` = '0.00',
					`bkm1odds` = '0.00',
					`bkm2odds` = '0.00',
					`grade` = '0',
					`ch` = '0',
					`aft` = '0',
					`horsewt` = '".$this->escape($value['horsewt'],$this->conn)."',
					`horsewtrem` = '0.00',
					`wintime` = '".$this->escape($value['wintime'],$this->conn)."',
					`verdict` = '',
					`racename` = '',
					`raceterm` = '',
					`penitrom` = '',
					`weather` = '',
					`falserails` = '',
					`recordno` = '0',
					`incident`  = '',
					`length` = '".$this->escape($value['length'],$this->conn)."',
					`sirename_damname`  = '',
					`day_race_no`  = '".$this->escape($value['raceno'],$this->conn)."'
                ");
              	$this->query($insert_query,$this->conn);
            }
        }
		$json['status'] = 1;		
	    return $json;	
	}
}
?>

